<?php

namespace App\Livewire\Produccion;

use App\Models\OrdenProduccion;
use App\Models\User;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Producción')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'filtroEstado')]
    public string $filtroEstado    = '';
    public string $filtroPrioridad = '';

    // Modal asignación
    public bool   $modalAsignar   = false;
    public ?int   $ordenId        = null;
    public ?int   $operarioId     = null;
    public int    $prioridad      = 2;
    public string $observaciones  = '';

    // Advertencia de operario ocupado (para el modal)
    public string $avisoOperario  = '';

    public function updatedFiltroEstado(): void    { $this->resetPage(); }
    public function updatedFiltroPrioridad(): void { $this->resetPage(); }

    public function updatedOperarioId(): void
    {
        $this->avisoOperario = '';

        if (! $this->operarioId) return;

        $enProceso = OrdenProduccion::where('usuario_id', $this->operarioId)
            ->where('estado', OrdenProduccion::EN_PROCESO)
            ->where('id', '!=', $this->ordenId)
            ->first();

        if ($enProceso) {
            $this->avisoOperario = "Este operario ya tiene la orden #OP{$enProceso->id} en proceso.";
            return;
        }

        $pausado = OrdenProduccion::where('usuario_id', $this->operarioId)
            ->where('estado', OrdenProduccion::PAUSADO)
            ->where('id', '!=', $this->ordenId)
            ->first();

        if ($pausado) {
            $this->avisoOperario = "Este operario tiene la orden #OP{$pausado->id} pausada.";
        }
    }

    public function iniciar(int $id): void
    {
        $orden = OrdenProduccion::findOrFail($id);

        // Bloquear si no hay operario asignado
        if (! $orden->usuario_id) {
            Flux::toast('Debes asignar un operario antes de iniciar la producción.', variant: 'danger');
            return;
        }

        // Bloquear si el operario asignado ya tiene otra orden en proceso
        if ($orden->usuario_id) {
            $yaEnProceso = OrdenProduccion::where('usuario_id', $orden->usuario_id)
                ->where('estado', OrdenProduccion::EN_PROCESO)
                ->where('id', '!=', $id)
                ->first();

            if ($yaEnProceso) {
                Flux::toast(
                    "El operario ya está trabajando en la orden #OP{$yaEnProceso->id}. Debe completarla o pausarla primero.",
                    variant: 'danger'
                );
                return;
            }
        }

        $orden->iniciarProceso();
        Flux::toast("Orden #OP{$id} iniciada.", variant: 'success');
    }

    public function completar(int $id): void
    {
        $orden = OrdenProduccion::findOrFail($id);
        $orden->completar();
        Flux::toast("Orden #OP{$id} completada. Pedido marcado como Listo.", variant: 'success');
    }

    public function pausar(int $id): void
    {
        $orden = OrdenProduccion::findOrFail($id);
        $orden->pausar();
        Flux::toast("Orden #OP{$id} pausada.", variant: 'warning');
    }

    public function abrirAsignar(int $id): void
    {
        $orden               = OrdenProduccion::findOrFail($id);
        $this->ordenId       = $id;
        $this->operarioId    = $orden->usuario_id;
        $this->prioridad     = $orden->prioridad;
        $this->observaciones = $orden->observaciones ?? '';
        $this->avisoOperario = '';
        $this->modalAsignar  = true;

        // Calcular aviso inicial si ya hay un operario asignado
        if ($this->operarioId) {
            $this->updatedOperarioId();
        }
    }

    public function guardarAsignacion(): void
    {
        $this->validate([
            'operarioId' => 'nullable|exists:users,id',
            'prioridad'  => 'required|integer|in:1,2,3',
        ]);

        OrdenProduccion::findOrFail($this->ordenId)->update([
            'usuario_id'    => $this->operarioId,
            'prioridad'     => $this->prioridad,
            'observaciones' => $this->observaciones,
        ]);

        $this->modalAsignar = false;
        Flux::toast('Orden actualizada.', variant: 'success');
    }

    public function render(): View
    {
        $ordenes = OrdenProduccion::with(['pedido.cliente', 'operario'])
            ->when($this->filtroEstado,    fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when($this->filtroPrioridad, fn ($q) => $q->where('prioridad', $this->filtroPrioridad))
            ->whereNotIn('estado', [OrdenProduccion::COMPLETADO])
            ->orderBy('prioridad')
            ->orderBy('created_at')
            ->paginate(15);

        // Operarios con su carga de trabajo activa para el panel y el modal
        $operarios = User::where('rol', 'produccion')
            ->where('activo', true)
            ->with(['ordenesProduccion' => fn ($q) => $q->whereNotIn('estado', [OrdenProduccion::COMPLETADO])])
            ->orderBy('name')
            ->get();

        return view('livewire.produccion.index', compact('ordenes', 'operarios'));
    }
}
