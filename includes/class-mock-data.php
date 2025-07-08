<?php
namespace SchoolManagerPro;

defined('ABSPATH') || exit;

class MockData {
    /**
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * @var Database
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->db = new Database();
    }
    
    /**
     * Generate mock data if tables are empty
     */
    public function maybe_generate_mock_data() {
        if ($this->should_generate_mock_data()) {
            return $this->generate_mock_data();
        }
        return false;
    }
    
    /**
     * Check if we should generate mock data
     */
    private function should_generate_mock_data() {
        error_log('School Manager Pro: Checking if mock data should be generated');
        
        // Check if any data exists in our tables
        $tables = ['teachers', 'students', 'classes', 'promo_codes'];
        
        foreach ($tables as $table) {
            $table_name = $this->wpdb->prefix . 'edc_school_' . $table;
            $count = $this->wpdb->get_var($this->wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
                DB_NAME,
                $table_name
            ));
            
            error_log("School Manager Pro: Checking table {$table_name} - " . ($count ? 'Exists' : 'Does not exist'));
            
            if ($count) {
                $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM `{$table_name}`");
                error_log("School Manager Pro: Table {$table_name} has {$row_count} rows");
                
                if ($row_count > 0) {
                    error_log("School Manager Pro: Data exists in {$table_name}, skipping mock data generation");
                    return false; // Data exists, don't generate mock data
                }
            } else {
                error_log("School Manager Pro: Table {$table_name} does not exist, will create it");
            }
        }
        
