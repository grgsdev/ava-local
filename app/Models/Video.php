<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser atribuídos em massa.
     */
    protected $fillable = [
        'project_id',
        'url',
        'title',
    ];

    /**
     * Define a relação inversa: um Vídeo pertence a um Projeto.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Define a relação: um Vídeo pode ter muitas Interações.
     */
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }
}
