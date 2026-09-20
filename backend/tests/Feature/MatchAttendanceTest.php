<?php

namespace Tests\Feature;

use App\Services\Attendance\AttendanceService;

class MatchAttendanceTest
{
    /**
     * Test single subject enforcement on match attendance
     */
    public function testMatchAttendanceMultipleSubjectsFails(): bool
    {
        $service = new AttendanceService();
        try {
            $service->markMatchAttendance(1, [
                'match_id' => 1,
                'athlete_id' => 20,
                'employee_id' => 1, // Multiple subjects!
                'attendance_status' => 'present'
            ]);
            return false;
        } catch (\Throwable $e) {
            return str_contains($e->getMessage(), 'Exactly one attendance subject');
        }
    }

    /**
     * Test valid match attendance recording
     */
    public function testValidMatchAttendanceSucceeds(): bool
    {
        $service = new AttendanceService();
        $record = $service->markMatchAttendance(1, [
            'match_id' => 1,
            'employee_id' => 2,
            'attendance_status' => 'present',
            'check_in_time' => '14:30:00',
            'remarks' => 'Head coach on match bench'
        ]);

        return (!empty($record['match_id']) && $record['attendance_status'] === 'present');
    }
}