        error_log('School Manager Pro: No data found in any tables, will generate mock data');
        return true; // No data found, generate mock data
    }
    
    /**
     * Generate mock data
     */
    public function generate_mock_data() {
        // Generate teachers
        $teacher_ids = $this->generate_teachers(3);
        
        // Generate students (3 per teacher)
        $students_per_teacher = 3;
        $student_ids = $this->generate_students(count($teacher_ids) * $students_per_teacher);
        
        // Generate classes and assign students
        $this->generate_classes_and_assignments($teacher_ids, $student_ids, $students_per_teacher);
        
        // Generate promo codes
        $this->generate_promo_codes(5);
        
        return true;
    }
    
    /**
     * Generate mock teachers
     */
    private function generate_teachers($count) {
        $teacher_ids = [];
        $first_names = ['Sarah', 'David', 'Rachel', 'Michael', 'Leah'];
        $last_names = ['Cohen', 'Levi', 'Goldberg', 'Stein', 'Friedman'];
        
        for ($i = 0; $i < $count; $i++) {
            $first_name = $first_names[array_rand($first_names)];
            $last_name = $last_names[array_rand($last_names)];
            $email = strtolower($first_name . '.' . $last_name . ($i + 1) . '@example.com');
            $mobile = '05' . rand(20, 59) . rand(100000, 999999);
            
            $data = [
                'email' => $email,
                'mobile' => $mobile,
                'password' => wp_hash_password('teacher123'),
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $this->wpdb->insert(
                $this->wpdb->prefix . 'edc_school_teachers',
                $data
            );
            
            $teacher_ids[] = $this->wpdb->insert_id;
        }
        
        return $teacher_ids;
    }
    
    /**
     * Generate mock students
     */
    private function generate_students($count) {
        $student_ids = [];
        $first_names = ['Noam', 'Yael', 'Amit', 'Tamar', 'Ethan', 'Leah', 'Daniel', 'Rivka', 'Avi', 'Miriam'];
        $last_names = ['Cohen', 'Levi', 'Mizrahi', 'Peretz', 'Azulai', 'Friedman', 'Goldberg', 'Katz', 'Rabin', 'Weiss'];
        $promo_codes = ['WELCOME2023', 'STUDENT25', 'EARLYBIRD', 'REFER5', null, null, null]; // 3/7 chance of having a promo code
        
        error_log('School Manager Pro: Starting to generate ' . $count . ' students');
        
        // First, check if the table exists
        $table_name = $this->wpdb->prefix . 'edc_school_students';
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            error_log("School Manager Pro: ERROR - Table {$table_name} does not exist!");
            return [];
        }
        
        // Check table structure
        $columns = $this->wpdb->get_results("SHOW COLUMNS FROM `{$table_name}`");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        error_log('School Manager Pro: Table columns: ' . implode(', ', $column_names));
        
        for ($i = 0; $i < $count; $i++) {
            try {
                // Generate random but unique mobile number
                $attempts = 0;
                do {
                    $mobile = '05' . rand(20, 59) . rand(100000, 999999);
                    $exists = $this->wpdb->get_var($this->wpdb->prepare(
                        "SELECT id FROM `{$table_name}` WHERE mobile = %s",
                        $mobile
                    ));
                    $attempts++;
                    if ($attempts > 10) {
                        error_log("School Manager Pro: Failed to generate unique mobile after 10 attempts");
                        continue 2; // Skip to next student
                    }
                } while ($exists);
                
                $first_name = $first_names[array_rand($first_names)];
                $last_name = $last_names[array_rand($last_names)];
                $promo_code = $promo_codes[array_rand($promo_codes)];
                
                // Generate student data matching the actual schema
                $data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'mobile' => $mobile,
                    'password' => wp_hash_password('student123'),
                    'promo_code' => $promo_code,
                    'is_active' => 1, // Active by default
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'expiry_date' => date('Y-06-30 23:59:59', strtotime('+1 year')) // Next June 30th
                ];
                
                // Log the data we're about to insert for debugging
                error_log('School Manager Pro: Attempting to insert student data: ' . print_r($data, true));
                
                // Insert the student
                $result = $this->wpdb->insert($table_name, $data);
                
                if ($result === false) {
                    $error = $this->wpdb->last_error;
                    $query = $this->wpdb->last_query;
                    error_log("School Manager Pro: Failed to insert student - Error: {$error}");
                    error_log("School Manager Pro: Failed query: {$query}");
                    
                    // Try to get more detailed error information
                    $mysql_error = $this->wpdb->last_error;
                    $mysql_errno = $this->wpdb->last_error_no;
                    error_log("School Manager Pro: MySQL Error #{$mysql_errno}: {$mysql_error}");
                } else {
                    $student_id = $this->wpdb->insert_id;
                    $student_ids[] = $student_id;
                    error_log("School Manager Pro: Successfully inserted student with ID: {$student_id}");
                }
            } catch (Exception $e) {
                error_log("School Manager Pro: Exception while generating student: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            
            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'edc_school_students',
                $data
            );
            
            if ($result === false) {
                error_log('School Manager Pro: Failed to insert student - ' . $this->wpdb->last_error);
                error_log('School Manager Pro: Data being inserted: ' . print_r($data, true));
            } else {
                $student_ids[] = $this->wpdb->insert_id;
                error_log('School Manager Pro: Successfully inserted student with ID: ' . $this->wpdb->insert_id);
            }
        }
        
        return $student_ids;
    }
    
    /**
     * Generate classes and assign students
     */
    private function generate_classes_and_assignments($teacher_ids, $student_ids, $students_per_teacher) {
        $class_names = [
            'Math 101', 'English Literature', 'Computer Science',
            'History of Art', 'Physics Fundamentals', 'Chemistry Lab'
        ];
        
        $class_ids = [];
        $student_chunks = array_chunk($student_ids, $students_per_teacher);
        
        foreach ($teacher_ids as $index => $teacher_id) {
            // Create 1-2 classes per teacher
            $class_count = rand(1, 2);
            
            for ($i = 0; $i < $class_count; $i++) {
                $class_name = array_shift($class_names) ?: 'Class ' . ($i + 1);
                
                $data = [
                    'name' => $class_name,
                    'teacher_id' => $teacher_id,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ];
                
                $this->wpdb->insert(
                    $this->wpdb->prefix . 'edc_school_classes',
                    $data
                );
                
                $class_id = $this->wpdb->insert_id;
                $class_ids[] = $class_id;
                
                // Assign students to this class
                if (isset($student_chunks[$index])) {
                    foreach ($student_chunks[$index] as $student_id) {
                        $this->wpdb->insert(
                            $this->wpdb->prefix . 'edc_school_class_students',
                            [
                                'class_id' => $class_id,
                                'student_id' => $student_id,
                                'status' => 'active',
                                'enrolled_at' => current_time('mysql')
                            ]
                        );
                    }
                }
            }
        }
        
        return $class_ids;
    }
    
    /**
     * Generate promo codes
     */
    private function generate_promo_codes($count) {
        $types = ['percent', 'fixed'];
        $statuses = ['active', 'inactive'];
        
        for ($i = 0; $i < $count; $i++) {
            $type = $types[array_rand($types)];
            $amount = $type === 'percent' ? rand(5, 50) : rand(50, 500);
            
            $data = [
                'code' => 'PROMO' . strtoupper(substr(md5(rand()), 0, 6)),
                'discount_amount' => $amount,
                'discount_type' => $type,
                'usage_limit' => rand(5, 100),
                'usage_count' => 0,
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            $this->wpdb->insert(
                $this->wpdb->prefix . 'edc_school_promo_codes',
                $data
            );
        }
    }
}
