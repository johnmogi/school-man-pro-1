<?php
/**
 * CSV Handler
 *
 * @package School_Manager\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CSV_Handler class.
 */
class SMP_CSV_Handler {

    /**
     * Export data to CSV file.
     *
     * @param array  $data       Data to export.
     * @param string $filename   Output filename.
     * @param array  $columns    Column headers.
     * @return void
     */
    public static function export( $data, $filename, $columns = [] ) {
        // Set headers for CSV download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );
        
        // Create output stream
        $output = fopen( 'php://output', 'w' );
        
        // Add BOM for proper UTF-8 encoding in Excel
        fputs( $output, "\xEF\xBB\xBF" );
        
        // Output column headers if provided
        if ( ! empty( $columns ) ) {
            fputcsv( $output, $columns );
        }
        
        // Output data rows
        foreach ( $data as $row ) {
            // Convert object to array if needed
            if ( is_object( $row ) ) {
                $row = (array) $row;
            }
            
            // Ensure consistent order of columns
            $ordered_row = [];
            if ( ! empty( $columns ) ) {
                foreach ( array_keys( $columns ) as $key ) {
                    $ordered_row[ $key ] = isset( $row[ $key ] ) ? $row[ $key ] : '';
                }
            } else {
                $ordered_row = $row;
            }
            
            fputcsv( $output, $ordered_row );
        }
        
        fclose( $output );
        exit;
    }
    
    /**
     * Import data from CSV file.
     *
     * @param string $file_path Path to CSV file.
     * @param array  $args     Import arguments.
     * @return array|WP_Error Array of imported data or WP_Error on failure.
     */
    public static function import( $file_path, $args = [] ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return new WP_Error( 'file_error', __( 'File does not exist or is not readable.', 'school-manager-pro' ) );
        }
        
        $defaults = [
            'delimiter'  => ',',
            'enclosure'  => '"',
            'escape'     => '\\',
            'has_header' => true,
            'columns'    => [],
        ];
        
        $args = wp_parse_args( $args, $defaults );
        
