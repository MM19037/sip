<?php

namespace App\Livewire\Pedidos;

use App\Models\Pedido;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pedidos')]
class Index extends Component
{
    use WithPagination;

    public string $busqueda = '';

    #[Url(as: 'filtroEstado')]
    public string $filtroEstado = '';

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function enviarAProduccion(int $id): void
    {
        $pedido = Pedido::findOrFail($id);

        if (!$pedido->puedeIrAProduccion()) {
            Flux::toast('El pedido no puede enviarse a producción.', heading: 'No permitido', variant: 'danger');
            return;
        }

        $pedido->update(['estado' => Pedido::EN_PRODUCCION]);
        Flux::toast("Pedido #{$id} en cola de producción.", heading: 'Enviado a producción', variant: 'success');
    }

    public function marcarEntregado(int $id): void
    {
        $pedido = Pedido::findOrFail($id);

        if (!$pedido->puedeEntregarse()) {
            Flux::toast('El pedido no está listo para entrega.', heading: 'No permitido', variant: 'danger');
            return;
        }

        $pedido->marcarEntregado();
        Flux::toast("Pedido #{$id} marcado como entregado.", heading: 'Entregado', variant: 'success');
    }

    public function cancelar(int $id): void
    {
        $pedido = Pedido::findOrFail($id);

        if (!$pedido->puedeCancelarse()) {
            Flux::toast('Este pedido no puede cancelarse.', heading: 'No permitido', variant: 'danger');
            return;
        }

        $pedido->update(['estado' => Pedido::CANCELADO]);
        Flux::toast("Pedido #{$id} cancelado.", heading: 'Cancelado', variant: 'warning');
    }

    public function render(): View
    {
        $pedidos = Pedido::with(['cliente', 'usuario'])
            ->when($this->filtroEstado, fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->busqueda, fn ($q) => $q->whereHas(
                'cliente',
                fn ($q2) => $q2->where('nombre', 'like', "%{$this->busqueda}%")
            ))
            ->latest('fecha_pedido')
            ->paginate(15);

        return view('livewire.pedidos.index', compact('pedidos'));
    }
}
