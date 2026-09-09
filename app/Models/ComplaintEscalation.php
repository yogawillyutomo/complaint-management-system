<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintEscalation extends Model
{
    protected $fillable = [
        'complaint_id',
        'escalated_by',
        'escalated_to',
        'reason_code',
        'escalation_level',
        'note',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function escalator()
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
