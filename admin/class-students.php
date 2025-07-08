<?php
namespace SchoolManagerPro\Admin;

defined('ABSPATH') || exit;

class Students {
    /**
     * @var Students_List
     */
    private $students_list;
    
    /**
     * @var bool Whether the class has been initialized
     */
    private $initialized = false;
    
    /**
     * Constructor
     * 
     * @param bool $register_menu Whether to register the admin menu
     */
    public function __construct($register_menu = true) {
        // Only initialize once
        if ($this->initialized) {
            return;
        }
        
        // Register menu if needed
        if ($register_menu) {
            add_action('admin_menu', [$this, 'add_menu_page']);
        }
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Mark as initialized
        $this->initialized = true;
    }
    
    /**
     * Add menu page
     */
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        $hook = add_submenu_page(
            'school-manager-pro',
            __('Students', 'school-manager-pro'),
            __('Students', 'school-manager-pro'),
            'manage_options',
            'school-manager-students',
            [$this, 'render_page']
        );
        
        add_action("load-$hook", [$this, 'screen_option']);
    }
    
    /**
     * Screen options
     */
    public function screen_option() {
        $option = 'per_page';
        $args   = [
            'label'   => 'Students per page',
            'default' => 20,
            'option'  => 'students_per_page'
        ];

        add_screen_option($option, $args);
        
        // Initialize the Students_List class
        $this->students_list = new Students_List();
        
        // Process any bulk actions
        $this->students_list->process_bulk_action();
        
        // Prepare the items
        $this->students_list->prepare_items();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        global $wp_scripts;
        
        // Check both the old and new page hooks since there might be inconsistencies
        $valid_hooks = array(
            'school-manager_page_school-manager-students',
            'school-manager-pro_page_school-manager-students',
            'toplevel_page_school-manager-students'
        );
        
        // Debug hook information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Current hook: ' . $hook);
            error_log('Screen ID: ' . (get_current_screen() ? get_current_screen()->id : 'unknown'));
        }
        
        // Adjust the condition to be more flexible with different hook formats
        if (!in_array($hook, $valid_hooks) && strpos($hook, 'school-manager-students') === false) {
            return;
        }
        
        // Enqueue WordPress scripts and styles first
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Enqueue our custom admin script to fix the "New" button refresh issue
        wp_enqueue_script(
            'smp-admin-js',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/smp-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Get the jQuery UI version
        $jquery_ui_version = isset($wp_scripts->registered['jquery-ui-core']->ver) 
            ? $wp_scripts->registered['jquery-ui-core']->ver 
            : '1.12.1';
        
        // Enqueue jQuery UI theme
        wp_enqueue_style(
            'jquery-ui-theme-smoothness',
            sprintf('//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $jquery_ui_version),
            [],
            $jquery_ui_version
        );
        
        // Enqueue Select2 for better dropdowns
        $select2_version = '4.1.0-rc.0';
        
        wp_enqueue_script(
            'select2',
            sprintf('https://cdn.jsdelivr.net/npm/select2@%s/dist/js/select2.min.js', $select2_version),
            ['jquery'],
            $select2_version,
            true
        );
        
        wp_enqueue_style(
            'select2',
            sprintf('https://cdn.jsdelivr.net/npm/select2@%s/dist/css/select2.min.css', $select2_version),
            [],
            $select2_version
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'smp-admin',
            plugins_url('assets/css/admin.css', dirname(__FILE__)),
            [],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin.css')
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'smp-admin',
            plugins_url('assets/js/admin.js', dirname(__FILE__)),
            ['jquery', 'jquery-ui-datepicker', 'select2'],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin.js'),
            true
        );
        
        // Localize script with translations if needed
        wp_localize_script('smp-admin', 'smp_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('smp-admin-nonce'),
        ]);
    }
    
    /**
     * Render the admin page
     */
    public function render_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show import results if available
        $this->show_import_results();
        
        // Get the current action
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        // Handle different actions
        switch ($action) {
            case 'add':
            case 'edit':
                $this->render_student_form();
                break;
            case 'import':
                $this->render_import_form();
                break;
            default:
                $this->render_list();
                break;
        }
    }
    
    /**
     * Show import results if available
     */
    private function show_import_results() {
        if (!isset($_GET['imported'])) {
            return;
        }
        
        $transient_key = 'smp_import_results_' . get_current_user_id();
        $results = get_transient($transient_key);
        
        if (false === $results) {
            return;
        }
        
        // Delete the transient so we don't show the message again
        delete_transient($transient_key);
        
        $class = 'notice notice-success';
        $message = sprintf(
            _n(
                'Successfully imported %d student.',
                'Successfully imported %d students.',
                $results['inserted'] + $results['updated'],
                'school-manager-pro'
            ),
            $results['inserted'] + $results['updated']
        );
        
        if ($results['skipped'] > 0) {
            $message .= ' ' . sprintf(
                _n(
                    '%d record was skipped.',
                    '%d records were skipped.',
                    $results['skipped'],
                    'school-manager-pro'
                ),
                $results['skipped']
            );
            
            if (!empty($results['errors'])) {
                $class = 'notice notice-warning';
                $message .= ' ' . __('Some errors occurred:', 'school-manager-pro');
                $message .= '<ul style="margin: 0.5em 0; padding-left: 2em; list-style-type: disc;">';
                foreach ($results['errors'] as $error) {
                    $message .= '<li>' . esc_html($error) . '</li>';
                }
                $message .= '</ul>';
            }
        }
        
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
    }
    
    /**
     * Render the import form
     */
    private function render_import_form() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('Import Students', 'school-manager-pro'); ?></h2>
                
                <div class="inside">
                    <p><?php esc_html_e('Upload a CSV file containing students to import.', 'school-manager-pro'); ?></p>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: Download template link */
                            __('Need a template? %s', 'school-manager-pro'),
                            sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(wp_nonce_url(admin_url('admin.php?page=school-manager-students&smp_download_template=students'), 'smp_download_template_students')),
                                esc_html__('Download the CSV template', 'school-manager-pro')
                            )
                        );
                        ?>
                    </p>
                    
                    <form method="post" enctype="multipart/form-data" action="">
                        <?php wp_nonce_field('smp_import_students'); ?>
                        <input type="hidden" name="smp_import" value="students" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php esc_html_e('CSV File', 'school-manager-pro'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".csv" required />
                                    <p class="description">
                                        <?php esc_html_e('Upload a CSV file containing students. The first row should be column headers.', 'school-manager-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="import_behavior"><?php esc_html_e('If student exists', 'school-manager-pro'); ?></label>
                                </th>
                                <td>
                                    <select name="import_behavior" id="import_behavior">
                                        <option value="skip"><?php esc_html_e('Skip the row', 'school-manager-pro'); ?></option>
                                        <option value="update"><?php esc_html_e('Update existing student', 'school-manager-pro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="send_welcome_email"><?php esc_html_e('Welcome Email', 'school-manager-pro'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="send_welcome_email" id="send_welcome_email" value="1" />
                                        <?php esc_html_e('Send welcome email to new students', 'school-manager-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Import Students', 'school-manager-pro'), 'primary', 'submit', false); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=school-manager-students')); ?>" class="button">
                            <?php esc_html_e('Cancel', 'school-manager-pro'); ?>
                        </a>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the students list
     */
    private function render_list() {
        // Output the list table
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=school-manager-students&action=add')); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=school-manager-students&action=import')); ?>" class="page-title-action">
                <?php esc_html_e('Import', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=school-manager-students&smp_export=students'), 'smp_export_students')); ?>" class="page-title-action">
                <?php esc_html_e('Export', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=school-manager-students&smp_download_template=students'), 'smp_download_template_students')); ?>" class="page-title-action">
                <?php esc_html_e('Download Template', 'school-manager-pro'); ?>
            </a>
            <hr class="wp-header-end">
            
            <?php $this->students_list->views(); ?>
            
            <form method="get">
                <input type="hidden" name="page" value="smp-students" />
                <?php 
                $this->students_list->search_box(__('Search Students', 'school-manager-pro'), 'student-search');
                $this->students_list->display(); 
                ?>
            </form>
            
            <div class="smp-import-export-options" style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3><?php esc_html_e('Export Options', 'school-manager-pro'); ?></h3>
                <form method="get" action="" id="smp-export-form">
                    <input type="hidden" name="page" value="school-manager-students" />
                    <input type="hidden" name="smp_export" value="students" />
                    <?php wp_nonce_field('smp_export_students'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="export_start_date"><?php esc_html_e('Start Date', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="export_start_date" name="start_date" class="smp-datepicker" autocomplete="off" />
                                <p class="description"><?php esc_html_e('Export students created on or after this date.', 'school-manager-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="export_end_date"><?php esc_html_e('End Date', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="export_end_date" name="end_date" class="smp-datepicker" autocomplete="off" />
                                <p class="description"><?php esc_html_e('Export students created on or before this date.', 'school-manager-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="export_status"><?php esc_html_e('Status', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <select id="export_status" name="status">
                                    <option value=""><?php esc_html_e('All Statuses', 'school-manager-pro'); ?></option>
                                    <option value="active"><?php esc_html_e('Active', 'school-manager-pro'); ?></option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'school-manager-pro'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="export_class"><?php esc_html_e('Class', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <select id="export_class" name="class_id" class="smp-select2" style="width: 50%;">
                                    <option value=""><?php esc_html_e('All Classes', 'school-manager-pro'); ?></option>
                                    <?php
                                    global $wpdb;
                                    $classes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}edc_school_classes WHERE status = 'active' ORDER BY name");
                                    foreach ($classes as $class) {
                                        echo sprintf(
                                            '<option value="%d">%s</option>',
                                            esc_attr($class->id),
                                            esc_html($class->name)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Export Students', 'school-manager-pro'), 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Initialize datepicker
                $('.smp-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-10:+5'
                });
                
                // Initialize Select2
                if ($.fn.select2) {
                    $('.smp-select2').select2({
                        placeholder: '<?php echo esc_js(__('Select a class', 'school-manager-pro')); ?>',
                        allowClear: true,
                        width: 'resolve'
                    });
                }
                
                // Handle form submission for export with filters
                $('#smp-export-form').on('submit', function(e) {
                    var form = $(this);
                    var url = form.attr('action') || window.location.href.split('?')[0];
                    var params = form.serialize();
                    
                    // Open export in new tab
                    window.open(url + '&' + params, '_blank');
                    
                    // Prevent default form submission
                    e.preventDefault();
                    return false;
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Render student form
     */
    private function render_student_form() {
        include_once SMP_PATH . 'admin/views/student-form.php';
    }
    
    /**
     * Save student
     */
    private function save_student() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'smp_save_student')) {
            wp_die(__('Security check failed.', 'school-manager-pro'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'school-manager-pro'));
        }
        
        global $wpdb;
        
        $student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';
        $promo_code = !empty($_POST['promo_code']) ? sanitize_text_field($_POST['promo_code']) : '';
        $password = !empty($_POST['password']) ? $_POST['password'] : '';
        $classes = isset($_POST['classes']) ? array_map('absint', $_POST['classes']) : [];
        
        // Basic validation
        $errors = [];
        if (empty($first_name)) {
            $errors[] = __('First name is required.', 'school-manager-pro');
        }
        if (empty($last_name)) {
            $errors[] = __('Last name is required.', 'school-manager-pro');
        }
        if (empty($email) || !is_email($email)) {
            $errors[] = __('A valid email address is required.', 'school-manager-pro');
        }
        if (empty($mobile)) {
            $errors[] = __('Mobile number is required.', 'school-manager-pro');
        }
        if (!$student_id && empty($password)) {
            $errors[] = __('Password is required for new students.', 'school-manager-pro');
        }
        
        // Check for duplicate email
        $email_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}edc_school_students WHERE email = %s AND id != %d",
            $email,
            $student_id
        ));
        
        if ($email_exists) {
            $errors[] = __('A student with this email address already exists.', 'school-manager-pro');
        }
        
        // Check for duplicate mobile (username)
        $mobile_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}edc_school_students WHERE mobile = %s AND id != %d",
            $mobile,
            $student_id
        ));
        
        if ($mobile_exists) {
            $errors[] = __('A student with this mobile number already exists.', 'school-manager-pro');
        }
        
        // If there are validation errors, show them and stop
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_notice($error, 'error');
            }
            wp_redirect(add_query_arg(['action' => $student_id ? 'edit' : 'add', 'id' => $student_id], 'admin.php?page=school-manager-students'));
            exit;
        }
        
        // Prepare student data
        $student_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'mobile'     => $mobile,
            'status'     => $status,
            'promo_code' => $promo_code,
            'updated_at' => current_time('mysql')
        ];
        
        // Handle password if provided
        if (!empty($password)) {
            $student_data['password'] = wp_hash_password($password);
        }
        
        if ($student_id) {
            // Update existing student
            $wpdb->update(
                $wpdb->prefix . 'edc_school_students',
                $student_data,
                ['id' => $student_id],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            $message = __('Student updated successfully.', 'school-manager-pro');
        } else {
            // Add new student
            $student_data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $wpdb->prefix . 'edc_school_students',
                $student_data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            $student_id = $wpdb->insert_id;
            $message = __('Student added successfully.', 'school-manager-pro');
        }
        
        // Update class enrollments if we have a valid student ID
        if ($student_id) {
            // Remove existing class enrollments
            $wpdb->delete(
                $wpdb->prefix . 'edc_school_class_students',
                ['student_id' => $student_id],
                ['%d']
            );
            
            // Add new class enrollments
            foreach ($classes as $class_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'edc_school_class_students',
                    [
                        'class_id' => $class_id,
                        'student_id' => $student_id,
                        'enrolled_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s']
                );
            }
            
            // Clear any related caches if needed
            // ...
        }
        
        $this->add_notice($message, 'success');
        wp_redirect('admin.php?page=school-manager-students');
        exit;
    }
    
    /**
     * Delete student
     */
    private function delete_student() {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_student_' . $_GET['id'])) {
            wp_die(__('Security check failed.', 'school-manager-pro'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'school-manager-pro'));
        }
        
        global $wpdb;
        
        $student_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        
        if ($student_id) {
            // Delete student from classes
            $wpdb->delete(
                $wpdb->prefix . 'edc_school_class_students',
                ['student_id' => $student_id],
                ['%d']
            );
            
            // Delete the student
            $wpdb->delete(
                $wpdb->prefix . 'edc_school_students',
                ['id' => $student_id],
                ['%d']
            );
            
            $this->add_notice(__('Student deleted successfully.', 'school-manager-pro'), 'success');
        }
        
        wp_redirect('admin.php?page=school-manager-students');
        exit;
    }
    
    /**
     * Display admin notices
     */
    private function display_notices() {
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $type = isset($_GET['message_type']) ? sanitize_text_field($_GET['message_type']) : 'success';
            ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Add admin notice
     */
    private function add_notice($message, $type = 'success') {
        add_action('admin_notices', function() use ($message, $type) {
            ?>
            <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        });
    }
}
