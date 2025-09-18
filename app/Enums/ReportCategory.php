<?php

namespace App\Enums;

enum ReportCategory: string
{
    case SUBMISSION = 'SUBMISSION';
    case REVOKE = 'REVOKE';
    case UPDATE = 'UPDATE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
