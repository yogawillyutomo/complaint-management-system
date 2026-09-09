<?php

namespace App\Services;

use App\Enums\FeedbackOutcome;
use App\Models\Complaint;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UatReadinessService
{
    public function evaluate(): array
    {
        $checks = collect();

        $checks->push($this->check(
            'environment.non_production',
            ! app()->environment('production'),
            'UAT must run outside production.'
        ));

        $passwordConfigured = strlen((string) config('uat.demo_password')) >= 12;

        $checks->push($this->check(
            'configuration.uat_password',
            $passwordConfigured,
            'A dedicated UAT_DEMO_PASSWORD of at least 12 characters is configured.',
            'UAT_DEMO_PASSWORD is missing or shorter than 12 characters.'
        ));

        $checks = $checks
            ->merge($this->roleChecks())
            ->merge($this->priorityChecks())
            ->merge($this->accountChecks())
            ->merge($this->scenarioChecks());

        return [
            'ready' => $checks->every(
                fn (array $check): bool => $check['status'] === 'PASS'
            ),
            'environment' => app()->environment(),
            'database_driver' => config('database.default'),
            'checks' => $checks->values()->all(),
            'summary' => [
                'pass' => $checks->where('status', 'PASS')->count(),
                'fail' => $checks->where('status', 'FAIL')->count(),
                'total' => $checks->count(),
            ],
            'secrets_included' => false,
        ];
    }

    private function roleChecks(): Collection
    {
        return collect(array_keys(config('uat.accounts')))
            ->map(function (string $role): array {
                return $this->check(
                    "role.{$role}",
                    Role::query()
                        ->where('name', $role)
                        ->where('guard_name', 'web')
                        ->exists(),
                    "Role {$role} exists.",
                    "Role {$role} is missing."
                );
            });
    }

    private function priorityChecks(): Collection
    {
        $expected = [
            'Low' => [480, 4320],
            'Medium' => [240, 2880],
            'High' => [120, 1440],
            'Critical' => [30, 480],
        ];

        return collect($expected)
            ->map(function (array $sla, string $name): array {
                $priority = Priority::query()
                    ->where('name', $name)
                    ->first();

                $matches = $priority
                    && (int) $priority->response_minutes === $sla[0]
                    && (int) $priority->resolution_minutes === $sla[1];

                return $this->check(
                    'priority.' . strtolower($name),
                    $matches,
                    "{$name} priority matches the expected UAT SLA reference.",
                    "{$name} priority is missing or its SLA reference values drifted."
                );
            })
            ->values();
    }

    private function accountChecks(): Collection
    {
        return collect(config('uat.accounts'))
            ->map(function (array $data, string $role): array {
                $user = User::query()
                    ->where('email', $data['email'])
                    ->first();

                $valid = $user
                    && $user->is_active
                    && $user->hasRole($role);

                return $this->check(
                    "account.{$role}",
                    (bool) $valid,
                    "Active UAT {$role} account is ready.",
                    "UAT {$role} account is missing, inactive, or has the wrong role."
                );
            })
            ->values();
    }

    private function scenarioChecks(): Collection
    {
        return collect(config('uat.scenarios'))
            ->map(function (array $scenario, string $key): array {
                $complaint = Complaint::query()
                    ->where('ticket_number', $scenario['ticket'])
                    ->first();

                if (! $complaint) {
                    return $this->check(
                        "scenario.{$key}",
                        false,
                        '',
                        "Scenario {$scenario['ticket']} is missing."
                    );
                }

                $valid = $complaint->status === $scenario['status'];

                if (isset($scenario['feedback_outcome'])) {
                    $valid = $valid
                        && $complaint->feedback
                        && $complaint->feedback->outcome === $scenario['feedback_outcome'];
                }

                if (($scenario['active_escalation'] ?? false) === true) {
                    $valid = $valid
                        && $complaint->activeEscalation()->exists();
                }

                return $this->check(
                    "scenario.{$key}",
                    $valid,
                    "Scenario {$scenario['ticket']} is ready.",
                    "Scenario {$scenario['ticket']} exists but does not match the UAT contract."
                );
            })
            ->values();
    }

    private function check(
        string $id,
        bool $passed,
        string $passMessage,
        ?string $failMessage = null
    ): array {
        return [
            'id' => $id,
            'status' => $passed ? 'PASS' : 'FAIL',
            'message' => $passed
                ? $passMessage
                : ($failMessage ?? $passMessage),
        ];
    }
}
