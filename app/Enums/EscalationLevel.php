<?php

namespace App\Enums;

enum EscalationLevel: string
{
    case Leadership = 'leadership';
    case HigherAuthority = 'higher_authority';

    public function label(): string
    {
        return match ($this) {
            self::Leadership => 'Pimpinan / Area Leadership',
            self::HigherAuthority => 'Higher Authority / External Unit',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $level): string => $level->value,
            self::cases()
        );
    }
}
