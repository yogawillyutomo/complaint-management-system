<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $fillable = [
        'name',
        'level',
        'response_minutes',
        'resolution_minutes',
    ];

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
