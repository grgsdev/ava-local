<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CourseLesson extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'course_module_id',
        'title',
        'slug',
        'video_provider',
        'video_url',
        'duration_minutes',
        'description',
        'is_free_preview',
        'order_column'
    ];

    protected $casts = [
        'is_free_preview' => 'boolean',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    // --- Media ---
    public function registerMediaCollections(): void
    {
        // Materiais de apoio (PDFs, Planilhas)
        $this->addMediaCollection('attachments');

        // Se formos fazer upload de vídeo local (não youtube)
        $this->addMediaCollection('video_file')->singleFile();
    }

    public function questions()
    {
        // Ordenar pelo tempo para facilitar o player de vídeo saber qual é a próxima
        return $this->hasMany(LessonQuestion::class)->orderBy('timestamp_seconds');
    }
}
