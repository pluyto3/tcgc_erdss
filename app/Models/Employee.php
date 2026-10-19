<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $guarded = ['id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
