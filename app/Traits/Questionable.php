<?php

namespace App\Traits;

use App\Models\Question;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Questionable
{
    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable');
    }
}
