<?php

namespace App\Models;

use App\Enums\FeedbackOutcome;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ComplaintFeedback extends Model
{
    protected $table = 'complaint_feedback';

    protected $fillable = [
        'complaint_id',
        'submitted_by',
        'outcome',
        'satisfaction_rating',
        'comment',
        'addressed_by',
        'addressed_at',
        'address_note',
    ];

    protected function casts(): array
    {
        return [
            'satisfaction_rating' => 'integer',
            'addressed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ComplaintFeedback $feedback): void {
            if (! FeedbackOutcome::tryFrom((string) $feedback->outcome)) {
                throw new InvalidArgumentException(
                    'Invalid complaint feedback outcome: ' . $feedback->outcome
                );
            }

            if (
                (int) $feedback->satisfaction_rating < 1
                || (int) $feedback->satisfaction_rating > 5
            ) {
                throw new InvalidArgumentException(
                    'Complaint feedback rating must be between 1 and 5.'
                );
            }
        });

        static::updating(function (ComplaintFeedback $feedback): void {
            $immutableFields = [
                'complaint_id',
                'submitted_by',
                'outcome',
                'satisfaction_rating',
                'comment',
            ];

            foreach ($immutableFields as $field) {
                if ($feedback->isDirty($field)) {
                    throw new InvalidArgumentException(
                        'Submitted complaint feedback cannot be rewritten.'
                    );
                }
            }
        });
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function addressedBy()
    {
        return $this->belongsTo(User::class, 'addressed_by');
    }

    public function needsFollowUp(): bool
    {
        return $this->outcome === FeedbackOutcome::NeedsFollowUp->value;
    }

    public function isAddressed(): bool
    {
        return ! is_null($this->addressed_at);
    }
}
