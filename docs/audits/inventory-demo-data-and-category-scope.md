# Inventory Category Scope & Demo Data Audit

## Scope Change
The category list for the Inventory system has been strictly constrained to 6 key sports: Athletics, Badminton, Basketball, Cricket, Football, and Swimming.
The global sports catalog (`config/sports.php`) remains untouched since other aspects of the system (teams, athletes, tournaments) rely on it.
To accomplish this, `InventoryCategoryService::provisionSportCategories()` was updated to selectively provision only these 6 sports. The dropdown population queries in `inventory.blade.php`, `inventory-create.blade.php`, and `inventory-edit.blade.php` were explicitly scoped to pull `WHERE name IN (...)` ensuring isolation.

## Extra Categories Handled
The previous provisioning task had seeded other global sports (like Gymnastics, Hockey, etc.) into the `inventory_categories` table.
- A scripted cleanup was performed on the database to delete empty extra categories (Dodgeball, Gymnastics, Handball, Hockey, Kabaddi, Running, Tennis, Volleyball).
- The 'Archery' category had one existing inventory item, and 'General Sports Gear' had 19 items. These categories and their items were preserved and NOT deleted, following the strict directive.
- Since they are absent from the UI dropdowns, any item being edited that references these legacy categories will compel the user to migrate the item to one of the 6 official categories.

## Demo Data Created
36 total realistic inventory items were created via an explicit CLI PHP script, representing exact sports equipment suitable for each of the 6 configured categories (6 items per category).
- **Athletics**: Starting Blocks, Relay Batons, Hurdles, Shot Put, Discus, Training Cones.
- **Badminton**: Rackets, Shuttlecocks, Nets, Grip Tape, Court Markers, Training Rackets.
- **Basketball**: Basketballs, Training Cones, Bibs, Nets, Pumps, Knee Supports.
- **Cricket**: Bats, Balls, Stumps, Batting Gloves, Batting Pads, Helmets.
- **Football**: Footballs, Training Cones, Bibs, Goal Nets, Shin Guards, Goalkeeper Gloves.
- **Swimming**: Goggles, Caps, Kickboards, Pull Buoys, Fins, Training Paddles.

## Demo Data Stock and Pricing
- Realistic stock numbers, minimum levels, and unit costs were populated.
- 6 out-of-stock items (quantity = 0) were intentionally included (e.g., Football Goalkeeper Gloves, Athletics Shot Put, etc.) across the 6 categories.
- 4 low-stock items were mapped via the minimum threshold.
- The item code convention uses `DEMO-[SPORT]-00X` making it cleanly identifiable (e.g. `DEMO-ATH-001`).

## UI & Filter Verification
- The dashboard automatically recalculates these totals effectively: Active items = 57, Low Stock items = 4, Out of Stock items = 6.
- The SQL backend natively scales combined cumulative filtering (`Category = Cricket` + `Status = Active`), dynamically returning intersecting matches without frontend Javascript hacks.

## Test Results
The local automated test suite (`php run_tests.php`) returned 54 Passed and 0 Failed tests, asserting that the new constraints produced no regressions and organization isolation remained fully intact.

## Database Schema / Data Changes
- Schema changes: NONE.
- Data changes: 36 new inventory items seeded. Empty legacy categories deleted. No global sports modifications.
