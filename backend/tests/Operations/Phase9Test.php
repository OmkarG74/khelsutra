<?php

namespace Tests\Operations;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\Operations\ExpenseRecorder;

class Phase9Test extends TestCase
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

    public function test_expense_recorder()
    {
        $recorder = new ExpenseRecorder();
        
        $expenseId = $recorder->recordExpense(1, 500.00, 'Bus Repair', [
            'vendor_id' => 10
        ]);

        $this->assertNotNull($expenseId);
        
        $expense = DB::table('expenses')->find($expenseId);
        $this->assertEquals(500.00, $expense->amount);
        $this->assertEquals(10, $expense->vendor_id);
        $this->assertEquals('pending', $expense->payment_status);
    }
}
