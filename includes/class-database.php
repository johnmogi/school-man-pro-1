<?php
namespace SchoolManagerPro;

defined('ABSPATH') || exit;

class Database {
    /**
     * Database version
     */
    private $db_version = '1.0.0';

    /**
     * Table prefix
     */
    private $prefix = 'edc_school_';

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize if needed
    }

    /**
     * Update existing student records with sample names if missing
     */
    public function update_existing_students_with_names() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $this->prefix . 'students';
        
        // Check if the first_name column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'first_name'");
        
        if (empty($column_exists)) {
            return false; // Columns don't exist, can't update
        }
        
        // Sample first and last names
        $first_names = ['John', 'Sarah', 'Michael', 'Emily', 'David', 'Emma', 'James', 'Olivia', 'Daniel', 'Sophia'];
        $last_names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        
        // Get all students missing first or last name
        $students = $wpdb->get_results("
            SELECT id 
            FROM $table_name 
            WHERE first_name IS NULL OR first_name = '' OR last_name IS NULL OR last_name = ''
        ");
        
        if (empty($students)) {
            return true; // No students need updating
        }
        
        $updated = 0;
        
        foreach ($students as $student) {
            $first_name = $first_names[array_rand($first_names)];
            $last_name = $last_names[array_rand($last_names)];
            
            $result = $wpdb->update(
                $table_name,
                [
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ],
                ['id' => $student->id],
                ['%s', '%s'],
                ['%d']
            );
            
            if ($result !== false) {
                $updated++;
            }
        }
        
        return $updated;
    }
    
    /**
     * Create all required database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $wpdb->hide_errors();
        error_log('School Manager Pro: Starting database table creation');
        
        // Make sure dbDelta function is available
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        $tables_created = true;
        
        // Get the schema
        $tables = $this->get_schema();
        
        // Create each table
        foreach ($tables as $table_name => $sql) {
            $full_table_name = $wpdb->prefix . $this->prefix . $table_name;
            
            // Replace placeholders
            $sql = str_replace('%PREFIX%', $wpdb->prefix . $this->prefix, $sql);
            
            // Drop table if it exists
            $wpdb->query("DROP TABLE IF EXISTS `{$full_table_name}`");
            
            // Create table with dbDelta
            $result = dbDelta($sql . ' ' . $charset_collate);
            
            // Check for errors
            if (!empty($wpdb->last_error)) {
                $error_msg = "Error creating table {$table_name}: " . $wpdb->last_error;
                error_log('School Manager Pro: ' . $error_msg);
                $tables_created = false;
            } else {
                error_log("School Manager Pro: Table created: {$full_table_name}");
            }
        }
        
        // Update the database version if tables were created successfully
        if ($tables_created) {
            update_option('smp_db_version', $this->db_version);
            error_log('School Manager Pro: Database tables created successfully');
            
            // Generate sample data if tables are empty
            $this->generate_sample_data();
        } else {
            error_log('School Manager Pro: Database table creation encountered errors');
        }
        
        return $tables_created;
    }
    
    /**
     * Generate sample data for testing
     */
    private function generate_sample_data() {
        global $wpdb;
        
        // Check if we already have data
        $teachers_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}teachers");
        
        if ($teachers_count > 0) {
            error_log('School Manager Pro: Sample data already exists, skipping generation');
            return;
        }
        
        error_log('School Manager Pro: Generating sample data');
        
        // Generate sample teachers
        $teacher_ids = [];
        $teacher_emails = ['john.doe@example.com', 'jane.smith@example.com', 'mike.johnson@example.com'];
        $teacher_names = ['John Doe', 'Jane Smith', 'Mike Johnson'];
        $teacher_mobiles = ['1234567890', '2345678901', '3456789012'];
        
        foreach (range(0, 2) as $i) {
            $teacher_data = [
                'email' => $teacher_emails[$i],
                'mobile' => $teacher_mobiles[$i],
                'password' => wp_hash_password('password123'),
                'status' => 'active'
            ];
            
            $wpdb->insert(
                $wpdb->prefix . $this->prefix . 'teachers',
                $teacher_data,
                ['%s', '%s', '%s', '%s']
            );
            
            $teacher_ids[] = $wpdb->insert_id;
        }
        
        // Generate sample classes
        $class_ids = [];
        $class_names = ['Math 101', 'Science 201', 'History 301'];
        
        foreach (range(0, 2) as $i) {
            $class_data = [
                'name' => $class_names[$i],
                'status' => 'active',
                'teacher_id' => $teacher_ids[$i] ?? 1
            ];
            
            $wpdb->insert(
                $wpdb->prefix . $this->prefix . 'classes',
                $class_data,
                ['%s', '%s', '%d']
            );
            
            $class_ids[] = $wpdb->insert_id;
        }
        
        // Generate sample students with first and last names
        $student_data = [
            ['John', 'Smith', 'john@example.com', '1234567890'],
            ['Sarah', 'Johnson', 'sarah@example.com', '9876543210'],
            ['Michael', 'Brown', 'michael@example.com', '5551234567'],
            ['Emily', 'Davis', 'emily@example.com', '4445556666'],
            ['David', 'Wilson', 'david@example.com', '7778889999'],
            ['Emma', 'Taylor', 'emma@example.com', '1112223333'],
            ['James', 'Anderson', 'james@example.com', '9998887777'],
            ['Olivia', 'Thomas', 'olivia@example.com', '6665554444'],
            ['Daniel', 'Jackson', 'daniel@example.com', '3332221111'],
            ['Ivy', 'Martinez', 'ivy@example.com', '2345610987']
        ];
        
        $student_ids = [];
        foreach ($student_data as $i => $data) {
            $wpdb->insert(
                $wpdb->prefix . $this->prefix . 'students',
                [
                    'first_name' => $data[0],
                    'last_name' => $data[1],
                    'email' => $data[2],
                    'mobile' => $data[3],
                    'password' => wp_hash_password('password123'),
                    'promo_code' => 'STUDENT' . ($i + 100),
                    'is_active' => 1
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d']
            );
            
            $student_id = $wpdb->insert_id;
            $student_ids[] = $student_id;
            
            // Assign student to a class (3 students per class)
            $class_index = (int)($i / 3);
            if (isset($class_ids[$class_index])) {
                $wpdb->insert(
                    $wpdb->prefix . $this->prefix . 'class_students',
                    [
                        'class_id' => $class_ids[$class_index],
                        'student_id' => $student_id,
                        'status' => 'active'
                    ],
                    ['%d', '%d', '%s']
                );
            }
        }
        
        error_log('School Manager Pro: Sample data generated successfully');
    }
    
    /**
     * Get the database schema
     */
    private function get_schema() {
        global $wpdb;
        
        $tables = [];
        
        // Teachers table
        $tables['teachers'] = "CREATE TABLE %PREFIX%teachers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            mobile varchar(20) NOT NULL,
            password varchar(255) NOT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expiry_date datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
                INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            UNIQUE KEY mobile (mobile),
            KEY status (status)
        ) ENGINE=InnoDB";

        // Students table
        $tables['students'] = "CREATE TABLE %PREFIX%students (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            mobile varchar(20) NOT NULL,
            password varchar(255) NOT NULL,
            promo_code varchar(50) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expiry_date datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
                INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
            PRIMARY KEY  (id),
            UNIQUE KEY mobile (mobile),
            KEY promo_code (promo_code),
            KEY is_active (is_active),
            KEY name (first_name, last_name)
        ) ENGINE=InnoDB";

        // Classes table
        $tables['classes'] = "CREATE TABLE %PREFIX%classes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            teacher_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expiry_date datetime DEFAULT (DATE_ADD(DATE_FORMAT(CONCAT(YEAR(CURRENT_DATE), '-06-30 23:59:59'), '%Y-%m-%d %H:%i:%s'), 
                INTERVAL IF(DAYOFYEAR(CURRENT_DATE) > DAYOFYEAR(CONCAT(YEAR(CURRENT_DATE), '-06-30')), 1, 0) YEAR)),
            PRIMARY KEY  (id),
            KEY teacher_id (teacher_id),
            KEY status (status)
        ) ENGINE=InnoDB";

        // Class Students (Junction Table)
        $tables['class_students'] = "CREATE TABLE %PREFIX%class_students (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            class_id bigint(20) UNSIGNED NOT NULL,
            student_id bigint(20) UNSIGNED NOT NULL,
            enrolled_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY class_student (class_id, student_id),
            KEY student_id (student_id)
        ) ENGINE=InnoDB";

        // Promo Codes table
        $tables['promo_codes'] = "CREATE TABLE %PREFIX%promo_codes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            discount_amount decimal(10,2) NOT NULL,
            discount_type enum('percent','fixed') NOT NULL,
            usage_limit int(11) DEFAULT NULL,
            usage_count int(11) NOT NULL DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY status (status),
            KEY end_date (end_date)
        ) ENGINE=InnoDB";

        // Add table prefix
        foreach ($tables as $name => $sql) {
            $tables[$name] = str_replace('%PREFIX%', $wpdb->prefix . $this->prefix, $sql);
        }

        return $tables;
    }
    
    /**
     * Check if the database needs an update
     */
    public function needs_update() {
        $current_version = get_option('smp_db_version', '0');
        return version_compare($current_version, $this->db_version, '<');
    }
}
