<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class QuestionOption extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'lesson_question_id',
        'text',
        'is_correct',
        'coordinates',
        'group_id',
        'text_color',
        'show_text',
        'background_color',
        'background_opacity',
        'text_align',
        'vertical_align',
        'text_font_size',
        'text_font_family',
        'correct_action',
        'correct_seek_to',
        'correct_target_lesson_id',
        'wrong_action',
        'wrong_seek_to',
        'wrong_target_lesson_id',
        'is_zone',
        'delay_seconds',
        'feedback_text',
        'shape',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'coordinates' => 'array',
        'show_text' => 'boolean',
        'correct_seek_to' => 'float',
        'wrong_seek_to' => 'float',
        'is_zone' => 'boolean',
    ];

    protected $appends = [
        'image_url',
        'zone_image_url',
    ];

    public function registerMediaCollections(): void
    {
        // imagem do ITEM
        $this->addMediaCollection('image')->singleFile();

        // imagem da ZONA
        $this->addMediaCollection('zone')->singleFile();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('image') ?: null;
    }

    public function getZoneImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('zone') ?: null;
    }
}
