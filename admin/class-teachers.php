<?php
namespace SchoolManagerPro\Admin;

defined('ABSPATH') || exit;

class Teachers {
    /**
     * @var Teachers_List
     */
    private $teachers_list;
    
    /**
     * Constructor
     * 
     * @param bool $register_menu Whether to register the admin menu
     */
    public function __construct($register_menu = true) {
        if ($register_menu) {
            add_action('admin_menu', [$this, 'add_menu_page']);
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        $hook = add_submenu_page(
            'school-manager-pro',
            __('Teachers', 'school-manager-pro'),
            __('Teachers', 'school-manager-pro'),
            'manage_options',
            'smp-teachers',
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
            'label'   => 'Teachers per page',
            'default' => 20,
            'option'  => 'teachers_per_page'
        ];

        add_screen_option($option, $args);
        
        require_once SMP_PATH . 'includes/admin/class-teachers-list.php';
        $this->teachers_list = new Teachers_List();
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        global $wp_scripts;
        
        if ('school-manager-pro_page_smp-teachers' !== $hook) {
            return;
        }
        
        // Enqueue jQuery UI Datepicker and other dependencies
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Get the jQuery UI version
        $jquery_ui_version = isset($wp_scripts->registered['jquery-ui-core']->ver) 
            ? $wp_scripts->registered['jquery-ui-core']->ver 
            : '1.12.1';
        
        // Enqueue jQuery UI theme
        wp_enqueue_style(
            'jquery-ui-theme-smoothness',
            sprintf('//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $jquery_ui_version)
        );
        
        // Enqueue Select2 for better dropdowns
        wp_enqueue_script(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );
        
        wp_enqueue_style(
            'select2',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0-rc.0'
        );
        
        // Enqueue admin styles and scripts
        wp_enqueue_style('smp-admin');
        wp_enqueue_script(
            'smp-admin',
            plugins_url('assets/js/admin.js', dirname(__FILE__)),
            ['jquery', 'jquery-ui-datepicker', 'select2'],
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin.js'),
            true
        );
    }
    
    /**
     * Render page
     */
    public function render_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show import results if available
        $this->show_import_results();
        
        $action = isset($_REQUEST['action']) ? sanitize_text_field($_REQUEST['action']) : 'list';
        
        switch ($action) {
            case 'add':
            case 'edit':
                $this->render_teacher_form();
                break;
                
            case 'save':
                $this->save_teacher();
                break;
                
            case 'import':
                $this->render_import_form();
                break;
                
            default:
                $this->render_teacher_list();
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
                'Successfully imported %d teacher.',
                'Successfully imported %d teachers.',
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
                <h2><?php esc_html_e('Import Teachers', 'school-manager-pro'); ?></h2>
                
                <div class="inside">
                    <p><?php esc_html_e('Upload a CSV file containing teachers to import.', 'school-manager-pro'); ?></p>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: Download template link */
                            __('Need a template? %s', 'school-manager-pro'),
                            sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(wp_nonce_url(admin_url('admin.php?page=smp-teachers&smp_download_template=teachers'), 'smp_download_template_teachers')),
                                esc_html__('Download the CSV template', 'school-manager-pro')
                            )
                        );
                        ?>
                    </p>
                    
                    <form method="post" enctype="multipart/form-data" action="">
                        <?php wp_nonce_field('smp_import_teachers'); ?>
                        <input type="hidden" name="smp_import" value="teachers" />
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="import_file"><?php esc_html_e('CSV File', 'school-manager-pro'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="import_file" id="import_file" accept=".csv" required />
                                    <p class="description">
                                        <?php esc_html_e('Upload a CSV file containing teachers. The first row should be column headers.', 'school-manager-pro'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="import_behavior"><?php esc_html_e('If teacher exists', 'school-manager-pro'); ?></label>
                                </th>
                                <td>
                                    <select name="import_behavior" id="import_behavior">
                                        <option value="skip"><?php esc_html_e('Skip the row', 'school-manager-pro'); ?></option>
                                        <option value="update"><?php esc_html_e('Update existing teacher', 'school-manager-pro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Import Teachers', 'school-manager-pro'), 'primary', 'submit', false); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=smp-teachers')); ?>" class="button">
                            <?php esc_html_e('Cancel', 'school-manager-pro'); ?>
                        </a>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render teacher list
     */
    private function render_teacher_list() {
        $this->teachers_list->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=smp-teachers&action=add')); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=smp-teachers&action=import')); ?>" class="page-title-action">
                <?php esc_html_e('Import', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=smp-teachers&smp_export=teachers'), 'smp_export_teachers')); ?>" class="page-title-action">
                <?php esc_html_e('Export', 'school-manager-pro'); ?>
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=smp-teachers&smp_download_template=teachers'), 'smp_download_template_teachers')); ?>" class="page-title-action">
                <?php esc_html_e('Download Template', 'school-manager-pro'); ?>
            </a>
            
            <hr class="wp-header-end">
            
            <?php $this->display_notices(); ?>
            
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="smp-teachers" />
                <?php 
                $this->teachers_list->search_box(__('Search Teachers', 'school-manager-pro'), 'teacher-search');
                $this->teachers_list->display(); 
                ?>
            </form>
            
            <div class="smp-import-export-options" style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3><?php esc_html_e('Export Options', 'school-manager-pro'); ?></h3>
                <form method="get" action="" id="smp-export-form">
                    <input type="hidden" name="page" value="smp-teachers" />
                    <input type="hidden" name="smp_export" value="teachers" />
                    <?php wp_nonce_field('smp_export_teachers'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="export_start_date"><?php esc_html_e('Start Date', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="export_start_date" name="start_date" class="smp-datepicker" autocomplete="off" />
                                <p class="description"><?php esc_html_e('Export teachers created on or after this date.', 'school-manager-pro'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="export_end_date"><?php esc_html_e('End Date', 'school-manager-pro'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="export_end_date" name="end_date" class="smp-datepicker" autocomplete="off" />
                                <p class="description"><?php esc_html_e('Export teachers created on or before this date.', 'school-manager-pro'); ?></p>
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
                    </table>
                    
                    <?php submit_button(__('Export Teachers', 'school-manager-pro'), 'primary', 'submit', false); ?>
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
                        placeholder: '<?php echo esc_js(__('Select an option', 'school-manager-pro')); ?>',
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
            </script>        ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render teacher form
     */
    private function render_teacher_form() {
        include_once SMP_PATH . 'admin/views/teacher-form.php';
    }
    
    /**
     * Save teacher
     */
    private function save_teacher() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'smp_save_teacher')) {
            wp_die(__('Security check failed.', 'school-manager-pro'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'school-manager-pro'));
        }
        
        global $wpdb;
        
        $teacher_id = isset($_POST['teacher_id']) ? absint($_POST['teacher_id']) : 0;
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $mobile = sanitize_text_field($_POST['mobile']);
        $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';
        $classes = isset($_POST['classes']) ? array_map('absint', $_POST['classes']) : [];
        
        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $this->add_notice(__('Please fill in all required fields.', 'school-manager-pro'), 'error');
            wp_redirect(add_query_arg(['action' => $teacher_id ? 'edit' : 'add', 'id' => $teacher_id], 'admin.php?page=smp-teachers'));
            exit;
        }
        
        // Check if email already exists
        $email_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}smp_teachers WHERE email = %s AND id != %d",
            $email,
            $teacher_id
        ));
        
