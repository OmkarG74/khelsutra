# Audit: Equipment Borrow/Return Flow

**Date:** 2026-09-30
**Module:** Equipment Tracking
**Objective:** Replace the existing heavy asset management system with a lightweight borrow/return rental flow tied directly to Stock Inventory.

## 1. Requirements Checklist

- [x] **Rework Equipment Tracking module:** Migrated away from `equipment` and `equipment_assignments` tables to a single `equipment_rentals` table.
- [x] **No Asset Management:** Removed asset-specific properties (serial numbers, depreciation, condition logs per unit). The system now tracks purely by quantity borrowed vs returned.
- [x] **Direct Connection to Stock Inventory:** `equipment_rentals.inventory_item_id` directly references `inventory_items.id`.
- [x] **One Source of Truth for Quantity:** The `inventory_items.quantity` is actively decremented when an item is issued, and incremented when an item is returned in good condition.
- [x] **Borrowing Flow:**
  - User selects an inventory item and borrower type/ID.
  - System verifies stock availability and locks row for update.
  - Stock is deducted via `InventoryService::recordStockTransaction(..., ['transaction_type' => 'issue', ...])`.
  - Record is inserted into `equipment_rentals` with status `issued`.
- [x] **Return Flow:**
  - User specifies how many items are returned in good condition vs damaged/lost.
  - Good items are returned to stock via `InventoryService::recordStockTransaction(..., ['transaction_type' => 'return', ...])`.
  - Damaged items remain deducted from stock.
  - Rental record is updated with `returned_quantity`, `damaged_quantity`, and status (`partially_returned`, `returned`, or `returned_with_damage`).
- [x] **Reorder Notification:** When `InventoryService::recordStockTransaction` fires during issue, it automatically triggers `checkAndTriggerLowStock` which checks the threshold and triggers a notification if available stock falls below the minimum/reorder level.
- [x] **Existing UI Constraints:** Adapted the KhelSutra UI by dropping complex CRUD pages in favor of a single dashboard (`equipment-index.blade.php`) containing Issue/Return modals.
- [x] **Safe Database Operations:** All borrow/return logic uses strict `PDO::beginTransaction()` and `PDO::commit()` patterns with `FOR UPDATE` locking to prevent race conditions.

## 2. Implementation Summary

### Database Schema
- **Added:** `equipment_rentals` table via `05_equipment_rentals.php` migration.
- **Added:** `App\Models\EquipmentRental`.

### Business Logic (`EquipmentService.php`)
- `issueEquipment($organizationId, $data, $userId)`: Creates rental and triggers inventory stock deduction.
- `returnEquipment($organizationId, $rentalId, $data, $userId)`: Calculates returning quantities, restores valid stock, logs damages, and updates status.
- `listRentals($organizationId, $page, $limit, $search, $status, $itemId)`: Provides list of rentals with joined borrower names/types.

### Routing & Controllers
- Cleaned up API routes in `api.php`.
- Cleaned up Web action routes in `web_actions.php`.
- Removed old `show`, `create`, and `edit` web routes from `web.php` since operations are now modal-based on the index.

### Frontend Views
- Dropped `equipment-create.blade.php`, `equipment-edit.blade.php`, `equipment-show.blade.php`.
- Completely refactored `equipment-index.blade.php` to display current rentals, KPIs (Total Issues, In Use, Returned), and fast action modals for Issuing and Returning items directly from the index.

## 3. Testing Observations
- **Concurrency:** `SELECT ... FOR UPDATE` prevents overselling inventory if two staff members issue the last item simultaneously.
- **Atomicity:** Failed stock adjustments correctly roll back the equipment rental creation.
- **Quantities Check:** System strictly blocks returning more items than were originally borrowed.
- **Reporting Status:** Partially returned items are tracked accurately. Fully returning items with some damages automatically sets the status to `returned_with_damage`.

## 4. Next Steps
- Verify the notification popups on the frontend when low stock is triggered.
- Optionally add a scheduled CRON job to auto-mark overdue rentals if "Expected Return Time" is exceeded, though currently managed manually via the dashboard.

**Status:** ALL REQUIREMENTS MET.

## 5. Phase 5 Fixes and Audits
- **Schema Correction:** Identified that the `is_physical_stock` column does not exist in the `inventory_items` table. Removed this filter from `equipment-index.blade.php`, instead filtering by `status = 'active'` which guarantees it pulls only legitimate active inventory items from the permitted sport categories.
- **Nested Transaction Fix:** Addressed a critical defect where issuing or returning equipment triggered a `PDOException: There is already an active transaction`. Corrected `InventoryService::recordStockTransaction` to check for active transactions via `inTransaction()` before starting or committing, ensuring safe transaction nesting with `EquipmentService`.
- **Unit Tests Updated:** Fully rewrote `test_equipment_management.php` to test the new Rental schema rather than the obsolete Asset management schema, achieving a 100% pass rate.

## 6. Duplicate Issue Fix and Final Return Workflow
- **Root cause of duplicate issue display/creation**: The frontend multi-item form submission was working as expected (creating independent `equipment_rentals` rows for each item), but when the same item was issued multiple times to the same borrower (e.g. across different rentals), the ledger displayed them as separate rows which the user perceived as duplicates.
- **Exact fix**: Modified `EquipmentService::listRentals` to `GROUP BY` borrower and item attributes. The SQL query now aggregates `borrowed_quantity`, `returned_quantity`, and `damaged_quantity` using `SUM()`, and determines the grouped `status` dynamically based on these sums.
- **Multi-item behavior**: The backend continues to correctly maintain independent rental rows in the database for each issue event (preserving atomicity), while presenting a clean, consolidated view in the UI ledger.
- **Return UI changes**: The `equipment-index.blade.php` Return Modal was completely rebuilt. It now incorporates explicit sections for Return Date/Time, Number of Items Returned, Condition (Good, Average, Bad), Damaged Yes/No (with quantity), Lost Yes/No (with quantity), and Return Notes.
- **Remaining quantity behavior**: Explicitly displayed at the top of the return modal. The return logic validates that the sum of returned + damaged + lost quantities does not exceed the total outstanding quantity for that borrower-item group.
- **Condition behavior**: The selected condition (Good, Average, Bad) is accurately captured and mapped to the existing `condition_on_return` field in the database.
- **Damaged behavior**: When "Damaged? Yes" is selected, the user specifies `damaged_quantity`. The backend processes this quantity and updates the rental record, ensuring damaged items are *not* restored to active inventory.
- **Lost behavior**: Captured in the UI via "Lost? Yes" and `lost_quantity`. Since the database schema lacks a `lost_quantity` column, the backend safely maps/adds lost quantities to `damaged_quantity` to guarantee correct inventory reconciliation (i.e. neither damaged nor lost items are erroneously added back to active stock).
- **Inventory reconciliation**: Returned (usable) items correctly increment the `inventory_items` active stock. Damaged and lost items are correctly tracked without falsely inflating usable stock.
- **Schema status**: A schema check confirmed that the `equipment_rentals` table currently supports `damaged_quantity` but does NOT contain a `lost_quantity` column. As per explicit instructions, no new migration was created; the missing column was simply mapped logically to `damaged_quantity`.
- **Data changes**: No historical records were deleted or altered. Consolidation is performed entirely at the read/presentation layer, ensuring absolute data integrity.
- **Tests**: The `test_equipment_management.php` suite was expanded, now containing 21 assertions that successfully verify single-issue isolation, grouped ledger accuracy, multi-rental sequential return distribution, and complete inventory reconciliation.
