<?php

namespace App\Livewire\Permisos;

use App\Models\PermisoRol;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public array $permisos = [];

    public function mount(): void
    {
        $this->cargar();
    }

    public function toggle(string $seccion, string $rol): void
    {
        if (! array_key_exists($seccion, PermisoRol::SECCIONES) || ! in_array($rol, PermisoRol::ROLES_CONFIGURABLES)) {
            return;
        }

        $nuevoValor = ! ($this->permisos[$seccion][$rol] ?? true);

        PermisoRol::updateOrCreate(
            ['rol' => $rol, 'seccion' => $seccion],
            ['activo' => $nuevoValor]
        );

        PermisoRol::limpiarCache($rol);

        $this->cargar();
    }

    private function cargar(): void
    {
        $registros = PermisoRol::whereIn('rol', PermisoRol::ROLES_CONFIGURABLES)->get();

        foreach (array_keys(PermisoRol::SECCIONES) as $seccion) {
            foreach (PermisoRol::ROLES_CONFIGURABLES as $rol) {
                $registro = $registros->where('rol', $rol)->where('seccion', $seccion)->first();
                $this->permisos[$seccion][$rol] = $registro ? $registro->activo : true;
            }
        }
    }

    public function render(): View
    {
        return view('livewire.permisos.index', [
            'secciones' => PermisoRol::SECCIONES,
            'roles'     => PermisoRol::ROLES_CONFIGURABLES,
            'grupos'    => PermisoRol::GRUPOS,
        ]);
    }
}
