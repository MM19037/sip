<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pedido extends Model
{
    const ESPERANDO_STOCK = 'esperando_stock';
    const PENDIENTE       = 'pendiente';
    const EN_PRODUCCION   = 'en_produccion';
    const LISTO           = 'listo';
    const ENTREGADO       = 'entregado';
    const CANCELADO       = 'cancelado';

    const ESTADOS = [
        self::ESPERANDO_STOCK => 'Esperando stock',
        self::PENDIENTE       => 'Pendiente',
        self::EN_PRODUCCION   => 'En producción',
        self::LISTO           => 'Listo',
        self::ENTREGADO       => 'Entregado',
        self::CANCELADO       => 'Cancelado',
    ];

    protected $fillable = [
        'cliente_id', 'usuario_id', 'estado',
        'subtotal', 'descuento', 'total', 'total_costo',
        'notas', 'fecha_pedido', 'fecha_prometida', 'fecha_entrega',
    ];

    protected $casts = [
        'fecha_pedido'    => 'datetime',
        'fecha_prometida' => 'date',
        'fecha_entrega'   => 'datetime',
        'subtotal'        => 'decimal:2',
        'descuento'       => 'decimal:2',
        'total'           => 'decimal:2',
        'total_costo'     => 'decimal:2',
        'ganancia'        => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function ordenProduccion(): HasOne
    {
        return $this->hasOne(OrdenProduccion::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaStock::class);
    }

    public function solicitudesReabastecimiento(): HasMany
    {
        return $this->hasMany(SolicitudReabastecimiento::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNotIn('estado', [self::ENTREGADO, self::CANCELADO]);
    }

    public function estaEsperandoStock(): bool
    {
        return $this->estado === self::ESPERANDO_STOCK;
    }

    public function puedeIrAProduccion(): bool
    {
        return $this->estado === self::PENDIENTE && $this->detalles()->exists();
    }

    public function puedeEntregarse(): bool
    {
        return $this->estado === self::LISTO;
    }

    public function puedeCancelarse(): bool
    {
        return !in_array($this->estado, [self::ENTREGADO, self::CANCELADO]);
    }

    public function marcarEntregado(): void
    {
        $this->update([
            'estado'        => self::ENTREGADO,
            'fecha_entrega' => now(),
        ]);
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function estadoColor(): string
    {
        return match ($this->estado) {
            self::ESPERANDO_STOCK => 'orange',
            self::PENDIENTE       => 'yellow',
            self::EN_PRODUCCION   => 'blue',
            self::LISTO           => 'lime',
            self::ENTREGADO       => 'zinc',
            self::CANCELADO       => 'red',
            default               => 'zinc',
        };
    }
}
