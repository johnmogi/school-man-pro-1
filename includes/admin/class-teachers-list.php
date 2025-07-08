<?php
namespace SchoolManagerPro\Admin;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Teachers_List extends WP_List_Table {
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
            'singular' => 'teacher',
            'plural'   => 'teachers',
            'ajax'     => false
        ]);
    }
    
    /**
     * Get columns
     */
    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'id'          => __('ID', 'school-manager-pro'),
            'email'       => __('Email', 'school-manager-pro'),
            'mobile'      => __('Mobile', 'school-manager-pro'),
            'classes'     => __('Classes', 'school-manager-pro'),
            'status'      => __('Status', 'school-manager-pro'),
            'created_at'  => __('Date Added', 'school-manager-pro'),
        ];
    }
    
    /**
     * Get sortable columns
     */
    protected function get_sortable_columns() {
        return [
            'id'         => ['id', false],
            'email'      => ['email', false],
            'mobile'     => ['mobile', false],
            'created_at' => ['created_at', true],
        ];
    }
    
    /**
     * Column default
     */
    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'email':
            case 'mobile':
                return $item->$column_name ?: '—';
                
            case 'status':
                return $item->status === 'active'
                    ? '<span class="smp-status-active">' . __('Active', 'school-manager-pro') . '</span>'
                    : '<span class="smp-status-inactive">' . __('Inactive', 'school-manager-pro') . '</span>';
                
            case 'classes':
                return $this->get_teacher_classes($item->id);
                
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
            '<input type="checkbox" name="teacher[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Column email
     */
    protected function column_email($item) {
        $actions = [
            'edit'   => sprintf(
                '<a href="?page=smp-teachers&action=edit&id=%d">%s</a>',
                $item->id,
                __('Edit', 'school-manager-pro')
            ),
            'delete' => sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                wp_nonce_url(
                    add_query_arg(
                        [
                            'page'   => 'smp-teachers',
                            'action' => 'delete',
                            'id'     => $item->id,
                        ],
                        admin_url('admin.php')
                    ),
                    'delete_teacher_' . $item->id
                ),
                esc_js(__('Are you sure you want to delete this teacher? This action cannot be undone.', 'school-manager-pro')),
                __('Delete', 'school-manager-pro')
            ),
        ];
        
        // Filter row actions
        $actions = apply_filters('smp_teacher_row_actions', $actions, $item);
        
        return sprintf(
            '<strong><a href="?page=smp-teachers&action=edit&id=%d" class="row-title">%s</a></strong>%s',
            $item->id,
            $item->email,
            $this->row_actions($actions)
        );
    }
    
    /**
     * Get teacher classes
     */
    private function get_teacher_classes($teacher_id) {
        $classes = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT name, COUNT(DISTINCT cs.student_id) as student_count 
             FROM {$this->wpdb->prefix}edc_school_classes c
             LEFT JOIN {$this->wpdb->prefix}edc_school_class_students cs ON c.id = cs.class_id
             WHERE c.teacher_id = %d
             GROUP BY c.id",
            $teacher_id
        ));
        
        if (empty($classes)) {
            return '—';
        }
        
        $class_list = [];
        foreach ($classes as $class) {
            $class_list[] = sprintf('%s (%d)', $class->name, $class->student_count);
        }
        
        return implode(', ', $class_list);
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        $per_page = $this->get_items_per_page('teachers_per_page', 20);
        $current_page = $this->get_pagenum();
        
        // Handle bulk actions
        $this->process_bulk_action();
        
        // Get data
        $data = $this->get_teachers($per_page, $current_page);
        
        // Set pagination
        $this->set_pagination_args([
            'total_items' => $this->record_count(),
            'per_page'    => $per_page,
        ]);
        
        $this->items = $data;
    }
    
    /**
     * Get teachers
     */
    private function get_teachers($per_page = 20, $page_number = 1) {
        $sql = "SELECT * FROM {$this->wpdb->prefix}edc_school_teachers";
        
        // Handle search
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $this->wpdb->prepare(" WHERE email LIKE '%%%s%%' OR mobile LIKE '%%%s%%'", $search, $search);
        }
        
        // Handle order by
        if (!empty($_REQUEST['orderby'])) {
            $orderby = sanitize_sql_orderby($_REQUEST['orderby']);
            $order = (!empty($_REQUEST['order']) && strtoupper($_REQUEST['order']) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $orderby $order";
        } else {
            $sql .= ' ORDER BY created_at DESC';
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
        $sql = "SELECT COUNT(*) FROM {$this->wpdb->prefix}edc_school_teachers";
        
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $sql .= $this->wpdb->prepare(" WHERE email LIKE '%%%s%%' OR mobile LIKE '%%%s%%'", $search, $search);
        }
        
        return $this->wpdb->get_var($sql);
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
        
        return apply_filters('smp_teacher_bulk_actions', $actions);
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
            
            if (wp_verify_nonce($nonce, 'delete_teacher_' . $id)) {
                $this->delete_teacher($id);
                
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
            
            $teacher_ids = isset($_POST['teacher']) ? array_map('absint', $_POST['teacher']) : [];
            
            if (empty($teacher_ids)) {
                return;
            }
            
            switch ($action) {
                case 'bulk-delete':
                    foreach ($teacher_ids as $id) {
                        $this->delete_teacher($id);
                    }
                    break;
                    
                case 'bulk-activate':
                    $this->update_teachers_status($teacher_ids, 'active');
                    break;
                    
                case 'bulk-deactivate':
                    $this->update_teachers_status($teacher_ids, 'inactive');
                    break;
            }
            
            wp_redirect(remove_query_arg(['action', 'action2', 'teacher', '_wpnonce']));
            exit;
        }
    }
    
    /**
     * Delete teacher
     */
    private function delete_teacher($id) {
        // First, unassign any classes from this teacher
        $this->wpdb->update(
            $this->wpdb->prefix . 'edc_school_classes',
            ['teacher_id' => null],
            ['teacher_id' => $id],
            ['%d'],
            ['%d']
        );
        
        // Then delete the teacher
        $this->wpdb->delete(
            $this->wpdb->prefix . 'smp_teachers',
            ['id' => $id],
            ['%d']
        );
    }
    
    /**
     * Update teachers status
     */
    private function update_teachers_status($ids, $status) {
        $ids = array_map('absint', $ids);
        $ids = implode(',', $ids);
        
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}smp_teachers SET status = %s WHERE id IN ($ids)",
                $status
            )
        );
    }
    
    /**
     * No items found text
     */
    public function no_items() {
        _e('No teachers found.', 'school-manager-pro');
    }
}
