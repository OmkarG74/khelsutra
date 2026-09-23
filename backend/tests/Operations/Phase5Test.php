<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Venue;
use App\Services\Operations\MaintenanceService;
use App\Services\Operations\HousekeepingService;
use App\Models\VenueMaintenance;
use App\Models\HousekeepingTask;

class Phase5Test extends TestCase
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
        DB::statement("SET FOREIGN_KEY_CHECKS=1;");
        parent::tearDown();
    }

    public function test_maintenance_ticket_creation()
    {
        $venue = Venue::create(['organization_id' => 1, 'name' => 'Test Venue', 'venue_code' => 'TEST-02']);

        $service = new MaintenanceService();
        $ticket = $service->createTicket(1, [
            'venue_id' => $venue->id,
            'issue_title' => 'Broken Light',
            'priority' => 'high'
        ]);

        $this->assertNotNull($ticket->id);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('reported', $ticket->status);
        $this->assertStringStartsWith('MN-', $ticket->maintenance_reference);
    }

    public function test_housekeeping_task_creation()
    {
        $venue = Venue::create(['organization_id' => 1, 'name' => 'Test Venue', 'venue_code' => 'TEST-03']);

        $service = new HousekeepingService();
        $task = $service->createTask(1, [
            'venue_id' => $venue->id,
            'task_type' => 'Pitch Cleaning',
            'priority' => 'medium'
        ]);

        $this->assertNotNull($task->id);
        $this->assertEquals('medium', $task->priority);
        $this->assertEquals('pending', $task->status);
        $this->assertStringStartsWith('HK-', $task->task_reference);
    }
}
