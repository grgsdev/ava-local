<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media; // <-- IMPORTE O MODELO MEDIA

class Interaction extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'video_id',
        'type',
        'timestamp',
        'data',
    ];

    /**
     * Converte atributos de/para tipos de dados nativos.
     */
    protected $casts = [
        'data' => 'array', // Trata a coluna 'data' como um array JSON
    ];

    /**
     * Define a relação inversa: uma Interação pertence a um Vídeo.
     */
    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Define as conversões de mídia para este modelo.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(200)  // Largura do thumbnail
            ->height(200) // Altura do thumbnail
            ->sharpen(10); // Aplica um pequeno filtro de nitidez
    }
}
