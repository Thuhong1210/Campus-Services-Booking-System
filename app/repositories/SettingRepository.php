<?php
declare(strict_types=1);

class SettingRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all settings as key-value pairs
     */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings');
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[$row['setting_key']] = $row['setting_value'];
        }
        return $results;
    }

    /**
     * Get a setting value by key
     */
    public function getValue(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    /**
     * Update setting value
     */
    public function update(string $key, string $value): void
    {
        $stmt = $this->db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
        $stmt->execute([$value, $key]);
    }
}
