<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedidoLote extends Model
{
    public $timestamps = false;

    protected $table = 'detalle_pedido_lotes';

    protected $fillable = [
        'detalle_pedido_id', 'lote_id', 'cantidad_asignada', 'costo_unitario',
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:2',
    ];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(DetallePedido::class, 'detalle_pedido_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
