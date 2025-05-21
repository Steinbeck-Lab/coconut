<?php

namespace App\Enums;

enum ReportStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case COMPLETED = 'COMPLETED';
    case INPROGRESS = 'INPROGRESS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
