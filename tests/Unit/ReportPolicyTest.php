<?php

namespace Tests\Unit;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use App\Policies\ReportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ReportPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ReportPolicy;
        Role::create(['name' => 'curator', 'guard_name' => 'web']);

        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS reports_status_check');
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_status');
        DB::statement(
            "ALTER TABLE reports ADD CONSTRAINT check_status CHECK (status IN ('"
            .implode("','", ReportStatus::values())
            ."'))"
        );
    }

    public function test_assigned_curator_one_can_update_submitted_report_without_update_report_permission(): void
    {
        $reporter = User::factory()->create();
        $curator = User::factory()->create();
        $curator->assignRole('curator');

        $report = $this->createReport($reporter, ReportStatus::SUBMITTED);
        $this->assignCurator($report, $curator, 1);

        $this->assertTrue($this->policy->update($curator, $report));
    }

    public function test_curator_one_cannot_update_pending_approval_report_four_eyes(): void
    {
        $reporter = User::factory()->create();
        $curatorOne = User::factory()->create();
        $curatorOne->assignRole('curator');

        $report = $this->createReport($reporter, ReportStatus::PENDING_APPROVAL);
        $this->assignCurator($report, $curatorOne, 1);

        $this->assertFalse($this->policy->update($curatorOne, $report));
    }

    public function test_assigned_curator_two_can_update_pending_approval_report(): void
    {
        $reporter = User::factory()->create();
        $curatorOne = User::factory()->create();
        $curatorTwo = User::factory()->create();
        $curatorTwo->assignRole('curator');

        $report = $this->createReport($reporter, ReportStatus::PENDING_APPROVAL);
        $this->assignCurator($report, $curatorOne, 1);
        $this->assignCurator($report, $curatorTwo, 2);

        $this->assertTrue($this->policy->update($curatorTwo, $report));
    }

    public function test_curator_cannot_update_approved_or_rejected_report(): void
    {
        $reporter = User::factory()->create();
        $curator = User::factory()->create();
        $curator->assignRole('curator');

        foreach ([ReportStatus::APPROVED, ReportStatus::REJECTED] as $status) {
            $report = $this->createReport($reporter, $status);
            $this->assignCurator($report, $curator, 1);

            $this->assertFalse(
                $this->policy->update($curator, $report),
                "Curator should not update {$status->value} reports"
            );
        }
    }

    public function test_reporter_cannot_update_submitted_report(): void
    {
        $reporter = User::factory()->create();
        $report = $this->createReport($reporter, ReportStatus::SUBMITTED);

        $this->assertFalse($this->policy->update($reporter, $report));
    }

    public function test_unassigned_curator_can_update_submitted_report_with_no_curator_one(): void
    {
        $reporter = User::factory()->create();
        $curator = User::factory()->create();
        $curator->assignRole('curator');

        $report = $this->createReport($reporter, ReportStatus::SUBMITTED);

        $this->assertTrue($this->policy->update($curator, $report));
    }

    private function createReport(User $reporter, ReportStatus $status): Report
    {
        return Report::create([
            'title' => 'Test report',
            'user_id' => $reporter->id,
            'status' => $status->value,
            'report_category' => ReportCategory::UPDATE->value,
            'suggested_changes' => ['overall_changes' => [], 'curator' => []],
        ]);
    }

    private function assignCurator(Report $report, User $curator, int $curatorNumber): void
    {
        $report->curators()->attach($curator->id, [
            'curator_number' => $curatorNumber,
            'status' => null,
            'comment' => null,
        ]);
    }
}
