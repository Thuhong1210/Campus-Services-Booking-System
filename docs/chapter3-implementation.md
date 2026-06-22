# Chapter 3 – Implementation

## 3.1 Sample Source Code

This chapter presents selected source code from the Campus Services Booking System. The explanation focuses on backend implementation, database interaction, validation, business logic, role-based access control (RBAC), and core system workflows. The system is designed using a custom PHP Model-View-Controller (MVC) architecture, utilizing PHP Data Objects (PDO) for secure database queries and the Repository pattern for data access isolation.

---

### 3.1.1 Common User Functions

This section describes functions shared by all actors, such as login, logout, authentication, password verification, session handling, profile update, change password, validation, flash messages, and role checking.

#### 1. User Login and Authentication

**Description:**
The login function allows users to authenticate using their email, username, or student/staff code. The system checks recent failed attempts to prevent brute-force attacks via the `login_attempts` table, validates the bcrypt password hash, locks accounts temporarily after exceeding max failures, updates the last login time, and records the login transaction in the audit trail.

**Implementation Location / Evidence:**
* Controller Action: `AuthController::login` in `app/controllers/AuthController.php`
* Service Method: `AuthService::login` in `app/services/AuthService.php`
* Repository Method: `UserRepository::findByLogin` in `app/repositories/UserRepository.php`

**Source Code Sample:**

```php
// File: app/services/AuthService.php
public function login(string $login, string $password, string $ip = ''): array
{
    $maxAttempts   = (int) setting('max_login_attempts', 5);
    $lockoutMinutes = (int) setting('lockout_duration_minutes', 30);
    $db = Database::getInstance()->getConnection();

    // Check brute force attempts
    $windowStart = date('Y-m-d H:i:s', time() - $lockoutMinutes * 60);
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE identifier = ? AND attempted_at > ?'
    );
    $stmt->execute([$login, $windowStart]);
    $recentAttempts = (int) $stmt->fetchColumn();

    if ($recentAttempts >= $maxAttempts) {
        return [
            'success' => false,
            'message' => "Too many failed login attempts. Please wait $lockoutMinutes minute(s) before trying again.",
        ];
    }

    $user = $this->userRepo->findByLogin($login);

    if (!$user) {
        $this->recordFailedAttempt($login, $ip);
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    // Check if account is locked
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        $until = date('H:i', strtotime($user['locked_until']));
        return ['success' => false, 'message' => "Account locked until $until. Please try again later."];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        $this->recordFailedAttempt($login, $ip);

        // Lock account after max attempts
        $stmt2 = $db->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > ?'
        );
        $stmt2->execute([$login, $windowStart]);
        $total = (int) $stmt2->fetchColumn();

        if ($total >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
            $this->userRepo->update((int) $user['id'], ['locked_until' => $lockedUntil], null);
            return [
                'success' => false,
                'message' => "Too many failed attempts. Account locked for $lockoutMinutes minute(s).",
            ];
        }

        $remaining = $maxAttempts - $total;
        return ['success' => false, 'message' => "Invalid credentials. $remaining attempt(s) remaining."];
    }

    // Successful login: clear attempts and update last login
    $db->prepare('DELETE FROM login_attempts WHERE identifier = ?')->execute([$login]);
    $this->userRepo->update((int) $user['id'], [
        'locked_until'         => null,
        'failed_login_attempts'=> 0,
        'last_login_at'        => date('Y-m-d H:i:s'),
    ], null);

    unset($user['password_hash']);
    $roles = $this->userRepo->getRoles((int) $user['id']);
    Auth::login($user, $roles);

    session_regenerate_id(true);

    $this->auditLog->log('login', 'users', (int) $user['id'], null, [
        'email'    => $user['email'],
        'username' => $user['username'],
        'ip'       => $ip,
    ], (int) $user['id']);

    return ['success' => true, 'user' => $user, 'roles' => $roles];
}
```

**Code Explanation:**
The service validates brute-force attempts in `login_attempts` over a rolling time window specified by system settings. It verifies the hashed password using PHP's `password_verify` method. On success, it clears lockout flags, regenerates the session ID to avoid Session Fixation, updates the session variables via `Auth::login`, and writes a log entry to the `audit_logs` table.

---

#### 2. User Logout

**Description:**
Terminates the user session safely, records the logout action in audit logs, and redirects the user back to the login page.

**Implementation Location / Evidence:**
* Controller Action: `AuthController::logout` in `app/controllers/AuthController.php`
* Service Method: `AuthService::logout` in `app/services/AuthService.php`
* Core Helper: `Auth::logout` in `app/core/Auth.php`

**Source Code Sample:**

```php
// File: app/services/AuthService.php
public function logout(): void
{
    $userId = Auth::id();
    if ($userId) {
        $this->auditLog->log('logout', 'users', $userId, null, null, $userId);
    }
    Auth::logout();
}
```

**Code Explanation:**
When triggered, the service logs the logout action using the active user ID. It then invokes `Auth::logout()`, which unsets `$_SESSION['user']` and `$_SESSION['roles']` to clear session storage, ensuring session invalidation.

---

#### 3. Role-Based Access Control / Middleware

**Description:**
Enforces access control rules for routes by checking the active user's roles stored in their session attributes.

**Implementation Location / Evidence:**
* Core Class: `Middleware` in `app/core/Middleware.php`
* Role Check Helpers: `Auth::hasAnyRole` and `Auth::check` in `app/core/Auth.php`

**Source Code Sample:**

```php
// File: app/core/Middleware.php
class Middleware
{
    public static function auth(): void
    {
        if (!Auth::check()) {
            Flash::error('Please log in to continue.');
            redirect('login.php');
        }
    }

    public static function role(array $roles): void
    {
        self::auth();
        if (!Auth::hasAnyRole($roles)) {
            Flash::error('You do not have permission to perform this action.');
            redirect('index.php?page=dashboard');
        }
    }
}
```

**Code Explanation:**
When a user accesses a restricted route, the controller calls `Middleware::role()` passing the authorized role array. If a user is unauthenticated, the middleware redirects them to the login page. If they lack the required roles, they are redirected to the dashboard with an error flash message.

---

#### 4. Profile Update

**Description:**
Allows users to modify their personal details (full name, phone, department) and upload a profile photo. The upload utilizes strict file extension and MIME type validation to prevent uploading execution scripts.

**Implementation Location / Evidence:**
* Controller Action: `ProfileController::uploadAvatar` in `app/controllers/ProfileController.php`
* Repository Method: `UserRepository::update` in `app/repositories/UserRepository.php`

**Source Code Sample:**

```php
// File: app/controllers/ProfileController.php
public function uploadAvatar(): void
{
    Middleware::auth();
    $this->verifyCsrf();

    $userId = (int) Auth::id();
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        Flash::error('No file uploaded or upload error occurred.');
        redirect('index.php?page=profile');
    }

    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        Flash::error('Only JPG, PNG, GIF, or WEBP images are allowed.');
        redirect('index.php?page=profile');
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        Flash::error('Image must be under 10MB.');
        redirect('index.php?page=profile');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename  = 'avatar_' . $userId . '_' . time() . '.' . strtolower($ext);
    $uploadDir = dirname(__DIR__, 2) . '/public/assets/avatars/';

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $this->userRepo->update($userId, ['avatar' => $filename], $this->getCurrentRoleIds($userId));
        $_SESSION['user']['avatar'] = $filename;
        Flash::success('Profile photo updated successfully.');
    }
    redirect('index.php?page=profile');
}
```

**Code Explanation:**
The controller processes the file upload by resolving its actual MIME type using `finfo_file` to restrict uploads to images (JPG, PNG, GIF, WEBP) and enforces a 10MB limit. It saves the file using a randomized timestamp pattern and updates the avatar path in the database.

---

#### 5. Change Password

**Description:**
Enables users to change their account password. The system verifies their current password using password hashes and enforces a minimum character length of 8.

**Implementation Location / Evidence:**
* Service Method: `AuthService::changePassword` in `app/services/AuthService.php`

**Source Code Sample:**

```php
// File: app/services/AuthService.php
public function changePassword(int $userId, string $currentPassword, string $newPassword): array
{
    $user = $this->userRepo->findById($userId);

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }

    $roles = $this->userRepo->getRoles($userId);
    $roleIds = [];
    $roleRepo = new RoleRepository();
    foreach ($roles as $roleName) {
        $role = $roleRepo->findByName($roleName);
        if ($role) $roleIds[] = (int) $role['id'];
    }

    $this->userRepo->update($userId, ['password' => $newPassword, 'must_change_password' => 0], $roleIds);
    $this->auditLog->log('change_password', 'users', $userId, null, null, $userId);

    return ['success' => true, 'message' => 'Password changed successfully.'];
}
```

**Code Explanation:**
The method fetches the user record, uses `password_verify` to authenticate the current password, enforces validation on password length, hashes the password via `password_hash`, saves it to the database, and logs the change.

---

#### 6. Flash Message / Validation Handling

**Description:**
Handles temporary session storage to present notification alerts across redirects.

**Implementation Location / Evidence:**
* Core Helper: `Flash` in `app/core/Flash.php`
* Core Validator: `Validator` in `app/core/Validator.php`

**Source Code Sample:**

```php
// File: app/core/Flash.php
class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    public static function get(string $type): array
    {
        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }
}
```

**Code Explanation:**
`Flash` stores status messages in the user session. Once read by the view using `Flash::get()`, the messages are unset, ensuring they are only displayed once.

---

### 3.1.2 Student / User Functions

This section covers features designed for students to find resources, manage bookings, check in, cancel requests, and monitor schedules.

#### 1. Browse and Search Campus Resources

**Description:**
Enables students to search and filter active campus resources based on criteria such as category, keyword (resource name or code), or location.

