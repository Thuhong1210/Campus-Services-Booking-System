<?php
declare(strict_types=1);

class ImportService
{
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private AuditLogService $auditLog;

    public function __construct()
    {
        $this->userRepo     = new UserRepository();
        $this->resourceRepo = new ResourceRepository();
        $this->auditLog     = new AuditLogService();
    }

    // ─────────────────────────────────────────────────────────────────
    // USERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Parse and validate a CSV file for users.
     * Returns ['valid' => [...], 'errors' => [...]]
     */
    public function previewUsers(array $fileData): array
    {
        $rows   = $this->parseCsv($fileData);
        $valid  = [];
        $errors = [];

        $requiredCols = ['full_name', 'username', 'email', 'password'];

        foreach ($rows as $lineNum => $row) {
            $rowErrors = [];

            foreach ($requiredCols as $col) {
                if (empty(trim($row[$col] ?? ''))) {
                    $rowErrors[] = "Missing '$col'";
                }
            }

            $email = trim($row['email'] ?? '');
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "Invalid email format";
            }
            if ($email && $this->userRepo->exists('email', $email)) {
                $rowErrors[] = "Email already exists: $email";
            }

            $username = trim($row['username'] ?? '');
            if ($username && $this->userRepo->exists('username', $username)) {
                $rowErrors[] = "Username already taken: $username";
            }

            $studentCode = trim($row['student_code'] ?? '');
            if ($studentCode && $this->userRepo->exists('student_code', $studentCode)) {
                $rowErrors[] = "Student code already exists: $studentCode";
            }

            if (!empty($rowErrors)) {
                $errors[] = ['row' => $lineNum + 2, 'data' => $row, 'errors' => $rowErrors];
            } else {
                $valid[] = $row;
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Import validated users in a transaction.
     */
    public function importUsers(array $rows, int $adminId): array
    {
        $imported = 0;
        $failed   = [];

        try {
            // Default role: Student (id=2)
            $roleRepo = new RoleRepository();
            $defaultRole = $roleRepo->findByName('Student');
            $defaultRoleId = $defaultRole ? [(int) $defaultRole['id']] : [2];

            foreach ($rows as $row) {
                try {
                    $this->userRepo->create([
                        'full_name'    => trim($row['full_name']),
                        'username'     => trim($row['username']),
                        'email'        => strtolower(trim($row['email'])),
                        'password'     => $row['password'],
                        'phone'        => $row['phone'] ?? null,
                        'student_code' => $row['student_code'] ?? null,
                        'staff_code'   => $row['staff_code'] ?? null,
                        'status'       => 'active',
                        'department_id'=> null,
                    ], $defaultRoleId);
                    $imported++;
                } catch (Throwable $e) {
                    $failed[] = ['row' => $row, 'error' => $e->getMessage()];
                }
            }

            $this->auditLog->log('import_users', 'users', null, null, [
                'imported' => $imported,
                'failed'   => count($failed),
            ], $adminId);

        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Import failed: ' . $e->getMessage()];
        }

        return [
            'success'  => true,
            'imported' => $imported,
            'failed'   => $failed,
            'message'  => "Imported $imported user(s) successfully." . (count($failed) > 0 ? ' ' . count($failed) . ' failed.' : ''),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // RESOURCES
    // ─────────────────────────────────────────────────────────────────

    public function previewResources(array $fileData): array
    {
        $rows   = $this->parseCsv($fileData);
        $valid  = [];
        $errors = [];
        $db     = Database::getInstance()->getConnection();

        foreach ($rows as $lineNum => $row) {
            $rowErrors = [];

            foreach (['resource_code', 'resource_name', 'location', 'category_id'] as $col) {
                if (empty(trim($row[$col] ?? ''))) {
                    $rowErrors[] = "Missing '$col'";
                }
            }

            $code = trim($row['resource_code'] ?? '');
            if ($code) {
                $exists = $db->prepare('SELECT COUNT(*) FROM resources WHERE resource_code = ?');
                $exists->execute([$code]);
                if ((int) $exists->fetchColumn() > 0) {
                    $rowErrors[] = "Resource code already exists: $code";
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = ['row' => $lineNum + 2, 'data' => $row, 'errors' => $rowErrors];
            } else {
                $valid[] = $row;
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    public function importResources(array $rows, int $adminId): array
    {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        $imported = 0;
        $failed   = [];

        try {
            foreach ($rows as $row) {
                try {
                    $stmt = $db->prepare(
                        'INSERT INTO resources (category_id, resource_code, resource_name, location, capacity, description, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        (int) $row['category_id'],
                        trim($row['resource_code']),
                        trim($row['resource_name']),
                        trim($row['location']),
                        (int) ($row['capacity'] ?? 1),
                        $row['description'] ?? null,
                        'available',
                    ]);
                    $imported++;
                } catch (Throwable $e) {
                    $failed[] = ['row' => $row, 'error' => $e->getMessage()];
                }
            }

            $db->commit();

            $this->auditLog->log('import_resources', 'resources', null, null, [
                'imported' => $imported,
                'failed'   => count($failed),
            ], $adminId);

        } catch (Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Import failed: ' . $e->getMessage()];
        }

        return [
            'success'  => true,
            'imported' => $imported,
            'failed'   => $failed,
            'message'  => "Imported $imported resource(s) successfully.",
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────

    private function parseCsv(array $fileData): array
    {
        $tmpPath = $fileData['tmp_name'];
        $rows    = [];

        if (!file_exists($tmpPath) || !is_readable($tmpPath)) {
            return [];
        }

        $handle = fopen($tmpPath, 'r');
        if (!$handle) {
            return [];
        }

        // Detect and strip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) {
            fclose($handle);
            return [];
        }

        // Normalize headers
        $headers = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', $h))), $headers);

        $maxRows = (int) setting('import_max_rows', 500);
        $count   = 0;

        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false && $count < $maxRows) {
            if (count($line) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, array_map('trim', $line));
            if ($row) {
                $rows[] = $row;
                $count++;
            }
        }

        fclose($handle);
        return $rows;
    }
}
