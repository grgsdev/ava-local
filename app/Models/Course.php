<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'company_id',
        'source_course_id',
        'user_id',
        'title',
        'slug',
        'headline',
        'description',
        'price',
        'is_published',
        'level',
        'duration_minutes'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_publication_snapshot' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected $appends = ['thumbnail_url'];

    // --- Relações ---
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }
    public function sourceCourse(): BelongsTo { return $this->belongsTo(self::class, 'source_course_id'); }
    public function publicationSnapshots(): HasMany { return $this->hasMany(self::class, 'source_course_id'); }
    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('order_column');
    }

    // Relação "HasManyThrough" útil para pegar todas as aulas direto do curso
    public function lessons()
    {
        return $this->hasManyThrough(CourseLesson::class, CourseModule::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    // --- Media ---
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->getFirstMediaUrl('thumbnail');
    }
}
