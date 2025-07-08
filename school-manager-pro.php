<?php
/**
 * Plugin Name: School Manager Pro
 * Plugin URI:  https://testli.co.il/plugins/school-manager-pro
 * Description: A comprehensive school management system for WordPress with LearnDash integration
 * Version:     1.0.0
 * Author:      Lilac
 * Author URI:  https://testli.co.il
 * Text Domain: school-manager-pro
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SMP_VERSION', '1.0.0');
define('SMP_FILE', __FILE__);
define('SMP_PATH', plugin_dir_path(__FILE__));
define('SMP_URL', plugin_dir_url(__FILE__));
define('SMP_BASENAME', plugin_basename(__FILE__));

// Debug mode - Only in development
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
    ini_set('display_errors', 0); // Don't show errors on screen
    ini_set('log_errors', 1);
    ini_set('error_log', WP_CONTENT_DIR . '/debug-school-manager-pro.log');
}

// Autoloader
spl_autoload_register(function ($class_name) {
    $prefix = 'SchoolManagerPro\\';
    $len = strlen($prefix);
    
    // Does the class use the namespace prefix?
    if (strncmp($prefix, $class_name, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class_name, $len);
    
    // Check for Admin namespace
    if (strncmp('Admin\\', $relative_class, 6) === 0) {
        $admin_class = substr($relative_class, 6);
        $file = SMP_PATH . 'admin/class-' . strtolower(str_replace('_', '-', $admin_class)) . '.php';
    } else {
        // Regular includes path
        $file = SMP_PATH . 'includes/class-' . str_replace('\\', '/', strtolower(str_replace('_', '-', $relative_class))) . '.php';
    }
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("School Manager Pro: Class file not found: $file for class $class_name");
    }
});

// Clean up output buffers
function smp_clean_buffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'smp_deactivate_plugin');
function smp_deactivate_plugin() {
    smp_clean_buffers();
    // Add any other cleanup code here
}

// Activation hook
register_activation_hook(__FILE__, 'smp_activate_plugin');
function smp_activate_plugin() {
    smp_clean_buffers();
    error_log('School Manager Pro: Activation started');
    
    // Make sure database functions are available
    if (!function_exists('dbDelta')) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }
    
    // Create database tables and update existing data
    try {
        require_once SMP_PATH . 'includes/class-database.php';
        $db = new SchoolManagerPro\Database();
        $db->create_tables();
        
        // Update existing student records with sample names if needed
        $updated = $db->update_existing_students_with_names();
        if ($updated !== false) {
            error_log(sprintf('School Manager Pro: Updated %d student records with sample names', $updated));
        }
        
        error_log('School Manager Pro: Activation completed successfully');
    } catch (Exception $e) {
        error_log('School Manager Pro: Activation failed - ' . $e->getMessage());
        wp_die('Plugin activation failed: ' . $e->getMessage());
    }
}

/**
 * Load plugin textdomain and handle dependencies
 */
function smp_load_textdomain() {
    static $loaded = false;
    
    // Only run once
    if ($loaded) {
        return;
    }
    
    // Load plugin textdomain
    load_plugin_textdomain(
        'school-manager-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
    
    // Mark as loaded
    $loaded = true;
    do_action('smp_textdomain_loaded');
}

/**
 * Initialize plugin dependencies
 */
function smp_init_dependencies() {
    // Check for required plugins
    $missing_deps = [];
    
    if (!class_exists('WooCommerce')) {
        $missing_deps[] = 'WooCommerce';
    }
    
    if (!defined('LEARNDASH_VERSION')) {
        $missing_deps[] = 'LearnDash';
    }
    
    // Show admin notice if dependencies are missing
    if (!empty($missing_deps) && current_user_can('activate_plugins')) {
        add_action('admin_notices', function() use ($missing_deps) {
            $message = sprintf(
                __('School Manager Pro requires the following plugins to be active: %s', 'school-manager-pro'),
                implode(', ', $missing_deps)
            );
            echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
        });
        
        // Don't initialize the plugin if dependencies are missing
        remove_action('plugins_loaded', 'smp_init_plugin');
        return;
    }
    
    // Load translations after all plugins are loaded
    smp_load_textdomain();
}

// Initialize dependencies after plugins are loaded
add_action('plugins_loaded', 'smp_init_dependencies', 5);

// Initialize the plugin
function smp_init_plugin() {
    try {
        // Make sure we're after init hook
        if (!did_action('init')) {
            add_action('init', 'smp_init_plugin', 20);
            return;
        }
        
        // Initialize admin
        if (is_admin()) {
            require_once SMP_PATH . 'admin/class-admin.php';
            $admin = new SchoolManagerPro\Admin\Admin();
            // Admin is initialized in its constructor
            
            // Generate mock data if needed
            require_once SMP_PATH . 'includes/class-mock-data.php';
            $mock_data = new SchoolManagerPro\MockData();
            $mock_data->maybe_generate_mock_data();
        }
        
        // Mark plugin as initialized
        do_action('smp_plugin_initialized');
        
    } catch (Exception $e) {
        error_log('School Manager Pro: Initialization failed - ' . $e->getMessage());
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>School Manager Pro Error: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
}
add_action('plugins_loaded', 'smp_init_plugin');

// Clean up output buffers at the end of the request
register_shutdown_function(function() {
    // Only clean buffers if we started them
    if (function_exists('smp_clean_buffers') && did_action('smp_plugin_initialized')) {
        smp_clean_buffers();
    }
});
