<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$student_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$is_edit = $student_id > 0;
$student = null;

if ($is_edit) {
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}edc_school_students WHERE id = %d",
        $student_id
    ));
    
    if (!$student) {
        wp_die(__('Student not found.', 'school-manager-pro'));
    }
}

// Get student data
$first_name = $student->first_name ?? '';
$last_name = $student->last_name ?? '';
$email = $student->email ?? '';
$mobile = $student->mobile ?? '';
$status = $student->status ?? 'active';
$promo_code = $student->promo_code ?? '';

// Get all active classes for the dropdown
$classes = $wpdb->get_results(
    "SELECT id, name 
     FROM {$wpdb->prefix}edc_school_classes 
     WHERE status = 'active'
     ORDER BY name"
);

// Get classes this student is enrolled in
$student_classes = [];
if ($is_edit) {
    $student_classes = $wpdb->get_col($wpdb->prepare(
        "SELECT class_id 
         FROM {$wpdb->prefix}edc_school_class_students 
         WHERE student_id = %d",
        $student_id
    ));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Student', 'school-manager-pro') : __('Add New Student', 'school-manager-pro'); ?>
    </h1>
    <a href="?page=school-manager-students" class="page-title-action"><?php _e('Back to Students', 'school-manager-pro'); ?></a>
    
    <hr class="wp-header-end">
    
    <form method="post" action="" class="smp-form">
        <?php wp_nonce_field('smp_save_student'); ?>
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Student Details', 'school-manager-pro'); ?></h2>
                        <div class="inside">
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
                                
                                <tr class="form-field form-required">
                                    <th scope="row">
                                        <label for="mobile"><?php _e('Mobile Number', 'school-manager-pro'); ?> <span class="description"><?php _e('(required, used as username)'); ?></span></label>
                                    </th>
                                    <td>
                                        <input name="mobile" type="tel" id="mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="promo_code"><?php _e('Promo Code', 'school-manager-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input name="promo_code" type="text" id="promo_code" value="<?php echo esc_attr($promo_code); ?>" class="regular-text">
                                        <p class="description"><?php _e('Optional promo code for discounts.', 'school-manager-pro'); ?></p>
                                    </td>
                                </tr>
                                
                                <?php if ($is_edit) : ?>
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="password"><?php _e('Password', 'school-manager-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input name="password" type="password" id="password" class="regular-text" autocomplete="new-password">
                                        <p class="description"><?php _e('Leave blank to keep current password.', 'school-manager-pro'); ?></p>
                                    </td>
                                </tr>
                                <?php else : ?>
                                <tr class="form-field form-required">
                                    <th scope="row">
                                        <label for="password"><?php _e('Password', 'school-manager-pro'); ?> <span class="description"><?php _e('(required)'); ?></span></label>
                                    </th>
                                    <td>
                                        <input name="password" type="password" id="password" class="regular-text" required>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
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
                            </table>
                        </div>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Class Enrollment', 'school-manager-pro'); ?></h2>
                        <div class="inside">
                            <?php if (!empty($classes)) : ?>
                                <div class="smp-checkbox-list">
                                    <?php foreach ($classes as $class) : ?>
                                        <label>
                                            <input type="checkbox" name="classes[]" value="<?php echo $class->id; ?>" <?php checked(in_array($class->id, $student_classes)); ?>>
                                            <?php echo esc_html($class->name); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p><?php _e('No active classes found.', 'school-manager-pro'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <div class="inside">
                            <p class="submit">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? __('Update Student', 'school-manager-pro') : __('Add Student', 'school-manager-pro'); ?>">
                                <a href="?page=school-manager-students" class="button button-secondary"><?php _e('Cancel', 'school-manager-pro'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
