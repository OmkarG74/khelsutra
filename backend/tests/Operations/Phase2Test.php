<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Venue;

class Phase2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_migration_idempotency()
    {
        $this->expectNotToPerformAssertions();
        // Run migration logic again to prove idempotency
        $migration = require __DIR__ . '/../../database/migrations/01_operations_delta.php';
        $migration->up();
    }

    public function test_transaction_rollback_works_on_innodb()
    {
        $initialCount = Venue::withoutGlobalScopes()->count();
        
        $venue = new Venue();
        $venue->organization_id = 1;
        $venue->name = "Test Rollback Venue";
        $venue->venue_code = "TEST-01";
        $venue->save();

        $this->assertEquals($initialCount + 1, Venue::withoutGlobalScopes()->count());

        DB::rollBack();

        $this->assertEquals($initialCount, Venue::withoutGlobalScopes()->count());
        
        DB::beginTransaction(); // For teardown
    }

    public function test_tenant_scope_is_enforced()
    {
        $org1 = 1;
        $org2 = 2; // We'll mock CURRENT_ORGANIZATION_ID

        // Insert venue for org 1
        $v1 = new Venue();
        $v1->organization_id = $org1;
        $v1->name = "Org 1 Venue";
        $v1->venue_code = "V-O1";
        $v1->save();

        // Insert venue for org 2
        $v2 = new Venue();
        $v2->organization_id = $org2;
        $v2->name = "Org 2 Venue";
        $v2->venue_code = "V-O2";
        $v2->save();

        // Simulate Org 1 context
        if (!defined('CURRENT_ORGANIZATION_ID')) {
            define('CURRENT_ORGANIZATION_ID', 1);
        }

        $venues = Venue::all();
        
        foreach ($venues as $v) {
            $this->assertEquals(1, $v->organization_id);
        }
    }
}
