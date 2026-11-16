<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'authorized_embed_origin'];

    //INCLUDES

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'status');
    }

    /**
     * Define a relação de que uma Empresa (Company) tem muitos Projetos (Projects).
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
