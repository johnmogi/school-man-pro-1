<?php 
if (!defined('ABSPATH')) exit;

// Initialize the Students_List class
$students_list = new SchoolManagerPro\Admin\Students_List();
$students_list->prepare_items();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Students', 'school-manager-pro'); ?></h1>
    <a href="?page=smp-students&action=add" class="page-title-action"><?php _e('Add New', 'school-manager-pro'); ?></a>
    <hr class="wp-header-end">
    
    <div class="smp-admin-content">
        <?php $students_list->display_notices(); ?>
        
        <form method="post">
            <?php 
            $students_list->search_box(__('Search Students', 'school-manager-pro'), 'student');
            $students_list->display(); 
            ?>
        </form>
    </div>
</div>
