# Campus Services Booking System

**INS3064 Backend / Web Development — International School, Vietnam National University (IS-VNU)**

A centralized web application for booking shared campus resources (study rooms, laboratories, sports courts, meeting rooms, media studios, etc.) with full backend validation, role-based access control, approval workflow, reporting, and audit logging.

## Problem Statement

Manual booking via Excel, email, and chat causes double-bookings, poor visibility of availability, fragmented approval processes, and no usage analytics. This system validates every booking request on the backend before saving.

## Technologies

- PHP 8+ · MySQL/MariaDB · XAMPP
- HTML · CSS · JavaScript · Bootstrap 5
- PDO · MVC · Singleton Database · Repository/DAO · Service Layer
- Chart.js · Session authentication · password_hash()

## Installation

1. **Copy project** to XAMPP htdocs (symlink already created):
   ```
   /Applications/XAMPP/xamppfiles/htdocs/campus-services-booking/
   ```

2. **Start XAMPP** — Apache and MySQL

3. **Import database** via phpMyAdmin or CLI:
   ```bash
   mysql -u root < database/campus_services_booking.sql
   ```

4. **Configure** (if needed) edit `app/config/config.php`:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
   - `APP_URL` (default: `/campus-services-booking/public`)

5. **Open browser:**
   ```
   http://localhost/campus-services-booking/public/
   ```

## Demo Accounts

| Role     | Email               | Password    |
|----------|---------------------|-------------|
| Admin    | admin@example.com   | admin123    |
| Student  | student@example.com | student123  |
| Lecturer | lecturer@example.com| lecturer123 |
| Approver | approver@example.com| approver123 |

## Folder Structure

```
campus-services-booking/
├── public/           # Entry point (index.php, login.php, assets)
├── app/
│   ├── config/       # Database & app settings
│   ├── core/         # Database, Auth, Controller, Middleware, Router
│   ├── controllers/  # Request handlers
│   ├── repositories/ # Data access (DAO)
│   ├── services/     # Business logic
│   ├── views/        # PHP templates
│   └── helpers/      # Utility functions
├── database/         # SQL schema + seed data
└── docs/             # ERD, demo script, team assignment
```

## Database Tables (17)

`roles`, `users`, `user_roles`, `departments`, `resource_categories`, `resources`, `equipment`, `resource_equipment`, `time_slots`, `booking_policies`, `bookings`, `approvals`, `cancellations`, `notifications`, `usage_reports`, `audit_logs`, `maintenance_schedules`

## Main Modules

- **Authentication** — Login, logout, change password, session RBAC
- **User Management** — Admin CRUD with duplicate prevention
- **Resource Management** — Categories, resources, equipment, time slots
- **Booking Engine** — Conflict detection, peak-hour limits, maintenance checks
- **Approval Workflow** — Lab/studio pending → approve/reject
- **Cancellations** — Reason tracking, permission checks
- **Reports** — Utilization, charts, CSV export
- **Notifications & Audit Logs**

## Business Rules (Backend)

1. No overlapping bookings (pending/approved) on same resource
2. Start must be before end; no past bookings
3. Cannot book maintenance/unavailable/restricted resources
4. Students max 2 peak-hour bookings per week
5. Lab/Media Studio bookings require approval (status = pending)
6. Only Admin/Lecturer/Approver can approve/reject
7. Cancellation requires reason; students cancel own only
8. Cannot delete referenced users/resources/categories
9. Duplicate email/username/student_code/resource_code blocked
10. All important actions logged in audit_logs

## MVC Architecture

- **Models/Repositories** — PDO queries with prepared statements
- **Services** — Business rules (BookingService, ApprovalService, etc.)
- **Controllers** — HTTP handling, validation, redirects
- **Views** — Display only; no SQL in views

## PDO Singleton

`Database::getInstance()` returns a single PDO connection with exception mode and utf8mb4 charset.

## Repository/DAO Pattern

Each table has a repository class (e.g. `BookingRepository`) encapsulating all SQL. Controllers call services; services call repositories.

## Demo Script (75 seconds)

1. Login as **Student** → Browse Resources → Book study room (auto-approved)
2. Try same slot again → **Conflict error**
3. Book a **Laboratory** → Status = **Pending**
4. Login as **Lecturer** → Approve booking
5. Login as **Student** → See updated status
6. Login as **Admin** → View Usage Reports dashboard

See `docs/demo-script.md` for full presentation script.

## Team Assignment

See `docs/module-assignment.md` for 3-member task split.

## Screenshots

<!-- Add screenshots here after running the application -->

## Notes for Presentation

- Emphasize **backend validation** over UI
- Explain ERD and 3NF normalization
- Demo conflict detection live
- Show audit log entries after actions
- Explain Singleton, Repository, and Service layers
