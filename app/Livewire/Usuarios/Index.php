<?php

namespace App\Livewire\Usuarios;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Usuarios')]
class Index extends Component
{
    use WithPagination;

    public string $busqueda     = '';
    public bool   $modalAbierto = false;
    public ?int   $editandoId   = null;

    // Formulario
    public string  $name      = '';
    public string  $email     = '';
    public string  $rol       = 'recepcionista';
    public bool    $activo    = true;
    public string  $password  = '';
    public string  $passwordConfirmation = '';

    public function updatedBusqueda(): void { $this->resetPage(); }

    public function abrirCrear(): void
    {
        $this->resetFormulario();
        $this->modalAbierto = true;
    }

    public function abrirEditar(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editandoId = $id;
        $this->name       = $u->name;
        $this->email      = $u->email;
        $this->rol        = $u->rol;
        $this->activo     = $u->activo;
        $this->password   = '';
        $this->passwordConfirmation = '';
        $this->modalAbierto = true;
    }

    public function guardar(): void
    {
        $rules = [
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email' . ($this->editandoId ? ",{$this->editandoId}" : ''),
            'rol'   => 'required|in:administrador,recepcionista,produccion',
        ];

        if (!$this->editandoId || $this->password) {
            $rules['password'] = 'required|string|min:8|same:passwordConfirmation';
        }

        $this->validate($rules, [
            'name.required'     => 'El nombre es obligatorio.',
            'email.unique'      => 'Este correo ya está registrado.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.same' => 'Las contraseñas no coinciden.',
        ]);

        $datos = [
            'name'   => $this->name,
            'email'  => $this->email,
            'rol'    => $this->rol,
            'activo' => $this->activo,
        ];

        if ($this->password) {
            $datos['password'] = Hash::make($this->password);
        }

        if ($this->editandoId) {
            User::findOrFail($this->editandoId)->update($datos);
            Flux::toast('Usuario actualizado.', variant: 'success');
        } else {
            User::create($datos);
            Flux::toast('Usuario creado.', variant: 'success');
        }

        $this->modalAbierto = false;
        $this->resetFormulario();
    }

    public function toggleActivo(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast('No puedes desactivar tu propia cuenta.', variant: 'danger');
            return;
        }

        $u = User::findOrFail($id);
        $u->update(['activo' => !$u->activo]);
        Flux::toast($u->activo ? 'Usuario activado.' : 'Usuario desactivado.', variant: 'success');
    }

    private function resetFormulario(): void
    {
        $this->editandoId          = null;
        $this->name                = '';
        $this->email               = '';
        $this->rol                 = 'recepcionista';
        $this->activo              = true;
        $this->password            = '';
        $this->passwordConfirmation = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $usuarios = User::when($this->busqueda, fn ($q) => $q
            ->where('name', 'like', "%{$this->busqueda}%")
            ->orWhere('email', 'like', "%{$this->busqueda}%"))
            ->orderBy('rol')->orderBy('name')
            ->paginate(20);

        return view('livewire.usuarios.index', compact('usuarios'));
    }
}
