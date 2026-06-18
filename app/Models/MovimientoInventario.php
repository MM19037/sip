<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MovimientoInventario extends Model
{
    public $timestamps = false;

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id', 'usuario_id', 'pedido_id',
        'tipo', 'cantidad', 'costo_unitario', 'motivo',
    ];

    protected $casts = [
        'fecha'          => 'datetime',
        'costo_unitario' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function lote(): HasOne
    {
        return $this->hasOne(Lote::class, 'movimiento_id');
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'entrada' => 'Entrada',
            'salida'  => 'Salida',
            'ajuste'  => 'Ajuste',
            default   => $this->tipo,
        };
    }

    public function tipoColor(): string
    {
        return match ($this->tipo) {
            'entrada' => 'lime',
            'salida'  => 'red',
            'ajuste'  => 'yellow',
            default   => 'zinc',
        };
    }
}
