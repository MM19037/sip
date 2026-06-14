<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = ['nombre', 'telefono', 'email', 'direccion', 'notas'];

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function totalPedidos(): int
    {
        return $this->pedidos()->count();
    }

    public function totalGastado(): float
    {
        return (float) $this->pedidos()->where('estado', Pedido::ENTREGADO)->sum('total');
    }
}
