<?php

namespace Tests\Unit;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Report;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportApprovalMessageTest extends TestCase
{
    private function makeReport(string $category, string $status, string $reportType = 'molecule'): Report
    {
        return new Report([
            'report_category' => $category,
            'status' => $status,
            'report_type' => $reportType,
        ]);
    }

    #[DataProvider('firstReviewModalProvider')]
    public function test_first_review_modal_content(
        string $category,
        string $reportType,
        string $expectedDescriptionFragment,
    ): void {
        $record = $this->makeReport($category, ReportStatus::SUBMITTED->value, $reportType);
        $content = ReportResource::getApprovalModalContent($record);

        $this->assertSame('Approve for second review', $content['heading']);
        $this->assertSame('Approve for second review', $content['submit_label']);
        $this->assertStringContainsString($expectedDescriptionFragment, $content['description']);
    }

    public static function firstReviewModalProvider(): array
    {
        return [
            'submission' => [
                ReportCategory::SUBMISSION->value,
                'molecule',
                'No molecule will be created yet',
            ],
            'update' => [
                ReportCategory::UPDATE->value,
                'molecule',
                'No changes will be applied yet',
            ],
            'revoke molecule' => [
                ReportCategory::REVOKE->value,
                'molecule',
                'The molecule will not be revoked yet',
            ],
            'revoke citation' => [
                ReportCategory::REVOKE->value,
                'citation',
                'forward it to a second curator',
            ],
        ];
    }

    #[DataProvider('finalReviewModalProvider')]
    public function test_final_review_modal_content(
        string $category,
        string $reportType,
        string $expectedHeading,
        string $expectedDescriptionFragment,
        string $expectedSubmitLabel,
    ): void {
        $record = $this->makeReport($category, ReportStatus::PENDING_APPROVAL->value, $reportType);
        $content = ReportResource::getApprovalModalContent($record);

        $this->assertSame($expectedHeading, $content['heading']);
        $this->assertSame($expectedSubmitLabel, $content['submit_label']);
        $this->assertStringContainsString($expectedDescriptionFragment, $content['description']);
    }

    public static function finalReviewModalProvider(): array
    {
        return [
            'submission' => [
                ReportCategory::SUBMISSION->value,
                'molecule',
                'Approve new molecule',
                'create and submit the new molecule entry',
                'Approve and create molecule',
            ],
            'update' => [
                ReportCategory::UPDATE->value,
                'molecule',
                'Approve changes',
                'apply the selected changes to the molecule',
                'Approve and apply changes',
            ],
            'revoke molecule' => [
                ReportCategory::REVOKE->value,
                'molecule',
                'Revoke molecule',
                'deactivate the molecule and mark it as REVOKED',
                'Approve and revoke molecule',
            ],
            'revoke citation' => [
                ReportCategory::REVOKE->value,
                'citation',
                'Approve report',
                'only change the status of the report',
                'Approve report',
            ],
        ];
    }

    public function test_is_final_approval_review(): void
    {
        $submitted = $this->makeReport(ReportCategory::UPDATE->value, ReportStatus::SUBMITTED->value);
        $pendingApproval = $this->makeReport(ReportCategory::UPDATE->value, ReportStatus::PENDING_APPROVAL->value);
        $pendingRejection = $this->makeReport(ReportCategory::UPDATE->value, ReportStatus::PENDING_REJECTION->value);

        $this->assertFalse(ReportResource::isFinalApprovalReview($submitted));
        $this->assertTrue(ReportResource::isFinalApprovalReview($pendingApproval));
        $this->assertTrue(ReportResource::isFinalApprovalReview($pendingRejection));
    }

    #[DataProvider('notificationProvider')]
    public function test_approval_notification_content(
        string $category,
        string $reportType,
        string $resultingStatus,
        string $expectedTitle,
        string $expectedBodyFragment,
    ): void {
        $record = $this->makeReport($category, ReportStatus::PENDING_APPROVAL->value, $reportType);
        $content = ReportResource::getApprovalNotificationContent($record, $resultingStatus);

        $this->assertSame($expectedTitle, $content['title']);
        $this->assertStringContainsString($expectedBodyFragment, $content['body']);
    }

    public static function notificationProvider(): array
    {
        return [
            'first review' => [
                ReportCategory::UPDATE->value,
                'molecule',
                ReportStatus::PENDING_APPROVAL->value,
                'Report approved for first review',
                'awaiting a second curator',
            ],
            'final submission' => [
                ReportCategory::SUBMISSION->value,
                'molecule',
                ReportStatus::APPROVED->value,
                'New molecule approved',
                'entry has been created',
            ],
            'final update' => [
                ReportCategory::UPDATE->value,
                'molecule',
                ReportStatus::APPROVED->value,
                'Changes approved',
                'applied to the molecule',
            ],
            'final revoke molecule' => [
                ReportCategory::REVOKE->value,
                'molecule',
                ReportStatus::APPROVED->value,
                'Molecule revoked',
                'revoked and deactivated',
            ],
            'final revoke citation' => [
                ReportCategory::REVOKE->value,
                'citation',
                ReportStatus::APPROVED->value,
                'Report approved',
                'manual changes were applied',
            ],
        ];
    }
}
