<?php

namespace App\Livewire\Inventario;

use App\Models\AlertaStock;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Flux\Flux;
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

    public string $filtroTipo = '';
    public string $busqueda   = '';

    public bool   $modalAbierto   = false;
    public ?int   $productoId     = null;
    public string $tipo           = 'entrada';
    public int    $cantidad        = 1;
    public float  $costoUnitario  = 0;
    public string $motivo         = '';

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

        MovimientoInventario::create([
            'producto_id'    => $this->productoId,
            'usuario_id'     => auth()->id(),
            'tipo'           => $this->tipo,
            'cantidad'       => $cantidadFinal,
            'costo_unitario' => $this->costoUnitario,
            'motivo'         => $this->motivo,
        ]);
        // El trigger trg_movimiento_insert actualiza el stock y genera alerta si es necesario

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
        $movimientos = MovimientoInventario::with(['producto', 'usuario'])
            ->when($this->filtroTipo, fn ($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->busqueda, fn ($q) => $q->whereHas(
                'producto',
                fn ($q2) => $q2->where('nombre', 'like', "%{$this->busqueda}%")
            ))
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