        if ($email_exists) {
            $this->add_notice(__('A teacher with this email already exists.', 'school-manager-pro'), 'error');
            wp_redirect(add_query_arg(['action' => $teacher_id ? 'edit' : 'add', 'id' => $teacher_id], 'admin.php?page=smp-teachers'));
            exit;
        }
        
        $teacher_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'mobile'     => $mobile,
            'status'     => $status,
            'updated_at' => current_time('mysql')
        ];
        
        if ($teacher_id) {
            // Update existing teacher
            $wpdb->update(
                $wpdb->prefix . 'smp_teachers',
                $teacher_data,
                ['id' => $teacher_id],
                ['%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            $message = __('Teacher updated successfully.', 'school-manager-pro');
        } else {
            // Add new teacher
            $teacher_data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $wpdb->prefix . 'smp_teachers',
                $teacher_data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
            $teacher_id = $wpdb->insert_id;
            $message = __('Teacher added successfully.', 'school-manager-pro');
        }
        
        // Update class assignments
        if ($teacher_id) {
            // Remove existing class assignments
            $wpdb->delete(
                $wpdb->prefix . 'smp_class_teachers',
                ['teacher_id' => $teacher_id],
                ['%d']
            );
            
            // Add new class assignments
            foreach ($classes as $class_id) {
                $wpdb->insert(
                    $wpdb->prefix . 'smp_class_teachers',
                    [
                        'class_id'   => $class_id,
                        'teacher_id' => $teacher_id,
                        'created_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s']
                );
            }
            
            // Clear any related caches if needed
            // ...
        }
        
        $this->add_notice($message, 'success');
        wp_redirect('admin.php?page=smp-teachers');
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
