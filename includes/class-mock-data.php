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
        
        for ($i = 0; $i < $count; $i++) {
            $first_name = $first_names[array_rand($first_names)];
            $last_name = $last_names[array_rand($last_names)];
            $mobile = '05' . rand(20, 59) . rand(100000, 999999);
            
            $username = strtolower($first_name . $last_name . ($i + 1));
            $email = strtolower($first_name . '.' . $last_name . ($i + 1) . '@example.com');
            $password = wp_hash_password('student123');
            
            $data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'phone' => $mobile,
                'student_id_number' => 'STU' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            // Add some optional fields for variety
            if (rand(0, 1)) {
                $cities = ['Jerusalem', 'Tel Aviv', 'Haifa', 'Beer Sheva', 'Eilat', 'Netanya'];
                $data['city'] = $cities[array_rand($cities)];
                $data['date_of_birth'] = date('Y-m-d', strtotime('-' . rand(18, 25) . ' years'));
            }
            
            $this->wpdb->insert(
                $this->wpdb->prefix . 'edc_school_students',
                $data
            );
            
            $student_ids[] = $this->wpdb->insert_id;
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
