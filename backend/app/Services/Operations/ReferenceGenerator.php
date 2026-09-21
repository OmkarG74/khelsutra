<?php

namespace App\Services\Operations;

use Illuminate\Database\Capsule\Manager as DB;

class ReferenceGenerator
{
    /**
     * Generate a unique reference number.
     * @param string $prefix E.g., 'BK' for Booking
     * @param string $table The table name to check against
     * @param string $column The column name
     * @param int $orgId The organization ID
     * @return string E.g., BK-20260920-0001
     */
    public static function generate(string $prefix, string $table, string $column, int $orgId): string
    {
        $date = date('Ymd');
        $base = "{$prefix}-{$date}-";
        
        $lastRow = DB::table($table)
            ->where('organization_id', $orgId)
            ->where($column, 'like', "{$base}%")
            ->orderBy($column, 'desc')
            ->first();

        if (!$lastRow) {
            return $base . '0001';
        }

        $lastRef = $lastRow->$column;
        $sequence = (int) substr($lastRef, -4);
        $nextSequence = str_pad($sequence + 1, 4, '0', STR_PAD_LEFT);
        
        return $base . $nextSequence;
    }
}
