<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudReabastecimiento extends Model
{
    protected $table = 'solicitudes_reabastecimiento';

    protected $fillable = [
        'producto_id', 'pedido_id', 'alerta_id',
        'cantidad_pedida', 'estado', 'prioridad',
        'notas', 'atendido_por',
    ];

    const PENDIENTE   = 'pendiente';
    const EN_PROCESO  = 'en_proceso';
    const RECIBIDO    = 'recibido';
    const CANCELADO   = 'cancelado';

    const ESTADOS = [
        self::PENDIENTE  => 'Pendiente',
        self::EN_PROCESO => 'En proceso',
        self::RECIBIDO   => 'Recibido',
        self::CANCELADO  => 'Cancelado',
    ];

    const PRIORIDADES = [
        1 => 'Alta',
        2 => 'Normal',
        3 => 'Baja',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function alerta(): BelongsTo
    {
        return $this->belongsTo(AlertaStock::class, 'alerta_id');
    }

    public function atendidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function estadoColor(): string
    {
        return match ($this->estado) {
            self::PENDIENTE  => 'orange',
            self::EN_PROCESO => 'blue',
            self::RECIBIDO   => 'lime',
            self::CANCELADO  => 'zinc',
            default          => 'zinc',
        };
    }

    public function prioridadLabel(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? 'Normal';
    }

    public function prioridadColor(): string
    {
        return match ($this->prioridad) {
            1       => 'red',
            2       => 'yellow',
            3       => 'zinc',
            default => 'zinc',
        };
    }
}
