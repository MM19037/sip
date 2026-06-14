<?php

namespace App\Livewire\Pedidos;

use App\Models\Pedido;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detalle de Pedido')]
class Ver extends Component
{
    public int $pedidoId;

    public function mount(Pedido $pedido): void
    {
        $this->pedidoId = $pedido->id;
    }

    #[Computed]
    public function pedido(): Pedido
    {
        return Pedido::with([
            'cliente',
            'usuario',
            'detalles.producto',
            'ordenProduccion.operario',
        ])->findOrFail($this->pedidoId);
    }

    public function enviarAProduccion(): void
    {
        $pedido = $this->pedido;

        if (!$pedido->puedeIrAProduccion()) {
            Flux::toast('Acción no permitida.', variant: 'danger');
            return;
        }

        $pedido->update(['estado' => Pedido::EN_PRODUCCION]);
        unset($this->pedido);
        Flux::toast('Pedido enviado a producción.', variant: 'success');
    }

    public function marcarEntregado(): void
    {
        $pedido = $this->pedido;

        if (!$pedido->puedeEntregarse()) {
            Flux::toast('Acción no permitida.', variant: 'danger');
            return;
        }

        $pedido->marcarEntregado();
        unset($this->pedido);
        Flux::toast('Pedido marcado como entregado.', variant: 'success');
    }

    public function cancelar(): void
    {
        $pedido = $this->pedido;

        if (!$pedido->puedeCancelarse()) {
            Flux::toast('Acción no permitida.', variant: 'danger');
            return;
        }

        $pedido->update(['estado' => Pedido::CANCELADO]);
        unset($this->pedido);
        Flux::toast('Pedido cancelado.', variant: 'warning');
    }

    public function render(): View
    {
        return view('livewire.pedidos.ver');
    }
}
