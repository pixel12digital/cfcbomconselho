<?php

namespace App\Services;

class EnrollmentPolicy
{
    public static function canSchedule($enrollment)
    {
        if (!$enrollment) {
            return false;
        }
        
        return $enrollment['financial_status'] !== 'bloqueado';
    }

    public static function canStartLesson($enrollment)
    {
        if (!$enrollment) {
            return false;
        }
        
        return $enrollment['financial_status'] !== 'bloqueado';
    }
}
