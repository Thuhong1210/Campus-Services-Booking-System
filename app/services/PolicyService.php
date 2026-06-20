<?php
declare(strict_types=1);

class PolicyService
{
    private BookingPolicyRepository $policyRepo;
    private ResourceCategoryRepository $categoryRepo;
    private BookingRepository $bookingRepo;

    private TimeSlotRepository $timeSlotRepo;

    public function __construct()
    {
        $this->policyRepo = new BookingPolicyRepository();
        $this->categoryRepo = new ResourceCategoryRepository();
        $this->bookingRepo = new BookingRepository();
        $this->timeSlotRepo = new TimeSlotRepository();
    }

    public function validate(array $data, array $resource, array $userRoles): array
    {
        $errors = [];
        $categoryId = (int) $resource['category_id'];
        $category = $this->categoryRepo->findById($categoryId);
        $policy = $this->policyRepo->findByCategory($categoryId);

        if (!$category || $category['status'] !== 'active') {
            $errors[] = 'Resource category is not available for booking.';
            return $errors;
        }

        $start = strtotime($data['start_datetime']);
        $end = strtotime($data['end_datetime']);

        if ($start === false || $end === false || $end <= $start) {
            $errors[] = 'Invalid booking time range.';
            return $errors;
        }

        if ($start < time()) {
            $errors[] = 'Booking start time must be in the future.';
        }

        $durationHours = ($end - $start) / 3600;

        $maxDuration = (float) ($policy['max_duration_hours'] ?? $category['max_booking_hours_per_day'] ?? 4);
        if (in_array('Lecturer', $userRoles, true)) {
            $maxDuration *= 2; // Double duration for lecturers
        }
        if ($durationHours > $maxDuration) {
            $errors[] = sprintf('Booking duration exceeds maximum allowed (%.1f hours).', $maxDuration);
        }

        $excludeId = isset($data['exclude_booking_id']) ? (int) $data['exclude_booking_id'] : null;
        $bookingDate = date('Y-m-d', $start);
        $dailyHours = $this->bookingRepo->sumDailyHours((int) $data['user_id'], $categoryId, $bookingDate, $excludeId);
        $maxDaily = (float) ($category['max_booking_hours_per_day'] ?? 4);
        if (in_array('Lecturer', $userRoles, true)) {
            $maxDaily *= 2; // Double daily hours for lecturers
        }
        if ($dailyHours + $durationHours > $maxDaily) {
            $errors[] = sprintf('Daily booking limit exceeded (max %.1f hours).', $maxDaily);
        }

        $weeklyHours = $this->bookingRepo->sumWeeklyHours((int) $data['user_id'], $categoryId, $excludeId);
        $maxWeekly = (float) ($category['max_booking_hours_per_week'] ?? 10);
        if (in_array('Lecturer', $userRoles, true)) {
            $maxWeekly *= 2; // Double weekly hours for lecturers
        }
        if ($weeklyHours + $durationHours > $maxWeekly) {
            $errors[] = sprintf('Weekly booking hours limit exceeded (max %.1f hours).', $maxWeekly);
        }

        $weeklyQuota = (int) ($policy['weekly_quota'] ?? 5);
        if (in_array('Lecturer', $userRoles, true)) {
            $weeklyQuota *= 2; // Double quota for lecturers
        }
        $weeklyCount = $this->bookingRepo->countWeeklyBookings((int) $data['user_id'], $categoryId, $excludeId);
        if ($weeklyCount >= $weeklyQuota) {
            $errors[] = sprintf('Weekly booking quota reached (max %d bookings).', $weeklyQuota);
        }

        if (!$this->timeSlotRepo->isWithinAllowedSlots(
            (int) $resource['id'],
            $data['start_datetime'],
            $data['end_datetime']
        )) {
            $errors[] = 'The selected time is outside the allowed operating hours for this resource.';
        }

        return $errors;
    }

    public function requiresApproval(array $resource): bool
    {
        $categoryId = (int) $resource['category_id'];
        $category = $this->categoryRepo->findById($categoryId);
        $policy = $this->policyRepo->findByCategory($categoryId);

        if ($policy && (int) $policy['requires_approval'] === 1) {
            return true;
        }
        if ($category && (int) $category['requires_approval'] === 1) {
            return true;
        }
        return false;
    }

    public function canAutoApprove(array $resource): bool
    {
        if ($this->requiresApproval($resource)) {
            $policy = $this->policyRepo->findByCategory((int) $resource['category_id']);
            return $policy && (int) $policy['auto_approval_enabled'] === 1;
        }
        return true;
    }

    public function getCancellationDeadlineHours(int $categoryId): int
    {
        $policy = $this->policyRepo->findByCategory($categoryId);
        if ($policy) {
            return (int) $policy['cancellation_deadline_hours'];
        }
        $category = $this->categoryRepo->findById($categoryId);
        return (int) ($category['cancellation_deadline_hours'] ?? 24);
    }

    public function getMaxPeakSlotsPerWeek(int $categoryId): int
    {
        $policy = $this->policyRepo->findByCategory($categoryId);
        if ($policy) {
            return (int) $policy['max_peak_slots_per_week'];
        }
        $category = $this->categoryRepo->findById($categoryId);
        return (int) ($category['max_peak_slots_per_week'] ?? 2);
    }
}
