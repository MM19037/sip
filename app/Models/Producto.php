<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $fillable = [
        'nombre', 'categoria_id', 'descripcion',
        'costo_base', 'margen_ganancia',
        'stock_actual', 'stock_reservado', 'stock_minimo', 'activo',
    ];

    protected $casts = [
        'costo_base'      => 'decimal:2',
        'margen_ganancia' => 'decimal:2',
        'precio_venta'    => 'decimal:2',
        'activo'          => 'boolean',
    ];

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function stockDisponible(): int
    {
        return $this->stock_actual - $this->stock_reservado;
    }

    public function bajoStock(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function bajoStockDisponible(): bool
    {
        return $this->stockDisponible() <= $this->stock_minimo;
    }

    public function detallesPedido(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaStock::class);
    }

    public function solicitudesReabastecimiento(): HasMany
    {
        return $this->hasMany(SolicitudReabastecimiento::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function lotesActivos(): HasMany
    {
        return $this->hasMany(Lote::class)
            ->where('activo', true)
            ->where('cantidad_disponible', '>', 0);
    }
}
