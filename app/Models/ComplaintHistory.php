<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ComplaintHistory extends Model
{
    protected $fillable = [
        'complaint_id',
        'user_id',
        'type',
        'description',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Complaint audit history is append-only and cannot be updated.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Complaint audit history is append-only and cannot be deleted.'
            );
        });
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
