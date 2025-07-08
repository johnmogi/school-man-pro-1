<?php
/**
 * Debug script to check students table and data
 * 
 * USAGE: Run this script from the WordPress admin area by visiting:
 * /wp-admin/admin.php?page=smp-debug-students
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Only run in admin and for users with proper permissions
if (!is_admin() || !current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

global $wpdb;

// Start output buffering to capture all output
ob_start();

// Set content type header
header('Content-Type: text/html; charset=utf-8');

// Output the page
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Manager Pro - Student Data Debug</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; line-height: 1.6; margin: 20px; }
        h1 { color: #23282d; }
        h2 { color: #0073aa; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 3px; overflow-x: auto; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .warning { color: #ffb900; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #e5e5e5; padding: 8px 12px; text-align: left; }
        th { background: #f9f9f9; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>School Manager Pro - Student Data Debug</h1>
    <p>This page shows debug information about the students table and data.</p>
    <p><strong>Current Time:</strong> <?php echo current_time('mysql'); ?></p>
    <p><strong>Plugin Version:</strong> <?php echo SMP_VERSION; ?></p>
    
    <?php
    // Start the main debug output
    echo "<div class='debug-container'>";

// 1. Check if table exists
$table_name = $wpdb->prefix . 'edc_school_students';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

echo "<h2>1. Table Check</h2>";
echo "Table $table_name " . ($table_exists ? "exists" : "does not exist") . "<br><br>";

if (!$table_exists) {
    die("Table does not exist. Please check your database.");
}

// 2. Show table structure
echo "<h2>2. Table Structure</h2>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
if ($columns) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . esc_html($column->Field) . "</td>";
        echo "<td>" . esc_html($column->Type) . "</td>";
        echo "<td>" . esc_html($column->Null) . "</td>";
        echo "<td>" . esc_html($column->Key) . "</td>";
        echo "<td>" . esc_html($column->Default) . "</td>";
        echo "<td>" . esc_html($column->Extra) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "Could not retrieve table structure.<br><br>";
}

// 3. Count total students
$count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo "<h2>3. Student Count</h2>";
echo "Total students in database: " . (int)$count . "<br><br>";

// 4. Show sample data (first 5 records)
if ($count > 0) {
    echo "<h2>4. Sample Data (first 5 records)</h2>";
    $students = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5");
    
    if ($students) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        // Header row
        echo "<tr>";
        foreach ((array)$students[0] as $key => $value) {
            echo "<th>" . esc_html($key) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($students as $student) {
            echo "<tr>";
            foreach ((array)$student as $value) {
                echo "<td>" . esc_html(substr(print_r($value, true), 0, 100)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "No student records found.<br><br>";
    }
}

// 5. Check WordPress options for any plugin settings
echo "<h2>5. Plugin Options</h2>";
$options = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '%school_manager%' OR option_name LIKE '%smp%'");
if ($options) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Option Name</th><th>Value</th></tr>";
    foreach ($options as $option) {
        echo "<tr>";
        echo "<td>" . esc_html($option->option_name) . "</td>";
        echo "<td>" . esc_html(substr(print_r(maybe_unserialize($option->option_value), true), 0, 200)) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No relevant plugin options found.<br><br>";
}

// 6. Check for any error logs
echo "<h2>6. Error Logs</h2>";
$error_log = ini_get('error_log');
if (file_exists($error_log)) {
    $logs = file_get_contents($error_log);
    $relevant_logs = [];
    
    // Get last 20 lines of the log
    $log_lines = array_slice(explode("\n", $logs), -20);
    
    echo "<pre>Last 20 lines from $error_log:\n\n" . esc_html(implode("\n", $log_lines)) . "</pre>";
} else {
    echo "Error log file not found at: " . esc_html($error_log) . "<br><br>";
}

echo "<h2>7. WordPress Debug Log</h2>";
$wp_debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($wp_debug_log)) {
    $logs = file_get_contents($wp_debug_log);
    // Get last 20 lines of the log
    $log_lines = array_slice(explode("\n", $logs), -20);
    echo "<pre>Last 20 lines from WordPress debug.log:\n\n" . esc_html(implode("\n", $log_lines)) . "</pre>";
} else {
    echo "WordPress debug log not found at: " . esc_html($wp_debug_log) . "<br><br>";
}

// 8. Check if the table is empty
if ($count == 0) {
    echo "<h2>8. Database Population</h2>";
    echo "<p class='warning'>The students table is empty. You may need to run the database installation/update routine.</p>";
    
    // Add a button to run the database update
    echo "<p><a href='" . wp_nonce_url(admin_url('admin.php?page=smp-tools&action=install_db'), 'smp_install_db') . "' class='button button-primary'>Run Database Installation</a></p>";
    
    // Check if we can run the database update
    if (class_exists('SchoolManagerPro\Database')) {
        echo "<p>Attempting to run database update...</p>";
        $db = new SchoolManagerPro\Database();
        $db->create_tables();
        
        // Check count again
        $new_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo "<p>Student count after update: " . (int)$new_count . "</p>";
        
        if ($new_count == 0) {
            echo "<p>Still no students found. You may need to add students manually or import them.</p>";
        }
    } else {
        echo "<p>Could not find the Database class to run the update.</p>";
    }
}

echo "<h2>Debug Complete</h2>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1 { color: #23282d; }
    h2 { color: #0073aa; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f5f5f5; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; }
</style>
