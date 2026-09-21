<?php

namespace Tests\Feature;

use App\Services\Attendance\AttendanceService;
use App\Services\BaseService;

class TrainingAttendanceTest
{
    /**
     * Test single subject enforcement: supplying both athlete_id and coach_id must fail
     */
    public function testMultipleSubjectsFailsSingleSubjectRule(): bool
    {
        $service = new AttendanceService();
        try {
            $service->markTrainingAttendance(1, [
                'training_session_id' => 1,
                'athlete_id' => 10,
                'coach_id' => 5, // Conflicting!
                'attendance_status' => 'present',
                'recorded_by' => 1
            ]);
            return false;
        } catch (\Throwable $e) {
            return str_contains($e->getMessage(), 'Exactly one attendance subject');
        }
    }

    /**
     * Test single subject enforcement: supplying zero subjects must fail
     */
    public function testNoSubjectProvidedFails(): bool
    {
        $service = new AttendanceService();
        try {
            $service->markTrainingAttendance(1, [
                'training_session_id' => 1,
                'attendance_status' => 'present',
                'recorded_by' => 1
            ]);
            return false;
        } catch (\Throwable $e) {
            return str_contains($e->getMessage(), 'Exactly one attendance subject');
        }
    }

    /**
     * Test valid employee/staff attendance recording
     */
    public function testValidEmployeeAttendanceSucceeds(): bool
    {
        $service = new AttendanceService();
        $pdo = BaseService::getDatabaseConnection();
        $recordId = null;

        try {
            $record = $service->markTrainingAttendance(1, [
                'training_session_id' => 1,
                'employee_id' => 1,
                'attendance_status' => 'present',
                'check_in_time' => '07:00:00',
                'check_out_time' => '09:00:00',
                'remarks' => 'Morning Badminton session',
                'recorded_by' => 1
            ]);

            $recordId = $record['id'] ?? null;
            return (!empty($record['training_session_id']) && $record['attendance_status'] === 'present');
        } finally {
            if ($recordId && $pdo) {
                $stmt = $pdo->prepare("DELETE FROM training_attendance WHERE id = :id AND organization_id = 1");
                $stmt->execute([':id' => $recordId]);
            }
        }
    }
}
