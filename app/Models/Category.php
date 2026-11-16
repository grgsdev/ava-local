<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use NodeTrait; // A mágica da árvore
    use LogsActivity; // Auditoria

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'parent_id',
    ];

    // Configuração do Log de Atividades
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'parent_id']);
        // ->logName('category');
    }

    // Scopes para filtrar facilmente
    public function scopeArticles($query)
    {
        return $query->where('type', 'article');
    }

    public function scopeProducts($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeCourses($query)
    {
        return $query->where('type', 'course');
    }

    // Acessor para mostrar o caminho completo (ex: Eletrônicos > TV > 4K)
    // Útil para dropdowns
    public function getPathAttribute()
    {
        // Verifica se a relação 'ancestors' foi carregada para evitar N+1,
        // caso contrário usa a query padrão do NestedSet
        return $this->ancestors->pluck('name')->push($this->name)->implode(' > ');
    }
}
