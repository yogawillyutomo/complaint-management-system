<?php

namespace Database\Seeders;

use App\Enums\EscalationLevel;
use App\Enums\EscalationReason;
use App\Enums\FeedbackOutcome;
use App\Enums\RootCauseCategory;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\ComplaintFeedback;
use App\Models\ComplaintHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UatScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'UAT scenario seeding is disabled in production.'
            );
        }

        $password = (string) config('uat.demo_password');

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'Set UAT_DEMO_PASSWORD to at least 12 characters before seeding UAT scenarios.'
            );
        }

        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            PrioritySeeder::class,
        ]);

        $users = collect(config('uat.accounts'))
            ->map(function (array $data, string $role) use ($password): User {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$role]);

            return $user;
        });

        $category = Category::firstOrCreate(
            ['name' => 'UAT Operational Process'],
            ['description' => 'Non-production UAT scenario category']
        );

        $priority = Priority::where('name', 'High')->firstOrFail();

        $employee = $users['karyawan'];
        $hrd = $users['hrd'];
        $pimpinan = $users['pimpinan'];

        $submitted = $this->complaint(
            config('uat.scenarios.submitted.ticket'),
            $employee,
            $category,
            $priority,
            'submitted'
        );

        $assigned = $this->complaint(
            config('uat.scenarios.assigned.ticket'),
            $employee,
            $category,
            $priority,
            'assigned',
            $hrd
        );

        $waitingInput = $this->complaint(
            config('uat.scenarios.waiting_input.ticket'),
            $employee,
            $category,
            $priority,
            'waiting_input',
            $hrd
        );

        $resolved = $this->complaint(
            config('uat.scenarios.resolved_feedback.ticket'),
            $employee,
            $category,
            $priority,
            'resolved',
            $hrd,
            [
                'root_cause_category' => RootCauseCategory::CrossFunctionCoordination->value,
                'root_cause' => 'Cross-functional handoff ownership was unclear during operational handling.',
                'resolution_summary' => 'The responsible PIC and handoff sequence were clarified and communicated.',
                'resolved_at' => now(),
            ]
        );

        ComplaintFeedback::firstOrCreate(
            ['complaint_id' => $resolved->id],
            [
                'submitted_by' => $employee->id,
                'outcome' => FeedbackOutcome::NeedsFollowUp->value,
                'satisfaction_rating' => 2,
                'comment' => 'The employee still needs confirmation that the revised handoff is consistently applied.',
            ]
        );

        $escalated = $this->complaint(
            config('uat.scenarios.escalated.ticket'),
            $employee,
            $category,
            $priority,
            'in_progress',
            $hrd
        );

        if (! $escalated->activeEscalation()->exists()) {
            $escalated->escalations()->create([
                'escalated_by' => $hrd->id,
                'escalated_to' => $pimpinan->id,
                'reason_code' => EscalationReason::AuthorityLimit->value,
                'escalation_level' => EscalationLevel::Leadership->value,
                'note' => 'UAT scenario: leadership decision is required before HRD can continue.',
                'status' => 'active',
            ]);
        }

        foreach ([$submitted, $assigned, $waitingInput, $resolved, $escalated] as $complaint) {
            ComplaintHistory::firstOrCreate(
                [
                    'complaint_id' => $complaint->id,
                    'type' => 'uat_seeded',
                ],
                [
                    'user_id' => $users['admin']->id,
                    'description' => 'Non-production UAT scenario fixture created',
                ]
            );
        }
    }

    private function complaint(
        string $ticket,
        User $reporter,
        Category $category,
        Priority $priority,
        string $status,
        ?User $handler = null,
        array $extra = []
    ): Complaint {
        return Complaint::updateOrCreate(
            ['ticket_number' => $ticket],
            array_merge([
                'reporter_id' => $reporter->id,
                'assigned_to' => $handler?->id,
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'title' => "UAT scenario {$ticket}",
                'description' => 'Controlled non-production complaint used for user acceptance testing.',
                'status' => $status,
            ], $extra)
        );
    }
}