        // Open the file
        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return new WP_Error( 'file_open_error', __( 'Could not open the file for reading.', 'school-manager-pro' ) );
        }
        
        $data = [];
        $header = [];
        $row_count = 0;
        
        // Skip BOM if present
        $bom = fread( $handle, 3 );
        if ( "\xEF\xBB\xBF" !== $bom ) {
            rewind( $handle );
        }
        
        // Process the file
        while ( ( $row = fgetcsv( $handle, 0, $args['delimiter'], $args['enclosure'], $args['escape'] ) ) !== false ) {
            $row_count++;
            
            // Skip empty rows
            if ( empty( $row ) || ( count( $row ) === 1 && empty( $row[0] ) ) ) {
                continue;
            }
            
            // Handle header row
            if ( 1 === $row_count && $args['has_header'] ) {
                $header = array_map( 'trim', $row );
                
                // If columns are specified, validate header
                if ( ! empty( $args['columns'] ) ) {
                    $missing_columns = array_diff( $args['columns'], $header );
                    if ( ! empty( $missing_columns ) ) {
                        fclose( $handle );
                        return new WP_Error( 'invalid_header', sprintf( 
                            __( 'Missing required columns: %s', 'school-manager-pro' ), 
                            implode( ', ', $missing_columns ) 
                        ) );
                    }
                }
                
                continue;
            }
            
            // Map row to associative array if header exists
            if ( ! empty( $header ) ) {
                $row_data = [];
                foreach ( $header as $index => $key ) {
                    $row_data[ $key ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                }
                $data[] = $row_data;
            } else if ( ! empty( $args['columns'] ) ) {
                // Use numeric keys based on columns
                $row_data = [];
                foreach ( $args['columns'] as $index => $key ) {
                    $row_data[ $key ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                }
                $data[] = $row_data;
            } else {
                $data[] = $row;
            }
        }
        
        fclose( $handle );
        
        if ( empty( $data ) ) {
            return new WP_Error( 'no_data', __( 'No valid data found in the file.', 'school-manager-pro' ) );
        }
        
        return $data;
    }
    
    /**
     * Generate CSV template for import.
     *
     * @param array  $columns    Column headers.
     * @param string $filename   Output filename.
     * @return void
     */
    public static function generate_template( $columns, $filename = 'template' ) {
        // Set headers for CSV download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename . '.csv' );
        
        // Create output stream
        $output = fopen( 'php://output', 'w' );
        
        // Add BOM for proper UTF-8 encoding in Excel
        fputs( $output, "\xEF\xBB\xBF" );
        
        // Output column headers
        fputcsv( $output, array_values( $columns ) );
        
        // Add example row
        $example_row = [];
        foreach ( $columns as $key => $label ) {
            $example_row[] = sprintf( 'example_%s', $key );
        }
        fputcsv( $output, $example_row );
        
        // Add comments row
        $comments = [];
        foreach ( $columns as $key => $label ) {
            $comments[] = sprintf( '// %s', $label );
        }
        fputcsv( $output, $comments );
        
        fclose( $output );
        exit;
    }
    
    /**
     * Get columns for a specific entity type.
     *
     * @param string $entity_type Entity type (students, teachers, classes, promo_codes).
     * @return array Array of columns.
     */
    public static function get_columns( $entity_type ) {
        $columns = [];
        
        switch ( $entity_type ) {
            case 'students':
                $columns = [
                    'id'           => __( 'ID', 'school-manager-pro' ),
                    'first_name'   => __( 'First Name', 'school-manager-pro' ),
                    'last_name'    => __( 'Last Name', 'school-manager-pro' ),
                    'email'        => __( 'Email', 'school-manager-pro' ),
                    'mobile'       => __( 'Mobile', 'school-manager-pro' ),
                    'promo_code'   => __( 'Promo Code', 'school-manager-pro' ),
                    'status'       => __( 'Status', 'school-manager-pro' ) . ' (active/inactive)',
                    'date_created' => __( 'Date Created', 'school-manager-pro' ),
                ];
                break;
                
            case 'teachers':
                $columns = [
                    'id'           => __( 'ID', 'school-manager-pro' ),
                    'first_name'   => __( 'First Name', 'school-manager-pro' ),
                    'last_name'    => __( 'Last Name', 'school-manager-pro' ),
                    'email'        => __( 'Email', 'school-manager-pro' ),
                    'mobile'       => __( 'Mobile', 'school-manager-pro' ),
                    'status'       => __( 'Status', 'school-manager-pro' ) . ' (active/inactive)',
                    'date_created' => __( 'Date Created', 'school-manager-pro' ),
                ];
                break;
                
            case 'classes':
                $columns = [
                    'id'           => __( 'ID', 'school-manager-pro' ),
                    'name'         => __( 'Class Name', 'school-manager-pro' ),
                    'description'  => __( 'Description', 'school-manager-pro' ),
                    'teacher_id'   => __( 'Teacher ID', 'school-manager-pro' ),
                    'status'       => __( 'Status', 'school-manager-pro' ) . ' (active/inactive)',
                    'date_created' => __( 'Date Created', 'school-manager-pro' ),
                ];
                break;
                
            case 'promo_codes':
                $columns = [
                    'code'          => __( 'Code', 'school-manager-pro' ) . ' *',
                    'description'   => __( 'Description', 'school-manager-pro' ),
                    'discount_type' => __( 'Discount Type', 'school-manager-pro' ) . ' (fixed/percent)',
                    'amount'        => __( 'Amount', 'school-manager-pro' ) . ' *',
                    'usage_limit'   => __( 'Usage Limit', 'school-manager-pro' ) . ' (leave empty for unlimited)',
                    'expiry_date'   => __( 'Expiry Date', 'school-manager-pro' ) . ' (YYYY-MM-DD)',
                    'status'        => __( 'Status', 'school-manager-pro' ) . ' (active/inactive)',
                ];
                break;
        }
        
        return apply_filters( 'smp_csv_columns_' . $entity_type, $columns );
    }
    
    /**
     * Process import for a specific entity type.
     *
     * @param string $entity_type Entity type (students, teachers, classes, promo_codes).
     * @param array  $data       Import data.
     * @return array Results with success/failure counts and messages.
     */
    public static function process_import( $entity_type, $data ) {
        global $wpdb;
        
        $results = [
            'total'    => 0,
            'inserted' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];
        
        if ( empty( $data ) || ! is_array( $data ) ) {
            $results['errors'][] = __( 'No data to import.', 'school-manager-pro' );
            return $results;
        }
        
        $results['total'] = count( $data );
        $table_name = $wpdb->prefix . 'edc_school_' . $entity_type;
        $columns = self::get_columns( $entity_type );
        $required_columns = [];
        
        // Determine required columns (marked with * in the label)
        foreach ( $columns as $key => $label ) {
            if ( strpos( $label, '*' ) !== false ) {
                $required_columns[] = $key;
            }
        }
        
        foreach ( $data as $row_index => $row ) {
            $row_number = $row_index + 1;
            
            // Skip empty rows
            if ( empty( array_filter( $row ) ) ) {
                $results['skipped']++;
                continue;
            }
            
            // Validate required fields
            $missing_fields = [];
            foreach ( $required_columns as $field ) {
                if ( ! isset( $row[ $field ] ) || '' === trim( $row[ $field ] ) ) {
                    $missing_fields[] = $field;
                }
            }
            
            if ( ! empty( $missing_fields ) ) {
                $results['errors'][] = sprintf(
                    __( 'Row %d: Missing required fields: %s', 'school-manager-pro' ),
                    $row_number,
                    implode( ', ', $missing_fields )
                );
                $results['skipped']++;
                continue;
            }
            
            // Prepare data for insert/update
            $row_data = [];
            $format = [];
            
            foreach ( $row as $key => $value ) {
                if ( ! array_key_exists( $key, $columns ) ) {
                    continue; // Skip unknown columns
                }
                
                // Sanitize based on field type
                switch ( $key ) {
                    case 'id':
                        $row_data[ $key ] = absint( $value );
                        $format[] = '%d';
                        break;
                        
                    case 'amount':
                    case 'usage_limit':
                        $row_data[ $key ] = is_numeric( $value ) ? floatval( $value ) : 0;
                        $format[] = '%f';
                        break;
                        
                    case 'status':
                        $status = strtolower( $value );
                        $row_data[ $key ] = in_array( $status, [ 'active', '1', 'yes', 'true' ], true ) ? 'active' : 'inactive';
                        $format[] = '%s';
                        break;
                        
                    case 'expiry_date':
                        if ( ! empty( $value ) ) {
                            $timestamp = strtotime( $value );
                            $row_data[ $key ] = $timestamp ? date( 'Y-m-d 23:59:59', $timestamp ) : '';
                        } else {
                            $row_data[ $key ] = '0000-00-00 00:00:00';
                        }
                        $format[] = '%s';
                        break;
                        
                    case 'discount_type':
                        $discount_type = strtolower( $value );
                        $row_data[ $key ] = in_array( $discount_type, [ 'percent', 'percentage' ], true ) ? 'percent' : 'fixed';
                        $format[] = '%s';
                        break;
                        
                    default:
                        $row_data[ $key ] = sanitize_text_field( $value );
                        $format[] = '%s';
                }
            }
            
            // Add timestamps
            if ( ! isset( $row['id'] ) || empty( $row['id'] ) ) {
                // New record
                $row_data['date_created'] = current_time( 'mysql' );
                $format[] = '%s';
                
                $result = $wpdb->insert( $table_name, $row_data, $format );
                
                if ( false === $result ) {
                    $results['errors'][] = sprintf(
                        __( 'Row %d: Failed to insert record: %s', 'school-manager-pro' ),
                        $row_number,
                        $wpdb->last_error
                    );
                    $results['skipped']++;
                } else {
                    $results['inserted']++;
                }
            } else {
                // Update existing record
                $where = [ 'id' => $row_data['id'] ];
                $where_format = [ '%d' ];
                
                // Don't update the ID
                unset( $row_data['id'] );
                array_shift( $format );
                
                // Add updated_at timestamp
                $row_data['updated_at'] = current_time( 'mysql' );
                $format[] = '%s';
                
                $result = $wpdb->update( $table_name, $row_data, $where, $format, $where_format );
                
                if ( false === $result ) {
                    $results['errors'][] = sprintf(
                        __( 'Row %d: Failed to update record: %s', 'school-manager-pro' ),
                        $row_number,
                        $wpdb->last_error
                    );
                    $results['skipped']++;
                } else {
                    $results['updated']++;
                }
            }
        }
        
        return $results;
    }
}
