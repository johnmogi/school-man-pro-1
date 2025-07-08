<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="smp-dashboard-widgets">
        <div class="smp-widget">
            <h2><?php _e('Welcome to School Manager Pro', 'school-manager-pro'); ?></h2>
            <p><?php _e('Manage your school, teachers, students, and classes with ease.', 'school-manager-pro'); ?></p>
        </div>
        
        <div class="smp-widget">
            <h3><?php _e('Quick Stats', 'school-manager-pro'); ?></h3>
            <div class="smp-stats">
                <div class="smp-stat">
                    <span class="dashicons dashicons-admin-users"></span>
                    <strong>0</strong>
                    <span><?php _e('Teachers', 'school-manager-pro'); ?></span>
                </div>
                <div class="smp-stat">
                    <span class="dashicons dashicons-groups"></span>
                    <strong>0</strong>
                    <span><?php _e('Students', 'school-manager-pro'); ?></span>
                </div>
                <div class="smp-stat">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <strong>0</strong>
                    <span><?php _e('Classes', 'school-manager-pro'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
