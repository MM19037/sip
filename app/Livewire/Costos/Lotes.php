<?php

namespace App\Livewire\Costos;

use App\Models\Categoria;
use App\Models\Lote;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Costos — Lotes de inventario')]
class Lotes extends Component
{
    use WithPagination;

    public string $busqueda        = '';
    public string $filtroCategoria = '';   // categoria_id como string
    public string $filtroEstado    = 'activos'; // activos | agotados | todos

    public function updatedBusqueda(): void        { $this->resetPage(); }
    public function updatedFiltroCategoria(): void { $this->resetPage(); }
    public function updatedFiltroEstado(): void    { $this->resetPage(); }

    public function render(): View
    {
        $lotes = Lote::with('producto.categoria')
            ->when($this->busqueda, fn ($q) => $q->whereHas(
                'producto',
                fn ($q2) => $q2->where('nombre', 'like', "%{$this->busqueda}%")
            ))
            ->when($this->filtroCategoria, fn ($q) => $q->whereHas(
                'producto',
                fn ($q2) => $q2->where('categoria_id', $this->filtroCategoria)
            ))
            ->when($this->filtroEstado === 'activos',  fn ($q) => $q->where('activo', true)->where('cantidad_disponible', '>', 0))
            ->when($this->filtroEstado === 'agotados', fn ($q) => $q->where(fn ($q2) =>
                $q2->where('activo', false)->orWhere('cantidad_disponible', '<=', 0)
            ))
            ->orderByDesc('fecha_entrada')
            ->paginate(25);

        $categorias = Categoria::activo()->orderBy('nombre')->get();

        $resumen = DB::table('lotes')
            ->where('activo', true)
            ->where('cantidad_disponible', '>', 0)
            ->selectRaw('
                COUNT(*)                                    AS total_lotes,
                SUM(cantidad_disponible)                    AS unidades_total,
                SUM(cantidad_disponible * costo_unitario)  AS valor_total,
                SUM(cantidad_reservada)                     AS unidades_reservadas,
                SUM(cantidad_reservada  * costo_unitario)  AS valor_reservado
            ')
            ->first();

        return view('livewire.costos.lotes', compact('lotes', 'categorias', 'resumen'));
    }
}
