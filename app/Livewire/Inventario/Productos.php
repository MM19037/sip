<?php

namespace App\Livewire\Inventario;

use App\Models\Categoria;
use App\Models\Producto;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inventario — Productos')]
class Productos extends Component
{
    use WithPagination;

    public string $busqueda         = '';
    public string $filtroCategoria  = '';   // almacena categoria_id como string
    public bool   $soloActivos      = true;

    // Formulario modal
    public bool   $modalAbierto = false;
    public ?int   $editandoId   = null;

    public string  $nombre          = '';
    public ?int    $categoriaId     = null;
    public string  $descripcion     = '';
    public float   $costoBase       = 0;
    public float   $margenGanancia  = 0;
    public int     $stockMinimo     = 5;
    public bool    $activo          = true;

    public function updatedBusqueda(): void       { $this->resetPage(); }
    public function updatedFiltroCategoria(): void { $this->resetPage(); }

    public function abrirCrear(): void
    {
        $this->resetFormulario();
        $this->modalAbierto = true;
    }

    public function abrirEditar(int $id): void
    {
        $p = Producto::findOrFail($id);
        $this->editandoId      = $id;
        $this->nombre          = $p->nombre;
        $this->categoriaId     = $p->categoria_id;
        $this->descripcion     = $p->descripcion ?? '';
        $this->costoBase       = (float) $p->costo_base;
        $this->margenGanancia  = (float) $p->margen_ganancia;
        $this->stockMinimo     = $p->stock_minimo;
        $this->activo          = $p->activo;
        $this->modalAbierto    = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'         => 'required|string|max:150',
            'categoriaId'    => 'required|exists:categorias,id',
            'costoBase'      => 'required|numeric|min:0',
            'margenGanancia' => 'required|numeric|min:0',
            'stockMinimo'    => 'required|integer|min:0',
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'categoriaId.required' => 'Selecciona una categoría.',
            'categoriaId.exists'   => 'La categoría seleccionada no existe.',
        ]);

        $datos = [
            'nombre'          => $this->nombre,
            'categoria_id'    => $this->categoriaId,
            'descripcion'     => $this->descripcion,
            'costo_base'      => $this->costoBase,
            'margen_ganancia' => $this->margenGanancia,
            'stock_minimo'    => $this->stockMinimo,
            'activo'          => $this->activo,
        ];

        if ($this->editandoId) {
            Producto::findOrFail($this->editandoId)->update($datos);
            Flux::toast('Producto actualizado.', variant: 'success');
        } else {
            Producto::create($datos);
            Flux::toast('Producto creado.', variant: 'success');
        }

        $this->modalAbierto = false;
        $this->resetFormulario();
    }

    public function toggleActivo(int $id): void
    {
        $p = Producto::findOrFail($id);
        $p->update(['activo' => ! $p->activo]);
        Flux::toast($p->activo ? 'Producto activado.' : 'Producto desactivado.', variant: 'success');
    }

    private function resetFormulario(): void
    {
        $this->editandoId     = null;
        $this->nombre         = '';
        $this->categoriaId    = null;
        $this->descripcion    = '';
        $this->costoBase      = 0;
        $this->margenGanancia = 0;
        $this->stockMinimo    = 5;
        $this->activo         = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $productos = Producto::with('categoria')
            ->when($this->busqueda, fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%"))
            ->when($this->filtroCategoria, fn ($q) => $q->where('categoria_id', $this->filtroCategoria))
            ->when($this->soloActivos, fn ($q) => $q->where('activo', true))
            ->orderBy('categoria_id')->orderBy('nombre')
            ->paginate(20);

        $categorias = Categoria::activo()->orderBy('nombre')->get();

        return view('livewire.inventario.productos', compact('productos', 'categorias'));
    }
}
