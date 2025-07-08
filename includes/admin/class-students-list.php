<?php
namespace SchoolManagerPro\Admin;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Students_List extends WP_List_Table {
    /**
     * @var \wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        parent::__construct([
            'singular' => 'student',
            'plural'   => 'students',
            'ajax'     => false
        ]);
    }
    
    /**
     * Get columns
     */
    public function get_columns() {
        $columns = [
            'cb'                => '<input type="checkbox" />',
            'student_id_number' => __('Student ID', 'school-manager-pro'),
            'first_name'        => __('First Name', 'school-manager-pro'),
            'last_name'         => __('Last Name', 'school-manager-pro'),
            'email'             => __('Email', 'school-manager-pro'),
            'phone'             => __('Phone', 'school-manager-pro'),
            'status'            => __('Status', 'school-manager-pro'),
            'created_at'        => __('Date Created', 'school-manager-pro'),
        ];
        
        return $columns;
    }
    
    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return [
            'student_id_number' => ['student_id_number', true],
            'first_name'        => ['first_name', false],
            'last_name'         => ['last_name', false],
            'email'             => ['email', false],
            'phone'             => ['phone', false],
            'status'            => ['status', false],
            'created_at'        => ['created_at', true],
        ];
    }
    
    /**
     * Get bulk actions
     */
    protected function get_bulk_actions() {
        $actions = [
            'bulk-activate'   => __('Activate', 'school-manager-pro'),
            'bulk-deactivate' => __('Deactivate', 'school-manager-pro'),
            'bulk-delete'     => __('Delete', 'school-manager-pro'),
        ];
        
        return apply_filters('smp_student_bulk_actions', $actions);
    }
    
    /**
     * Get views for the status filter links
     */
    protected function get_views() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_students';
        
        $status_links = [];
        $current = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : 'all';
        
        // Get counts for each status
        $counts = $wpdb->get_results("
            SELECT 
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                COUNT(*) as total_count
            FROM $table_name
        ", ARRAY_A);
        
        $counts = !empty($counts) ? $counts[0] : ['active_count' => 0, 'inactive_count' => 0, 'total_count' => 0];
        
        // All link
        $class = 'all' === $current ? 'class="current"' : '';
        $url = remove_query_arg('status');
        $status_links['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url($url),
            $class,
            __('All', 'school-manager-pro'),
            number_format_i18n($counts['total_count'])
        );
        
        // Active link
        $class = 'active' === $current ? 'class="current"' : '';
        $url = add_query_arg('status', 'active');
        $status_links['active'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url($url),
            $class,
            __('Active', 'school-manager-pro'),
            number_format_i18n($counts['active_count'])
        );
        
        // Inactive link
        $class = 'inactive' === $current ? 'class="current"' : '';
        $url = add_query_arg('status', 'inactive');
        $status_links['inactive'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url($url),
            $class,
            __('Inactive', 'school-manager-pro'),
            number_format_i18n($counts['inactive_count'])
        );
        
        return $status_links;
    }
    
    /**
     * Extra controls to be displayed between bulk actions and pagination
     */
    protected function extra_tablenav($which) {
        if ('top' !== $which) {
            return;
        }
        
        global $wpdb;
        
        // Get all active classes for the filter dropdown
        $classes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}edc_school_classes WHERE status = 'active' ORDER BY name");
        $current_class = isset($_REQUEST['class_id']) ? absint($_REQUEST['class_id']) : '';
        
        if (!empty($classes)) :
            ?>
            <div class="alignleft actions">
                <label for="filter-by-class" class="screen-reader-text"><?php _e('Filter by class', 'school-manager-pro'); ?></label>
                <select name="class_id" id="filter-by-class">
                    <option value=""><?php _e('All Classes', 'school-manager-pro'); ?></option>
                    <?php foreach ($classes as $class) : ?>
                        <option value="<?php echo $class->id; ?>" <?php selected($current_class, $class->id); ?>><?php echo esc_html($class->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e('Filter', 'school-manager-pro'); ?>">
            </div>
            <?php
        endif;
    }
    
    /**
     * Add the "Add New" button to the page header
     */
    public function display_tablenav($which) {
        if ('top' === $which) {
            $add_new_url = add_query_arg([
                'page'   => 'school-manager-students',
                'action' => 'add'
            ], admin_url('admin.php'));
            
            echo '<div class="tablenav ' . esc_attr($which) . '">';
            echo '<div class="alignleft actions">';
            echo '<a href="' . esc_url($add_new_url) . '" class="button button-primary">' . __('Add New', 'school-manager-pro') . '</a>';
            echo '</div>';
            parent::display_tablenav($which);
            echo '</div>';
        } else {
            parent::display_tablenav($which);
        }
    }
    
    /**
     * Column - Student Code (primary identifier)
     */
    public function column_student_id_number($item) {
        $edit_url = add_query_arg([
            'page'   => 'school-manager-students',
            'action' => 'edit',
            'student' => $item->id
        ], admin_url('admin.php'));
        
        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                esc_url($edit_url),
                __('Edit', 'school-manager-pro')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\')">%s</a>',
                wp_nonce_url(
                    add_query_arg([
                        'page' => 'school-manager-students',
                        'action' => 'delete',
                        'student' => $item->id
                    ], admin_url('admin.php')),
                    'delete-student_' . $item->id
                ),
                esc_js(__('Are you sure you want to delete this student?', 'school-manager-pro')),
                __('Delete', 'school-manager-pro')
            )
        ];
        
        return sprintf(
            '<strong><a href="%1$s">%2$s</a></strong>%3$s',
            esc_url($edit_url),
            esc_html($item->student_id_number),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Column - First Name
     */
    public function column_first_name($item) {
        return !empty($item->first_name) ? esc_html($item->first_name) : '—';
    }
    
    /**
     * Column - Last Name
     */
    public function column_last_name($item) {
        return !empty($item->last_name) ? esc_html($item->last_name) : '—';
    }
    
    /**
     * Column - Email
     */
    public function column_email($item) {
        return !empty($item->email) ? esc_html($item->email) : '—';
    }
    
    /**
     * Column - Mobile
     */
    public function column_mobile($item) {
        return !empty($item->mobile) ? esc_html($item->mobile) : '—';
    }
    
    /**
     * Column - Status
     */
    public function column_status($item) {
        $status = isset($item->status) ? $item->status : 'active';
        $statuses = [
            'active'     => __('Active', 'school-manager-pro'),
            'inactive'   => __('Inactive', 'school-manager-pro'),
            'suspended'  => __('Suspended', 'school-manager-pro'),
        ];
        
        $status_label = isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
        $status_class = 'status-' . esc_attr($status);
        
        return sprintf(
            '<span class="%s">%s</span>',
            $status_class,
            esc_html($status_label)
        );
    }
    
    /**
     * Column - Default
     */
    public function column_default($item, $column_name) {
        // Handle custom column output
        switch ($column_name) {
            case 'student_id_number':
            case 'first_name':
            case 'last_name':
            case 'email':
            case 'phone':
            case 'created_at':
                return isset($item->$column_name) ? esc_html($item->$column_name) : '-';
            case 'status':
                return $this->column_status($item);
            default:
                // For any custom columns added via filters
                return apply_filters('smp_students_column_content', '', $column_name, $item);
        }
    }
    
    /**
     * Column cb
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="student[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Get student classes
     */
    private function get_student_classes($student_id) {
        $classes = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT c.name 
             FROM {$this->wpdb->prefix}edc_school_class_students cs
             JOIN {$this->wpdb->prefix}edc_school_classes c ON cs.class_id = c.id
             WHERE cs.student_id = %d",
            $student_id
        ));
        
        if (empty($classes)) {
            return '—';
        }
        
        return implode(', ', wp_list_pluck($classes, 'name'));
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_students';
        
        // Debug: Log table name and check if it exists
        error_log('School Manager Pro: Preparing items for table: ' . $table_name);
        
        // Check if the table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            error_log('School Manager Pro: Table does not exist: ' . $table_name);
            $this->items = [];
            return;
        }
        
        // Get the actual columns from the database
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
        $column_names = array_column($columns, 'Field');
        error_log('School Manager Pro: Actual columns in table: ' . print_r($column_names, true));
        error_log('School Manager Pro: Table structure: ' . print_r($columns, true));
        
        // Check for required columns
        $required_columns = ['id', 'student_id_number', 'first_name', 'last_name', 'email', 'phone', 'status', 'created_at'];
        $missing_columns = array_diff($required_columns, $column_names);
        
        if (!empty($missing_columns)) {
            error_log('School Manager Pro: WARNING - Missing required columns: ' . implode(', ', $missing_columns));
        } else {
            error_log('School Manager Pro: All required columns are present');
        }
        
        // Log available columns for debugging
        error_log('School Manager Pro: Available columns: ' . implode(', ', $column_names));
        
        // Get column names for validation
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = [];
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        // Debug: Log column names
        error_log('School Manager Pro: Column names: ' . print_r($column_names, true));
        error_log('School Manager Pro: Table columns: ' . print_r($column_names, true));
        
        // Debug: Count total students
        $total_students = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("School Manager Pro: Total students in database: $total_students");
        
        // Debug: Check table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = [];
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        error_log('School Manager Pro: Table columns: ' . print_r($column_names, true));
        
        // Debug: Count total students
        $total_students = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("School Manager Pro: Total students in database: $total_students");
        
        // Set up column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        
        // Debug: Log column headers and check against table columns
        error_log('School Manager Pro: Column headers: ' . print_r($this->_column_headers, true));
        
        // Debug: Check for missing columns
        $expected_columns = array_keys($columns);
        $missing_columns = array_diff($expected_columns, $column_names);
        if (!empty($missing_columns)) {
            error_log('School Manager Pro: WARNING - Missing expected columns: ' . implode(', ', $missing_columns));
        }
        
        // Debug: Check if we have the necessary capabilities
        if (!current_user_can('manage_options')) {
            error_log('School Manager Pro: Current user does not have manage_options capability');
        }
        
        // Process bulk actions
        $this->process_bulk_action();
        
        // Handle status filter
        if (isset($_REQUEST['status']) && in_array($_REQUEST['status'], ['active', 'inactive'])) {
            $status = sanitize_text_field($_REQUEST['status']);
            $query_where[] = $wpdb->prepare('status = %s', $status);
        }
        
        // Get search string
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Get order parameters
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'id';
        $order = isset($_REQUEST['order']) ? strtoupper(sanitize_text_field($_REQUEST['order'])) : 'DESC';
        $order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';
        
        // Set up pagination
        $per_page = $this->get_items_per_page('students_per_page', 10);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        // Debug: Log query parameters
        error_log("School Manager Pro: Query params - orderby: $orderby, order: $order, per_page: $per_page, offset: $offset, search: $search");
        
        // Build the base query - using direct column names from the table
        $query = [
            'select' => "SELECT *",
            'from'   => "FROM $table_name",
            'where'  => 'WHERE 1=1',
            'params' => []
        ];
        
        // Add search conditions - using actual column names from the table
        if (!empty($search)) {
            $search_conditions = [];
            $search_columns = ['id', 'first_name', 'last_name', 'mobile', 'promo_code'];
            
            foreach ($search_columns as $column) {
                if (in_array($column, $column_names)) {
                    $search_conditions[] = "$column LIKE %s";
                }
            }
            
            if (!empty($search_conditions)) {
                $query['where'] .= ' AND (' . implode(' OR ', $search_conditions) . ')';
                $search_term = '%' . $wpdb->esc_like($search) . '%';
                $query['params'] = array_merge($query['params'], array_fill(0, count($search_conditions), $search_term));
            }
        }
        
        // Get total items for pagination
        $count_query = "SELECT COUNT(*) {$query['from']} {$query['where']}";
        if (!empty($query['params'])) {
            $count_query = $wpdb->prepare($count_query, $query['params']);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Debug: Log the count query and result
        error_log("School Manager Pro: Count query: $count_query");
        error_log("School Manager Pro: Total items found: $total_items");
        
        // Add order by and limit for main query
        $orderby = in_array($orderby, $column_names) ? $orderby : 'id';
        
        // Ensure we have valid order direction
        $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';
        
        // Build the main query with proper escaping - don't prepare orderby/order as it needs to be a column name
        $main_query = "{$query['select']} {$query['from']} {$query['where']} ";
        $main_query .= "ORDER BY $orderby $order ";
        $main_query .= "LIMIT %d OFFSET %d";
        
        // Prepare the query with parameters
        $query_params = $query['params'];
        
        // Debug: Log the main query
        $debug_query = str_replace('\\0', '%s', $wpdb->prepare(
            $main_query,
            array_merge($query_params, [$per_page, $offset])
        ));
        
        error_log("School Manager Pro: Final query: $debug_query");
        
        // Run the main query - properly preparing it with all parameters
        $prepared_query = $wpdb->prepare(
            $main_query,
            array_merge($query_params, [$per_page, $offset])
        );
        $this->items = $wpdb->get_results($prepared_query);
        
        // Debug: Log the query and results
        error_log('School Manager Pro: Final query: ' . $debug_query);
        error_log('School Manager Pro: Query results count: ' . count((array)$this->items));
        
        // Debug: Log any database errors
        if ($wpdb->last_error) {
            error_log('School Manager Pro: Database error: ' . $wpdb->last_error);
            error_log('School Manager Pro: Last query: ' . $wpdb->last_query);
        }
        
        // Debug: Log first few items or indicate if empty
        if (!empty($this->items)) {
            $sample_items = array_slice((array)$this->items, 0, 3);
            error_log('School Manager Pro: Sample items from main query: ' . print_r($sample_items, true));
        } else {
            error_log('School Manager Pro: No items found in the main query results');
            
            // Try a simple query to verify data exists
            $simple_query = "SELECT * FROM $table_name LIMIT 5";
            $sample_data = $wpdb->get_results($simple_query);
            error_log('School Manager Pro: Simple query results: ' . print_r($sample_data, true));
            
            if ($wpdb->last_error) {
                error_log('School Manager Pro: Simple query error: ' . $wpdb->last_error);
            } else if (empty($sample_data)) {
                error_log('School Manager Pro: No data found in the students table');
            } else {
                error_log('School Manager Pro: Data exists in the table but not showing in the list. Possible column mismatch.');
            }
        }
        
        if (empty($this->items)) {
            // Try a simple query to see if we can get any data
            $simple_query = "SELECT * FROM $table_name LIMIT 5";
            $sample_data = $wpdb->get_results($simple_query);
            error_log('School Manager Pro: Simple query results: ' . print_r($sample_data, true));
            
            // Log any database errors
            if ($wpdb->last_error) {
                error_log('School Manager Pro: Database error: ' . $wpdb->last_error);
            }
        }
        
        // Set the pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Debug: Log final state
        error_log('School Manager Pro: prepare_items completed. Items found: ' . count((array)$this->items));
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        // Security check
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle single delete action
        if ('delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
            $id = isset($_REQUEST['student']) ? absint($_REQUEST['student']) : 0;
            
            if (wp_verify_nonce($nonce, 'delete-student_' . $id)) {
                $this->delete_student($id);
                
                // Redirect to avoid resubmission
                wp_redirect(remove_query_arg(['action', 'student', '_wpnonce', '_wp_http_referer']));
                exit;
            }
        }

        // Handle bulk actions
        if ((isset($_POST['action']) && -1 != $_POST['action']) || 
            (isset($_POST['action2']) && -1 != $_POST['action2'])) {
            
            // Get the action
            $action = isset($_POST['action']) && -1 != $_POST['action'] 
                ? $_POST['action'] 
                : $_POST['action2'];
            
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || 
                !wp_verify_nonce($_POST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
                return;
            }
            
            // Get the selected students
            $student_ids = isset($_POST['student']) ? array_map('absint', $_POST['student']) : [];
            
            if (empty($student_ids)) {
                return;
            }
            
            // Process the action
            switch ($action) {
                case 'bulk-delete':
                    foreach ($student_ids as $id) {
                        $this->delete_student($id);
                    }
                    break;
                    
                case 'bulk-activate':
                    $this->update_students_status($student_ids, 1);
                    break;
                    
                case 'bulk-deactivate':
                    $this->update_students_status($student_ids, 0);
                    break;
            }
            
            // Redirect to avoid resubmission
            wp_redirect(remove_query_arg(['action', 'action2', 'student', '_wpnonce', '_wp_http_referer']));
            exit;
        }
    }
    
    /**
     * Delete a student
     */
    private function delete_student($id) {
        global $wpdb;
        
        // Delete from students table
        $wpdb->delete(
            $wpdb->prefix . 'edc_school_students',
            ['id' => $id],
            ['%d']
        );
        
        // Remove from class relationships
        $wpdb->delete(
            $wpdb->prefix . 'edc_school_class_students',
            ['student_id' => $id],
            ['%d']
        );
    }
    
    /**
     * Update status for multiple students
     */
    private function update_students_status($student_ids, $status) {
        global $wpdb;
        
        if (empty($student_ids)) {
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($student_ids), '%d'));
        $query = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}edc_school_students SET is_active = %d WHERE id IN ($placeholders)",
            array_merge([$status], $student_ids)
        );
        
        // Execute the query
        $result = $wpdb->query($query);
        
        // Log result for debugging
        error_log("School Manager Pro: Updated {$result} student statuses to {$status}");
        
        // Redirect to avoid resubmission
        wp_redirect(remove_query_arg(['action', 'action2', 'student', '_wpnonce', '_wp_http_referer']));
        exit;
    }
}
