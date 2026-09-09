<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\RootCauseCategory;
use App\Models\Priority;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReleaseEvidenceService
{
    public const SCHEMA_VERSION = 1;

    public function build(?string $commitSha = null): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => now()->toIso8601String(),
            'release' => [
                'commit_sha' => $commitSha,
                'application' => config('app.name'),
                'environment' => app()->environment(),
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'laravel' => $this->packageVersion('laravel/framework'),
                'livewire' => $this->packageVersion('livewire/livewire'),
                'database_driver' => DB::getDriverName(),
                'database_version' => $this->databaseVersion(),
            ],
            'contracts' => [
                'roles' => [
                    'admin',
                    'karyawan',
                    'supervisor',
                    'hrd',
                    'pimpinan',
                ],
                'complaint_statuses' => ComplaintStatus::values(),
                'root_cause_categories' => RootCauseCategory::values(),
                'escalation_reasons' => EscalationReason::values(),
                'escalation_levels' => EscalationLevel::values(),
            ],
            'reference_data' => [
                'priorities' => Priority::query()
                    ->orderBy('level')
                    ->get([
                        'name',
                        'level',
                        'response_minutes',
                        'resolution_minutes',
                    ])
                    ->map(fn (Priority $priority): array => [
                        'name' => $priority->name,
                        'level' => (int) $priority->level,
                        'response_minutes' => (int) $priority->response_minutes,
                        'resolution_minutes' => (int) $priority->resolution_minutes,
                    ])
                    ->values()
                    ->all(),
            ],
            'database' => [
                'migrations_applied' => Schema::hasTable('migrations')
                    ? DB::table('migrations')->count()
                    : 0,
            ],
            'verification_contract' => [
                'automated_uat' => 'php artisan test tests/Feature/Uat',
                'manual_uat_status' => 'NOT_RUN',
                'manual_uat_claimed' => false,
            ],
            'security_contract' => [
                'public_registration' => false,
                'complaint_hard_delete' => false,
                'append_only_complaint_history' => true,
                'normal_seed_creates_login_accounts' => false,
                'uat_seed_production_allowed' => false,
                'secrets_included' => false,
            ],
            'notes' => [
                'SLA values are configurable operational defaults.',
                'Recurrence analytics are a management proxy, not proof of identical-case recurrence.',
                'Automated verification does not equal human UAT acceptance.',
            ],
        ];
    }

    private function packageVersion(string $package): ?string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package)
            ?? InstalledVersions::getVersion($package);
    }

    private function databaseVersion(): ?string
    {
        try {
            return match (DB::getDriverName()) {
                'mysql' => (string) (DB::selectOne('select version() as version')->version ?? null),
                'sqlite' => (string) (DB::selectOne('select sqlite_version() as version')->version ?? null),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
