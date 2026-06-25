<?php
namespace App\Helpers;

use App\Core\Database;
use App\Core\Session;

class AuditLogger {
    public static function log(string $action, mixed $details = null, ?int $userId = null): void {
        Session::start();
        $db = Database::getConnection();
        
        if ($userId === null) {
            $user = Session::get('user');
            $userId = $user ? (int)$user['id'] : null;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $detailsStr = is_string($details) ? $details : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $db->prepare("INSERT INTO audit_trails (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip_address)");
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'details' => $detailsStr,
                'ip_address' => $ip
            ]);
        } catch (\PDOException $e) {
            // Log to standard php error log if database logging fails
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }
}
