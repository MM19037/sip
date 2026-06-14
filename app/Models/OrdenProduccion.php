<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenProduccion extends Model
{
    protected $table = 'ordenes_produccion';

    const ASIGNADO   = 'asignado';
    const EN_PROCESO = 'en_proceso';
    const COMPLETADO = 'completado';
    const PAUSADO    = 'pausado';

    protected $fillable = [
        'pedido_id', 'usuario_id', 'estado', 'prioridad',
        'fecha_inicio', 'fecha_fin', 'observaciones',
    ];

    protected $casts = [
        'fecha_inicio'   => 'datetime',
        'fecha_fin'      => 'datetime',
        'prioridad'      => 'integer',
        'tiempo_minutos' => 'integer',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function operario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tiempoTranscurrido(): string
    {
        if (! $this->fecha_inicio) return '—';

        $fin     = $this->fecha_fin ?? now();
        $minutos = (int) $this->fecha_inicio->diffInMinutes($fin);

        if ($minutos < 60) return "{$minutos} min";
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    public function tiempoSemaforo(): string
    {
        if (! $this->fecha_inicio || $this->estado === self::ASIGNADO) return 'zinc';

        $minutos = (int) $this->fecha_inicio->diffInMinutes($this->fecha_fin ?? now());

        if ($minutos <= 120) return 'lime';
        if ($minutos <= 240) return 'yellow';
        return 'red';
    }

    public function iniciarProceso(): void
    {
        $this->update(['estado' => self::EN_PROCESO, 'fecha_inicio' => now()]);
    }

    public function completar(): void
    {
        $this->update(['estado' => self::COMPLETADO, 'fecha_fin' => now()]);
    }

    public function pausar(): void
    {
        $this->update(['estado' => self::PAUSADO]);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            self::ASIGNADO   => 'Asignado',
            self::EN_PROCESO => 'En proceso',
            self::COMPLETADO => 'Completado',
            self::PAUSADO    => 'Pausado',
            default          => $this->estado,
        };
    }

    public function estadoColor(): string
    {
        return match ($this->estado) {
            self::ASIGNADO   => 'yellow',
            self::EN_PROCESO => 'blue',
            self::COMPLETADO => 'lime',
            self::PAUSADO    => 'orange',
            default          => 'zinc',
        };
    }

    public function prioridadLabel(): string
    {
        return match ($this->prioridad) {
            1       => 'Alta',
            2       => 'Normal',
            3       => 'Baja',
            default => 'Normal',
        };
    }

    public function prioridadColor(): string
    {
        return match ($this->prioridad) {
            1       => 'red',
            2       => 'blue',
            3       => 'zinc',
            default => 'blue',
        };
    }
}
