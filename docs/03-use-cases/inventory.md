# Use Case: Inventory & Purchasing

## UC-INV-01: Inventory & Equipment Tracking
- **Primary Actor**: Inventory Manager.
- **Permission**: `inventory.manage`.
- **Main Flow**:
  1. Actor maintains item catalog and tracks physical stock in `inventory_items`.
  2. For serialized equipment (e.g. specialized gear, scoreboards, sensors), creates `equipment` records.
  3. Allocates equipment to an athlete, coach, employee, team, or venue facility in `equipment_assignments`.
  4. Manages condition (`good`, `fair`, `damaged`, `lost`) and return dates.

## UC-INV-02: Procurement & Vendor Workflow
- **Primary Actor**: Inventory Manager & HR & Finance.
- **Permission**: `purchase.manage`.
- **Workflow Lifecycle**:
  `Purchase Request` → `Approval` → `Purchase Order` → `Vendor` → `Goods Receipt (GRN)` → `Inventory Stock In` → `Vendor Invoice` → `Finance Payment`.
- **Implementation Status**: Skeleton active; barcode scanning `TODO: TO BE COMPLETED`.
