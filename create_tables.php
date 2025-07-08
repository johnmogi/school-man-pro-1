<?php
/**
 * Direct database table creation script for School Manager Pro
 * Run this script directly to create the required database tables
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

// Make sure dbDelta function is available
if (!function_exists('dbDelta')) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
}

// Table prefix
$prefix = $wpdb->prefix . 'smp_';

// SQL statements for table creation
$sql = [];

// Teachers table
$sql[] = "CREATE TABLE IF NOT EXISTS `{$prefix}teachers` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` varchar(100) NOT NULL,
    `mobile` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expiry_date` datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
        INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `mobile` (`mobile`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset};";

// Students table
$sql[] = "CREATE TABLE IF NOT EXISTS `{$prefix}students` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` varchar(100) NOT NULL,
    `last_name` varchar(100) NOT NULL,
    `email` varchar(100) DEFAULT NULL,
    `mobile` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `promo_code` varchar(50) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expiry_date` datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
        INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
    PRIMARY KEY (`id`),
    UNIQUE KEY `mobile` (`mobile`),
    KEY `status` (`status`),
    KEY `promo_code` (`promo_code`)
) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset};";

// Classes table
$sql[] = "CREATE TABLE IF NOT EXISTS `{$prefix}classes` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `teacher_id` bigint(20) UNSIGNED DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expiry_date` datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
        INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
    PRIMARY KEY (`id`),
    KEY `teacher_id` (`teacher_id`),
    KEY `status` (`status`),
    CONSTRAINT `{$prefix}classes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `{$prefix}teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset};";

// Class Students (junction table)
$sql[] = "CREATE TABLE IF NOT EXISTS `{$prefix}class_students` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` bigint(20) UNSIGNED NOT NULL,
    `student_id` bigint(20) UNSIGNED NOT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `enrolled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `class_student` (`class_id`, `student_id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `{$prefix}class_students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `{$prefix}classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `{$prefix}class_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `{$prefix}students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset};";

// Promo Codes table
$sql[] = "CREATE TABLE IF NOT EXISTS `{$prefix}promo_codes` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `discount_type` enum('percentage','fixed') NOT NULL,
    `discount_value` decimal(10,2) NOT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `usage_count` int(11) NOT NULL DEFAULT 0,
    `start_date` datetime DEFAULT NULL,
    `end_date` datetime DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset};";

// Execute SQL statements
$errors = [];
foreach ($sql as $query) {
    $result = $wpdb->query($query);
    
    if ($result === false) {
        $errors[] = "Error executing query: " . $wpdb->last_error . "\nQuery: " . $query;
    }
}

// Output results
if (empty($errors)) {
    echo "All tables created successfully!\n";
    
    // Check if tables exist
    $tables = [
        'teachers',
        'students',
        'classes',
        'class_students',
        'promo_codes'
    ];
    
    echo "\nChecking tables:\n";
    foreach ($tables as $table) {
        $table_name = $prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        echo "- $table_name: " . ($exists ? "✅ Exists" : "❌ Missing") . "\n";
    }
} else {
    echo "Errors occurred while creating tables:\n\n";
    echo implode("\n\n", $errors) . "\n";
}

echo "\nScript execution completed.\n";
