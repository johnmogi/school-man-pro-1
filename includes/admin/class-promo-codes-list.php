<?php
/**
 * Promo Codes List Table
 *
 * @package School_Manager\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Promo_Codes_List_Table class.
 */
class Promo_Codes_List_Table extends WP_List_Table {

    /**
     * Initialize the promo codes list table.
     */
    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Promo Code', 'school-manager-pro' ),
            'plural'   => __( 'Promo Codes', 'school-manager-pro' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Get table columns.
     *
     * @return array
     */
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'code'          => __( 'Code', 'school-manager-pro' ),
            'description'   => __( 'Description', 'school-manager-pro' ),
            'discount_type' => __( 'Discount Type', 'school-manager-pro' ),
            'amount'        => __( 'Amount', 'school-manager-pro' ),
            'usage_limit'   => __( 'Usage / Limit', 'school-manager-pro' ),
            'expiry_date'   => __( 'Expires', 'school-manager-pro' ),
            'status'        => __( 'Status', 'school-manager-pro' ),
            'date_created'  => __( 'Created', 'school-manager-pro' ),
        ];
    }

    /**
     * Get sortable columns.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return [
            'code'         => [ 'code', false ],
            'date_created' => [ 'date_created', true ],
        ];
    }

    /**
     * Column default.
     *
     * @param object $item        Item.
     * @param string $column_name Column name.
     * @return string
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'code':
                $edit_url = add_query_arg( [
                    'page'   => 'school-manager-promo-codes',
                    'action' => 'edit',
                    'id'     => $item->id,
                ], admin_url( 'admin.php' ) );
                
                $actions = [
                    'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'school-manager-pro' ) ),
                    'delete' => sprintf( '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\')">%s</a>',
                        wp_nonce_url( add_query_arg( [ 'action' => 'delete', 'id' => $item->id ], admin_url( 'admin-post.php?action=smp_delete_promo_code' ) ), 'delete-promo-code_' . $item->id ),
                        esc_js( __( 'Are you sure you want to delete this promo code?', 'school-manager-pro' ) ),
                        __( 'Delete', 'school-manager-pro' )
                    ),
                ];
                
                return sprintf( '<strong><a href="%s" class="row-title">%s</a></strong>%s',
                    esc_url( $edit_url ),
                    esc_html( $item->code ),
                    $this->row_actions( $actions )
                );

            case 'discount_type':
                return $item->discount_type === 'percent' ? __( 'Percentage', 'school-manager-pro' ) : __( 'Fixed Amount', 'school-manager-pro' );

            case 'amount':
                return $item->discount_type === 'percent' ? $item->amount . '%' : wc_price( $item->amount );

            case 'usage_limit':
                $usage_count = $this->get_usage_count( $item->id );
                $usage_limit = $item->usage_limit ? $item->usage_limit : '∞';
                return sprintf( '%s / %s', $usage_count, $usage_limit );

            case 'expiry_date':
                return $item->expiry_date ? date_i18n( get_option( 'date_format' ), strtotime( $item->expiry_date ) ) : __( 'Never', 'school-manager-pro' );

            case 'status':
                $status = $this->is_expired( $item ) ? 'expired' : $item->status;
                $statuses = [
                    'active'   => [ 'label' => __( 'Active', 'school-manager-pro' ), 'class' => 'status-active' ],
                    'inactive' => [ 'label' => __( 'Inactive', 'school-manager-pro' ), 'class' => 'status-inactive' ],
                    'expired'  => [ 'label' => __( 'Expired', 'school-manager-pro' ), 'class' => 'status-expired' ],
                ];
                
                if ( isset( $statuses[ $status ] ) ) {
                    return sprintf( '<span class="%s">%s</span>', 
                        esc_attr( $statuses[ $status ]['class'] ),
                        esc_html( $statuses[ $status ]['label'] )
                    );
                }
                
                return '—';

            case 'date_created':
                return $item->date_created ? date_i18n( get_option( 'date_format' ), strtotime( $item->date_created ) ) : '—';

            default:
                return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '—';
        }
    }

    /**
     * Column cb.
     *
     * @param object $item Item.
     * @return string
     */
    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="promo_code_ids[]" value="%s" />', $item->id );
    }

    /**
     * Get bulk actions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return [
            'activate'   => __( 'Activate', 'school-manager-pro' ),
            'deactivate' => __( 'Deactivate', 'school-manager-pro' ),
            'delete'     => __( 'Delete', 'school-manager-pro' ),
        ];
    }

    /**
     * Process bulk actions.
     */
    public function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_promo_codes';

        // Single delete
        if ( 'delete' === $this->current_action() && ! empty( $_GET['id'] ) ) {
            check_admin_referer( 'delete-promo-code_' . absint( $_GET['id'] ) );
            $wpdb->delete( $table_name, [ 'id' => absint( $_GET['id'] ) ], [ '%d' ] );
            
            wp_redirect( add_query_arg( 'deleted', 1, admin_url( 'admin.php?page=school-manager-promo-codes' ) ) );
            exit;
        }

        // Bulk actions
        if ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ! empty( $_POST['promo_code_ids'] ) ) {
            $action = isset( $_POST['action'] ) && -1 != $_POST['action'] ? $_POST['action'] : $_POST['action2'];
            $ids    = array_map( 'absint', (array) $_POST['promo_code_ids'] );
            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

            switch ( $action ) {
                case 'activate':
                    $wpdb->query( $wpdb->prepare( 
                        "UPDATE $table_name SET status = 'active' WHERE id IN ($placeholders)",
                        $ids
                    ) );
                    break;

                case 'deactivate':
                    $wpdb->query( $wpdb->prepare( 
                        "UPDATE $table_name SET status = 'inactive' WHERE id IN ($placeholders)",
                        $ids
                    ) );
                    break;

                case 'delete':
                    $wpdb->query( $wpdb->prepare( 
                        "DELETE FROM $table_name WHERE id IN ($placeholders)",
                        $ids
                    ) );
                    break;
            }

            wp_redirect( add_query_arg( 'bulk_updated', count( $ids ), admin_url( 'admin.php?page=school-manager-promo-codes' ) ) );
            exit;
        }
    }

    /**
     * Prepare items.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_promo_codes';
        $per_page = $this->get_items_per_page( 'promo_codes_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        // Handle search
        $where = [];
        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = sanitize_text_field( $_REQUEST['s'] );
            $where[] = $wpdb->prepare( '(code LIKE %s OR description LIKE %s)', "%$search%", "%$search%" );
        }

        // Handle status filter
        if ( ! empty( $_REQUEST['status'] ) && in_array( $_REQUEST['status'], [ 'active', 'inactive', 'expired' ] ) ) {
            $status = sanitize_text_field( $_REQUEST['status'] );
            if ( 'expired' === $status ) {
                $where[] = "(expiry_date < NOW() AND expiry_date != '0000-00-00 00:00:00')";
            } else {
                $where[] = $wpdb->prepare( 'status = %s', $status );
            }
        }

        // Build WHERE clause
        $where = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // Get total items
        $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where" );

        // Get items
        $orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'date_created';
        $order = ( ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ) ? 'ASC' : 'DESC';
        
        $this->items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );

        // Set pagination
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    /**
     * Get views for the list table.
     *
     * @return array
     */
    protected function get_views() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_promo_codes';
        
        $status_links = [];
        $base_url = remove_query_arg( [ 'status', 'paged' ] );
        $current = isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'all';
        $counts = $wpdb->get_row( "
            SELECT 
                SUM(CASE WHEN status = 'active' OR (expiry_date > NOW() AND expiry_date != '0000-00-00 00:00:00') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' AND (expiry_date > NOW() OR expiry_date = '0000-00-00 00:00:00') THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN expiry_date < NOW() AND expiry_date != '0000-00-00 00:00:00' THEN 1 ELSE 0 END) as expired,
                COUNT(*) as total
            FROM $table_name
        ", ARRAY_A );

        // All link
        $class = 'all' === $current ? 'class="current"' : '';
        $status_links['all'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url( remove_query_arg( 'status', $base_url ) ),
            $class,
            __( 'All', 'school-manager-pro' ),
            number_format_i18n( $counts['total'] )
        );

        // Active link
        $class = 'active' === $current ? 'class="current"' : '';
        $status_links['active'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'status', 'active', $base_url ) ),
            $class,
            __( 'Active', 'school-manager-pro' ),
            number_format_i18n( $counts['active'] )
        );

        // Inactive link
        $class = 'inactive' === $current ? 'class="current"' : '';
        $status_links['inactive'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'status', 'inactive', $base_url ) ),
            $class,
            __( 'Inactive', 'school-manager-pro' ),
            number_format_i18n( $counts['inactive'] )
        );

        // Expired link
        $class = 'expired' === $current ? 'class="current"' : '';
        $status_links['expired'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'status', 'expired', $base_url ) ),
            $class,
            __( 'Expired', 'school-manager-pro' ),
            number_format_i18n( $counts['expired'] )
        );

        return $status_links;
    }

    /**
     * Check if a promo code is expired.
     *
     * @param object $promo_code Promo code object.
     * @return bool
     */
    protected function is_expired( $promo_code ) {
        if ( '0000-00-00 00:00:00' === $promo_code->expiry_date || empty( $promo_code->expiry_date ) ) {
            return false;
        }
        return strtotime( $promo_code->expiry_date ) < current_time( 'timestamp' );
    }

    /**
     * Get usage count for a promo code.
     *
     * @param int $promo_code_id Promo code ID.
     * @return int
     */
    protected function get_usage_count( $promo_code_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_students';
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE promo_code_id = %d",
            $promo_code_id
        ) );
    }
}
