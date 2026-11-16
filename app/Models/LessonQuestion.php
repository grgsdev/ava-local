<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LessonQuestion extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'course_lesson_id',
        'title',
        'show_title',
        'title_color',         // <--- Novo
        'background_color',    // <--- Novo
        'background_opacity',  // <--- Novo
        'modal_coordinates',
        'modal_background_color',
        'modal_background_opacity',
        'question_coordinates', // <--- Novo
        'title_text_align',
        'title_vertical_align',
        'title_font_size',
        'title_font_family',
        'timestamp_seconds',
        'type',
        'correct_action',
        'correct_seek_to',
        'correct_target_lesson_id',
        'correct_feedback',
        'wrong_action',
        'wrong_seek_to',
        'wrong_target_lesson_id',
        'wrong_feedback',
        'loop_start_seconds', // 👈 ADICIONE AQUI
        'loop_end_seconds',
        'loop_seconds',
        'correct_feedback_config',
        'wrong_feedback_config',
        'confirmation_config',
        'global_background_color',
        'global_background_opacity',
        'global_background_coordinates',
        'global_background_text',
        'global_background_text_color',
        'global_background_text_font_size',
        'global_background_text_font_family',
        'global_background_text_align',
        'global_background_vertical_align',
    ];
    protected $casts = [
        'show_title' => 'boolean',
        'timestamp_seconds' => 'float',
        'loop_seconds' => 'float',
        'correct_seek_to' => 'float',
        'wrong_seek_to' => 'float',
        'modal_coordinates' => 'array',    // <--- Mágico: Converte JSON <-> Array
        'question_coordinates' => 'array',
        'correct_feedback_config' => 'array',
        'wrong_feedback_config'   => 'array',
        'confirmation_config' => 'array',
        'global_background_coordinates' => 'array',
        'loop_start_seconds' => 'float',
        'loop_end_seconds' => 'float',
    ];
    // Helper para converter HH:MM:SS em segundos (útil para o form admin)
    // Faremos isso no frontend ou via mutator se necessário.
    protected $appends = [
        'background_image',
        'title_image',
        'modal_image',
        'correct_feedback_image',
        'wrong_feedback_image',
        'confirmation_image',

    ];
    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('background')->singleFile();
        $this->addMediaCollection('title')->singleFile();
        $this->addMediaCollection('modal')->singleFile();
        $this->addMediaCollection('feedback_correct')->singleFile();
        $this->addMediaCollection('feedback_wrong')->singleFile();
        $this->addMediaCollection('confirmation')->singleFile();
    }

    public function getBackgroundImageAttribute()
    {
        return $this->getFirstMediaUrl('background') ?: null;
    }

    public function getTitleImageAttribute()
    {
        return $this->getFirstMediaUrl('title') ?: null;
    }

    public function getModalImageAttribute()
    {
        return $this->getFirstMediaUrl('modal') ?: null;
    }

    public function getCorrectFeedbackImageAttribute()
    {
        return $this->getFirstMediaUrl('feedback_correct') ?: null;
    }

    public function getWrongFeedbackImageAttribute()
    {
        return $this->getFirstMediaUrl('feedback_wrong') ?: null;
    }

    public function getConfirmationImageAttribute()
    {
        return $this->getFirstMediaUrl('confirmation') ?: null;
    }
}
