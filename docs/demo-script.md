# Demo Script — Campus Services Booking System

## Opening (15 sec)

"Our project solves the problem of shared campus resource booking at IS-VNU. The important point is not only storing booking records — the backend must decide whether a booking is valid: whether it overlaps with another booking, violates a policy, requires approval, or uses a resource under maintenance."

## Database & ERD (15 sec)

"We designed 17 normalized tables in 3NF, grouped into Account/Resource, Booking Engine, and Approval/Reporting. Foreign keys enforce referential integrity."

## MVC Architecture (10 sec)

"Requests go through Controllers, business rules run in Services, and Repositories handle PDO database access. Database uses the Singleton pattern."

## Student Booking Demo (15 sec)

1. Login as student@example.com
2. Browse Resources → select Group Study Room A
3. Create booking for tomorrow 08:00–10:00
4. System validates resource, policy, conflict → **Approved**

## Conflict Detection (10 sec)

5. Try booking the same room and time again
6. Backend conflict query blocks it: "This resource is already booked during the selected time period."

## Lab Approval Workflow (15 sec)

7. Book Computer Laboratory → status **Pending**
8. Login as lecturer → Pending Approvals → Approve
9. Student sees notification and updated status

## Usage Report Demo (10 sec)

10. Login as admin → Usage Reports
11. Show utilization rate, peak hours chart, most used resources

## Conclusion (5 sec)

"The same data used for booking validation powers operational reports. All critical actions are recorded in the audit log."
