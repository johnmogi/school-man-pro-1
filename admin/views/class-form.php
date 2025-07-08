<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$class_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$is_edit = $class_id > 0;
$class = null;

if ($is_edit) {
    $class = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}smp_classes WHERE id = %d",
        $class_id
    ));
    
    if (!$class) {
        wp_die(__('Class not found.', 'school-manager-pro'));
    }
}

$name = $class->name ?? '';
$description = $class->description ?? '';
$teacher_id = $class->teacher_id ?? 0;
$status = $class->status ?? 'active';
$capacity = $class->capacity ?? 20;

// Get all teachers for the dropdown
$teachers = $wpdb->get_results(
    "SELECT id, CONCAT(first_name, ' ', last_name) as name, email 
     FROM {$wpdb->prefix}smp_teachers 
     WHERE status = 'active'
     ORDER BY first_name, last_name"
);

// Get all students in this class
$class_students = [];
if ($is_edit) {
    $class_students = $wpdb->get_col($wpdb->prepare(
        "SELECT student_id 
         FROM {$wpdb->prefix}smp_class_students 
         WHERE class_id = %d",
        $class_id
    ));
}

// Get all active students
$all_students = $wpdb->get_results(
    "SELECT id, CONCAT(first_name, ' ', last_name) as name, email 
     FROM {$wpdb->prefix}smp_students 
     WHERE status = 'active'
     ORDER BY first_name, last_name"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Class', 'school-manager-pro') : __('Add New Class', 'school-manager-pro'); ?>
    </h1>
    <a href="?page=smp-classes" class="page-title-action"><?php _e('Back to Classes', 'school-manager-pro'); ?></a>
    
    <hr class="wp-header-end">
    
    <form method="post" action="" class="smp-form">
        <?php wp_nonce_field('smp_save_class'); ?>
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div class="postbox">
                        <div class="inside">
                            <table class="form-table">
                                <tr class="form-field form-required">
                                    <th scope="row">
                                        <label for="name"><?php _e('Class Name', 'school-manager-pro'); ?> <span class="description"><?php _e('(required)'); ?></span></label>
                                    </th>
                                    <td>
                                        <input name="name" type="text" id="name" value="<?php echo esc_attr($name); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="description"><?php _e('Description', 'school-manager-pro'); ?></label>
                                    </th>
                                    <td>
                                        <textarea name="description" id="description" rows="5" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                                    </td>
                                </tr>
                                
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="teacher_id"><?php _e('Primary Teacher', 'school-manager-pro'); ?></label>
                                    </th>
                                    <td>
                                        <select name="teacher_id" id="teacher_id" class="regular-text">
                                            <option value=""><?php _e('-- Select Teacher --', 'school-manager-pro'); ?></option>
                                            <?php foreach ($teachers as $teacher) : ?>
                                                <option value="<?php echo $teacher->id; ?>" <?php selected($teacher_id, $teacher->id); ?>>
                                                    <?php echo esc_html($teacher->name . ' (' . $teacher->email . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr class="form-field">
                                    <th scope="row">
                                        <label for="capacity"><?php _e('Capacity', 'school-manager-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input name="capacity" type="number" id="capacity" value="<?php echo esc_attr($capacity); ?>" min="1" class="small-text">
                                        <p class="description"><?php _e('Maximum number of students allowed in this class.', 'school-manager-pro'); ?></p>
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
                            </table>
                        </div>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Students', 'school-manager-pro'); ?></h2>
                        <div class="inside">
                            <?php if (!empty($all_students)) : ?>
                                <div class="smp-checkbox-list">
                                    <?php foreach ($all_students as $student) : ?>
                                        <label>
                                            <input type="checkbox" name="students[]" value="<?php echo $student->id; ?>" <?php checked(in_array($student->id, $class_students)); ?>>
                                            <?php echo esc_html($student->name . ' (' . $student->email . ')'); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p><?php _e('No students found.', 'school-manager-pro'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <div class="inside
                            <p class="submit">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? __('Update Class', 'school-manager-pro') : __('Add Class', 'school-manager-pro'); ?>">
                                <a href="?page=smp-classes" class="button button-secondary"><?php _e('Cancel', 'school-manager-pro'); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
