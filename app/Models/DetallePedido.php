<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetallePedido extends Model
{
    public $timestamps = false;

    protected $table = 'detalle_pedido';

    protected $fillable = [
        'pedido_id', 'producto_id', 'cantidad',
        'precio_unitario', 'costo_unitario', 'descripcion_custom',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'costo_unitario'  => 'decimal:2',
        'subtotal'        => 'decimal:2',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function lotesAsignados(): HasMany
    {
        return $this->hasMany(DetallePedidoLote::class);
    }
}
