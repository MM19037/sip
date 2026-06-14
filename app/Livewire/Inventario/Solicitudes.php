<?php

namespace App\Livewire\Inventario;

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

    public function updatedFiltroTipo(): void      { $this->resetPage(); }
    public function updatedFiltroEstado(): void    { $this->resetPage(); }
    public function updatedFiltroPrioridad(): void { $this->resetPage(); }

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

        return view('livewire.inventario.solicitudes', compact('solicitudes'));
    }
}
