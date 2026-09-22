<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Event;
use App\Services\Operations\EventService;

class Phase6Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::beginTransaction();
        
        DB::table('organizations')->insertOrIgnore([
            ['id' => 1, 'name' => 'Org 1']
        ]);
        if (!defined('CURRENT_ORGANIZATION_ID')) {
            define('CURRENT_ORGANIZATION_ID', 1);
        }
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_event_and_participant_logic()
    {
        $service = new EventService();
        $event = $service->createEvent(1, [
            'name' => 'Annual Seminar',
            'event_type' => 'Seminar',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'organizer_employee_id' => 1
        ]);

        $this->assertNotNull($event->id);
        $this->assertEquals('planned', $event->status);

        $participant = $service->addParticipant(1, $event->id, [
            'participant_type' => 'athlete',
            'participant_id' => 10
        ]);

        $this->assertNotNull($participant->id);
        $this->assertEquals(10, $participant->athlete_id);

        try {
            $service->addParticipant(1, $event->id, [
                'participant_type' => 'athlete',
                'participant_id' => 10
            ]);
            $this->fail('Expected exception on duplicate participant');
        } catch (\Exception $e) {
            $this->assertEquals(409, $e->getCode());
        }
    }

    public function test_school_activity_creation()
    {
        $service = new EventService();
        $activity = $service->createSchoolActivity(1, [
            'school_name' => 'City High School',
            'activity_name' => 'Sports Day',
            'activity_date' => '2026-11-01',
            'participant_count' => 150
        ]);

        $this->assertNotNull($activity->id);
        $this->assertEquals('City High School', $activity->school_name);
    }
}
