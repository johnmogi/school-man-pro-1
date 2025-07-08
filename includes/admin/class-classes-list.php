<?php
namespace SchoolManagerPro\Admin;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Classes_List extends WP_List_Table {
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
            'singular' => 'class',
            'plural'   => 'classes',
            'ajax'     => false
        ]);
    }
    
    /**
     * Get columns
     */
    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'name'        => __('Class Name', 'school-manager-pro'),
            'teacher'     => __('Teacher', 'school-manager-pro'),
            'students'    => __('Students', 'school-manager-pro'),
            'status'      => __('Status', 'school-manager-pro'),
            'created_at'  => __('Date Added', 'school-manager-pro'),
        ];
    }
    
    /**
     * Get sortable columns
     */
    protected function get_sortable_columns() {
        return [
            'name'       => ['name', false],
            'created_at' => ['created_at', true],
        ];
    }
    
    /**
     * Column default
     */
    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                return $item->name ?: '—';
                
            case 'teacher':
                return $this->get_teacher_name($item->teacher_id);
                
            case 'students':
                return $this->get_student_count($item->id);
                
            case 'status':
                return $item->status === 'active'
                    ? '<span class="smp-status-active">' . __('Active', 'school-manager-pro') . '</span>'
                    : '<span class="smp-status-inactive">' . __('Inactive', 'school-manager-pro') . '</span>';
                
            case 'created_at':
                return date_i18n(get_option('date_format'), strtotime($item->created_at));
                
            default:
                return print_r($item, true);
        }
    }
    
    /**
     * Column cb
     */
    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="class[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Column name
     */
    protected function column_name($item) {
        $actions = [
            'edit'   => sprintf(
                '<a href="?page=smp-classes&action=edit&id=%d">%s</a>',
                $item->id,
                __('Edit', 'school-manager-pro')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                wp_nonce_url(
                    add_query_arg(
                        [
                            'page'   => 'smp-classes',
                            'action' => 'delete',
                            'id'     => $item->id,
                        ],
                        admin_url('admin.php')
                    ),
                    'delete_class_' . $item->id
                ),
                esc_js(__('Are you sure you want to delete this class?', 'school-manager-pro')),
                __('Delete', 'school-manager-pro')
            ),
        ];
        
        return sprintf(
            '<strong><a href="?page=smp-classes&action=edit&id=%d">%s</a></strong>%s',
            $item->id,
            $item->name,
            $this->row_actions($actions)
        );
    }
    
    /**
     * Get teacher name
     */
    private function get_teacher_name($teacher_id) {
        if (empty($teacher_id)) {
            return '—';
        }
        
        $teacher = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT email, mobile as name 
             FROM {$this->wpdb->prefix}edc_school_teachers 
             WHERE id = %d",
            $teacher_id
        ));
        
        if (!$teacher) {
            return '—';
        }
        
        return sprintf('%s (%s)', $teacher->name, $teacher->email);
    }
    
    /**
     * Get student count for a class
     */
    private function get_student_count($class_id) {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$this->wpdb->prefix}edc_school_class_students 
             WHERE class_id = %d",
            $class_id
        ));
        
        return $count ?: '—';
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        $per_page = $this->get_items_per_page('classes_per_page', 20);
        $current_page = $this->get_pagenum();
        
        // Handle bulk actions
        $this->process_bulk_action();
        
        // Get data
        $data = $this->get_classes($per_page, $current_page);
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $this->record_count(),
            'per_page'    => $per_page,
        ]);
        
        $this->items = $data;
    }
    
    /**
     * Get classes
     */
    private function get_classes($per_page = 20, $page_number = 1) {
        $sql = "SELECT c.*, t.email, t.mobile 
                FROM {$this->wpdb->prefix}edc_school_classes c
                LEFT JOIN {$this->wpdb->prefix}edc_school_teachers t ON c.teacher_id = t.id";
        
        // Handle search
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $this->wpdb->prepare(" 
                WHERE c.name LIKE '%%%s%%' 
                OR t.first_name LIKE '%%%s%%' 
                OR t.last_name LIKE '%%%s%%' 
                OR t.email LIKE '%%%s%%'
            ", $search, $search, $search, $search);
        }
        
        // Handle order by
        if (!empty($_REQUEST['orderby'])) {
            $orderby = sanitize_sql_orderby($_REQUEST['orderby']);
            $order = (!empty($_REQUEST['order']) && strtoupper($_REQUEST['order']) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $orderby $order";
        } else {
            $sql .= ' ORDER BY c.created_at DESC';
        }
        
        // Add pagination
        $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", 
            $per_page,
            ($page_number - 1) * $per_page
        );
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get record count
     */
    public function record_count() {
        $sql = "SELECT COUNT(*) 
                FROM {$this->wpdb->prefix}edc_school_classes c";
        
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $this->wpdb->prepare(" 
                LEFT JOIN {$this->wpdb->prefix}edc_school_teachers t ON c.teacher_id = t.id
                WHERE c.name LIKE '%%%s%%' 
                OR t.first_name LIKE '%%%s%%' 
                OR t.last_name LIKE '%%%s%%' 
                OR t.email LIKE '%%%s%%'
            ", $search, $search, $search, $search);
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    /**
     * Get bulk actions
     */
    protected function get_bulk_actions() {
        return [
            'bulk-activate'   => __('Activate', 'school-manager-pro'),
            'bulk-deactivate' => __('Deactivate', 'school-manager-pro'),
            'bulk-delete'     => __('Delete', 'school-manager-pro'),
        ];
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Single delete
        if ('delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
            $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : 0;
            
            if (wp_verify_nonce($nonce, 'delete_class_' . $id)) {
                $this->delete_class($id);
                
                wp_redirect(remove_query_arg(['action', 'id', '_wpnonce']));
                exit;
            }
        }
        
        // Bulk actions
        if ((isset($_POST['action']) && in_array($_POST['action'], array_keys($this->get_bulk_actions())))
            || (isset($_POST['action2']) && in_array($_POST['action2'], array_keys($this->get_bulk_actions())))
        ) {
            $action = isset($_POST['action']) && -1 != $_POST['action'] 
                ? $_POST['action'] 
                : $_POST['action2'];
            
            $class_ids = isset($_POST['class']) ? array_map('absint', $_POST['class']) : [];
            
            if (empty($class_ids)) {
                return;
            }
            
            switch ($action) {
                case 'bulk-delete':
                    foreach ($class_ids as $id) {
                        $this->delete_class($id);
                    }
                    break;
                    
                case 'bulk-activate':
                    $this->update_classes_status($class_ids, 'active');
                    break;
                    
                case 'bulk-deactivate':
                    $this->update_classes_status($class_ids, 'inactive');
                    break;
            }
            
            wp_redirect(remove_query_arg(['action', 'action2', 'class', '_wpnonce']));
            exit;
        }
    }
    
    /**
     * Delete class
     */
    private function delete_class($id) {
        // First, delete class-student relationships
        $this->wpdb->delete(
            $this->wpdb->prefix . 'edc_school_class_students',
            ['class_id' => $id],
            ['%d']
        );
        
        // Then delete the class
        $this->wpdb->delete(
            $this->wpdb->prefix . 'edc_school_classes',
            ['id' => $id],
            ['%d']
        );
    }
    
    /**
     * Update classes status
     */
    private function update_classes_status($ids, $status) {
        $ids = array_map('absint', $ids);
        $ids = implode(',', $ids);
        
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}edc_school_classes SET status = %s WHERE id IN ($ids)",
                $status
            )
        );
    }
    
    /**
     * No items found text
     */
    public function no_items() {
        _e('No classes found.', 'school-manager-pro');
    }
}
