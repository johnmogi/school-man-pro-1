<?php
/**
 * Import/Export Handler
 *
 * @package School_Manager\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Import_Export_Handler class.
 */
class SMP_Import_Export_Handler {

    /**
     * Constructor.
     */
    public function __construct() {
        // Handle export requests
        add_action( 'admin_init', [ $this, 'handle_export' ] );
        
        // Handle import requests
        add_action( 'admin_init', [ $this, 'handle_import' ] );
        
        // Handle template download
        add_action( 'admin_init', [ $this, 'handle_template_download' ] );
    }
    
    /**
     * Handle export requests.
     */
    public function handle_export() {
        if ( ! isset( $_GET['smp_export'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'smp_export_' . $_GET['smp_export'] ) ) {
            wp_die( __( 'Invalid export request.', 'school-manager-pro' ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to export data.', 'school-manager-pro' ) );
        }
        
        $entity_type = sanitize_key( $_GET['smp_export'] );
        $entity_types = [ 'students', 'teachers', 'classes', 'promo_codes' ];
        
        if ( ! in_array( $entity_type, $entity_types, true ) ) {
            wp_die( __( 'Invalid export type.', 'school-manager-pro' ) );
        }
        
        // Get data based on entity type
        $data = $this->get_export_data( $entity_type );
        
        if ( is_wp_error( $data ) ) {
            wp_die( $data->get_error_message() );
        }
        
        // Get columns for the entity type
        $columns = SMP_CSV_Handler::get_columns( $entity_type );
        
        // Generate filename
        $filename = 'smp_' . $entity_type . '_export_' . date( 'Y-m-d' );
        
        // Export to CSV
        SMP_CSV_Handler::export( $data, $filename, $columns );
    }
    
    /**
     * Get data for export.
     *
     * @param string $entity_type Entity type.
     * @return array|WP_Error Array of data or WP_Error on failure.
     */
    protected function get_export_data( $entity_type ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'edc_school_' . $entity_type;
        
        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) !== $table_name ) {
            return new WP_Error( 'table_not_found', __( 'Table not found.', 'school-manager-pro' ) );
        }
        
        // Get data from the database
        $query = "SELECT * FROM $table_name";
        
        // Add WHERE clause if filtering
        $where = [];
        $query_params = [];
        
        // Handle date range filter
        if ( ! empty( $_GET['start_date'] ) ) {
            $start_date = sanitize_text_field( $_GET['start_date'] );
            $where[] = 'date_created >= %s';
            $query_params[] = $start_date . ' 00:00:00';
        }
        
        if ( ! empty( $_GET['end_date'] ) ) {
            $end_date = sanitize_text_field( $_GET['end_date'] );
            $where[] = 'date_created <= %s';
            $query_params[] = $end_date . ' 23:59:59';
        }
        
        // Add WHERE clause if we have conditions
        if ( ! empty( $where ) ) {
            $query .= ' WHERE ' . implode( ' AND ', $where );
        }
        
        // Prepare and execute query
        if ( ! empty( $query_params ) ) {
            $query = $wpdb->prepare( $query, $query_params );
        }
        
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( $wpdb->last_error ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }
        
        // Process results if needed
        if ( 'promo_codes' === $entity_type ) {
            foreach ( $results as &$row ) {
                // Format expiry date for better readability
                if ( ! empty( $row['expiry_date'] ) && '0000-00-00 00:00:00' !== $row['expiry_date'] ) {
                    $row['expiry_date'] = date( 'Y-m-d', strtotime( $row['expiry_date'] ) );
                } else {
                    $row['expiry_date'] = '';
                }
                
                // Format discount type
                if ( isset( $row['discount_type'] ) ) {
                    $row['discount_type'] = 'percent' === $row['discount_type'] ? 'percent' : 'fixed';
                }
                
                // Format status
                if ( isset( $row['status'] ) ) {
                    $row['status'] = 'active' === $row['status'] ? 'active' : 'inactive';
                }
            }
            unset( $row ); // Break the reference
        }
        
        return $results;
    }
    
    /**
     * Handle import requests.
     */
    public function handle_import() {
        if ( ! isset( $_POST['smp_import'] ) || ! isset( $_POST['_wpnonce'] ) ) {
            return;
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'smp_import_' . $_POST['smp_import'] ) ) {
            wp_die( __( 'Invalid import request.', 'school-manager-pro' ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to import data.', 'school-manager-pro' ) );
        }
        
        $entity_type = sanitize_key( $_POST['smp_import'] );
        $entity_types = [ 'students', 'teachers', 'classes', 'promo_codes' ];
        
        if ( ! in_array( $entity_type, $entity_types, true ) ) {
            wp_die( __( 'Invalid import type.', 'school-manager-pro' ) );
        }
        
        // Check if file was uploaded
        if ( ! isset( $_FILES['import_file'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
            wp_die( __( 'Please upload a file to import.', 'school-manager-pro' ) );
        }
        
        // Check for upload errors
        if ( $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
            $error_message = $this->get_upload_error_message( $_FILES['import_file']['error'] );
            wp_die( sprintf( __( 'Error uploading file: %s', 'school-manager-pro' ), $error_message ) );
        }
        
        // Check file type
        $file_type = wp_check_filetype( $_FILES['import_file']['name'], [ 'csv' => 'text/csv', 'txt' => 'text/plain' ] );
        if ( ! in_array( $file_type['ext'], [ 'csv', 'txt' ] ) ) {
            wp_die( __( 'Invalid file type. Please upload a CSV file.', 'school-manager-pro' ) );
        }
        
        // Process the file
        $file_path = $_FILES['import_file']['tmp_name'];
        $columns = SMP_CSV_Handler::get_columns( $entity_type );
        
        $args = [
            'columns'    => array_keys( $columns ),
            'has_header' => true,
        ];
        
        $data = SMP_CSV_Handler::import( $file_path, $args );
        
        if ( is_wp_error( $data ) ) {
            wp_die( sprintf( __( 'Error importing file: %s', 'school-manager-pro' ), $data->get_error_message() ) );
        }
        
        // Process the imported data
        $results = SMP_CSV_Handler::process_import( $entity_type, $data );
        
        // Set transient to show results
        $transient_key = 'smp_import_results_' . get_current_user_id();
        set_transient( $transient_key, $results, 60 ); // Store for 1 minute
        
        // Redirect back to the referring page
        $redirect_url = add_query_arg( 
            [
                'page' => 'school-manager-' . $entity_type,
                'imported' => 1,
            ], 
            admin_url( 'admin.php' ) 
        );
        
        wp_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Handle template download requests.
     */
    public function handle_template_download() {
        if ( ! isset( $_GET['smp_download_template'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'smp_download_template_' . $_GET['smp_download_template'] ) ) {
            wp_die( __( 'Invalid template request.', 'school-manager-pro' ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to download templates.', 'school-manager-pro' ) );
        }
        
        $entity_type = sanitize_key( $_GET['smp_download_template'] );
        $entity_types = [ 'students', 'teachers', 'classes', 'promo_codes' ];
        
        if ( ! in_array( $entity_type, $entity_types, true ) ) {
            wp_die( __( 'Invalid template type.', 'school-manager-pro' ) );
        }
        
        // Get columns for the entity type
        $columns = SMP_CSV_Handler::get_columns( $entity_type );
        
        // Generate filename
        $filename = 'smp_' . $entity_type . '_template';
        
        // Generate and download template
        SMP_CSV_Handler::generate_template( $columns, $filename );
    }
    
    /**
     * Get upload error message.
     *
     * @param int $error_code Error code.
     * @return string Error message.
     */
    protected function get_upload_error_message( $error_code ) {
        switch ( $error_code ) {
            case UPLOAD_ERR_INI_SIZE:
                return __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'school-manager-pro' );
            case UPLOAD_ERR_FORM_SIZE:
                return __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'school-manager-pro' );
            case UPLOAD_ERR_PARTIAL:
                return __( 'The uploaded file was only partially uploaded.', 'school-manager-pro' );
            case UPLOAD_ERR_NO_FILE:
                return __( 'No file was uploaded.', 'school-manager-pro' );
            case UPLOAD_ERR_NO_TMP_DIR:
                return __( 'Missing a temporary folder.', 'school-manager-pro' );
            case UPLOAD_ERR_CANT_WRITE:
                return __( 'Failed to write file to disk.', 'school-manager-pro' );
            case UPLOAD_ERR_EXTENSION:
                return __( 'A PHP extension stopped the file upload.', 'school-manager-pro' );
            default:
                return __( 'Unknown upload error.', 'school-manager-pro' );
        }
    }
}

// Initialize the import/export handler
new SMP_Import_Export_Handler();
