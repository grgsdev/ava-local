<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CheckIn extends Model
{
    const UPDATED_AT = null; // A nossa tabela só tem created_at
    protected $fillable = ['user_id', 'created_at'];
    public function checkable(): MorphTo
    {
        return $this->morphTo();
    }
}
