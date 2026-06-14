<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    public $timestamps = false;

    protected $table = 'lotes';

    protected $fillable = [
        'producto_id', 'movimiento_id', 'numero_lote', 'fecha_entrada',
        'cantidad_inicial', 'cantidad_disponible', 'cantidad_reservada',
        'costo_unitario', 'activo',
    ];

    protected $casts = [
        'fecha_entrada'       => 'datetime',
        'costo_unitario'      => 'decimal:2',
        'activo'              => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(DetallePedidoLote::class);
    }

    public function cantidadLibre(): int
    {
        return $this->cantidad_disponible - $this->cantidad_reservada;
    }

    public function valorDisponible(): float
    {
        return $this->cantidad_disponible * (float) $this->costo_unitario;
    }

    public function estaAgotado(): bool
    {
        return $this->cantidad_disponible <= 0;
    }
}
