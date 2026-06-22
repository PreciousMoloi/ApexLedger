<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `apexledger_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `apexledger_db`");
    
    // Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `user_id` INT NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(100) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('admin', 'accountant', 'manager') NOT NULL,
        `department` VARCHAR(50) DEFAULT NULL,
        `two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
        `notify_email` BOOLEAN NOT NULL DEFAULT TRUE,
        `notify_sms` BOOLEAN NOT NULL DEFAULT FALSE,
        `notify_push` BOOLEAN NOT NULL DEFAULT TRUE,
        `last_login` DATETIME DEFAULT NULL,
        `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `unique_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Clients Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `clients` (
        `client_id` INT NOT NULL AUTO_INCREMENT,
        `tax_number` VARCHAR(20) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(15) NOT NULL,
        `physical_address` TEXT NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `registration_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
        `assigned_accountant_id` INT DEFAULT NULL,
        `service_preference` ENUM('taxation','bookkeeping','payroll','advisory','audit') DEFAULT NULL,
        `two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
        `notify_email` BOOLEAN NOT NULL DEFAULT TRUE,
        `notify_sms` BOOLEAN NOT NULL DEFAULT FALSE,
        `notify_push` BOOLEAN NOT NULL DEFAULT TRUE,
        PRIMARY KEY (`client_id`),
        UNIQUE KEY `unique_tax_number` (`tax_number`),
        UNIQUE KEY `unique_email` (`email`),
        FOREIGN KEY (`assigned_accountant_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Documents Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `documents` (
        `document_id` INT NOT NULL AUTO_INCREMENT,
        `client_id` INT NOT NULL,
        `uploaded_by` INT NOT NULL,
        `uploaded_by_type` ENUM('user', 'client') NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `file_size_bytes` INT NOT NULL,
        `mime_type` VARCHAR(50) NOT NULL,
        `upload_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `document_category` ENUM('tax_return', 'bank_statement', 'invoice', 'contract', 'correspondence', 'other') NOT NULL,
        `review_status` ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        `review_notes` TEXT DEFAULT NULL,
        `version_number` INT NOT NULL DEFAULT 1,
        PRIMARY KEY (`document_id`),
        FOREIGN KEY (`client_id`) REFERENCES `clients`(`client_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Message Threads
    $pdo->exec("CREATE TABLE IF NOT EXISTS `message_threads` (
        `thread_id` INT NOT NULL AUTO_INCREMENT,
        `client_id` INT NOT NULL,
        `accountant_id` INT NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `is_closed` BOOLEAN NOT NULL DEFAULT FALSE,
        `closed_date` DATETIME DEFAULT NULL,
        PRIMARY KEY (`thread_id`),
        FOREIGN KEY (`client_id`) REFERENCES `clients`(`client_id`) ON DELETE CASCADE,
        FOREIGN KEY (`accountant_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Messages
    $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
        `message_id` INT NOT NULL AUTO_INCREMENT,
        `thread_id` INT NOT NULL,
        `sender_id` INT NOT NULL,
        `sender_type` ENUM('user', 'client') NOT NULL,
        `message_body` TEXT NOT NULL,
        `sent_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `is_urgent` BOOLEAN NOT NULL DEFAULT FALSE,
        `attachment_id` INT DEFAULT NULL,
        `read_timestamp` DATETIME DEFAULT NULL,
        PRIMARY KEY (`message_id`),
        FOREIGN KEY (`thread_id`) REFERENCES `message_threads`(`thread_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Service Tickets
    $pdo->exec("CREATE TABLE IF NOT EXISTS `service_tickets` (
        `ticket_id` INT NOT NULL AUTO_INCREMENT,
        `ticket_number` VARCHAR(20) NOT NULL,
        `client_id` INT NOT NULL,
        `assigned_accountant_id` INT NOT NULL,
        `service_type` ENUM('tax_return', 'payroll', 'bookkeeping', 'advisory', 'audit') NOT NULL,
        `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
        `status` ENUM('open', 'in_progress', 'awaiting_documents', 'under_review', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `due_date` DATE NOT NULL,
        `completed_date` DATETIME DEFAULT NULL,
        `description` TEXT NOT NULL,
        `internal_notes` TEXT DEFAULT NULL,
        PRIMARY KEY (`ticket_id`),
        UNIQUE KEY `unique_ticket_number` (`ticket_number`),
        FOREIGN KEY (`client_id`) REFERENCES `clients`(`client_id`) ON DELETE CASCADE,
        FOREIGN KEY (`assigned_accountant_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Ticket History
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_history` (
        `history_id` INT NOT NULL AUTO_INCREMENT,
        `ticket_id` INT NOT NULL,
        `old_status` VARCHAR(50) DEFAULT NULL,
        `new_status` VARCHAR(50) NOT NULL,
        `changed_by` INT NOT NULL,
        `change_timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `change_reason` TEXT DEFAULT NULL,
        PRIMARY KEY (`history_id`),
        FOREIGN KEY (`ticket_id`) REFERENCES `service_tickets`(`ticket_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Notifications
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `notification_id` INT NOT NULL AUTO_INCREMENT,
        `recipient_id` INT NOT NULL,
        `recipient_type` ENUM('user', 'client') NOT NULL,
        `notification_type` ENUM('message', 'ticket_update', 'document_upload', 'reminder', 'system_alert') NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL,
        `created_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `delivery_status` ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
        PRIMARY KEY (`notification_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Login Attempts
    $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
        `attempt_id` INT NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(100) NOT NULL,
        `attempt_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ip_address` VARCHAR(45) NOT NULL,
        `was_successful` BOOLEAN NOT NULL DEFAULT FALSE,
        PRIMARY KEY (`attempt_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Audit Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_log` (
        `log_id` INT NOT NULL AUTO_INCREMENT,
        `user_id` INT DEFAULT NULL,
        `user_type` ENUM('user', 'client') DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) NOT NULL,
        `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ---- Migration safety net ----
    // CREATE TABLE IF NOT EXISTS does nothing on a database that already existed
    // before this revision, so any *new* columns added since then (2FA, service
    // preference, notification prefs, review notes) need to be patched in here.
    // This runs on every request but is a no-op once the columns exist.
    function ensureColumn($pdo, $table, $column, $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
        }
    }

    ensureColumn($pdo, 'users', 'two_factor_enabled', "`two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `department`");
    ensureColumn($pdo, 'users', 'notify_email', "`notify_email` BOOLEAN NOT NULL DEFAULT TRUE");
    ensureColumn($pdo, 'users', 'notify_sms', "`notify_sms` BOOLEAN NOT NULL DEFAULT FALSE");
    ensureColumn($pdo, 'users', 'notify_push', "`notify_push` BOOLEAN NOT NULL DEFAULT TRUE");

    ensureColumn($pdo, 'clients', 'service_preference', "`service_preference` ENUM('taxation','bookkeeping','payroll','advisory','audit') DEFAULT NULL");
    ensureColumn($pdo, 'clients', 'two_factor_enabled', "`two_factor_enabled` BOOLEAN NOT NULL DEFAULT FALSE");
    ensureColumn($pdo, 'clients', 'notify_email', "`notify_email` BOOLEAN NOT NULL DEFAULT TRUE");
    ensureColumn($pdo, 'clients', 'notify_sms', "`notify_sms` BOOLEAN NOT NULL DEFAULT FALSE");
    ensureColumn($pdo, 'clients', 'notify_push', "`notify_push` BOOLEAN NOT NULL DEFAULT TRUE");

    ensureColumn($pdo, 'documents', 'review_notes', "`review_notes` TEXT DEFAULT NULL AFTER `review_status`");

    // Make admin/manager demo accounts 2FA-enabled if they already existed pre-migration
    $pdo->exec("UPDATE users SET two_factor_enabled = 1 WHERE role IN ('admin','manager') AND email IN ('admin@ntuli.co.za','mgr@ntuli.co.za')");

    // Insert default data
    $defaultPassword = password_hash('Password123!', PASSWORD_DEFAULT);
    
    $check = $pdo->query("SELECT COUNT(*) FROM users");
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, department, two_factor_enabled) VALUES 
            ('admin@ntuli.co.za', ?, 'Kemi Mthembu', 'admin', 'Administration', 1),
            ('acc1@ntuli.co.za', ?, 'Priya Pillay', 'accountant', 'Taxation', 0),
            ('acc2@ntuli.co.za', ?, 'Thabo Nkosi', 'accountant', 'Payroll', 0),
            ('mgr@ntuli.co.za', ?, 'Rudo Ndlovu', 'manager', 'Operations', 1)");
        $stmt->execute([$defaultPassword, $defaultPassword, $defaultPassword, $defaultPassword]);
    }
    
    $check = $pdo->query("SELECT COUNT(*) FROM clients");
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO clients (tax_number, full_name, email, phone, physical_address, password_hash, assigned_accountant_id) VALUES 
            ('9876543210', 'Thabo Mokoena', 'thabo@example.com', '0821234567', '12 Oak St, Johannesburg', ?, 2),
            ('1234567890', 'Zanele Dlamini', 'zanele@example.com', '0719876543', '5 Elm Rd, Cape Town', ?, 2),
            ('5551239876', 'Sipho Nkosi', 'sipho@example.com', '0831112222', '88 Pine Ave, Durban', ?, 3)");
        $stmt->execute([$defaultPassword, $defaultPassword, $defaultPassword]);
    }
    
    $check = $pdo->query("SELECT COUNT(*) FROM service_tickets");
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO service_tickets (ticket_number, client_id, assigned_accountant_id, service_type, priority, status, due_date, description) VALUES 
            ('TAX-2026-0001', 1, 2, 'tax_return', 'high', 'in_progress', '2026-05-30', 'Submit 2025 individual tax return'),
            ('PAY-2026-0002', 2, 3, 'payroll', 'medium', 'open', '2026-06-15', 'Monthly payroll processing March'),
            ('ADV-2026-0003', 3, 2, 'advisory', 'low', 'awaiting_documents', '2026-06-10', 'Investment advice consultation')");
        $stmt->execute();
    }
    
    $check = $pdo->query("SELECT COUNT(*) FROM message_threads");
    if ($check->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO message_threads (client_id, accountant_id, subject) VALUES (1, 2, 'Tax Return 2025 Query')")->execute();
        $pdo->prepare("INSERT INTO messages (thread_id, sender_id, sender_type, message_body) VALUES (1, 1, 'client', 'Please update my tax return status. I have uploaded all documents.')")->execute();
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // NFR-Security: automatically end inactive sessions after 30 minutes
    if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $_SESSION = [];
            session_unset();
            session_destroy();
            session_start();
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$conn = $pdo;

function logAction($pdo, $userId, $userType, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, user_type, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $userType, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
    } catch (Exception $e) {}
}

// FR: System shall flag messages containing urgent keywords ("urgent", "deadline", "penalty")
function containsUrgentKeyword($text) {
    $keywords = ['urgent', 'deadline', 'penalty', 'asap', 'overdue'];
    $text = strtolower($text);
    foreach ($keywords as $kw) {
        if (strpos($text, $kw) !== false) return true;
    }
    return false;
}

// NFR-Security: enforce account lockout after 5 failed login attempts within 15 minutes
function isAccountLocked($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND was_successful = 0 AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() >= 5;
}
?>