<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SuperAdmin';
    case CountryAdmin = 'CountryAdmin';
    case DataAnalyst = 'DataAnalyst';
    case LegalReviewer = 'LegalReviewer';
    case AdvisoryReviewer = 'AdvisoryReviewer';
    case SurveyManager = 'SurveyManager';
    case PublicUser = 'PublicUser';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
