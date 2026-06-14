<?php

namespace App\Livewire\Categorias;

use App\Models\Categoria;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Categorías de productos')]
class Index extends Component
{
    use WithPagination;

    public string $busqueda     = '';
    public bool   $soloActivas  = false;

    // Formulario
    public bool    $modalAbierto = false;
    public ?int    $editandoId   = null;
    public string  $nombre       = '';
    public string  $descripcion  = '';
    public bool    $activo       = true;

    // Confirmación de eliminación
    public bool $modalEliminar  = false;
    public ?int $eliminandoId   = null;
    public string $eliminandoNombre = '';

    public function updatedBusqueda(): void { $this->resetPage(); }

    public function abrirCrear(): void
    {
        $this->reset(['editandoId', 'nombre', 'descripcion', 'activo', 'modalEliminar']);
        $this->activo       = true;
        $this->modalAbierto = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $cat = Categoria::findOrFail($id);
        $this->editandoId  = $id;
        $this->nombre      = $cat->nombre;
        $this->descripcion = $cat->descripcion ?? '';
        $this->activo      = $cat->activo;
        $this->modalAbierto = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $unicidad = $this->editandoId
            ? 'unique:categorias,nombre,' . $this->editandoId
            : 'unique:categorias,nombre';

        $this->validate([
            'nombre'      => ['required', 'string', 'max:80', $unicidad],
            'descripcion' => 'nullable|string|max:500',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe una categoría con ese nombre.',
            'nombre.max'      => 'El nombre no puede superar 80 caracteres.',
        ]);

        $datos = [
            'nombre'      => trim($this->nombre),
            'descripcion' => trim($this->descripcion) ?: null,
            'activo'      => $this->activo,
        ];

        if ($this->editandoId) {
            Categoria::findOrFail($this->editandoId)->update($datos);
            Flux::toast('Categoría actualizada.', variant: 'success');
        } else {
            Categoria::create($datos);
            Flux::toast('Categoría creada.', variant: 'success');
        }

        $this->modalAbierto = false;
    }

    public function confirmarEliminar(int $id): void
    {
        $cat = Categoria::withCount('productos')->findOrFail($id);
        $this->eliminandoId     = $id;
        $this->eliminandoNombre = $cat->nombre;

        if ($cat->productos_count > 0) {
            Flux::toast(
                "No se puede eliminar: tiene {$cat->productos_count} producto(s) asociado(s). Desactívala en su lugar.",
                heading: 'Categoría en uso',
                variant: 'warning'
            );
            return;
        }

        $this->modalEliminar = true;
    }

    public function eliminar(): void
    {
        $cat = Categoria::withCount('productos')->findOrFail($this->eliminandoId);

        if ($cat->productos_count > 0) {
            Flux::toast('No se puede eliminar: tiene productos asociados.', variant: 'danger');
            $this->modalEliminar = false;
            return;
        }

        $cat->delete();
        Flux::toast('Categoría eliminada.', variant: 'success');
        $this->modalEliminar = false;
        $this->eliminandoId  = null;
    }

    public function toggleActivo(int $id): void
    {
        $cat = Categoria::findOrFail($id);
        $cat->update(['activo' => ! $cat->activo]);
        Flux::toast(
            $cat->activo ? 'Categoría activada.' : 'Categoría desactivada.',
            variant: 'success'
        );
    }

    public function render(): View
    {
        $categorias = Categoria::withCount('productos')
            ->when($this->busqueda, fn ($q) => $q->where('nombre', 'like', "%{$this->busqueda}%")
                ->orWhere('descripcion', 'like', "%{$this->busqueda}%"))
            ->when($this->soloActivas, fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.categorias.index', compact('categorias'));
    }
}
