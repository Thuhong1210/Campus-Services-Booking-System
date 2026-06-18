# ERD Description — Campus Services Booking System

## Table Groups

### Account & Resource Management
- `roles`, `users`, `user_roles`, `departments`
- `resource_categories`, `resources`, `equipment`, `resource_equipment`

### Booking Engine
- `time_slots`, `booking_policies`, `bookings`, `maintenance_schedules`, `notifications`

### Approval & Reporting
- `approvals`, `cancellations`, `usage_reports`, `audit_logs`

## Primary Keys

Every table uses `id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY`.

## Foreign Keys

| Child Table | FK Column | Parent Table |
|-------------|-----------|--------------|
| users | department_id | departments |
| user_roles | user_id, role_id | users, roles |
| resources | category_id | resource_categories |
| resource_equipment | resource_id, equipment_id | resources, equipment |
| time_slots | resource_id | resources |
| booking_policies | category_id | resource_categories |
| bookings | user_id, resource_id | users, resources |
| approvals | booking_id, approver_id | bookings, users |
| cancellations | booking_id, cancelled_by | bookings, users |
| notifications | user_id, booking_id | users, bookings |
| usage_reports | resource_id | resources |
| audit_logs | user_id | users |
| maintenance_schedules | resource_id, created_by | resources, users |

## Relationships

### One-to-Many (1-N)
- roles → user_roles
- users → user_roles, bookings, approvals, cancellations, notifications, audit_logs
- departments → users
- resource_categories → resources, booking_policies
- resources → time_slots, bookings, maintenance_schedules, usage_reports

### Many-to-Many (N-N)
- resources ↔ equipment via `resource_equipment`

### One-to-One (1-1)
- bookings → cancellations (one cancellation per booking)

## 3NF Compliance

1. **1NF**: All columns hold atomic values; no repeating groups
2. **2NF**: No partial dependencies — non-key attributes depend on full primary key
3. **3NF**: No transitive dependencies — category policy fields are in `resource_categories` and `booking_policies`, not duplicated in `bookings`. User details are in `users`, not repeated in bookings beyond FK.

## Integrated Business System

All tables connect through the booking workflow:
1. User (with role) requests a Resource (in a Category with Policy)
2. System checks Time Slots, Maintenance, and existing Bookings
3. Booking is created (pending or approved)
4. Approver records decision in Approvals
5. Notifications inform users; Audit Logs track actions
6. Usage Reports aggregate booking statistics
