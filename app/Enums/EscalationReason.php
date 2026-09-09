<?php

namespace App\Enums;

enum EscalationReason: string
{
    case AuthorityLimit = 'authority_limit';
    case SlaRisk = 'sla_risk';
    case SlaBreach = 'sla_breach';
    case ResourceConstraint = 'resource_constraint';
    case PolicySystem = 'policy_system';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AuthorityLimit => 'Outside Current Authority',
            self::SlaRisk => 'SLA at Risk',
            self::SlaBreach => 'SLA Breach',
            self::ResourceConstraint => 'Resource Constraint',
            self::PolicySystem => 'Policy / System Constraint',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $reason): string => $reason->value,
            self::cases()
        );
    }
}
