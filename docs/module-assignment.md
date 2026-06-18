# Team Module Assignment

## Member 1: Account and Resource Management

**Tables:** roles, users, user_roles, departments, resource_categories, resources

**Functions:**
- User CRUD
- Department management
- Resource Category CRUD
- Resource CRUD
- Prevent duplicate email, username, student code, resource code
- Prevent deleting referenced resources or categories

## Member 2: Booking Engine and Policy Management

**Tables:** time_slots, booking_policies, bookings, notifications, maintenance_schedules

**Functions:**
- Time Slot CRUD
- Policy CRUD
- Create booking with conflict detection
- Peak-hour limit enforcement
- Resource status and maintenance checking
- Notification creation

## Member 3: Approval, Cancellation, Reporting and Audit

**Tables:** approvals, cancellations, usage_reports, audit_logs, equipment, resource_equipment

**Functions:**
- Approval queue and approve/reject workflow
- Cancellation management
- Usage reports and charts
- Audit logs
- Equipment assignment to resources
