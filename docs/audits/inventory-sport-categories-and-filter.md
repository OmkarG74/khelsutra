# Inventory Sport Categories & Filter Audit

## Files Modified
- `backend/app/Services/Inventory/InventoryCategoryService.php`: Added `provisionSportCategories()` to idempotent-ly fetch and provision sport categories from the centralized configuration.
- `backend/config/sports.php`: Added missing official sports to the catalog to ensure comprehensive representation.
- `backend/resources/views/inventory/inventory.blade.php`: Injected a call to `provisionSportCategories()` to populate categories automatically on the list page.
- `backend/resources/views/inventory/inventory-create.blade.php`: Replaced hardcoded "General Sports Gear" dummy option with a generic `-- Select Category --` label, properly binding dynamic entries.
- `backend/resources/views/inventory/inventory-edit.blade.php`: Replaced hardcoded "General Sports Gear" dummy option with `-- Select Category --` and added frontend validation.

## Automated Provisioning
Sport-specific categories are automatically provisioned using the `provisionSportCategories(int $orgId)` method in `InventoryCategoryService`. This method reads the central list of sports from KhelSutra's `SportService` (derived from `config/sports.php`). By querying the database first to collect existing category names (`SELECT name FROM inventory_categories ...`), it ensures new categories are only inserted if they don't currently exist, guaranteeing an idempotent and unique provisioning process isolated perfectly by `organization_id`.

## Cumulative Filtering
The inventory list page filters are structured in the form using standard HTTP `GET` parameter arrays: `search`, `category_id`, and `status`. The `InventoryService::listItems` accepts these nullable arguments and applies independent, cumulative SQL conditionals (with proper bound parameters) via an `implode(' AND ', $conditions)` clause. As a result, applying `Category = Cricket` along with `Search = 'pro'` inherently constructs a query validating both constraints simultaneously, preventing one filter from overwriting another.

## Add/Edit Form Assignment
Both `inventory-create.blade.php` and `inventory-edit.blade.php` perform active database queries against `inventory_categories` for the active organization. Now that categories are provisioned directly from the official sports list, these forms automatically retrieve 'Cricket', 'Swimming', and all other sports without requiring schema modifications. The select elements have also been updated to remove legacy fallback text, requiring an explicit selection before submission.
