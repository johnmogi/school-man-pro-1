<?php
if (!defined('ABSPATH')) {
    exit;
}

$teacher_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$is_edit = $teacher_id > 0;
$teacher = null;

if ($is_edit) {
    global $wpdb;
    $teacher = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}smp_teachers WHERE id = %d",
        $teacher_id
    ));
    
    if (!$teacher) {
        wp_die(__('Teacher not found.', 'school-manager-pro'));
    }
}

$first_name = $teacher->first_name ?? '';
$last_name = $teacher->last_name ?? '';
$email = $teacher->email ?? '';
$mobile = $teacher->mobile ?? '';
$status = $teacher->status ?? 'active';

// Get all classes for the teacher
$classes = [];
if ($is_edit) {
    $classes = $wpdb->get_col($wpdb->prepare(
        "SELECT class_id FROM {$wpdb->prefix}smp_class_teachers WHERE teacher_id = %d",
        $teacher_id
    ));
}

// Get all available classes
$all_classes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}smp_classes ORDER BY name");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Teacher', 'school-manager-pro') : __('Add New Teacher', 'school-manager-pro'); ?>
    </h1>
    <a href="?page=smp-teachers" class="page-title-action"><?php _e('Back to Teachers', 'school-manager-pro'); ?></a>
    
    <hr class="wp-header-end">
    
    <form method="post" action="" class="smp-form">
        <?php wp_nonce_field('smp_save_teacher'); ?>
        <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
        
        <table class="form-table">
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="first_name"><?php _e('First Name', 'school-manager-pro'); ?> <span class="description"><?php _e('(required)'); ?></span></label>
                </th>
                <td>
                    <input name="first_name" type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="last_name"><?php _e('Last Name', 'school-manager-pro'); ?> <span class="description"><?php _e('(required)'); ?></span></label>
                </th>
                <td>
                    <input name="last_name" type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr class="form-field form-required">
                <th scope="row">
                    <label for="email"><?php _e('Email', 'school-manager-pro'); ?> <span class="description"><?php _e('(required)'); ?></span></label>
                </th>
                <td>
                    <input name="email" type="email" id="email" value="<?php echo esc_attr($email); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr class="form-field">
                <th scope="row">
                    <label for="mobile"><?php _e('Mobile', 'school-manager-pro'); ?></label>
                </th>
                <td>
                    <input name="mobile" type="tel" id="mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr class="form-field">
                <th scope="row"><?php _e('Status', 'school-manager-pro'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Status', 'school-manager-pro'); ?></span></legend>
                        <label for="status_active">
                            <input name="status" type="radio" id="status_active" value="active" <?php checked($status, 'active'); ?>>
                            <?php _e('Active', 'school-manager-pro'); ?>
                        </label><br>
                        <label for="status_inactive">
                            <input name="status" type="radio" id="status_inactive" value="inactive" <?php checked($status, 'inactive'); ?>>
                            <?php _e('Inactive', 'school-manager-pro'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <?php if (!empty($all_classes)) : ?>
            <tr class="form-field">
                <th scope="row"><?php _e('Assigned Classes', 'school-manager-pro'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Assigned Classes', 'school-manager-pro'); ?></span></legend>
                        <?php foreach ($all_classes as $class) : ?>
                            <label>
                                <input type="checkbox" name="classes[]" value="<?php echo $class->id; ?>" <?php checked(in_array($class->id, $classes)); ?>>
                                <?php echo esc_html($class->name); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </fieldset>
                    <p class="description"><?php _e('Select the classes this teacher is assigned to.', 'school-manager-pro'); ?></p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? __('Update Teacher', 'school-manager-pro') : __('Add Teacher', 'school-manager-pro'); ?>">
            <a href="?page=smp-teachers" class="button button-secondary"><?php _e('Cancel', 'school-manager-pro'); ?></a>
        </p>
    </form>
</div>
