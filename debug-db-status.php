<?php
/**
 * Debug script to check database status
 * 
 * Access this file directly in your browser to see the database status
 */

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

global $wpdb;

// Check if tables exist
$tables = [
    'smp_students',
    'smp_teachers',
    'smp_classes',
    'smp_class_students',
    'smp_promo_codes'
];

$table_status = [];
foreach ($tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") === $full_table_name;
    $table_status[$table] = [
        'exists' => $table_exists,
        'count' => $table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}") : 0
    ];
}

// Output results
header('Content-Type: text/plain');
echo "School Manager Pro - Database Status\n";
echo "================================\n\n";

foreach ($table_status as $table => $status) {
    echo "Table: {$wpdb->prefix}{$table}\n";
    echo "Status: " . ($status['exists'] ? 'Exists' : 'MISSING') . "\n";
    echo "Row count: " . $status['count'] . "\n\n";
}

// Sample data from students table if it exists
if ($table_status['smp_students']['exists'] && $table_status['smp_students']['count'] > 0) {
    echo "Sample student data:\n";
    $students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}smp_students LIMIT 5");
    print_r($students);
}

// Check for errors
if ($wpdb->last_error) {
    echo "\nDatabase Error: " . $wpdb->last_error . "\n";
}

// Check if the plugin is active
$active_plugins = get_option('active_plugins');
$is_plugin_active = in_array('school-manager-pro/school-manager-pro.php', $active_plugins);
echo "\nPlugin Active: " . ($is_plugin_active ? 'Yes' : 'No') . "\n";

// Check plugin version
$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/school-manager-pro/school-manager-pro.php');
echo "Plugin Version: " . ($plugin_data['Version'] ?? 'Unknown') . "\n";
?>