**Implementation Location / Evidence:**
* Controller Action: `ResourceController::browse` in `app/controllers/ResourceController.php`
* Repository Method: `ResourceRepository::findAvailable` in `app/repositories/ResourceRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ResourceRepository.php
public function findAvailable(array $filters = []): array
{
    $sql = 'SELECT r.*, rc.category_name
            FROM resources r
            JOIN resource_categories rc ON rc.id = r.category_id
            WHERE r.status = "available" AND rc.status = "active"';
    $params = [];

    if (!empty($filters['category_id'])) {
        $sql .= ' AND r.category_id = ?';
        $params[] = $filters['category_id'];
    }
    if (!empty($filters['search'])) {
        $sql .= ' AND (r.resource_name LIKE ? OR r.resource_code LIKE ?)';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
    }
    if (!empty($filters['location'])) {
        $sql .= ' AND r.location LIKE ?';
        $params[] = '%' . $filters['location'] . '%';
    }

    $sql .= ' ORDER BY r.resource_name ASC';
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

**Code Explanation:**
The repository constructs a query to fetch active resources. SQL parameters are bound dynamically using PDO to prevent SQL injection vulnerabilities.

---

#### 2. View Resource Detail

**Description:**
Displays detailed information for a specific resource including physical parameters, capacity, and active bookings.

**Implementation Location / Evidence:**
* Controller Action: `ResourceController::show` in `app/controllers/ResourceController.php`

**Source Code Sample:**

```php
// File: app/controllers/ResourceController.php
public function show(): void
{
    Middleware::auth();

    $id = $this->requireResourceId();
    $resource = $this->resourceRepo->findById($id);
    if (!$resource) {
        Flash::error('Resource not found.');
        redirect(Auth::isAdmin() ? 'index.php?page=resources' : 'index.php?page=resources&action=browse');
    }

    $equipment = $this->resourceRepo->getEquipment($id);
    $timeSlots = $this->timeSlotRepo->findByResource($id);
    $policy = $this->policyRepo->findByCategory((int) $resource['category_id']);
    $currentBookings = $this->bookingRepo->findAll(['resource_id' => (string) $id], 10, 0);

    $this->view('resources/detail', [
        'title' => $resource['resource_name'],
        'resource' => $resource,
        'equipment' => $equipment,
        'timeSlots' => $timeSlots,
        'policy' => $policy,
        'currentBookings' => $currentBookings,
    ]);
}
```

**Code Explanation:**
Checks authentication, retrieves the resource by ID, pulls the associated operating timeslots and equipment, and feeds the datasets to the details view.

---

#### 3. Create Booking Request

**Description:**
Enables users to reserve a resource for a specified date and time window. The request checks resource availability, scheduled maintenance schedules, peak quotas, and policy rules.

**Implementation Location / Evidence:**
* Service Method: `BookingService::createBooking` in `app/services/BookingService.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php
public function createBooking(array $data): array
{
    $resource = $this->resourceRepo->findById((int) $data['resource_id']);
    if (!$resource || $resource['status'] !== 'available') {
        return ['success' => false, 'message' => 'Resource is not available.'];
    }

    $userRoles = $this->userRepo->getRoles((int) $data['user_id']);

    // Check system maintenance mode
    if (setting('maintenance_mode', '0') === '1' && !in_array('Admin', $userRoles, true)) {
        return ['success' => false, 'message' => __('The system is currently undergoing maintenance. Booking functions are temporarily locked.')];
    }

    // Policy Check
    $policyErrors = $this->policyService->validate($data, $resource, $userRoles);
    if (!empty($policyErrors)) {
        return ['success' => false, 'message' => implode(' ', $policyErrors)];
    }

    // Conflict Check
    $conflicts = $this->bookingRepo->findConflicts((int) $data['resource_id'], $data['start_datetime'], $data['end_datetime']);
    if (!empty($conflicts)) {
        return ['success' => false, 'message' => 'This resource is already booked during the selected time period.'];
    }
    
    // Check maintenance schedule overlap
    $maintenance = $this->maintenanceRepo->findActiveForResource((int) $data['resource_id'], $data['start_datetime'], $data['end_datetime']);
    if (!empty($maintenance)) {
        return ['success' => false, 'message' => 'This resource is currently under maintenance and cannot be booked.'];
    }

    // Check peak hour limit for students
    $isPeak = $this->timeSlotRepo->isPeakSlot((int) $data['resource_id'], $data['start_datetime'], $data['end_datetime']);
    if ($isPeak && in_array('Student', $userRoles, true)) {
        $peakCount = $this->bookingRepo->countPeakBookingsThisWeek((int) $data['user_id'], $data['start_datetime']);
        $maxPeak = $this->policyService->getMaxPeakSlotsPerWeek((int) $resource['category_id']);
        if ($peakCount >= $maxPeak) {
            return [
                'success' => false,
                'message' => 'You have reached the maximum number of peak-hour bookings this week.',
            ];
        }
    }

    // Status Assignment
    $requiresApproval = $this->policyService->requiresApproval($resource);
    $canAutoApprove = $this->policyService->canAutoApprove($resource);
    $status = 'approved';
    if ($requiresApproval && !$canAutoApprove) {
        if (!in_array('Lecturer', $userRoles, true) && !in_array('Admin', $userRoles, true)) {
            $status = 'pending';
        }
    }

    // Insert into DB
    $ref = 'BK-' . strtoupper(bin2hex(random_bytes(4)));
    $qrToken = bin2hex(random_bytes(16));
    $bookingId = $this->bookingRepo->create([
        'user_id' => $data['user_id'],
        'resource_id' => $data['resource_id'],
        'booking_reference' => $ref,
        'start_datetime' => $data['start_datetime'],
        'end_datetime' => $data['end_datetime'],
        'purpose' => $data['purpose'],
        'additional_notes' => $data['additional_notes'] ?? null,
        'status' => $status,
        'qr_token' => $qrToken,
    ]);

    // Save equipment bookings
    if (!empty($data['equipment']) && is_array($data['equipment'])) {
        $bookingEquipRepo = new BookingEquipmentRepository();
        foreach ($data['equipment'] as $eqId => $qty) {
            $bookingEquipRepo->create($bookingId, (int) $eqId, (int) $qty);
        }
    }

    $booking = $this->bookingRepo->findById($bookingId);
    $this->notificationService->notifyBookingCreated((int) $data['user_id'], $booking, $resource);

    if ($status === 'pending') {
        $this->notificationService->notifyApproversPending($booking, $resource);
    }

    return ['success' => true, 'message' => 'Booking created successfully.', 'booking' => $booking];
}
```

**Code Explanation:**
The booking service evaluates the requested reservation. It validates policies (duration, daily/weekly hours, weekly count) using `PolicyService::validate`, verifies that the resource does not overlap with approved/pending bookings or scheduled maintenance, checks student weekly peak quotas, assigns status (`pending` or `approved`), writes the database entry, maps equipment allocations, and fires notification alerts.

---

#### 4. Backend Booking Conflict Detection

**Description:**
Prevents double-booking by checking for existing bookings on the resource that overlap with the requested slot.

**Implementation Location / Evidence:**
* Repository Method: `BookingRepository::findConflicts` in `app/repositories/BookingRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/BookingRepository.php
public function findConflicts(int $resourceId, string $startDatetime, string $endDatetime, ?int $excludeId = null): array
{
    $sql = 'SELECT b.*, u.full_name AS user_name
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            WHERE b.resource_id = ?
            AND b.status IN ("pending", "approved")
            AND b.start_datetime < ?
            AND b.end_datetime > ?';
    $params = [$resourceId, $endDatetime, $startDatetime];

    if ($excludeId !== null) {
        $sql .= ' AND b.id != ?';
        $params[] = $excludeId;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

**Code Explanation:**
The method checks if there are bookings (status: `pending` or `approved`) where `start_datetime < requested_end` and `end_datetime > requested_start`. It supports excluding an active booking ID to handle booking edits.

---

#### 5. Peak-Hour Booking Limit

**Description:**
Restricts students from booking resources during high-traffic intervals if they exceed their weekly allocation.

**Implementation Location / Evidence:**
* Repository Method: `BookingRepository::countPeakBookingsThisWeek` in `app/repositories/BookingRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/BookingRepository.php
public function countPeakBookingsThisWeek(int $userId, ?string $referenceDatetime = null, ?int $excludeBookingId = null): int
{
    $ref = $referenceDatetime ? strtotime($referenceDatetime) : time();
    $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week', $ref));
    $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week', $ref));

    $sql = 'SELECT COUNT(DISTINCT b.id) FROM bookings b
         JOIN time_slots ts ON ts.resource_id = b.resource_id
         WHERE b.user_id = ?
         AND b.status IN ("pending", "approved")
         AND b.start_datetime BETWEEN ? AND ?
         AND ts.is_peak = 1
         AND ts.is_active = 1
         AND ts.day_of_week = DAYOFWEEK(b.start_datetime) - 1
         AND ts.start_time < TIME(b.end_datetime)
         AND ts.end_time > TIME(b.start_datetime)';
    $params = [$userId, $weekStart, $weekEnd];
    if ($excludeBookingId !== null) {
        $sql .= ' AND b.id != ?';
        $params[] = $excludeBookingId;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}
```

**Code Explanation:**
Determines how many bookings the student has scheduled during peak times (`is_peak = 1` in `time_slots` table) for the selected week.

---

#### 6. Resource Status and Maintenance Checking

**Description:**
Restricts booking creations and updates on resources scheduled for maintenance.

**Implementation Location / Evidence:**
* Service check: `BookingService::createBooking` (lines 35-42) in `app/services/BookingService.php`
* Repository Method: `MaintenanceRepository::findActiveForResource` in `app/repositories/MaintenanceRepository.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php (Partial snippet)
$maintenance = $this->maintenanceRepo->findActiveForResource(
    (int) $data['resource_id'],
    $data['start_datetime'],
    $data['end_datetime']
);
if (!empty($maintenance)) {
    return ['success' => false, 'message' => 'This resource is currently under maintenance and cannot be booked.'];
}
```

**Code Explanation:**
Queries the repository to check if there are scheduled maintenance events overlapping the booking. If so, it blocks the reservation.

---

#### 7. Automatic Booking Status Assignment

**Description:**
Determines booking approval status dynamically during creation.

**Implementation Location / Evidence:**
* Service Check: `BookingService::createBooking` (lines 45-53) in `app/services/BookingService.php`
* Helper methods: `PolicyService::requiresApproval` and `PolicyService::canAutoApprove` in `app/services/PolicyService.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php (Partial snippet)
$requiresApproval = $this->policyService->requiresApproval($resource);
$canAutoApprove = $this->policyService->canAutoApprove($resource);

$status = 'approved';
if ($requiresApproval && !$canAutoApprove) {
    if (!in_array('Lecturer', $userRoles, true) && !in_array('Admin', $userRoles, true)) {
        $status = 'pending';
    }
}
```

**Code Explanation:**
Checks if the resource category requires approval. Student bookings default to `pending` unless auto-approval is toggled. Booking requests submitted by Admin or Lecturer bypass this check and are automatically approved.

---

#### 8. View My Bookings

**Description:**
Presents a paginated history of bookings created by the authenticated student.

**Implementation Location / Evidence:**
* Controller Action: `BookingController::myBookings` in `app/controllers/BookingController.php`
* Repository Method: `BookingRepository::findByUser` in `app/repositories/BookingRepository.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingController.php
public function myBookings(): void
{
    Middleware::auth();

    $filters = [
        'status' => $this->get()['status'] ?? '',
        'search' => trim((string) ($this->get()['search'] ?? '')),
        'category_id' => $this->get()['category_id'] ?? '',
        'date_from' => $this->get()['date_from'] ?? '',
        'date_to' => $this->get()['date_to'] ?? '',
    ];

    $userId = (int) Auth::id();
    $page = max(1, (int) ($this->get()['p'] ?? 1));
    $perPage = 20;
    $countFilters = array_merge($filters, ['user_id' => $userId]);
    $total = $this->bookingRepo->count($countFilters);
    $pagination = paginate($total, $page, $perPage, 'index.php?page=bookings/my');

    $bookings = $this->bookingRepo->findByUser($userId, $filters, $perPage, $pagination['offset']);
    $categories = $this->categoryRepo->findAll(['status' => 'active']);

    $this->view('bookings/my_bookings', [
        'title' => 'My Bookings',
        'bookings' => $bookings,
        'filters' => $filters,
        'pagination' => $pagination,
        'categories' => $categories,
    ]);
}
```

**Code Explanation:**
Extracts the user's ID from the session, processes filters, generates pagination offsets, queries user-specific bookings from the database, and passes them to the bookings view.

---

#### 9. Edit Future Booking

**Description:**
Allows students to update details (like purpose or time) for their own upcoming bookings.

**Implementation Location / Evidence:**
* Service Method: `BookingService::updateBooking` in `app/services/BookingService.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php (Partial snippet)
public function updateBooking(int $bookingId, array $data, int $actorId, bool $isAdmin = false): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found.'];
    }

    if (!$isAdmin && (int) $booking['user_id'] !== $actorId) {
        return ['success' => false, 'message' => 'You do not have permission to perform this action.'];
    }

    if (!in_array($booking['status'], ['pending', 'approved'], true)) {
        return ['success' => false, 'message' => 'Only pending or approved bookings can be edited.'];
    }

    if (strtotime($booking['start_datetime']) < time()) {
        return ['success' => false, 'message' => 'Past bookings cannot be edited.'];
    }

    $resource = $this->resourceRepo->findById((int) $data['resource_id']);
    if (!$resource) {
        return ['success' => false, 'message' => 'Resource not found.'];
    }
    
    // ... validates policies and updates database record ...
    $this->bookingRepo->update($bookingId, [
        'resource_id' => $data['resource_id'],
        'start_datetime' => $data['start_datetime'],
        'end_datetime' => $data['end_datetime'],
        'purpose' => $data['purpose'],
        'additional_notes' => $data['additional_notes'] ?? null,
    ]);
    return ['success' => true, 'message' => 'Booking updated successfully.'];
}
```

**Code Explanation:**
Verifies that the editor is the owner, that the booking start date is in the future, and that the booking is in `pending` or `approved` status. If checks pass, it applies policy and conflict validations on the new time range before updating the record.

---

#### 10. Cancel Booking with Reason

**Description:**
Enables users to cancel bookings. The system frees up the resource timeslot and processes waiting list queues.

**Implementation Location / Evidence:**
* Service Method: `CancellationService::cancel` in `app/services/CancellationService.php`

**Source Code Sample:**

```php
// File: app/services/CancellationService.php
public function cancel(int $bookingId, int $cancelledBy, string $reason, bool $isAdmin = false): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking || !in_array($booking['status'], ['pending', 'approved'], true)) {
        return ['success' => false, 'message' => 'This booking cannot be cancelled.'];
    }

    if (!$isAdmin && (int) $booking['user_id'] !== $cancelledBy) {
        return ['success' => false, 'message' => 'You can only cancel your own bookings.'];
    }

    $oldStatus = $booking['status'];
    $this->bookingRepo->update($bookingId, ['status' => 'cancelled']);
    $this->cancellationRepo->create([
        'booking_id' => $bookingId,
        'cancelled_by' => $cancelledBy,
        'reason' => $reason,
    ]);

    $this->auditLog->log('cancel_booking', 'bookings', $bookingId, 
        ['status' => $oldStatus], 
        ['status' => 'cancelled'], 
        $cancelledBy
    );

    $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
    if ($resource) {
        $this->notificationService->notifyBookingCancelled((int) $booking['user_id'], $booking, $resource);
    }

    // Process Waitlist: notify next waiting user
    $waitlistService = new WaitlistService();
    $waitlistService->processWaitlistAfterCancellation($booking);

    return ['success' => true, 'message' => 'Booking cancelled successfully.'];
}
```

**Code Explanation:**
Cancels a booking by changing its status to `cancelled`, registers the cancellation reason, logs the change, notifies the student, and triggers `WaitlistService` to offer the free slot to waitlisted users.

---

#### 11. View Personal Schedule

**Description:**
Displays a visual grid schedule of the student's bookings.

**Implementation Location / Evidence:**
* Controller Method: `BookingController::mySchedule` in `app/controllers/BookingController.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingController.php
public function mySchedule(): void
{
    Middleware::auth();

    $filters = [
        'view' => $this->get()['view'] ?? 'week',
        'status' => $this->get()['status'] ?? '',
        'category_id' => $this->get()['category_id'] ?? '',
        'date' => $this->get()['date'] ?? date('Y-m-d'),
    ];

    $bookingFilters = array_filter([
        'status' => $filters['status'],
        'category_id' => $filters['category_id'],
    ]);

    $schedule = $this->bookingRepo->findByUser((int) Auth::id(), $bookingFilters, 100, 0);

    $this->view('bookings/my_schedule', [
        'title' => 'My Schedule',
        'schedule' => $schedule,
        'filters' => $filters,
        'categories' => $this->categoryRepo->findAll(['status' => 'active']),
    ]);
}
```

**Code Explanation:**
Processes view parameters, queries the bookings repository for the logged-in student's schedule, and renders a calendar schedule layout.

---

#### 12. Export Personal Schedule

**Description:**
Exports upcoming bookings to a CSV file or allows downloading individual bookings as an ICS calendar file.

**Implementation Location / Evidence:**
* Controller Method: `BookingController::exportSchedule` in `app/controllers/BookingController.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingController.php
public function exportSchedule(): void
{
    Middleware::auth();

    $filters = array_filter([
        'status' => $this->get()['status'] ?? '',
        'category_id' => $this->get()['category_id'] ?? '',
    ]);
    $schedule = $this->bookingRepo->findByUser((int) Auth::id(), $filters, 500, 0);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="my_schedule_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    echo "Reference,Resource,Category,Start,End,Status,Purpose\n";
    foreach ($schedule as $b) {
        echo implode(',', [
            $b['booking_reference'],
            '"' . str_replace('"', '""', $b['resource_name']) . '"',
            '"' . str_replace('"', '""', $b['category_name'] ?? '') . '"',
            $b['start_datetime'],
            $b['end_datetime'],
            $b['status'],
            '"' . str_replace('"', '""', $b['purpose']) . '"',
        ]) . "\n";
    }
    exit;
}
```

**Code Explanation:**
Fetches the student's bookings, sets headers to serve a CSV attachment, formats raw values, and streams the data to the browser.

---

#### 13. View Notifications

**Description:**
Displays system notifications and updates linked to the user's account.

**Implementation Location / Evidence:**
* Controller Method: `NotificationController::index` in `app/controllers/NotificationController.php`

**Source Code Sample:**

```php
// File: app/controllers/NotificationController.php
public function index(): void
{
    Middleware::auth();

    $userId = (int) Auth::id();
    $filters = [
        'type' => $this->get()['type'] ?? '',
    ];

    if (($this->get()['is_read'] ?? '') !== '') {
        $filters['is_read'] = (int) $this->get()['is_read'];
    }

    $page = max(1, (int) ($this->get()['p'] ?? 1));
    $perPage = 20;
    $allNotifications = $this->notificationRepo->findByUser($userId, $filters, 1000, 0);
    $total = count($allNotifications);
    $pagination = paginate($total, $page, $perPage, 'index.php?page=notifications');
    $notifications = array_slice($allNotifications, $pagination['offset'], $perPage);
    $unreadCount = $this->notificationRepo->countUnread($userId);

    $this->view('notifications/index', [
        'title' => 'Notifications',
        'notifications' => $notifications,
        'filters' => $filters,
        'pagination' => $pagination,
        'unreadCount' => $unreadCount,
    ]);
}
```

**Code Explanation:**
Queries the user's notifications, parses them with pagination, fetches unread counts, and passes this data to the notifications view.

---

### 3.1.3 Lecturer / Approver Functions

This section covers features designed for Lecturers and Approvers. These users can manage approval queues and have access to higher booking quotas.

#### 1. Lecturer / Approver Dashboard

**Description:**
Displays pending booking approvals, decision histories, and schedule status counts.

**Implementation Location / Evidence:**
* Controller Method: `DashboardController::lecturerDashboard` in `app/controllers/DashboardController.php`

**Source Code Sample:**

```php
// File: app/controllers/DashboardController.php
private function lecturerDashboard(int $userId): void
{
    $stats = $this->bookingRepo->getDashboardStats($userId);
    $stats['pending_approvals'] = $this->approvalRepo->countPending();
    $pendingApprovals = $this->approvalRepo->findPending(5, 0);
    $recentHistory = $this->approvalRepo->findHistory(['approver_id' => $userId], 5, 0);
    $notifications = $this->notificationRepo->findByUser($userId, [], 5, 0);
    $unreadCount = $this->notificationRepo->countUnread($userId);
    $upcomingBookings = $this->bookingRepo->findUpcoming($userId, 5);

    $this->view('dashboard/lecturer', [
        'title' => 'Lecturer Dashboard',
        'stats' => $stats,
        'pendingApprovals' => $pendingApprovals,
        'recentHistory' => $recentHistory,
        'notifications' => $notifications,
        'unreadCount' => $unreadCount,
        'upcomingBookings' => $upcomingBookings,
    ]);
}
```

**Code Explanation:**
Prepares statistics (pending approvals count, history logs, unread notification alerts, upcoming schedules) to render the Lecturer/Approver dashboard.

---

#### 2. View Pending Approval Requests

**Description:**
Provides a paginated list of submitted student bookings awaiting review.

**Implementation Location / Evidence:**
* Controller Action: `ApprovalController::index` in `app/controllers/ApprovalController.php`

**Source Code Sample:**

```php
// File: app/controllers/ApprovalController.php
public function index(): void
{
    Middleware::approver();

    $page = max(1, (int) ($this->get()['p'] ?? 1));
    $perPage = 20;
    $total = $this->approvalRepo->countPending();
    $pagination = paginate($total, $page, $perPage, 'index.php?page=approvals');

    $pending = $this->approvalRepo->findPending($perPage, $pagination['offset']);

    $this->view('approvals/index', [
        'title' => 'Pending Approvals',
        'pending' => $pending,
        'pagination' => $pagination,
    ]);
}
```

**Code Explanation:**
Restricts entry using approver middleware, counts the pending requests, calculates pagination offsets, queries details from the database, and displays them.

---

#### 3. View Booking Detail for Approval

**Description:**
Shows details of a pending booking request so the lecturer can review it before approving or rejecting.

**Implementation Location / Evidence:**
* Controller Action: `ApprovalController::show` in `app/controllers/ApprovalController.php`

**Source Code Sample:**

```php
// File: app/controllers/ApprovalController.php
public function show(): void
{
    Middleware::approver();

    $bookingId = (int) ($_GET['id'] ?? 0);
    if ($bookingId <= 0) {
        Flash::error('Invalid booking ID.');
        redirect('index.php?page=approvals');
    }

    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking) {
        Flash::error('Booking not found.');
        redirect('index.php?page=approvals');
    }

    if ($booking['status'] !== 'pending') {
        Flash::error('This booking is no longer pending approval.');
        redirect('index.php?page=approvals');
    }

    $approval = $this->approvalRepo->findByBooking($bookingId);

    $this->view('approvals/detail', [
        'title' => 'Approval Request',
        'booking' => $booking,
        'approval' => $approval,
    ]);
}
```

**Code Explanation:**
Verifies authority, searches for the booking by ID, checks that its status is still pending, and loads the approval verification view.

---

#### 4. Approve Booking with Decision Note

**Description:**
Approves a pending booking request, checks for conflicts at the time of approval, updates its status to `approved`, and alerts the student.

**Implementation Location / Evidence:**
* Service Method: `ApprovalService::approve` in `app/services/ApprovalService.php`

**Source Code Sample:**

```php
// File: app/services/ApprovalService.php
public function approve(int $bookingId, int $approverId, ?string $comment = null): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking || $booking['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Only pending bookings can be approved.'];
    }

    // Secondary Conflict Check
    $conflicts = $this->bookingRepo->findConflicts(
        (int) $booking['resource_id'],
        $booking['start_datetime'],
        $booking['end_datetime'],
        $bookingId
    );
    if (!empty($conflicts)) {
        return ['success' => false, 'message' => 'Cannot approve: time slot conflicts with another booking.'];
    }

    $oldStatus = $booking['status'];
    $this->bookingRepo->update($bookingId, ['status' => 'approved']);

    $this->approvalRepo->create([
        'booking_id' => $bookingId,
        'approver_id' => $approverId,
        'decision' => 'approved',
        'comment' => $comment,
    ]);

    $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
    $updatedBooking = $this->bookingRepo->findById($bookingId);

    $this->auditLog->log('approve_booking', 'bookings', $bookingId, 
        ['status' => $oldStatus], 
        ['status' => 'approved', 'approver_id' => $approverId], 
        $approverId
    );
    
    if ($resource) {
        $this->notificationService->notifyBookingApproved((int) $booking['user_id'], $updatedBooking, $resource);
    }

    return ['success' => true, 'message' => 'Booking approved successfully.', 'booking' => $updatedBooking];
}
```

**Code Explanation:**
Before approving, the service re-checks for booking conflicts in case another booking was confirmed in the meantime. If clear, the status updates to `approved`, an approval history log is created, the action is logged in audit logs, and a notification is sent to the student.

---

#### 5. Reject Booking with Reason

**Description:**
Rejects a booking request and requires the lecturer to provide a reason for the rejection.

**Implementation Location / Evidence:**
* Service Method: `ApprovalService::reject` in `app/services/ApprovalService.php`

**Source Code Sample:**

```php
// File: app/services/ApprovalService.php
public function reject(int $bookingId, int $approverId, ?string $comment = null): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found.'];
    }

    if ($booking['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Only pending bookings can be rejected.'];
    }

    $oldStatus = $booking['status'];
    $this->bookingRepo->update($bookingId, ['status' => 'rejected']);

    $this->approvalRepo->create([
        'booking_id' => $bookingId,
        'approver_id' => $approverId,
        'decision' => 'rejected',
        'comment' => $comment,
    ]);

    $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
    $updatedBooking = $this->bookingRepo->findById($bookingId);

    $this->auditLog->log('reject_booking', 'bookings', $bookingId, 
        ['status' => $oldStatus], 
        ['status' => 'rejected', 'approver_id' => $approverId, 'comment' => $comment], 
        $approverId
    );

    if ($resource) {
        $this->notificationService->notifyBookingRejected(
            (int) $booking['user_id'],
            $updatedBooking,
            $resource,
            $comment
        );
    }

    return ['success' => true, 'message' => 'Booking rejected.', 'booking' => $updatedBooking];
}
```

**Code Explanation:**
Checks that the booking is pending, updates its status to `rejected`, records the decision in the approvals history, logs the event in the audit trail, and sends a rejection notification containing the comments to the student.

---

#### 6. Store Approval History

**Description:**
Saves approval and rejection decision history log details.

**Implementation Location / Evidence:**
* Repository Method: `ApprovalRepository::create` in `app/repositories/ApprovalRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ApprovalRepository.php
public function create(array $data): int
{
    $stmt = $this->db->prepare(
        'INSERT INTO approvals (booking_id, approver_id, decision, comment, decided_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['booking_id'],
        $data['approver_id'],
        $data['decision'],
        $data['comment'] ?? null,
        $data['decided_at'] ?? date('Y-m-d H:i:s'),
    ]);
    return (int) $this->db->lastInsertId();
}
```

**Code Explanation:**
Inserts an approval log entry containing the booking ID, approver ID, decision (`approved` or `rejected`), and comments into the `approvals` table.

---

#### 7. Create Supervised Booking

**Description:**
Allows lecturers, approvers, and admins to book resources on behalf of a student.

**Implementation Location / Evidence:**
* Controller Logic: `BookingController::store` in `app/controllers/BookingController.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingController.php (Partial snippet)
$data = $this->post();
$data['user_id'] = (int) Auth::id();
if (!empty($data['student_user_id']) && Auth::hasAnyRole(['Admin', 'Lecturer', 'Approver'])) {
    $student = $this->userRepo->findById((int) $data['student_user_id']);
    if ($student && $student['status'] === 'active') {
        $data['user_id'] = (int) $student['id'];
    }
}
```

**Code Explanation:**
If a `student_user_id` is supplied in the request and the user creating the booking has supervisor privileges, the system assigns the booking to the student's user ID.

---

#### 8. Lecturer Auto-Approval or Higher Booking Privilege

**Description:**
Grants lecturers double the booking duration, daily booking limits, and weekly quotas.

**Implementation Location / Evidence:**
* Service Check: `PolicyService::validate` in `app/services/PolicyService.php`

**Source Code Sample:**

```php
// File: app/services/PolicyService.php (Partial snippet)
$maxDuration = (float) ($policy['max_duration_hours'] ?? $category['max_booking_hours_per_day'] ?? 4);
if (in_array('Lecturer', $userRoles, true)) {
    $maxDuration *= 2; // Double duration for lecturers
}
if ($durationHours > $maxDuration) {
    $errors[] = sprintf('Booking duration exceeds maximum allowed (%.1f hours).', $maxDuration);
}

$dailyHours = $this->bookingRepo->sumDailyHours((int) $data['user_id'], $categoryId, $bookingDate, $excludeId);
$maxDaily = (float) ($category['max_booking_hours_per_day'] ?? 4);
if (in_array('Lecturer', $userRoles, true)) {
    $maxDaily *= 2; // Double daily hours for lecturers
}
```

**Code Explanation:**
The policy validation logic checks if the user has the `Lecturer` role. If so, it doubles their booking limits (including daily and weekly maximum booking hours) and automatically approves their requests.

---

#### 9. View Approval History

**Description:**
Allows lecturers to browse their previous approval decisions.

**Implementation Location / Evidence:**
* Repository Method: `ApprovalRepository::findHistory` in `app/repositories/ApprovalRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ApprovalRepository.php
public function findHistory(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $sql = 'SELECT a.*, b.booking_reference, b.start_datetime, b.end_datetime, 
                   u.full_name AS student_name, r.resource_name, ap.full_name AS approver_name
            FROM approvals a
            JOIN bookings b ON b.id = a.booking_id
            JOIN users u ON u.id = b.user_id
            JOIN resources r ON r.id = b.resource_id
            JOIN users ap ON ap.id = a.approver_id
            WHERE 1=1';
    $params = [];

    if (!empty($filters['approver_id'])) {
        $sql .= ' AND a.approver_id = ?';
        $params[] = $filters['approver_id'];
    }
    if (!empty($filters['decision'])) {
        $sql .= ' AND a.decision = ?';
        $params[] = $filters['decision'];
    }

    $sql .= ' ORDER BY a.decided_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Code Explanation:**
Queries the `approvals` table with filters for decisions, date ranges, and approver IDs, displaying the results in chronological order.

---

#### 10. View Notifications

**Description:**
Notifies approvers when new booking requests require review.

**Implementation Location / Evidence:**
* Service Method: `NotificationService::notifyApproversPending` in `app/services/NotificationService.php`

**Source Code Sample:**

```php
// File: app/services/NotificationService.php
public function notifyApproversPending(array $booking, array $resource): void
{
    $approvers = $this->userRepo->all(['role' => 'Approver'], 100, 0);
    $lecturers = $this->userRepo->all(['role' => 'Lecturer'], 100, 0);
    $admins = $this->userRepo->all(['role' => 'Admin'], 100, 0);
    $recipients = array_merge($approvers, $lecturers, $admins);

    $seen = [];
    foreach ($recipients as $recipient) {
        if (isset($seen[$recipient['id']])) {
            continue;
        }
        $seen[$recipient['id']] = true;
        $this->notify(
            (int) $recipient['id'],
            'Approval Required',
            sprintf(
                'New booking %s for %s requires your approval.',
                $booking['booking_reference'],
                $resource['resource_name']
            ),
            'pending_approval',
            (int) $booking['id']
        );
    }
}
```

**Code Explanation:**
Retrieves all users with `Approver`, `Lecturer`, or `Admin` roles and sends a pending approval notification to their dashboard feeds.

---

### 3.1.4 Admin Functions

This section details administrative features such as user management, resource configuration, booking policies, usage reporting, exports, auditing, and maintenance settings.

#### 1. User and Role Management

**Description:**
Provides tools for administrators to manage user accounts and assign roles.

**Implementation Location / Evidence:**
* Repository Method: `UserRepository::create` in `app/repositories/UserRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/UserRepository.php
public function create(array $data, array $roleIds): int
{
    $this->db->beginTransaction();
    try {
        $stmt = $this->db->prepare(
            'INSERT INTO users (department_id, full_name, username, email, password_hash, phone, student_code, staff_code, status)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['department_id'] ?: null,
            $data['full_name'], $data['username'], $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] ?? null,
            $data['student_code'] ?: null,
            $data['staff_code'] ?: null,
            $data['status'] ?? 'active',
        ]);
        $userId = (int) $this->db->lastInsertId();
        $roleStmt = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)');
        foreach ($roleIds as $roleId) {
            $roleStmt->execute([$userId, $roleId]);
        }
        $this->db->commit();
        return $userId;
    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

**Code Explanation:**
Uses a database transaction to insert user records into the `users` table and link their roles. The transaction is rolled back if any step fails.

---

#### 2. Prevent Duplicate User Data

**Description:**
Validates that new user details (like emails, usernames, student IDs, or staff IDs) do not duplicate existing accounts.

**Implementation Location / Evidence:**
* Controller Method: `UserController::validateUserData` in `app/controllers/UserController.php`
* Repository Method: `UserRepository::exists` in `app/repositories/UserRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/UserRepository.php
public function exists(string $field, string $value, ?int $excludeId = null): bool
{
    $allowed = ['email', 'username', 'student_code', 'staff_code'];
    if (!in_array($field, $allowed, true)) return false;
    $sql = "SELECT COUNT(*) FROM users WHERE $field = ?";
    $params = [$value];
    if ($excludeId) { $sql .= ' AND id != ?'; $params[] = $excludeId; }
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}
```

**Code Explanation:**
Checks for existing records in the `users` table matching the specified unique field value. If a duplicate is found, the registration is blocked.

---

#### 3. Prevent Deleting Users with Existing Bookings

**Description:**
Restricts deleting user accounts that have active bookings to maintain database integrity.

**Implementation Location / Evidence:**
* Controller Action: `UserController::delete` in `app/controllers/UserController.php`

**Source Code Sample:**

```php
// File: app/controllers/UserController.php (delete method)
public function delete(): void
{
    Middleware::admin();
    $this->verifyCsrf();

    $id = $this->requireUserId();
    if ($id === Auth::id()) {
        Flash::error('You cannot delete your own account.');
        redirect('index.php?page=users');
    }

    if ($this->userRepo->hasBookings($id)) {
        Flash::error('Cannot delete this user because they have existing bookings.');
        $this->auditLog->log('delete_attempt', 'users', $id, null, 'Has existing bookings');
        redirect('index.php?page=users');
    }

    try {
        $this->userRepo->delete($id);
        $this->auditLog->log('delete_attempt', 'users', $id, null, 'Deleted');
        Flash::success('User deleted successfully.');
    } catch (Exception $e) {
        Flash::error('Failed to delete user.');
    }

    redirect('index.php?page=users');
}
```

**Code Explanation:**
Checks if the user has booking records before deletion. If bookings are found, the deletion is blocked, the attempt is logged, and a warning is shown.

---

#### 4. Resource Category Management

**Description:**
Enables administrators to create and manage resource categories.

**Implementation Location / Evidence:**
* Repository Method: `ResourceCategoryRepository::create` in `app/repositories/ResourceCategoryRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ResourceCategoryRepository.php
public function create(array $data): int
{
    $stmt = $this->db->prepare(
        'INSERT INTO resource_categories
        (category_name, description, requires_approval, max_booking_hours_per_day,
         max_booking_hours_per_week, max_peak_slots_per_week, cancellation_deadline_hours, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['category_name'],
        $data['description'] ?? null,
        (int) ($data['requires_approval'] ?? 0),
        $data['max_booking_hours_per_day'] ?? 4.00,
        $data['max_booking_hours_per_week'] ?? 10.00,
        $data['max_peak_slots_per_week'] ?? 2,
        $data['cancellation_deadline_hours'] ?? 24,
        $data['status'] ?? 'active',
    ]);
    return (int) $this->db->lastInsertId();
}
```

**Code Explanation:**
Inserts new resource categories into the database and defines default settings, such as approval requirements and hourly constraints.

---

#### 5. Resource and Equipment Management

**Description:**
Allows administrators to manage resources and sync linked equipment.

**Implementation Location / Evidence:**
* Repository Method: `ResourceRepository::syncEquipment` in `app/repositories/ResourceRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ResourceRepository.php
public function syncEquipment(int $resourceId, array $equipmentIds): void
{
    $this->db->prepare('DELETE FROM resource_equipment WHERE resource_id = ?')->execute([$resourceId]);
    if (empty($equipmentIds)) {
        return;
    }
    $stmt = $this->db->prepare(
        'INSERT INTO resource_equipment (resource_id, equipment_id, quantity) VALUES (?,?,1)'
    );
    foreach ($equipmentIds as $equipmentId) {
        $equipmentId = (int) $equipmentId;
        if ($equipmentId > 0) {
            $stmt->execute([$resourceId, $equipmentId]);
        }
    }
}
```

**Code Explanation:**
Clears previous equipment links for the resource and updates the table with new equipment mappings.

---

#### 6. Time Slot Management

**Description:**
Configures resource operating hours and sets peak-hour schedules.

**Implementation Location / Evidence:**
* Repository Method: `TimeSlotRepository::isWithinAllowedSlots` in `app/repositories/TimeSlotRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/TimeSlotRepository.php (Partial snippet)
public function isWithinAllowedSlots(int $resourceId, string $startDatetime, string $endDatetime): bool
{
    $slots = $this->findByResource($resourceId);
    $activeSlots = array_values(array_filter($slots, fn ($s) => (int) ($s['is_active'] ?? 0) === 1));
    if (empty($activeSlots)) {
        return true;
    }

    $dayOfWeek = (int) date('w', strtotime($startDatetime));
    $startTime = date('H:i:s', strtotime($startDatetime));
    $endTime = date('H:i:s', strtotime($endDatetime));

    foreach ($activeSlots as $slot) {
        if ((int) $slot['day_of_week'] === $dayOfWeek
            && $slot['start_time'] <= $startTime
            && $slot['end_time'] >= $endTime) {
            return true;
        }
    }
    return false;
}
```

**Code Explanation:**
Determines if a requested booking falls within the resource's operating hours.

---

#### 7. Booking Policy Management

**Description:**
Configures category limits (like daily hours, weekly hours, and cancellation deadlines).

**Implementation Location / Evidence:**
* Controller Method: `BookingPolicyController::store` in `app/controllers/BookingPolicyController.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingPolicyController.php (Partial snippet)
public function store(): void
{
    Middleware::admin();
    $this->verifyCsrf();

    $data = $this->post();
    $validator = new Validator($data);
    $validator->required('policy_name', 'Policy Name')
              ->required('category_id', 'Category');

    if ($validator->fails()) {
        Flash::error($validator->firstError() ?? 'Validation failed.');
        redirect('index.php?page=booking_policies&action=create');
    }

    $clean = $this->normalizePolicyData($data);
    $this->policyRepo->create($clean);
    Flash::success('Booking policy created successfully.');
    redirect('index.php?page=booking_policies');
}
```

**Code Explanation:**
Validates and saves custom rules in the `booking_policies` table to enforce booking limits.

---

#### 8. Booking Management

**Description:**
Displays an administrative list of bookings with status and category filters.

**Implementation Location / Evidence:**
* Controller Method: `BookingController::index` in `app/controllers/BookingController.php`

**Source Code Sample:**

```php
// File: app/controllers/BookingController.php (Partial snippet)
public function index(): void
{
    Middleware::role(['Admin', 'Staff']);

    $filters = [
        'status' => $this->get()['status'] ?? '',
        'category_id' => $this->get()['category_id'] ?? '',
        'search' => trim((string) ($this->get()['search'] ?? '')),
    ];

    $page = max(1, (int) ($this->get()['p'] ?? 1));
    $perPage = 20;
    $total = $this->bookingRepo->count($filters);
    $pagination = paginate($total, $page, $perPage, 'index.php?page=bookings');
    $bookings = $this->bookingRepo->findAll($filters, $perPage, $pagination['offset']);

    $this->view('bookings/index', [
        'title' => 'Manage Bookings',
        'bookings' => $bookings,
        'filters' => $filters,
        'pagination' => $pagination,
        'categories' => $this->categoryRepo->findAll(['status' => 'active']),
    ]);
}
```

**Code Explanation:**
Loads the bookings listing view with search and filter controls.

---

#### 9. Cancellation Management

**Description:**
Displays a log of cancellations for administrative monitoring.

**Implementation Location / Evidence:**
* Service Method: `CancellationService::getAll` in `app/services/CancellationService.php`

**Source Code Sample:**

```php
// File: app/services/CancellationService.php
public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
{
    return $this->cancellationRepo->findAll($filters, $limit, $offset);
}
```

**Code Explanation:**
Retrieves cancellation logs, including details on the booking, the user who cancelled, and the reasons provided.

---

#### 10. Usage Reports and Statistics

**Description:**
Provides usage reports and key metrics (like utilization rates and no-show statistics) on the admin dashboard.

**Implementation Location / Evidence:**
* Repository Method: `ReportRepository::getDashboardChartData` in `app/repositories/ReportRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/ReportRepository.php (Partial snippet)
public function getDashboardChartData(): array
{
    $bookingsByStatus = $this->db->query(
        'SELECT status, COUNT(*) AS count FROM bookings GROUP BY status ORDER BY count DESC'
    )->fetchAll();

    $noShowStats = $this->db->query(
        'SELECT 
             SUM(CASE WHEN is_no_show = 1 THEN 1 ELSE 0 END) AS no_shows,
             SUM(CASE WHEN status IN ("approved", "completed") OR is_no_show = 1 THEN 1 ELSE 0 END) AS total_approved_ever,
             SUM(CASE WHEN start_datetime >= DATE_FORMAT(CURDATE(), "%Y-%m-01") THEN 1 ELSE 0 END) AS bookings_this_month
         FROM bookings'
    )->fetch();

    return [
        'by_status' => $bookingsByStatus,
        'no_shows' => $noShowStats,
    ];
}
```

**Code Explanation:**
Calculates occupancy metrics, monthly booking trends, and no-show rates for dashboard charts.

---

#### 11. Export Reports as CSV / Excel / PDF

**Description:**
Enables downloading reports as CSV, Excel, or PDF files.

**Implementation Location / Evidence:**
* Service Method: `ReportExportService::sendExcel` and `sendPdf` in `app/services/ReportExportService.php`

**Source Code Sample:**

```php
// File: app/services/ReportExportService.php
public function sendExcel(array $headers, array $rows, string $filename): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body><table border="1">';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th style="background:#1e3a5f;color:#fff;font-weight:bold;">' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
```

**Code Explanation:**
Constructs and sends an XML-styled Excel table spreadsheet directly to the browser. The browser downloads this stream as an Excel spreadsheet file.

---

#### 12. Audit Log Management

**Description:**
Automatically records actions (like user logins, bookings, and setting changes) in the database.

**Implementation Location / Evidence:**
* Service Method: `AuditLogService::log` in `app/services/AuditLogService.php`

**Source Code Sample:**

```php
// File: app/services/AuditLogService.php
public function log(
    string $action,
    ?string $tableName = null,
    ?int $recordId = null,
    mixed $oldValue = null,
    mixed $newValue = null,
    ?int $userId = null
): int {
    return $this->auditLogRepo->create([
        'user_id' => $userId ?? Auth::id(),
        'action' => $action,
        'table_name' => $tableName,
        'record_id' => $recordId,
        'old_value' => $oldValue !== null ? (is_string($oldValue) ? $oldValue : json_encode($oldValue)) : null,
        'new_value' => $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue)) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
```

**Code Explanation:**
Saves the action, table, database changes, user ID, and IP address to the `audit_logs` table.

---

#### 13. Maintenance Schedule Management

**Description:**
Allows administrators to manage maintenance schedules to lock resources.

**Implementation Location / Evidence:**
* Service Method: `MaintenanceService::activateMaintenance` in `app/services/MaintenanceService.php`

**Source Code Sample:**

```php
// File: app/services/MaintenanceService.php
public function activateMaintenance(int $maintenanceId, int $adminId): array
{
    $maintenance = $this->maintenanceRepo->findById($maintenanceId);
    if (!$maintenance) {
        return ['success' => false, 'message' => 'Maintenance record not found.'];
    }

    $this->maintenanceRepo->update($maintenanceId, ['status' => 'in_progress']);
    $this->resourceRepo->update((int) $maintenance['resource_id'], ['status' => 'maintenance']);

    $this->auditLog->log('activate_maintenance', 'maintenance_schedules', $maintenanceId,
        ['status' => $maintenance['status']],
        ['status' => 'in_progress'],
        $adminId
    );

    // Notify impacted users
    $this->notifyImpactedUsers($maintenanceId);

    return ['success' => true, 'message' => 'Maintenance activated and affected users notified.'];
}
```

**Code Explanation:**
Updates the resource status to block new bookings and completes maintenance tasks to release the resource.

---

#### 14. Maintenance Impact Report

**Description:**
Identifies which bookings conflict with a maintenance window.

**Implementation Location / Evidence:**
* Controller Method: `MaintenanceController::impactReport` in `app/controllers/MaintenanceController.php`

**Source Code Sample:**

```php
// File: app/controllers/MaintenanceController.php
public function impactReport(): void
{
    Middleware::admin();

    $id = (int) ($_GET['id'] ?? 0);
    $maint = $this->maintenanceService->getById($id);
    if (!$maint) {
        Flash::error('Maintenance record not found.');
        redirect('index.php?page=maintenance');
    }

    $bookings = $this->maintenanceService->detectImpactedBookings(
        (int) $maint['resource_id'],
        $maint['maintenance_start'],
        $maint['maintenance_end']
    );

    $this->view('maintenance/impact', [
        'title' => 'Maintenance Impact Report',
        'maint' => $maint,
        'bookings' => $bookings,
    ]);
}
```

**Code Explanation:**
Queries booking overlap lists for a maintenance timeslot and displays them on the dashboard.

---

#### 15. System Settings

**Description:**
Manages system settings (like max login attempts, default timezone, default limit, and maintenance mode).

**Implementation Location / Evidence:**
* Controller Method: `SettingsController::update` in `app/controllers/SettingsController.php`

**Source Code Sample:**

```php
// File: app/controllers/SettingsController.php
public function update(): void
{
    Middleware::admin();
    $this->verifyCsrf();

    $data = $this->post();

    $validator = new Validator($data);
    $validator->required('system_name', 'System Name')
              ->required('default_timezone', 'Default Timezone')
              ->required('default_booking_limit', 'Default Booking Limit')
              ->required('default_cancellation_deadline', 'Default Cancellation Deadline')
              ->numeric('default_booking_limit')
              ->numeric('default_cancellation_deadline')
              ->required('language_default', 'Default Language');

    if ($validator->fails()) {
        Flash::error($validator->firstError() ?? 'Validation failed.');
        redirect('index.php?page=settings');
    }

    $oldSettings = $this->settingRepo->all();
    $fields = ['system_name', 'default_timezone', 'maintenance_mode', 'default_booking_limit', 'default_cancellation_deadline', 'email_notification_enabled', 'language_default'];

    $updated = [];
    foreach ($fields as $field) {
        $val = trim((string) ($data[$field] ?? '0'));
        if ($field === 'system_name' || $field === 'default_timezone' || $field === 'language_default') {
            $val = trim((string) ($data[$field] ?? ''));
        }
        $this->settingRepo->update($field, $val);
        $updated[$field] = $val;
    }

    $this->auditLog->log('update_settings', 'settings', 1, $oldSettings, $updated);

    if (isset($updated['language_default'])) {
        $_SESSION['lang'] = $updated['language_default'];
    }

    Flash::success('System settings updated successfully.');
    redirect('index.php?page=settings');
}
```

**Code Explanation:**
Iterates through setting values, updates database parameters, and logs the changes.

---

### 3.1.5 Advanced System-Level Business Functions

This section summarizes the core backend logic that works across the whole system.

#### 1. Booking Conflict Detection

**Description:**
Checks for overlaps with approved or pending bookings on the same resource during a requested time window.

**Implementation Location / Evidence:**
* Repository Method: `BookingRepository::findConflicts` in `app/repositories/BookingRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/BookingRepository.php
public function findConflicts(int $resourceId, string $startDatetime, string $endDatetime, ?int $excludeId = null): array
{
    $sql = 'SELECT b.*, u.full_name AS user_name
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            WHERE b.resource_id = ?
            AND b.status IN ("pending", "approved")
            AND b.start_datetime < ?
            AND b.end_datetime > ?';
    $params = [$resourceId, $endDatetime, $startDatetime];

    if ($excludeId !== null) {
        $sql .= ' AND b.id != ?';
        $params[] = $excludeId;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

**Code Explanation:**
Checks for existing bookings (marked as `pending` or `approved`) on the same resource where the existing booking times overlap with the requested time frame.

---

#### 2. Peak-Hour Limit Enforcement

**Description:**
Restricts student weekly bookings during peak hours to ensure fair resource allocation.

**Implementation Location / Evidence:**
* Repository Method: `BookingRepository::countPeakBookingsThisWeek` in `app/repositories/BookingRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/BookingRepository.php (Partial snippet)
$sql = 'SELECT COUNT(DISTINCT b.id) FROM bookings b
     JOIN time_slots ts ON ts.resource_id = b.resource_id
     WHERE b.user_id = ?
     AND b.status IN ("pending", "approved")
     AND b.start_datetime BETWEEN ? AND ?
     AND ts.is_peak = 1';
```

**Code Explanation:**
Queries the database to verify if the student has reached their weekly peak booking limit. If so, new requests are blocked.

---

#### 3. Resource Maintenance Checking

**Description:**
Prevents resources under maintenance from being booked.

**Implementation Location / Evidence:**
* Repository Method: `MaintenanceRepository::findActiveForResource` in `app/repositories/MaintenanceRepository.php`

**Source Code Sample:**

```php
// File: app/repositories/MaintenanceRepository.php (Partial snippet)
public function findActiveForResource(int $resourceId, string $start, string $end): array
{
    $stmt = $this->db->prepare(
        'SELECT * FROM maintenance_schedules
         WHERE resource_id = ?
         AND status IN ("scheduled", "in_progress")
         AND maintenance_start < ?
         AND maintenance_end > ?'
    );
    $stmt->execute([$resourceId, $end, $start]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Code Explanation:**
Checks for scheduled or ongoing maintenance on a resource during the booking window.

---

#### 4. Automatic Booking Status Assignment

**Description:**
Determines if a booking requires manual approval based on category policies.

**Implementation Location / Evidence:**
* Service Check: `BookingService::createBooking` in `app/services/BookingService.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php (Partial snippet)
$requiresApproval = $this->policyService->requiresApproval($resource);
$canAutoApprove = $this->policyService->canAutoApprove($resource);

$status = 'approved';
if ($requiresApproval && !$canAutoApprove) {
    if (!in_array('Lecturer', $userRoles, true) && !in_array('Admin', $userRoles, true)) {
        $status = 'pending';
    }
}
```

**Code Explanation:**
During booking creation, the service evaluates category policies to verify if manual approval is required. Student accounts default to a `pending` status, while administrator and lecturer reservations bypass manual approval and are assigned `approved` directly.

---

#### 5. Approval Workflow

**Description:**
Updates booking status, registers approval details, and notifies the student.

**Implementation Location / Evidence:**
* Service Method: `ApprovalService::approve` in `app/services/ApprovalService.php`

**Source Code Sample:**

```php
// File: app/services/ApprovalService.php
public function approve(int $bookingId, int $approverId, ?string $comment = null): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking || $booking['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Only pending bookings can be approved.'];
    }

    $conflicts = $this->bookingRepo->findConflicts(
        (int) $booking['resource_id'],
        $booking['start_datetime'],
        $booking['end_datetime'],
        $bookingId
    );
    if (!empty($conflicts)) {
        return ['success' => false, 'message' => 'Cannot approve: time slot conflicts with another booking.'];
    }

    $oldStatus = $booking['status'];
    $this->bookingRepo->update($bookingId, ['status' => 'approved']);

    $this->approvalRepo->create([
        'booking_id' => $bookingId,
        'approver_id' => $approverId,
        'decision' => 'approved',
        'comment' => $comment,
    ]);

    $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
    $updatedBooking = $this->bookingRepo->findById($bookingId);

    $this->auditLog->log('approve_booking', 'bookings', $bookingId, ['status' => $oldStatus], ['status' => 'approved', 'approver_id' => $approverId], $approverId);
    if ($resource) {
        $this->notificationService->notifyBookingApproved((int) $booking['user_id'], $updatedBooking, $resource);
    }

    return ['success' => true, 'message' => 'Booking approved successfully.', 'booking' => $updatedBooking];
}
```

**Code Explanation:**
Verifies that the target booking exists and is currently pending. It checks for time slot conflicts dynamically before committing the change. Once verified, the booking is updated to `approved`, an approval log containing comments is saved, an audit trail entry is written, and an in-app notification is sent to the student.

---

#### 6. Cancellation Workflow

**Description:**
Cancels active bookings, logs the cancellation reason, and processes the waitlist to reallocate the slot.

**Implementation Location / Evidence:**
* Service Method: `CancellationService::cancel` in `app/services/CancellationService.php`

**Source Code Sample:**

```php
// File: app/services/CancellationService.php
public function cancel(int $bookingId, int $cancelledBy, string $reason, bool $isAdmin = false): array
{
    $booking = $this->bookingRepo->findById($bookingId);
    if (!$booking || !in_array($booking['status'], ['pending', 'approved'], true)) {
        return ['success' => false, 'message' => 'This booking cannot be cancelled.'];
    }

    if (!$isAdmin && (int) $booking['user_id'] !== $cancelledBy) {
        return ['success' => false, 'message' => 'You can only cancel your own bookings.'];
    }

    $oldStatus = $booking['status'];
    $this->bookingRepo->update($bookingId, ['status' => 'cancelled']);
    $this->cancellationRepo->create([
        'booking_id' => $bookingId,
        'cancelled_by' => $cancelledBy,
        'reason' => $reason,
    ]);

    $this->auditLog->log('cancel_booking', 'bookings', $bookingId, ['status' => $oldStatus], ['status' => 'cancelled'], $cancelledBy);

    $resource = $this->resourceRepo->findById((int) $booking['resource_id']);
    if ($resource) {
        $this->notificationService->notifyBookingCancelled((int) $booking['user_id'], $booking, $resource);
    }

    $waitlistService = new WaitlistService();
    $waitlistService->processWaitlistAfterCancellation($booking);

    return ['success' => true, 'message' => 'Booking cancelled successfully.'];
}
```

**Code Explanation:**
Checks permissions and confirms that the booking is in a cancellable state. It changes the status to `cancelled`, registers the cancellation reason, writes an audit record, and alerts the user. Finally, it invokes `WaitlistService` to search for waitlisted users and reallocate the vacant slot.

---

#### 7. Notification Creation

**Description:**
Creates system notifications to alert users about booking status updates.

**Implementation Location / Evidence:**
* Service Method: `NotificationService::notify` in `app/services/NotificationService.php`

**Source Code Sample:**

```php
// File: app/services/NotificationService.php
public function notify(int $userId, string $title, string $message, string $type, ?int $bookingId = null): int
{
    return $this->notificationRepo->create([
        'user_id' => $userId,
        'booking_id' => $bookingId,
        'title' => $title,
        'message' => $message,
        'type' => $type,
    ]);
}
```

**Code Explanation:**
Inserts notification details (including user ID, booking reference, title, message text, and category type) into the database, making them visible to users in their in-app notifications feeds.

---

#### 8. Audit Log Tracking

**Description:**
Logs user and administrator actions to maintain audit records.

**Implementation Location / Evidence:**
* Service Method: `AuditLogService::log` in `app/services/AuditLogService.php`

**Source Code Sample:**

```php
// File: app/services/AuditLogService.php
public function log(
    string $action,
    ?string $tableName = null,
    ?int $recordId = null,
    mixed $oldValue = null,
    mixed $newValue = null,
    ?int $userId = null
): int {
    return $this->auditLogRepo->create([
        'user_id' => $userId ?? Auth::id(),
        'action' => $action,
        'table_name' => $tableName,
        'record_id' => $recordId,
        'old_value' => $oldValue !== null ? (is_string($oldValue) ? $oldValue : json_encode($oldValue)) : null,
        'new_value' => $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue)) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
```

**Code Explanation:**
Saves metadata regarding database modifications and state updates, recording the user ID, action name, impacted table, record reference, pre-state changes, post-state changes, and client IP address.

---

#### 9. Report Generation

**Description:**
Generates utilization statistics for specific resources and date ranges.

**Implementation Location / Evidence:**
* Service Method: `ReportService::generate` in `app/services/ReportService.php`

**Source Code Sample:**

```php
// File: app/services/ReportService.php
public function generate(int $resourceId, string $reportType, string $periodStart, string $periodEnd): array
{
    $resource = $this->resourceRepo->findById($resourceId);
    if (!$resource) {
        return ['success' => false, 'message' => 'Resource not found.'];
    }

    $stats = $this->reportRepo->generateStats($resourceId, $reportType, $periodStart, $periodEnd);

    $reportId = $this->reportRepo->saveReport([
        'resource_id' => $resourceId,
        'report_type' => $reportType,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
        'total_bookings' => (int) ($stats['total_bookings'] ?? 0),
        'total_approved' => (int) ($stats['total_approved'] ?? 0),
        'total_rejected' => (int) ($stats['total_rejected'] ?? 0),
        'total_cancelled' => (int) ($stats['total_cancelled'] ?? 0),
        'total_hours' => (float) ($stats['total_hours'] ?? 0),
        'peak_hour_bookings' => (int) ($stats['peak_hour_bookings'] ?? 0),
        'utilization_rate' => (float) ($stats['utilization_rate'] ?? 0),
    ]);

    $this->auditLog->log('generate_report', 'usage_reports', $reportId, null, [
        'resource_id' => $resourceId,
        'report_type' => $reportType,
        'period_start' => $periodStart,
        'period_end' => $periodEnd,
    ]);

    return [
        'success' => true,
        'message' => 'Report generated successfully.',
        'report_id' => $reportId,
        'stats' => $stats,
        'resource' => $resource,
    ];
}
```

**Code Explanation:**
Fetches the target resource, compiles statistics (aggregate count, total approved, rejected, cancelled, booking hours, peak hour load, occupancy utilization percentage) over the specified dates, writes the summary row to `usage_reports`, and logs the creation action.

---

#### 10. QR Code Check-in / No-show Auto Release

**Description:**
Allows users to check in using booking tokens and releases rooms if users do not check in within 15 minutes of the booking start time.

**Implementation Location / Evidence:**
* Service Method: `BookingService::autoReleaseNoShows` in `app/services/BookingService.php`
* Service Method: `BookingService::checkIn` in `app/services/BookingService.php`

**Source Code Sample:**

```php
// File: app/services/BookingService.php
public function autoReleaseNoShows(): int
{
    $cutoff = date('Y-m-d H:i:s', time() - 900); // 15 minutes ago
    $expired = $this->bookingRepo->findExpiredApproved($cutoff);
    
    $releasedCount = 0;
    foreach ($expired as $b) {
        $this->bookingRepo->update((int) $b['id'], [
            'status' => 'cancelled',
            'is_no_show' => 1
        ]);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            'INSERT INTO cancellations (booking_id, cancelled_by, reason, cancelled_at) 
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([
            $b['id'],
            $b['user_id'],
            'Auto-Released: No-show for 15 minutes.'
        ]);

        $this->auditLog->log('auto_release_no_show', 'bookings', (int) $b['id'], null, [
            'reference' => $b['booking_reference'],
            'reason' => 'No-show'
        ]);

        try {
            $msg = "Lịch đặt {$b['booking_reference']} đã bị hệ thống tự động hủy do bạn không điểm danh nhận phòng đúng hạn (quá 15 phút).";
            $this->notificationService->notify((int) $b['user_id'], 'Tự động giải phóng phòng (No-show)', $msg, 'system', (int) $b['id']);
        } catch (Throwable $e) {
            // ignore
        }

        $releasedCount++;
    }
    return $releasedCount;
}
```

**Code Explanation:**
Queries for bookings with an `approved` status where the start time is past the 15-minute cutoff window and `checked_in` is 0. It cancels the booking, records the cancellation, logs the event as a no-show, and alerts the student.

---

## 3.2 Images of Final Application

This section presents selected screenshots of the final Campus Services Booking System. The screenshots demonstrate the completed user interface, the main actor workflows, and the integration between frontend pages and backend functions.

---

### 3.2.1 Common Interface Screens

#### 1. Login Page

![Login Page](docs/screenshots/figure_3_1_login_page.png)
*(Or refer to placeholder: [Insert screenshot: Login Page])*

**Figure 3.1. Login Page of the Campus Services Booking System**

The login page provides secure access to the Campus Services Booking System. Users can sign in using their registered email, username, or student code. The page interfaces with the backend lockout mechanism, which tracks recent login attempts and temporarily disables input fields on brute-force detection. After successful authentication, the system redirects users to their designated dashboard depending on their assigned database role.

---

#### 2. Profile Page

![Profile Page](docs/screenshots/figure_3_2_profile_page.png)
*(Or refer to placeholder: [Insert screenshot: Profile Page])*

**Figure 3.2. User Profile Management Page**

The user profile page allows authenticated users to update their full name, phone number, and department. It also provides a file upload component for profile avatars, validated on the backend against strict MIME types and file size restrictions. Additionally, a dedicated password change form allows users to update their credentials after verifying their current password.

---

#### 3. Notification Page

![Notification Page](docs/screenshots/figure_3_3_notifications_page.png)
*(Or refer to placeholder: [Insert screenshot: Notification Page])*

**Figure 3.3. System Notifications Page**

The notifications page displays a list of system alerts and status updates tailored to the logged-in user. Notifications are categorized by type, such as booking approvals, cancellations, and waitlist availabilities. Users can filter notifications by their read status or click to mark individual alerts as read, helping them stay informed about resource allocations.

---

### 3.2.2 Student / User Screens

#### 1. Student Dashboard

![Student Dashboard](docs/screenshots/figure_3_4_student_dashboard.png)
*(Or refer to placeholder: [Insert screenshot: Student Dashboard])*

**Figure 3.4. Student Dashboard Interface**

The student dashboard provides a high-level overview of the student's booking activities. It displays statistics on active, pending, and cancelled bookings, along with a feed of recent notifications. The screen features progress bars showing weekly quota utilization and peak-hour booking counts, as well as a list of smart resource recommendations based on category preferences.

---

#### 2. Browse Resources Page

![Browse Resources Page](docs/screenshots/figure_3_5_student_browse_resources.png)
*(Or refer to placeholder: [Insert screenshot: Browse Resources Page])*

**Figure 3.5. Browse and Search Resources Page**

The browse resources page allows students to search and filter campus facilities by category, location, capacity, and availability status. Resources are displayed as card components, each detailing the facility name, category name, location, associated equipment, and current status badge. This interface enables users to identify available slots before initiating booking forms.

---

#### 3. Resource Detail Page

![Resource Detail Page](docs/screenshots/figure_3_6_resource_detail.png)
*(Or refer to placeholder: [Insert screenshot: Resource Detail Page])*

**Figure 3.6. Resource Detail Page**

The resource detail page displays complete information for a selected campus resource. It displays physical facility descriptions, operating hour timeslots, category booking limits, and available equipment mappings. It also includes an embedded booking calendar showing upcoming reserved blocks, helping students select conflict-free time slots.

---

#### 4. Create Booking Form

![Create Booking Form](docs/screenshots/figure_3_7_create_booking.png)
*(Or refer to placeholder: [Insert screenshot: Create Booking Form])*

**Figure 3.7. Create Booking Form**

The booking creation page features a multi-input form where students specify the date, start/end times, and booking purpose. The form also lists available resource equipment, allowing users to request specific quantities for their reservation. Upon submission, the form triggers real-time conflict, policy, and quota validations.

---

#### 5. My Bookings Page

![My Bookings Page](docs/screenshots/figure_3_8_my_bookings.png)
*(Or refer to placeholder: [Insert screenshot: My Bookings Page])*

**Figure 3.8. Student Bookings History Page**

The my bookings page lists all booking requests submitted by the logged-in student. Bookings are organized in a table layout and can be filtered by their current status (pending, approved, rejected, cancelled). From this view, students can view their unique booking QR codes for check-in or request cancellations for upcoming bookings by submitting a reason.

---

#### 6. Personal Schedule Page

![Personal Schedule Page](docs/screenshots/figure_3_9_my_schedule.png)
*(Or refer to placeholder: [Insert screenshot: Personal Schedule Page])*

**Figure 3.9. Student Personal Schedule Calendar View**

The personal schedule page visualizes the student's approved bookings within weekly and monthly calendar grids. Users can click on scheduled blocks to view detailed reservation summaries and download individual ICS files to sync with external calendar clients. It also supports exporting the schedule as a CSV spreadsheet.

---

### 3.2.3 Lecturer / Approver Screens

#### 1. Lecturer Dashboard

![Lecturer Dashboard](docs/screenshots/figure_3_10_lecturer_dashboard.png)
*(Or refer to placeholder: [Insert screenshot: Lecturer Dashboard])*

**Figure 3.10. Lecturer and Approver Dashboard**

The lecturer dashboard displays summary statistics, unread notification counts, and a summary of upcoming reservations. It also features a dedicated pending approval queue, which allows approvers to quickly review and process incoming student booking requests.

---

#### 2. Pending Approval Queue

![Pending Approval Queue](docs/screenshots/figure_3_11_pending_approvals.png)
*(Or refer to placeholder: [Insert screenshot: Pending Approval Queue])*

**Figure 3.11. Pending Booking Approval Queue**

The pending approval queue list shows all student booking requests currently awaiting review. It displays key information for each request, including the requester, selected resource, booking time, purpose, and current status. Approvers can click review options to navigate to detail inspection screens.

---

#### 3. Approval Detail Page

![Approval Detail Page](docs/screenshots/figure_3_12_approval_detail.png)
*(Or refer to placeholder: [Insert screenshot: Approval Detail Page])*

**Figure 3.12. Approval Request Review Page**

The approval detail page allows lecturers to inspect booking details, check for potential timeslot conflicts, and view requested equipment. The page contains options to approve or reject the request, prompting the user for decision comments that will be sent to the student.

---

#### 4. Approval History Page

![Approval History Page](docs/screenshots/figure_3_13_approval_history.png)
*(Or refer to placeholder: [Insert screenshot: Approval History Page])*

**Figure 3.13. Approval Decisions History Log**

The approval history page lists all previous approval decisions made by the logged-in lecturer or approver. The history table records the student name, resource code, requested time, decision outcome, decision comment, and processing timestamp, maintaining accountability for resource allocations.

---

### 3.2.4 Admin Screens

#### 1. Admin Dashboard

![Admin Dashboard](docs/screenshots/figure_3_14_admin_dashboard.png)
*(Or refer to placeholder: [Insert screenshot: Admin Dashboard])*

**Figure 3.14. Administrator Control Dashboard**

The admin dashboard provides a centralized view of system activity and resource utilization. It displays metrics on total users, resources, active bookings, pending reviews, cancellation rates, and no-shows. It also lists recent audit log actions, helping administrators monitor system activity.

---

#### 2. User Management Page

![User Management Page](docs/screenshots/figure_3_15_user_management.png)
*(Or refer to placeholder: [Insert screenshot: User Management Page])*

**Figure 3.15. User Accounts Management Page**

The user management page allows administrators to manage user accounts, assign roles, and toggle account statuses. The interface prevents deleting users with active bookings and includes a CSV import feature to bulk-register students and staff.

---

#### 3. Resource Management Page

![Resource Management Page](docs/screenshots/figure_3_16_resource_management.png)
*(Or refer to placeholder: [Insert screenshot: Resource Management Page])*

**Figure 3.16. Resources and Equipment Association Page**

The resource management page allows administrators to manage resources, configure capacities, set locations, and map equipment. Blocked resources are excluded from search, and changes are recorded in the system audit logs.

---

#### 4. Time Slot Management Page

![Time Slot Management Page](docs/screenshots/figure_3_17_timeslot_management.png)
*(Or refer to placeholder: [Insert screenshot: Time Slot Management Page])*

**Figure 3.17. Time Slots and Operating Hours Configuration**

The time slot management page allows configuring operating hours for resources. Administrators can define operating windows per day of the week, mark slots as active, and toggle peak-hour flags, which are validated during booking creation.

---

#### 5. Booking Policy Management Page

![Booking Policy Management Page](docs/screenshots/figure_3_18_booking_policy_management.png)
*(Or refer to placeholder: [Insert screenshot: Booking Policy Management Page])*

**Figure 3.18. Booking Policies Configuration Page**

The booking policy page allows administrators to configure booking rules per category. Administrators can set maximum durations, weekly booking quotas, peak slot limits, cancellation deadlines, and approval requirements.

---

#### 6. Booking Management Page

![Booking Management Page](docs/screenshots/figure_3_19_booking_management.png)
*(Or refer to placeholder: [Insert screenshot: Booking Management Page])*

**Figure 3.19. Central Booking Management Console**

The booking management page lists all bookings in the system. Administrators can monitor booking details, edit booking time allocations, or cancel reservations, helping resolve booking conflicts or facility changes.

---

#### 7. Usage Reports Dashboard

![Usage Reports Dashboard](docs/screenshots/figure_3_20_usage_reports.png)
*(Or refer to placeholder: [Insert screenshot: Usage Reports Dashboard])*

**Figure 3.20. Usage Reports and Analytical Data Dashboard**

The usage reports dashboard generates resource utilization reports based on active booking records. It displays utilization rates, peak vs off-peak usage, and monthly booking counts, and supports exporting reports as CSV, Excel, or PDF.

---

#### 8. Audit Logs Page

![Audit Logs Page](docs/screenshots/figure_3_21_audit_logs.png)
*(Or refer to placeholder: [Insert screenshot: Audit Logs Page])*

**Figure 3.21. System Activity Audit Logs Trail**

The audit logs page displays a chronological log of all modifications in the system. It records the action type, impacted database table, row reference, pre-state, post-state, user, client IP address, and date/time, providing an audit trail.

---

#### 9. Maintenance Schedule Management Page

![Maintenance Schedule Management Page](docs/screenshots/figure_3_22_maintenance_management.png)
*(Or refer to placeholder: [Insert screenshot: Maintenance Schedule Management Page])*

**Figure 3.22. Maintenance Schedules and Impact Analysis**

The maintenance page allows scheduling resource maintenance. Activating a maintenance window changes the resource status to 'maintenance' and sends notifications to users with bookings during that window, helping prevent double-bookings.

---

#### 10. System Settings Page

![System Settings Page](docs/screenshots/figure_3_23_system_settings.png)
*(Or refer to placeholder: [Insert screenshot: System Settings Page])*

**Figure 3.23. Dynamic System Configuration Settings**

The system settings page allows administrators to configure system settings, including the application name, default timezone, maximum bookings, and cancellation deadlines, helping adapt the system to changing campus rules.

---

### 3.2.5 Concluding Summary of System Interfaces

In conclusion, the final application interfaces demonstrate a complete role-based web application. Each actor (Student, Lecturer, Admin) has a dedicated workflow, and all major screens are fully integrated with the custom PHP backend and MySQL database, ensuring consistency, reliability, and security.

---

## 3.3 Summary and Conclusion

The implementation details of the Campus Services Booking System demonstrate a structured Model-View-Controller (MVC) architecture built in PHP. It uses PHP Data Objects (PDO) to protect against SQL injections and follows the Repository/DAO pattern to separate the database query logic from the controllers. Security controls, including role-based access control, CSRF validation, and input sanitization, protect administrative functions and resources. This backend architecture integrates with a MySQL database to support campus resource scheduling and user management.
