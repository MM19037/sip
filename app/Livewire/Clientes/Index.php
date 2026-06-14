<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clientes')]
class Index extends Component
{
    use WithPagination;

    public string $busqueda     = '';
    public bool   $modalAbierto = false;
    public ?int   $editandoId   = null;

    // Historial
    public ?int $verHistorialId = null;

    // Formulario
    public string $nombre    = '';
    public string $telefono  = '';
    public string $email     = '';
    public string $direccion = '';
    public string $notas     = '';

    public function updatedBusqueda(): void { $this->resetPage(); }

    public function abrirCrear(): void
    {
        $this->resetFormulario();
        $this->modalAbierto = true;
    }

    public function abrirEditar(int $id): void
    {
        $c = Cliente::findOrFail($id);
        $this->editandoId = $id;
        $this->nombre     = $c->nombre;
        $this->telefono   = $c->telefono ?? '';
        $this->email      = $c->email ?? '';
        $this->direccion  = $c->direccion ?? '';
        $this->notas      = $c->notas ?? '';
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'   => 'required|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:150',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'email.email'     => 'Ingresa un correo válido.',
        ]);

        $datos = [
            'nombre'    => $this->nombre,
            'telefono'  => $this->telefono,
            'email'     => $this->email,
            'direccion' => $this->direccion,
            'notas'     => $this->notas,
        ];

        if ($this->editandoId) {
            Cliente::findOrFail($this->editandoId)->update($datos);
            Flux::toast('Cliente actualizado.', variant: 'success');
        } else {
            Cliente::create($datos);
            Flux::toast('Cliente registrado.', variant: 'success');
        }

        $this->modalAbierto = false;
        $this->resetFormulario();
    }

    public function verHistorial(int $id): void
    {
        $this->verHistorialId = $id;
    }

    private function resetFormulario(): void
    {
        $this->editandoId = null;
        $this->nombre     = '';
        $this->telefono   = '';
        $this->email      = '';
        $this->direccion  = '';
        $this->notas      = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $clientes = Cliente::when($this->busqueda, fn ($q) => $q
            ->where('nombre', 'like', "%{$this->busqueda}%")
            ->orWhere('telefono', 'like', "%{$this->busqueda}%")
            ->orWhere('email', 'like', "%{$this->busqueda}%"))
            ->withCount('pedidos')
            ->orderBy('nombre')
            ->paginate(20);

        $historial = $this->verHistorialId
            ? Cliente::with(['pedidos' => fn ($q) => $q->latest('fecha_pedido')->limit(10)])
                ->find($this->verHistorialId)
            : null;

        return view('livewire.clientes.index', compact('clientes', 'historial'));
    }
}
