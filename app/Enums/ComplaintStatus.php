<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Submitted = 'submitted';
    case Validated = 'validated';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingInput = 'waiting_input';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
