<?php
namespace SchoolManagerPro\Admin;

defined('ABSPATH') || exit;

class Admin {
    /**
     * @var Students
     */
    private $students_page;
    
    /**
     * @var Teachers
     */
    private $teachers_page;
    
    /**
     * @var Classes
     */
    private $classes_page;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once SMP_PATH . 'admin/class-students.php';
        require_once SMP_PATH . 'admin/class-teachers.php';
        require_once SMP_PATH . 'admin/class-classes.php';
        
        // List table classes
        require_once SMP_PATH . 'includes/admin/class-students-list.php';
        require_once SMP_PATH . 'includes/admin/class-teachers-list.php';
        require_once SMP_PATH . 'includes/admin/class-classes-list.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register admin menu and assets
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Initialize sub-pages on admin_init to ensure all plugins are loaded
        add_action('admin_init', [$this, 'init_admin_pages'], 20);
    }
    
    /**
     * Initialize admin pages
     */
    public function init_admin_pages() {
        // Only initialize once
        if (did_action('smp_admin_pages_initialized')) {
            return;
        }
        
        // Initialize sub-pages without adding duplicate menus
        $this->students_page = new Students(false);
        $this->teachers_page = new Teachers(false);
        $this->classes_page = new Classes(false);
        
        // Mark as initialized
        do_action('smp_admin_pages_initialized');
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('School Manager Pro', 'school-manager-pro'),
            __('School Manager', 'school-manager-pro'),
            'manage_options',
            'school-manager-pro',
            [$this, 'render_dashboard'],
            'dashicons-welcome-learn-more',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'school-manager-pro',
            __('Dashboard', 'school-manager-pro'),
            __('Dashboard', 'school-manager-pro'),
            'manage_options',
            'school-manager-pro',
            [$this, 'render_dashboard']
        );
        
        // Teachers
        add_submenu_page(
            'school-manager-pro',
            __('Teachers', 'school-manager-pro'),
            __('Teachers', 'school-manager-pro'),
            'manage_options',
            'smp-teachers',
            [$this, 'render_teachers_page']
        );
        
        // Students
        add_submenu_page(
            'school-manager-pro',
            __('Students', 'school-manager-pro'),
            __('Students', 'school-manager-pro'),
            'manage_options',
            'school-manager-students',
            [$this, 'render_students_page']
        );
        
        // Classes
        add_submenu_page(
            'school-manager-pro',
            __('Classes', 'school-manager-pro'),
            __('Classes', 'school-manager-pro'),
            'manage_options',
            'smp-classes',
            [$this, 'render_classes_page']
        );
        
        // Promo Codes
        add_submenu_page(
            'school-manager-pro',
            __('Promo Codes', 'school-manager-pro'),
            __('Promo Codes', 'school-manager-pro'),
            'manage_options',
            'smp-promo-codes',
            [$this, 'render_promo_codes_page']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'school-manager-pro') === false && strpos($hook, 'smp-') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'smp-admin-style',
            SMP_URL . 'assets/css/admin.css',
            [],
            SMP_VERSION
        );
        
        // JS
        wp_enqueue_script(
            'smp-admin-script',
            SMP_URL . 'assets/js/admin.js',
            ['jquery'],
            SMP_VERSION,
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('smp-admin-script', 'smp_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smp_admin_nonce')
        ]);
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include SMP_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Render teachers page
     */
    public function render_teachers_page() {
        include SMP_PATH . 'admin/views/teachers.php';
    }
    
    /**
     * Render students page
     */
    public function render_students_page() {
        include SMP_PATH . 'admin/views/students.php';
    }
    
    /**
     * Render classes page
     */
    public function render_classes_page() {
        include SMP_PATH . 'admin/views/classes.php';
    }
    
    /**
     * Render promo codes page
     */
    public function render_promo_codes_page() {
        include SMP_PATH . 'admin/views/promo-codes.php';
    }
}
