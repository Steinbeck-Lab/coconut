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
        'created', 'updated', 'deleted',
    ];

    public function transformAudit(array $data): array
    {
        $data['auditable_id'] = $data['new_values']['report_id'] ?? $data['old_values']['report_id'];
        $data['auditable_type'] = Report::class;

        return changeAudit($data);
    }
}
