<?php

namespace App\Enums;

enum RootCauseCategory: string
{
    case WorkloadTarget = 'workload_target';
    case CrossFunctionCoordination = 'cross_function_coordination';
    case ResourceLimitation = 'resource_limitation';
    case BusinessTechnologyChange = 'business_technology_change';
    case CommunicationPerception = 'communication_perception';
    case AdministrativeProcess = 'administrative_process';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WorkloadTarget => 'Workload & Target',
            self::CrossFunctionCoordination => 'Cross-function Coordination',
            self::ResourceLimitation => 'Resource Limitation',
            self::BusinessTechnologyChange => 'Business / Technology Change & Adaptation',
            self::CommunicationPerception => 'Communication & Perception',
            self::AdministrativeProcess => 'Administrative / Approval Process',
            self::Other => 'Other',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $category): string => $category->value,
            self::cases()
        );
    }
}
