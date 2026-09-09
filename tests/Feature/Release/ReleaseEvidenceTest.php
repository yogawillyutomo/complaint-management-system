<?php

namespace Tests\Feature\Release;

use App\Enums\ComplaintStatus;
use App\Services\ReleaseEvidenceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReleaseEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_evidence_contains_source_of_truth_contract_without_manual_uat_claim(): void
    {
        $this->seed(DatabaseSeeder::class);

        $manifest = app(ReleaseEvidenceService::class)
            ->build('abcdef1234567890');

        $this->assertSame(
            ReleaseEvidenceService::SCHEMA_VERSION,
            $manifest['schema_version']
        );

        $this->assertSame(
            'abcdef1234567890',
            $manifest['release']['commit_sha']
        );

        $this->assertSame(
            ComplaintStatus::values(),
            $manifest['contracts']['complaint_statuses']
        );

        $this->assertSame(
            ['admin', 'karyawan', 'supervisor', 'hrd', 'pimpinan'],
            $manifest['contracts']['roles']
        );

        $this->assertSame(
            ['Low', 'Medium', 'High', 'Critical'],
            array_column(
                $manifest['reference_data']['priorities'],
                'name'
            )
        );

        $this->assertSame(
            'NOT_RUN',
            $manifest['verification_contract']['manual_uat_status']
        );

        $this->assertFalse(
            $manifest['verification_contract']['manual_uat_claimed']
        );

        $this->assertFalse(
            $manifest['security_contract']['secrets_included']
        );
    }

    public function test_release_evidence_contains_no_secret_configuration_keys_or_values(): void
    {
        config()->set('app.key', 'base64:SUPER-SECRET-APP-KEY');
        config()->set('database.connections.sqlite.password', 'DB-SECRET');
        config()->set('services.mailgun.secret', 'MAIL-SECRET');

        $manifest = app(ReleaseEvidenceService::class)->build();

        $json = strtolower(json_encode($manifest, JSON_THROW_ON_ERROR));

        foreach ([
            'super-secret-app-key',
            'db-secret',
            'mail-secret',
            'app_key',
            'password',
            'token',
            'secret',
            'credential',
        ] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $json,
                "Release evidence must not contain {$forbidden}."
            );
        }
    }

    public function test_release_evidence_command_writes_json_for_exact_commit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $relative = 'storage/app/testing/release-evidence.json';
        $path = base_path($relative);

        File::delete($path);

        $this->artisan('release:evidence', [
            '--commit' => 'abcdef1234567890',
            '--output' => $relative,
        ])->assertSuccessful();

        $this->assertFileExists($path);

        $manifest = json_decode(
            File::get($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            'abcdef1234567890',
            $manifest['release']['commit_sha']
        );

        $this->assertSame(
            'NOT_RUN',
            $manifest['verification_contract']['manual_uat_status']
        );

        File::delete($path);
    }

    public function test_release_evidence_command_rejects_invalid_commit_identifier(): void
    {
        $this->artisan('release:evidence', [
            '--commit' => 'not-a-git-sha',
            '--stdout' => true,
        ])->assertFailed();
    }
}
