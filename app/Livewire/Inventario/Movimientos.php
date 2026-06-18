<?php

namespace App\Livewire\Inventario;

use App\Models\AlertaStock;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inventario — Movimientos')]
class Movimientos extends Component
{
    use WithPagination;

    public string $filtroTipo  = '';
    public string $busqueda    = '';
    public string $inputDesde  = '';
    public string $inputHasta  = '';
    public string $desde       = '';
    public string $hasta       = '';

    public bool   $modalAbierto   = false;
    public ?int   $productoId     = null;
    public string $tipo           = 'entrada';
    public int    $cantidad        = 1;
    public float  $costoUnitario  = 0;
    public string $motivo         = '';

    public function mount(): void
    {
        $this->inputDesde = now()->startOfMonth()->format('Y-m-d');
        $this->inputHasta = now()->endOfMonth()->format('Y-m-d');
    }

    public function aplicarFechas(): void
    {
        $this->desde = $this->inputDesde;
        $this->hasta = $this->inputHasta;
        $this->resetPage();
    }

    public function limpiarFechas(): void
    {
        $this->desde = '';
        $this->hasta = '';
        $this->resetPage();
    }

    public function updatedFiltroTipo(): void { $this->resetPage(); }
    public function updatedBusqueda(): void   { $this->resetPage(); }

    public function abrirModal(): void
    {
        $this->productoId    = null;
        $this->tipo          = 'entrada';
        $this->cantidad      = 1;
        $this->costoUnitario = 0;
        $this->motivo        = '';
        $this->resetValidation();
        $this->modalAbierto  = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'productoId'    => 'required|exists:productos,id',
            'tipo'          => 'required|in:entrada,salida,ajuste',
            'cantidad'      => 'required|integer|not_in:0',
            'costoUnitario' => 'required|numeric|min:0',
            'motivo'        => 'nullable|string|max:200',
        ], [
            'productoId.required' => 'Selecciona un producto.',
            'cantidad.not_in'     => 'La cantidad no puede ser cero.',
        ]);

        // Salidas y ajustes negativos se almacenan como cantidad negativa
        $cantidadFinal = ($this->tipo === 'entrada')
            ? abs($this->cantidad)
            : -abs($this->cantidad);

        // Para entradas: capturar pedidos bloqueados antes del movimiento
        $bloqueadosAntes = $this->tipo === 'entrada'
            ? DB::table('detalle_pedido as dp')
                ->join('pedidos as p', 'p.id', '=', 'dp.pedido_id')
                ->where('dp.producto_id', $this->productoId)
                ->where('p.estado', 'esperando_stock')
                ->distinct()
                ->pluck('dp.pedido_id')
            : collect();

        MovimientoInventario::create([
            'producto_id'    => $this->productoId,
            'usuario_id'     => auth()->id(),
            'tipo'           => $this->tipo,
            'cantidad'       => $cantidadFinal,
            'costo_unitario' => $this->costoUnitario,
            'motivo'         => $this->motivo,
        ]);

        // Asignar lotes FIFO a los pedidos que el trigger acaba de liberar
        if ($bloqueadosAntes->isNotEmpty()) {
            Pedido::whereIn('id', $bloqueadosAntes)
                ->where('estado', 'pendiente')
                ->pluck('id')
                ->each(fn($id) => DB::statement("CALL sp_asignar_lotes_fifo({$id})"));
        }

        Flux::toast('Movimiento registrado.', variant: 'success');
        $this->modalAbierto = false;
    }

    public function resolverAlerta(int $id): void
    {
        AlertaStock::findOrFail($id)->resolver();
        Flux::toast('Alerta marcada como resuelta.', variant: 'success');
    }

    public function render(): View
    {
        $movimientos = MovimientoInventario::with(['producto', 'usuario', 'lote'])
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->busqueda, fn ($q) => $q->whereHas(
                'producto',
                fn ($q2) => $q2->where('nombre', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->desde, fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->orderByDesc('fecha')
            ->paginate(20);

        $alertasGenerales = AlertaStock::with('producto.categoria')
            ->where('resuelta', false)
            ->whereNull('pedido_id')
            ->latest('created_at')
            ->get();

        $alertasPedidos = AlertaStock::with('producto.categoria')
            ->where('resuelta', false)
            ->whereNotNull('pedido_id')
            ->latest('created_at')
            ->get();

        $productos = Producto::activo()->orderBy('nombre')->get();

        return view('livewire.inventario.movimientos', compact(
            'movimientos', 'alertasGenerales', 'alertasPedidos', 'productos'
        ));
    }
}
