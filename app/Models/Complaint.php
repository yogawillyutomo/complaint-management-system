<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Complaint extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Complaint $complaint): void {
            if (! ComplaintStatus::tryFrom((string) $complaint->status)) {
                throw new InvalidArgumentException(
                    'Invalid complaint status: ' . $complaint->status
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'follow_up_required' => 'boolean',
            'follow_up_due_at' => 'datetime',
            'follow_up_completed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'ticket_number',
        'reporter_id',
        'assigned_to',
        'category_id',
        'priority_id',
        'title',
        'description',
        'root_cause',
        'resolution_summary',
        'resolution_evidence',
        'follow_up_required',
        'follow_up_due_at',
        'follow_up_completed_at',
        'follow_up_note',
        'status',
        'resolved_at',
        'closed_at',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function histories()
    {
        return $this->hasMany(ComplaintHistory::class)
            ->latest();
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)
            ->latest();
    }
}
