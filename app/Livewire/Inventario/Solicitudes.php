<?php

namespace App\Livewire\Inventario;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\SolicitudReabastecimiento;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Solicitudes de Reabastecimiento')]
class Solicitudes extends Component
{
    use WithPagination;

    public string $filtroTipo      = '';
    public string $filtroEstado    = 'pendiente';
    public string $filtroPrioridad = '';

    // Modal registrar entrada
    public bool   $modalEntrada   = false;
    public ?int   $solicitudId    = null;
    public ?int   $productoId     = null;
    public int    $cantidad       = 1;
    public ?float $costoUnitario  = null;
    public string $motivo         = '';

    public function updatedFiltroTipo(): void      { $this->resetPage(); }
    public function updatedFiltroEstado(): void    { $this->resetPage(); }
    public function updatedFiltroPrioridad(): void { $this->resetPage(); }

    public function abrirEntrada(int $id): void
    {
        $s = SolicitudReabastecimiento::findOrFail($id);

        $this->solicitudId   = $id;
        $this->productoId    = $s->producto_id;
        $this->cantidad      = $s->cantidad_pedida;
        $this->costoUnitario = null;
        $this->motivo        = "Reabastecimiento — Solicitud #{$id}";
        $this->resetValidation();
        $this->modalEntrada  = true;
    }

    public function guardarEntrada(): void
    {
        $this->validate([
            'productoId'    => 'required|exists:productos,id',
            'cantidad'      => 'required|integer|min:1',
            'costoUnitario' => 'required|numeric|min:0',
        ]);

        MovimientoInventario::create([
            'producto_id'    => $this->productoId,
            'usuario_id'     => auth()->id(),
            'tipo'           => 'entrada',
            'cantidad'       => abs($this->cantidad),
            'costo_unitario' => $this->costoUnitario,
            'motivo'         => $this->motivo,
        ]);

        SolicitudReabastecimiento::findOrFail($this->solicitudId)->update([
            'estado'       => SolicitudReabastecimiento::RECIBIDO,
            'atendido_por' => auth()->id(),
        ]);

        Flux::toast('Entrada registrada y solicitud marcada como recibida.', variant: 'success');
        $this->modalEntrada = false;
    }

    public function marcarEnProceso(int $id): void
    {
        $s = SolicitudReabastecimiento::findOrFail($id);

        if ($s->estado !== SolicitudReabastecimiento::PENDIENTE) {
            Flux::toast('Solo se pueden procesar solicitudes pendientes.', variant: 'warning');
            return;
        }

        $s->update([
            'estado'       => SolicitudReabastecimiento::EN_PROCESO,
            'atendido_por' => auth()->id(),
        ]);

        Flux::toast("Solicitud #{$id} marcada en proceso.", variant: 'success');
    }

    public function cancelar(int $id): void
    {
        $s = SolicitudReabastecimiento::findOrFail($id);

        if (!in_array($s->estado, [SolicitudReabastecimiento::PENDIENTE, SolicitudReabastecimiento::EN_PROCESO])) {
            Flux::toast('Esta solicitud ya no puede cancelarse.', variant: 'warning');
            return;
        }

        $s->update([
            'estado'       => SolicitudReabastecimiento::CANCELADO,
            'atendido_por' => auth()->id(),
        ]);

        Flux::toast("Solicitud #{$id} cancelada.", variant: 'success');
    }

    public function render(): View
    {
        $solicitudes = SolicitudReabastecimiento::with(['producto.categoria', 'pedido.cliente', 'atendidoPor'])
            ->when($this->filtroTipo === 'general', fn ($q) => $q->whereNull('pedido_id'))
            ->when($this->filtroTipo === 'pedido',  fn ($q) => $q->whereNotNull('pedido_id'))
            ->when($this->filtroEstado,    fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroPrioridad, fn ($q) => $q->where('prioridad', $this->filtroPrioridad))
            ->orderBy('prioridad')
            ->orderBy('created_at')
            ->paginate(20);

        $productos = Producto::activo()->orderBy('nombre')->get();

        return view('livewire.inventario.solicitudes', compact('solicitudes', 'productos'));
    }
}
