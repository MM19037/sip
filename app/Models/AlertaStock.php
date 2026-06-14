<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertaStock extends Model
{
    public $timestamps = false;

    protected $table = 'alertas_stock';

    protected $fillable = [
        'producto_id', 'stock_al_generar', 'stock_minimo',
        'cantidad_faltante', 'pedido_id', 'resuelta', 'resuelta_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime',
        'resuelta_at' => 'datetime',
        'resuelta'    => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudReabastecimiento::class, 'alerta_id');
    }

    public function resolver(): void
    {
        $this->update(['resuelta' => true, 'resuelta_at' => now()]);
    }
}
