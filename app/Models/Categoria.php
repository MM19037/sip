<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function productosActivos(): HasMany
    {
        return $this->hasMany(Producto::class)->where('activo', true);
    }
}
