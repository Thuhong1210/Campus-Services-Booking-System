<?php

declare(strict_types=1);

class SettingsController extends Controller
{
    private SettingRepository $settingRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->settingRepo = new SettingRepository();
        $this->auditLog = new AuditLogService();
    }

    public function index(): void
    {
        Middleware::admin();

        $settings = $this->settingRepo->all();

        // Ensure defaults are populated if missing in DB
        $settings['system_name'] = $settings['system_name'] ?? 'Campus Services Booking System';
        $settings['default_timezone'] = $settings['default_timezone'] ?? 'Asia/Ho_Chi_Minh';
        $settings['maintenance_mode'] = $settings['maintenance_mode'] ?? '0';
        $settings['default_booking_limit'] = $settings['default_booking_limit'] ?? '2';
        $settings['default_cancellation_deadline'] = $settings['default_cancellation_deadline'] ?? '24';
        $settings['email_notification_enabled'] = $settings['email_notification_enabled'] ?? '1';
        $settings['language_default'] = $settings['language_default'] ?? 'en';
        $settings['session_name'] = SESSION_NAME;

        $this->view('settings/index', [
            'title' => 'System Settings',
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        Middleware::admin();
        $this->verifyCsrf();

        $data = $this->post();

        $validator = new Validator($data);
        $validator
            ->required('system_name', 'System Name')
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

        // 7 exact keys
        $fields = [
            'system_name',
            'default_timezone',
            'maintenance_mode',
            'default_booking_limit',
            'default_cancellation_deadline',
            'email_notification_enabled',
            'language_default'
        ];

        $updated = [];
        foreach ($fields as $field) {
            // For select/input fields
            $val = trim((string) ($data[$field] ?? '0'));
            if ($field === 'system_name' || $field === 'default_timezone' || $field === 'language_default') {
                $val = trim((string) ($data[$field] ?? ''));
            }
            $this->settingRepo->update($field, $val);
            $updated[$field] = $val;
        }

        // Log to Audit Trail
        $this->auditLog->log('update_settings', 'settings', 1, $oldSettings, $updated);

        // Update active session language if default system language changed
        if (isset($updated['language_default'])) {
            $_SESSION['lang'] = $updated['language_default'];
        }

        Flash::success('System settings updated successfully.');
        redirect('index.php?page=settings');
    }
}
