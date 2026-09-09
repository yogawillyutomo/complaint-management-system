<?php

namespace App\Enums;

enum FeedbackOutcome: string
{
    case Accepted = 'accepted';
    case NeedsFollowUp = 'needs_follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Resolution Accepted',
            self::NeedsFollowUp => 'Needs Follow-up',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $outcome): string => $outcome->value,
            self::cases()
        );
    }
}
