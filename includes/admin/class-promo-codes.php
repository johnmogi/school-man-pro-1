<?php
/**
 * Promo Codes Admin
 *
 * @package School_Manager\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Promo_Codes class.
 */
class SMP_Admin_Promo_Codes {

    /**
     * Initialize the promo codes admin.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_item' ] );
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
    }

    /**
     * Add menu items.
     */
    public function add_menu_item() {
        add_submenu_page(
            'school-manager',
            __( 'Promo Codes', 'school-manager-pro' ),
            __( 'Promo Codes', 'school-manager-pro' ),
            'manage_options',
            'school-manager-promo-codes',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_scripts( $hook_suffix ) {
        if ( 'school-manager_page_school-manager-promo-codes' !== $hook_suffix ) {
            return;
        }
        
        // Enqueue jQuery UI for datepicker
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
        
        // Enqueue select2 for better dropdowns
        wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], '4.1.0-rc.0', true );
        wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0' );
        
        // Enqueue our custom scripts and styles
        wp_enqueue_script(
            'smp-promo-codes',
            SMP_URL . 'assets/js/admin/promo-codes.js',
            [ 'jquery', 'jquery-ui-datepicker', 'select2' ],
            filemtime( SMP_PATH . 'assets/js/admin/promo-codes.js' ),
            true
        );
        
        wp_enqueue_style(
            'smp-admin',
            SMP_URL . 'assets/css/admin.css',
            [],
            filemtime( SMP_PATH . 'assets/css/admin.css' )
        );
        
        // Localize script with translations and settings
        wp_localize_script(
            'smp-promo-codes',
            'smpPromoCodes',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'smp_promo_codes_nonce' ),
                'i18n' => [
                    'confirmDelete' => __( 'Are you sure you want to delete the selected promo codes? This action cannot be undone.', 'school-manager-pro' ),
                    'confirmDeactivate' => __( 'Are you sure you want to deactivate the selected promo codes?', 'school-manager-pro' ),
                    'confirmActivate' => __( 'Are you sure you want to activate the selected promo codes?', 'school-manager-pro' ),
                    'selectPromoCode' => __( 'Select a promo code', 'school-manager-pro' ),
                    'noResults' => __( 'No results found', 'school-manager-pro' ),
                    'searching' => __( 'Searching...', 'school-manager-pro' ),
                ],
            ]
        );

        wp_enqueue_style( 'smp-admin' );
        
        // Datepicker
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
        
        // Custom admin JS
        wp_enqueue_script(
            'smp-promo-codes',
            SMP_PLUGIN_URL . 'assets/js/admin/promo-codes.js',
            [ 'jquery', 'jquery-ui-datepicker' ],
            SMP_VERSION,
            true
        );
        
        wp_localize_script( 'smp-promo-codes', 'smpPromoCodes', [
            'i18n' => [
                'deleteConfirm' => __( 'Are you sure you want to delete this promo code?', 'school-manager-pro' ),
            ],
            'dateFormat' => 'yy-mm-dd',
        ] );
    }

    /**
     * Handle form submissions and actions.
     */
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || 'school-manager-promo-codes' !== $_GET['page'] ) {
            return;
        }

        // Handle add/edit form submission
        if ( ! empty( $_POST['smp_promo_code_nonce'] ) && wp_verify_nonce( $_POST['smp_promo_code_nonce'], 'smp_save_promo_code' ) ) {
            $this->save_promo_code();
        }
    }

    /**
     * Save promo code.
     */
    protected function save_promo_code() {
        global $wpdb;
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'school-manager-pro' ) );
        }

        $table_name = $wpdb->prefix . 'edc_school_promo_codes';
        $data = [
            'code'           => sanitize_text_field( $_POST['code'] ),
            'description'    => sanitize_textarea_field( $_POST['description'] ),
            'discount_type'  => in_array( $_POST['discount_type'], [ 'fixed', 'percent' ] ) ? $_POST['discount_type'] : 'fixed',
            'amount'         => floatval( $_POST['amount'] ),
            'usage_limit'    => ! empty( $_POST['usage_limit'] ) ? absint( $_POST['usage_limit'] ) : null,
            'expiry_date'    => ! empty( $_POST['expiry_date'] ) ? sanitize_text_field( $_POST['expiry_date'] ) . ' 23:59:59' : '0000-00-00 00:00:00',
            'status'         => isset( $_POST['status'] ) ? 'active' : 'inactive',
            'date_created'   => current_time( 'mysql' ),
            'updated_at'     => current_time( 'mysql' ),
        ];

        // Format for wpdb
        $format = [
            '%s', // code
            '%s', // description
            '%s', // discount_type
            '%f', // amount
            '%d', // usage_limit
            '%s', // expiry_date
            '%s', // status
            '%s', // date_created
            '%s', // updated_at
        ];

        // Check if this is an update
        if ( ! empty( $_POST['id'] ) ) {
            $id = absint( $_POST['id'] );
            $result = $wpdb->update( $table_name, $data, [ 'id' => $id ], $format, [ '%d' ] );
            $message = $result ? 'updated' : 'error';
        } else {
            // Check if code already exists
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table_name WHERE code = %s",
                $data['code']
            ) );

            if ( $exists ) {
                wp_redirect( add_query_arg( 'message', 'exists', wp_get_referer() ) );
                exit;
            }

            $result = $wpdb->insert( $table_name, $data, $format );
            $id = $wpdb->insert_id;
            $message = $result ? 'added' : 'error';
        }

        if ( $result ) {
            // Handle class assignments
            $this->save_promo_code_assignments( $id, ! empty( $_POST['class_ids'] ) ? $_POST['class_ids'] : [] );
        }

        $redirect = add_query_arg( 'message', $message, menu_page_url( 'school-manager-promo-codes', false ) );
        wp_redirect( $redirect );
        exit;
    }

    /**
     * Save promo code class assignments.
     *
     * @param int   $promo_code_id Promo code ID.
     * @param array $class_ids     Array of class IDs.
     */
    protected function save_promo_code_assignments( $promo_code_id, $class_ids ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_promo_code_classes';
        
        // Delete existing assignments
        $wpdb->delete( $table_name, [ 'promo_code_id' => $promo_code_id ], [ '%d' ] );
        
        // Add new assignments
        if ( ! empty( $class_ids ) ) {
            foreach ( $class_ids as $class_id ) {
                $wpdb->insert(
                    $table_name,
                    [
                        'promo_code_id' => $promo_code_id,
                        'class_id'      => absint( $class_id ),
                    ],
                    [ '%d', '%d' ]
                );
            }
        }
    }

    /**
     * Display admin notices.
     */
    public function admin_notices() {
        if ( ! isset( $_GET['message'] ) ) {
            return;
        }

        $messages = [
            'added'   => __( 'Promo code added successfully.', 'school-manager-pro' ),
            'updated' => __( 'Promo code updated successfully.', 'school-manager-pro' ),
            'deleted' => __( 'Promo code deleted successfully.', 'school-manager-pro' ),
            'exists'  => __( 'A promo code with that code already exists.', 'school-manager-pro' ),
            'error'   => __( 'An error occurred. Please try again.', 'school-manager-pro' ),
        ];

        $message = sanitize_text_field( $_GET['message'] );
        if ( isset( $messages[ $message ] ) ) {
            $class = in_array( $message, [ 'added', 'updated', 'deleted' ] ) ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $messages[ $message ] ) . '</p></div>';
        }
    }

    /**
     * Render the promo codes page.
     */
    public function render_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        // Handle different views
        switch ( $action ) {
            case 'add':
            case 'edit':
                $this->render_edit_form( $id );
                break;
            
            default:
                $this->render_list_table();
                break;
        }
    }

    /**
     * Render the promo codes list table.
     */
    protected function render_list_table() {
        // Include the list table class
        require_once SMP_PLUGIN_DIR . 'includes/admin/class-promo-codes-list.php';
        
        // Create an instance of our package class...
        $promo_codes_table = new Promo_Codes_List_Table();
        
        // Process any bulk actions
        $promo_codes_table->process_bulk_action();
        
        // Prepare the items
        $promo_codes_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Promo Codes', 'school-manager-pro' ); ?></h1>
            <a href="<?php echo esc_url( add_query_arg( 'action', 'add', menu_page_url( 'school-manager-promo-codes', false ) ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Add New', 'school-manager-pro' ); ?>
            </a>
            <hr class="wp-header-end">

            <?php $this->render_import_export(); ?>

            <form method="post">
                <?php
                $promo_codes_table->search_box( __( 'Search Promo Codes', 'school-manager-pro' ), 'promo-code-search' );
                $promo_codes_table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the add/edit promo code form.
     *
     * @param int $id Promo code ID.
     */
    protected function render_edit_form( $id = 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edc_school_promo_codes';
        
        // Get promo code data if editing
        $promo_code = null;
        $class_ids = [];
        
        if ( $id > 0 ) {
            $promo_code = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
            
            if ( ! $promo_code ) {
                wp_die( __( 'Promo code not found.', 'school-manager-pro' ) );
            }
            
            // Get assigned class IDs
            $class_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT class_id FROM {$wpdb->prefix}edc_school_promo_code_classes WHERE promo_code_id = %d",
                $id
            ) );
        }
        
        // Get all classes for the select field
        $classes = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}edc_school_classes ORDER BY name" );
        
        // Set up form data
        $data = [
            'id'            => $id,
            'code'          => $promo_code->code ?? '',
            'description'   => $promo_code->description ?? '',
            'discount_type' => $promo_code->discount_type ?? 'fixed',
            'amount'        => isset( $promo_code->amount ) ? number_format( $promo_code->amount, 2, '.', '' ) : '',
            'usage_limit'   => $promo_code->usage_limit ?? '',
            'expiry_date'   => ( ! empty( $promo_code->expiry_date ) && '0000-00-00 00:00:00' !== $promo_code->expiry_date ) ? date( 'Y-m-d', strtotime( $promo_code->expiry_date ) ) : '',
            'status'        => ( $promo_code->status ?? 'active' ) === 'active',
            'class_ids'     => $class_ids,
        ];
        
        ?>
        <div class="wrap">
            <h1><?php echo $id ? esc_html__( 'Edit Promo Code', 'school-manager-pro' ) : esc_html__( 'Add New Promo Code', 'school-manager-pro' ); ?></h1>
            
            <div class="smp-form-wrap">
                <form method="post" action="" class="smp-form">
                    <?php wp_nonce_field( 'smp_save_promo_code', 'smp_promo_code_nonce' ); ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr( $data['id'] ); ?>">
                    
                    <table class="form-table">
                        <tr class="form-field form-required">
                            <th scope="row">
                                <label for="code"><?php esc_html_e( 'Code', 'school-manager-pro' ); ?> <span class="description">(<?php esc_html_e( 'required', 'school-manager-pro' ); ?>)</span></label>
                            </th>
                            <td>
                                <input name="code" type="text" id="code" value="<?php echo esc_attr( $data['code'] ); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e( 'The unique code that customers will enter at checkout.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="description"><?php esc_html_e( 'Description', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea( $data['description'] ); ?></textarea>
                                <p class="description"><?php esc_html_e( 'Optional description for internal use.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="discount_type"><?php esc_html_e( 'Discount Type', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <select name="discount_type" id="discount_type" class="regular-text">
                                    <option value="fixed" <?php selected( $data['discount_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'school-manager-pro' ); ?></option>
                                    <option value="percent" <?php selected( $data['discount_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'school-manager-pro' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="amount"><?php esc_html_e( 'Amount', 'school-manager-pro' ); ?> <span class="description">(<?php esc_html_e( 'required', 'school-manager-pro' ); ?>)</span></label>
                            </th>
                            <td>
                                <input name="amount" type="number" id="amount" value="<?php echo esc_attr( $data['amount'] ); ?>" class="small-text" step="0.01" min="0" required>
                                <span class="discount-type-symbol"><?php echo 'percent' === $data['discount_type'] ? '%' : html_entity_decode( get_woocommerce_currency_symbol() ); ?></span>
                                <p class="description">
                                    <?php echo 'percent' === $data['discount_type'] 
                                        ? esc_html__( 'The percentage discount to apply.', 'school-manager-pro' )
                                        : esc_html__( 'The fixed amount discount to apply.', 'school-manager-pro' ); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="usage_limit"><?php esc_html_e( 'Usage Limit', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <input name="usage_limit" type="number" id="usage_limit" value="<?php echo esc_attr( $data['usage_limit'] ); ?>" class="small-text" min="0">
                                <p class="description"><?php esc_html_e( 'Maximum number of times this promo code can be used. Leave blank for unlimited usage.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="expiry_date"><?php esc_html_e( 'Expiry Date', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <input name="expiry_date" type="text" id="expiry_date" value="<?php echo esc_attr( $data['expiry_date'] ); ?>" class="regular-text datepicker">
                                <p class="description"><?php esc_html_e( 'The date this promo code expires. Leave blank for no expiration.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th scope="row">
                                <label for="status"><?php esc_html_e( 'Status', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input name="status" type="checkbox" id="status" value="1" <?php checked( $data['status'] ); ?>>
                                    <?php esc_html_e( 'Active', 'school-manager-pro' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Inactive promo codes cannot be used.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        
                        <?php if ( ! empty( $classes ) ) : ?>
                        <tr class="form-field">
                            <th scope="row">
                                <label><?php esc_html_e( 'Restrict to Classes', 'school-manager-pro' ); ?></label>
                            </th>
                            <td>
                                <div class="smp-checkbox-list">
                                    <?php foreach ( $classes as $class ) : ?>
                                        <label>
                                            <input type="checkbox" name="class_ids[]" value="<?php echo esc_attr( $class->id ); ?>" <?php checked( in_array( $class->id, $data['class_ids'] ) ); ?>>
                                            <?php echo esc_html( $class->name ); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description"><?php esc_html_e( 'Select classes this promo code applies to. Leave all unchecked to apply to all classes.', 'school-manager-pro' ); ?></p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Promo Code', 'school-manager-pro' ); ?></button>
                        <a href="<?php echo esc_url( menu_page_url( 'school-manager-promo-codes', false ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'school-manager-pro' ); ?></a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the import/export section.
     */
    protected function render_import_export() {
        ?>
        <div class="smp-import-export">
            <h2 class="title"><?php esc_html_e( 'Import/Export', 'school-manager-pro' ); ?></h2>
            <div class="smp-import-export-actions">
                <div class="smp-export">
                    <h3><?php esc_html_e( 'Export Promo Codes', 'school-manager-pro' ); ?></h3>
                    <p><?php esc_html_e( 'Export your promo codes to a CSV file.', 'school-manager-pro' ); ?></p>
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'export_promo_codes', admin_url( 'admin-post.php' ) ), 'smp_export_promo_codes', 'smp_nonce' ) ); ?>" class="button">
                        <?php esc_html_e( 'Export to CSV', 'school-manager-pro' ); ?>
                    </a>
                </div>
                
                <div class="smp-import">
                    <h3><?php esc_html_e( 'Import Promo Codes', 'school-manager-pro' ); ?></h3>
                    <p><?php esc_html_e( 'Import promo codes from a CSV file.', 'school-manager-pro' ); ?></p>
                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="smp_import_promo_codes">
                        <?php wp_nonce_field( 'smp_import_promo_codes', 'smp_nonce' ); ?>
                        <input type="file" name="import_file" accept=".csv">
                        <?php submit_button( __( 'Import from CSV', 'school-manager-pro' ), 'secondary', 'submit', false ); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the promo codes admin
new SMP_Admin_Promo_Codes();
