<?php

namespace Tests\Unit;

use App\Support\ReleaseFreezePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReleaseFreezePolicyTest extends TestCase
{
    private ReleaseFreezePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReleaseFreezePolicy();
    }

    public function test_inactive_freeze_allows_changes_without_rc_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(active: false),
            [
                ['status' => 'M', 'path' => 'app/Models/Complaint.php'],
            ],
            []
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('INACTIVE', $result['code']);
    }

    public function test_active_freeze_allows_documentation_only_change_without_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                ['status' => 'M', 'path' => 'README.md'],
                ['status' => 'A', 'path' => 'docs/uat/NOTE.md'],
            ],
            []
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('DOCS_ONLY', $result['code']);
    }

    public function test_active_freeze_blocks_application_change_without_approved_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                ['status' => 'M', 'path' => 'app/Models/Complaint.php'],
            ],
            []
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'RC_CHANGE_REASON_REQUIRED',
            $result['code']
        );
    }

    #[DataProvider('approvedLabels')]
    public function test_active_freeze_allows_non_documentation_change_with_approved_reason(
        string $label
    ): void {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                ['status' => 'M', 'path' => 'app/Services/ActionCenterService.php'],
            ],
            [$label]
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('RC_CHANGE_APPROVED', $result['code']);
        $this->assertTrue($result['replacement_candidate_required']);
        $this->assertContains(
            strtolower($label),
            $result['matching_labels']
        );
    }

    public static function approvedLabels(): array
    {
        return [
            'defect fix' => ['rc-defect-fix'],
            'security fix' => ['rc-security-fix'],
            'test correction' => ['rc-test-fix'],
            'candidate replacement' => ['rc-candidate-replace'],
        ];
    }

    #[DataProvider('approvedTitlePrefixes')]
    public function test_active_freeze_allows_non_documentation_change_with_approved_title_prefix(
        string $title
    ): void {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                ['status' => 'M', 'path' => 'app/Models/Complaint.php'],
            ],
            [],
            $title
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('RC_CHANGE_APPROVED', $result['code']);
        $this->assertTrue($result['replacement_candidate_required']);
        $this->assertNotEmpty($result['matching_title_prefixes']);
    }

    public static function approvedTitlePrefixes(): array
    {
        return [
            'defect fix' => ['[rc-defect] Fix UAT defect'],
            'security fix' => ['[rc-security] Patch dependency issue'],
            'test correction' => ['[rc-test] Correct regression fixture'],
            'candidate replacement' => ['[rc-replace] Replace release candidate'],
        ];
    }

    public function test_historical_migration_modification_is_blocked_even_with_approved_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'M',
                    'path' => 'database/migrations/2026_09_09_083500_harden_complaint_data_integrity.php',
                ],
            ],
            ['rc-defect-fix']
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'HISTORICAL_MIGRATION_REWRITE',
            $result['code']
        );
    }

    public function test_historical_migration_delete_is_blocked_even_with_approved_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'D',
                    'path' => 'database/migrations/2026_09_09_111500_add_complaint_feedback.php',
                ],
            ],
            ['rc-security-fix']
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'HISTORICAL_MIGRATION_REWRITE',
            $result['code']
        );
    }

    public function test_historical_migration_rename_is_blocked_even_when_destination_leaves_migration_folder(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'R',
                    'old_path' => 'database/migrations/2026_09_09_101500_add_sla_and_escalations.php',
                    'path' => 'archive/old-sla-migration.php',
                ],
            ],
            ['rc-candidate-replace']
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'HISTORICAL_MIGRATION_REWRITE',
            $result['code']
        );
    }

    public function test_new_forward_migration_is_allowed_with_approved_rc_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'A',
                    'path' => 'database/migrations/2026_09_10_010000_fix_confirmed_uat_defect.php',
                ],
            ],
            ['rc-defect-fix']
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('RC_CHANGE_APPROVED', $result['code']);
    }

    public function test_freeze_config_cannot_be_changed_without_admin_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'M',
                    'path' => '.github/rc-freeze.json',
                ],
            ],
            ['rc-defect-fix']
        );

        $this->assertFalse($result['allowed']);
        $this->assertSame(
            'FREEZE_CONFIG_PROTECTED',
            $result['code']
        );
    }

    public function test_freeze_config_can_be_changed_with_freeze_admin_title_prefix(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'M',
                    'path' => '.github/rc-freeze.json',
                ],
            ],
            [],
            '[rc-admin] Activate release-candidate freeze'
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('RC_CHANGE_APPROVED', $result['code']);
    }

    public function test_freeze_config_can_be_changed_with_freeze_admin_label(): void
    {
        $result = $this->policy->evaluate(
            $this->config(),
            [
                [
                    'status' => 'M',
                    'path' => '.github/rc-freeze.json',
                ],
            ],
            ['rc-freeze-admin']
        );

        $this->assertTrue($result['allowed']);
        $this->assertSame('RC_CHANGE_APPROVED', $result['code']);
    }

    private function config(bool $active = true): array
    {
        return [
            'schema_version' => 1,
            'active' => $active,
            'allowed_change_labels' => [
                'rc-defect-fix',
                'rc-security-fix',
                'rc-test-fix',
                'rc-candidate-replace',
                'rc-freeze-admin',
            ],
            'freeze_admin_label' => 'rc-freeze-admin',
            'allowed_title_prefixes' => [
                '[rc-defect]',
                '[rc-security]',
                '[rc-test]',
                '[rc-replace]',
                '[rc-admin]',
            ],
            'freeze_admin_title_prefix' => '[rc-admin]',
            'docs_only_prefixes' => [
                'docs/',
            ],
            'docs_only_files' => [
                'README.md',
            ],
            'freeze_config_path' => '.github/rc-freeze.json',
        ];
    }
}
