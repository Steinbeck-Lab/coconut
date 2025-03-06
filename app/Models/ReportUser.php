<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ReportUser extends Pivot implements Auditable
{
    use AuditableTrait;

    protected $table = 'report_user';

    // Make sure to define which attributes you want to audit
    protected $fillable = ['report_id', 'user_id', 'curator_number', 'status', 'comment'];

    // Optional: Set auditable events
    protected $auditableEvents = [
        'created',
        'updated',
        'deleted',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    // public function transformAudit(array $data): array
    // {
    //     $data['auditable_id'] = $this->report_id;
    //     $data['auditable_type'] = Report::class;

    //     return changeAudit($data);
    // }
}
