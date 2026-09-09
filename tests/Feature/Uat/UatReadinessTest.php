<?php

namespace Tests\Feature\Uat;

use App\Models\Complaint;
use App\Models\User;
use App\Services\UatReadinessService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UatScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_fails_before_controlled_uat_seed_without_exposing_password(): void
    {
        config()->set(
            'uat.demo_password',
            'UAT-Readiness-Secret-2026'
        );

        $this->seed(DatabaseSeeder::class);

        $result = app(UatReadinessService::class)->evaluate();

        $this->assertFalse($result['ready']);
        $this->assertGreaterThan(0, $result['summary']['fail']);
        $this->assertFalse($result['secrets_included']);

        $json = json_encode(
            $result,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringNotContainsString(
            'UAT-Readiness-Secret-2026',
            $json
        );

        $this->assertContains(
            'FAIL',
            collect($result['checks'])
                ->where('id', 'account.hrd')
                ->pluck('status')
                ->all()
        );

        $this->assertContains(
            'FAIL',
            collect($result['checks'])
                ->where('id', 'scenario.submitted')
                ->pluck('status')
                ->all()
        );
    }

    public function test_readiness_passes_after_controlled_uat_seed(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(UatScenarioSeeder::class);

        $result = app(UatReadinessService::class)->evaluate();

        $this->assertTrue($result['ready']);
        $this->assertSame(0, $result['summary']['fail']);
        $this->assertSame(
            $result['summary']['total'],
            $result['summary']['pass']
        );

        foreach ($result['checks'] as $check) {
            $this->assertSame(
                'PASS',
                $check['status'],
                "Readiness check {$check['id']} should pass."
            );
        }
    }

    public function test_readiness_detects_uat_account_role_or_activation_drift(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(UatScenarioSeeder::class);

        User::where(
            'email',
            config('uat.accounts.hrd.email')
        )->firstOrFail()->update([
            'is_active' => false,
        ]);

        $result = app(UatReadinessService::class)->evaluate();

        $this->assertFalse($result['ready']);

        $check = collect($result['checks'])
            ->firstWhere('id', 'account.hrd');

        $this->assertSame('FAIL', $check['status']);
    }

    public function test_readiness_detects_scenario_state_drift(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(UatScenarioSeeder::class);

        Complaint::where(
            'ticket_number',
            config('uat.scenarios.submitted.ticket')
        )->firstOrFail()->update([
            'status' => 'validated',
        ]);

        $result = app(UatReadinessService::class)->evaluate();

        $this->assertFalse($result['ready']);

        $check = collect($result['checks'])
            ->firstWhere('id', 'scenario.submitted');

        $this->assertSame('FAIL', $check['status']);
    }

    public function test_readiness_command_returns_failure_before_uat_seed(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(DatabaseSeeder::class);

        $this->artisan('uat:readiness')
            ->expectsOutputToContain('UAT readiness:')
            ->assertFailed();
    }

    public function test_readiness_command_returns_success_after_uat_seed(): void
    {
        config()->set(
            'uat.demo_password',
            'Local-UAT-Only-Password-2026'
        );

        $this->seed(UatScenarioSeeder::class);

        $this->artisan('uat:readiness')
            ->expectsOutputToContain(
                'UAT environment is ready for manual scenario execution.'
            )
            ->assertSuccessful();
    }
}
