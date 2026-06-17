<?php

namespace App\Livewire\Pedidos;

use App\Models\AlertaStock;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\SolicitudReabastecimiento;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Nuevo Pedido')]
class Crear extends Component
{
    // Cliente
    public string $clienteBusqueda = '';
    public ?int   $clienteId       = null;
    public string $clienteNombre   = '';

    // Datos del pedido
    public string $notas          = '';
    public string $fechaPrometida = '';
    public float  $descuentoPct   = 0;

    // Línea en curso
    public ?int   $addProductoId        = null;
    public int    $addCantidad          = 1;
    public string $addDescripcionCustom = '';

    // Líneas confirmadas
    public array $lineas = [];

    #[Computed]
    public function clientesBusqueda()
    {
        if ($this->clienteId || strlen($this->clienteBusqueda) < 2) {
            return collect();
        }

        return Cliente::where('nombre', 'like', "%{$this->clienteBusqueda}%")
            ->orWhere('telefono', 'like', "%{$this->clienteBusqueda}%")
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->lineas)->sum(fn ($l) => $l['precio_unitario'] * $l['cantidad']);
    }

    #[Computed]
    public function descuentoMonto(): float
    {
        return $this->subtotal * ($this->descuentoPct / 100);
    }

    #[Computed]
    public function total(): float
    {
        return max(0, $this->subtotal - $this->descuentoMonto);
    }

    public function seleccionarCliente(int $id, string $nombre): void
    {
        $this->clienteId       = $id;
        $this->clienteNombre   = $nombre;
        $this->clienteBusqueda = $nombre;
        unset($this->clientesBusqueda);
    }

    public function limpiarCliente(): void
    {
        $this->clienteId       = null;
        $this->clienteNombre   = '';
        $this->clienteBusqueda = '';
    }

    public function agregarLinea(): void
    {
        $this->validate([
            'addProductoId' => 'required|exists:productos,id',
            'addCantidad'   => 'required|integer|min:1',
        ], [
            'addProductoId.required' => 'Selecciona un producto.',
            'addCantidad.min'        => 'La cantidad mínima es 1.',
        ]);

        $producto = Producto::findOrFail($this->addProductoId);

        $this->lineas[] = [
            'producto_id'        => $producto->id,
            'nombre'             => $producto->nombre,
            'cantidad'           => $this->addCantidad,
            'precio_unitario'    => (float) $producto->precio_venta,
            'costo_unitario'     => (float) $producto->costo_base,
            'descripcion_custom' => $this->addDescripcionCustom,
        ];

        $this->addProductoId        = null;
        $this->addCantidad          = 1;
        $this->addDescripcionCustom = '';
        unset($this->subtotal, $this->descuentoMonto, $this->total);
    }

    public function quitarLinea(int $index): void
    {
        array_splice($this->lineas, $index, 1);
        $this->lineas = array_values($this->lineas);
        unset($this->subtotal, $this->descuentoMonto, $this->total);
    }

    public function guardar(): void
    {
        $this->validate([
            'clienteId'      => 'required|exists:clientes,id',
            'lineas'         => 'required|array|min:1',
            'fechaPrometida' => 'nullable|date',
            'descuentoPct'   => 'numeric|min:0|max:100',
        ], [
            'clienteId.required' => 'Debes seleccionar un cliente.',
            'lineas.min'         => 'El pedido debe tener al menos un producto.',
        ]);

        $esperandoStock = false;

        DB::transaction(function () use (&$esperandoStock) {
            $pedido = Pedido::create([
                'cliente_id'      => $this->clienteId,
                'usuario_id'      => auth()->id(),
                'estado'          => Pedido::PENDIENTE,
                'descuento'       => round($this->descuentoMonto, 2),
                'notas'           => $this->notas,
                'fecha_prometida' => $this->fechaPrometida ?: null,
            ]);

            foreach ($this->lineas as $linea) {
                $pedido->detalles()->create([
                    'producto_id'        => $linea['producto_id'],
                    'cantidad'           => $linea['cantidad'],
                    'precio_unitario'    => $linea['precio_unitario'],
                    'costo_unitario'     => $linea['costo_unitario'],
                    'descripcion_custom' => $linea['descripcion_custom'],
                ]);
            }

            // Agrupar cantidades por producto (por si el mismo producto aparece en varias líneas)
            $lineasPorProducto = [];
            foreach ($this->lineas as $linea) {
                $pid = $linea['producto_id'];
                $lineasPorProducto[$pid] = ($lineasPorProducto[$pid] ?? 0) + $linea['cantidad'];
            }

            // Bloquear los productos para evitar condiciones de carrera
            $productos = Producto::whereIn('id', array_keys($lineasPorProducto))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Detectar qué productos no tienen stock suficiente
            $faltantes = [];
            foreach ($lineasPorProducto as $productoId => $cantidadTotal) {
                $producto   = $productos[$productoId];
                $disponible = $producto->stock_actual - $producto->stock_reservado;
                if ($disponible < $cantidadTotal) {
                    $faltantes[$productoId] = [
                        'producto' => $producto,
                        'faltante' => $cantidadTotal - max($disponible, 0),
                    ];
                }
            }

            if (empty($faltantes)) {
                // Stock disponible: reservar para todas las líneas
                foreach ($lineasPorProducto as $productoId => $cantidad) {
                    Producto::where('id', $productoId)->increment('stock_reservado', $cantidad);
                }

                // Asignar lotes FIFO a las líneas del pedido y actualizar costos reales
                try {
                    DB::statement("CALL sp_asignar_lotes_fifo({$pedido->id})");
                } catch (\Throwable $e) {
                    // Si no hay lotes aún (p.ej. stock inicial sin movimiento), se omite
                    Log::warning("FIFO lot assignment skipped for pedido {$pedido->id}: {$e->getMessage()}");
                }

                // Verificar si algún producto quedó con stock disponible bajo el mínimo
                // tras la reserva. El trigger solo evalúa movimientos, no cambios en stock_reservado.
                $productosActualizados = Producto::whereIn('id', array_keys($lineasPorProducto))
                    ->get()
                    ->keyBy('id');

                foreach ($productosActualizados as $producto) {
                    $disponible = $producto->stock_actual - $producto->stock_reservado;

                    if ($disponible < $producto->stock_minimo) {
                        $alertaExiste = AlertaStock::where('producto_id', $producto->id)
                            ->whereNull('pedido_id')
                            ->where('resuelta', false)
                            ->exists();

                        if (! $alertaExiste) {
                            $alerta = AlertaStock::create([
                                'producto_id'       => $producto->id,
                                'stock_al_generar'  => $producto->stock_actual,
                                'stock_minimo'      => $producto->stock_minimo,
                                'cantidad_faltante' => 0,
                                'pedido_id'         => null,
                            ]);

                            SolicitudReabastecimiento::create([
                                'producto_id'     => $producto->id,
                                'pedido_id'       => null,
                                'alerta_id'       => $alerta->id,
                                'cantidad_pedida' => max(
                                    $producto->stock_minimo * 2 - $disponible,
                                    $producto->stock_minimo
                                ),
                                'prioridad'       => $disponible <= 0 ? 1 : 2,
                            ]);
                        }
                    }
                }
            } else {
                // Stock insuficiente: bloquear pedido y generar alertas/solicitudes
                $pedido->update(['estado' => Pedido::ESPERANDO_STOCK]);
                $esperandoStock = true;

                foreach ($faltantes as $data) {
                    $alerta = AlertaStock::create([
                        'producto_id'      => $data['producto']->id,
                        'stock_al_generar' => $data['producto']->stock_actual,
                        'stock_minimo'     => $data['producto']->stock_minimo,
                        'cantidad_faltante' => $data['faltante'],
                        'pedido_id'        => $pedido->id,
                    ]);

                    SolicitudReabastecimiento::create([
                        'producto_id'     => $data['producto']->id,
                        'pedido_id'       => $pedido->id,
                        'alerta_id'       => $alerta->id,
                        'cantidad_pedida' => $data['faltante'] + $data['producto']->stock_minimo,
                        'prioridad'       => 1,
                    ]);
                }
            }
        });

        if ($esperandoStock) {
            Flux::toast(
                'El pedido quedó bloqueado por falta de stock. Se generó una solicitud de reabastecimiento.',
                heading: 'Stock insuficiente',
                variant: 'warning'
            );
        } else {
            Flux::toast('Pedido creado correctamente.', variant: 'success');
        }

        $this->redirect(route('pedidos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pedidos.crear', [
            'productos' => Producto::activo()->orderBy('nombre')->get(),
        ]);
    }
}
