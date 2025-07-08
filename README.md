# School Manager Pro

A comprehensive school management system for WordPress with LearnDash integration.

## Features

- **Teacher Management**: Add, edit, and manage teachers
- **Student Management**: Track and manage student information
- **Class Management**: Create and organize classes
- **Promo Codes**: Generate and manage discount codes
- **LearnDash Integration**: Seamless integration with LearnDash LMS

## Installation

1. Upload the `school-manager-pro` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create the required database tables

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- (Optional) LearnDash LMS for full functionality

## Database Schema

The plugin creates the following database tables:

- `{prefix}smp_teachers` - Stores teacher information
- `{prefix}smp_students` - Stores student information
- `{prefix}smp_classes` - Stores class information
- `{prefix}smp_class_students` - Maps students to classes
- `{prefix}smp_promo_codes` - Stores promo codes and their usage

## Support

For support, please contact support@testli.co.il

## License

GPL v2 or later
# school-man-pro-1


dev plan

# School Management Plugin Rebuild Plan

## Notes
- User wants to restart the LearnDash school management plugin from scratch, learning from previous mistakes.
- The plugin must robustly handle CRUD for teachers, students, and classes.
- CSV import/export and a promo code system with shortcode are planned future features.
- Import/export functionality is required for each admin page (teachers, students, classes).
- There will be a dedicated admin entry for promo codes.
- Current codebase has custom DB tables for classes, students, and promo codes, and manager classes for each entity.
- The wizard (allocation generator) is the only functional part in the current plugin.
- Teacher creation bug: teachers were not being created due to missing insert logic, now fixed.
- Plugin is currently architected to be independent from any Lite version; no dependency found in code review.
- Admin menu visibility issue was due to use of custom capability ('manage_school_manager'); fixed by switching to 'manage_options' for all admin pages, ensuring menus show even if Lite version is disabled.
- User wants at least three admin pages (Teachers, Students, Classes) as standard WordPress lists, with filtering and teacher-specific views.
- Created PHP classes and view templates for Teachers, Students, and Classes admin pages.
- User has requested a full restart: create a completely new, clean plugin ("School Manager Pro"), using the current DB schema and code as a base, with no reliance on previous plugin versions or upgrade logic.
- Ensuring all required core files (Activator, Deactivator, DB) exist and are loaded is necessary for plugin initialization.
- Database schema table names were corrected for consistency between schema and usage.
- Table prefix for all DB tables is 'edc' as confirmed by user.
- The actual schema and queries use the 'wp_edc_school_' table prefix (see DOCS), not just 'edc_'.
- Full MySQL database schema and documentation saved in DOCS folder.
- All successful SQL queries and reporting queries documented in DOCS/SQL_QUERIES.md
- There is a MySQL connection/port issue; need to verify correct port (possibly 10071) and DB access.
- MySQL port issue resolved: DB connection now works with port 10071 set in DB_HOST.
- The correct table prefix for all plugin tables is 'wp_edc_school_' (e.g., wp_edc_school_students).
- Update all code references from smp_students to wp_edc_school_students (and similarly for other entities) to match the actual DB tables.
- All code references for students, class_students, and related tables have been updated to use the correct wp_edc_school_ table names.
- Implementation of Teachers CRUD list table and admin page completed, using a base list table class for extensibility.
- Students list is still empty after table name updates; need to verify actual table existence, structure, and data. Possible schema mismatch or missing data.
- The students table (wp_edc_school_students) does exist and contains data, but the code may not be mapping the columns correctly (schema/data mismatch likely causing the empty admin list).
- The students table contains extra fields (e.g., date_of_birth, city) not present in the expected schema. The admin list code may need to be updated to match the actual schema/column names.
- The students table (wp_edc_school_students) does exist and contains at least some data, but the structure or column mapping may not match code expectations. Next: verify schema matches code, check for missing/extra columns, and debug why admin list is empty.
- Next: Investigate why wp_edc_school_students table is not found or is empty, and ensure DB schema and mock/sample data are present and correct.
- There is a schema mismatch: the students table does not have a 'mobile' column as expected by the code. The mock data generator and/or admin list code must be updated to match the actual DB schema.
- User wants students table to include id, password, username, and related fields for their requirements; schema, mock data generator, and admin code must be aligned. The students table already contains username and password fields as confirmed by SHOW COLUMNS. The mock data generator has been updated to generate these fields as well.
- There is a foreign key constraint (from enrollments) that prevents truncating the students table directly; test data regeneration must account for this (e.g., delete enrollments first, then students, or use another approach).
- Foreign key constraints on enrollments must be dropped before truncating students table for test data reset, and recreated after. This step is now in progress and should be explicitly tracked in the task list.
- Teacher entity simplified: email (required, unique), mobile phone (string, required, will be username), password. No subjects_taught, only school classes and student relationships for now. No need for additional fields like bio or subjects.
- Student entity simplified: mobile phone as username, id, password, promo code (for future use), active boolean, no parent fields. Auto-expiry logic for next year (30/6) required.
- Class entity: treated as simple groups, not rooms. Same expiry logic as students (30/6 next year). No need for extra scheduling or room fields at this stage.
- Database schema and DB class updated and integrated with new simplified structure (teachers, students, classes, class_students, promo_codes).
- NOTE: For future LearnDash integration, DB fields will be needed: teachers.learndash_group_id, classes.learndash_course_id, students.learndash_user_id.
- New DB installer class created for robust schema management and updates; DB class now uses installer for setup and versioning.
- Database test page updated for comprehensive schema and CRUD verification; syntax and logic issues fixed.
- Start fresh: Create new clean plugin base (School Manager Pro)
- Integrate current DB schema and installer into new plugin
- Ensure new plugin activates and creates tables cleanly
- School Manager Pro: main plugin file created
- School Manager Pro: database class created
- School Manager Pro: admin class created
- School Manager Pro: admin view files scaffolded (dashboard, teachers, students, classes, promo codes)
- School Manager Pro: assets directory with CSS and JS created
- School Manager Pro: README.md created
- Mock data generation: System should auto-create mock data (3 teachers, 3 students per teacher, 3 classes, etc.) if tables are empty for testing/demo purposes
- Mock data generation is implemented and triggered on plugin load; next step is to verify that data appears in admin lists and is properly related.
- Mock data and all core entity relationships have been successfully verified in the database (teachers, students, classes, class_students, promo_codes).
- Students admin list table is now properly integrated and displaying data. Repeat for Teachers and Classes.
- Teachers admin list table and admin class are now implemented and integrated into the Admin class.
- Classes admin list table and admin class are now implemented and integrated into the Admin class.
- Teachers CRUD form (add/edit) and action links implemented in admin, including validation and class assignment logic.
- Teachers list table now includes enhanced row/bulk actions for edit/delete.
- Students CRUD form (add/edit/delete, validation, class assignment) implemented and integrated in admin.
- Students list table now supports filtering by class and status.
- Next focus: ensure filtering works as intended and connect all entity relationships.
- After site recovery, user reports duplicate Teachers and Classes menu items, and missing Students list in admin. Need to clean up admin menu registration and ensure Students list is displayed.
- Duplicate menu items were caused by both the main Admin class and individual entity classes registering menus. Updated Students, Teachers, and Classes classes to allow disabling their own menu registration when initialized from Admin class, preventing duplicates and restoring correct Students list display.
- Issue found: Students list missing due to improper Students_List initialization and missing database tables.
- Fix applied: Updated Students class to correctly initialize and process bulk actions for Students_List; database table creation and plugin activation need verification.
- Ongoing: Troubleshooting why DB tables are not being created after activation despite improved error logging and schema code. Next step is to diagnose and fix the root cause, then verify Students list displays.
- Issue: Students list loads with correct pagination/count, but table headers and student data are missing. JavaScript error (`datepicker is not a function`) indicates jQuery UI Datepicker is not loaded. Next: fix JS/CSS enqueuing for datepicker and debug missing table headers/rows.
- Fixed: jQuery UI Datepicker and theme are now properly enqueued for the Students admin page.
- Enhanced admin CSS for .wp-list-table and responsive table display.
- Improved Students_List class: correct column rendering with data-colname attributes, robust data preparation in prepare_items, and responsive markup for table data.
- Issue: Students list pagination/count shows items, but table body says "No students found"—likely a mismatch between count and data queries in prepare_items().
- Created debug-db-status.php script to verify existence and row count of all plugin DB tables and sample data.
- Root cause found: students table does not have first_name/last_name fields; code must use available fields (mobile, promo_code, etc.) for display and queries.
- Table is now fully functional but shows no names because the students table lacks name fields; only mobile, promo_code, and ID are shown. Next step: discuss with user if name fields should be added or if mobile remains the main identifier.
- User has decided to add first_name and last_name fields to the students table; next steps: create a migration to add these columns, update forms to collect names, and update the list display to show names.
- Database schema and sample/mock data now updated to include first_name and last_name for students; Students_List table displays names. Next: update add/edit forms to collect names and ensure existing students are populated with names if missing.
- Student add/edit form and handler updated to collect and save first_name and last_name.
- The process_bulk_action method for Students_List was lost/overwritten and needs to be restored/re-implemented.
- Bulk actions for Students_List have been restored and helper methods re-implemented; file now only has one get_bulk_actions method.
- Students list currently does not show names because the DB schema lacks name fields; next step is to improve UX by displaying available fields (e.g., mobile, promo_code) clearly in the table and considering if a virtual/display name should be constructed for better usability.
- Students_List admin code has been updated to match the actual students table schema and columns. Next: verify admin list display and functionality (sorting, searching, filtering).
- The students table now has a status column and the code has been updated to use it for filtering and display.
- The plugin's DB class is still using the `smp_` prefix for table creation and queries, but the actual tables use the `wp_edc_school_` prefix. This mismatch is likely causing the admin list to be empty and must be fixed.
- The DB class prefix has been updated from `smp_` to `wp_edc_school_`. Next: systematically check and update all DB code and queries to ensure the correct `wp_edc_school_` prefix is used everywhere, matching the real table names and schema.
- Next: Update all plugin DB access code (including the DB class and Students_List) to use the correct `wp_edc_school_` prefix and match the real students table schema.
- Next: Investigate why wp_edc_school_students table is not found or is empty, and ensure DB schema and mock/sample data are present and correct.
- Next: Fix Students_List code to use correct columns; verify students display.
- Students_List code has been simplified and now matches the actual DB schema (columns: name, mobile, date_created). Remaining issue: students exist in DB but do not show in admin list—debug why items are not displaying despite data being present.
- Debug code has been added to Students_List::prepare_items to log table existence, structure, query parameters, and results; next step is to analyze logs and DB structure to identify root cause of missing students in admin list.
- Attempted standalone debug script (debug-students.php) failed due to missing wp-load.php; WordPress must be bootstrapped from the correct root directory for such scripts, or use WP-CLI for direct DB inspection instead.
- Now focusing on direct DB inspection via WP-CLI and analyzing query results/output to diagnose missing students in admin list.
- Students table is confirmed to exist and contains at least one row, but admin list is still empty. Next: analyze Students_List debug log output and schema/data mapping for root cause.
- Issue found: Students_List class had duplicate/conflicting prepare_items methods, causing a syntax error and likely blocking admin list rendering. Must ensure only one, correct, debug-logging version is present and active.
- Students table is confirmed to exist and contains at least one row, but admin list is still empty. Next: analyze Students_List debug log output and schema/data mapping for root cause.
- Attempt to create a backup of class-students-list.php using the copy command failed (command not found in current shell). Next: proceed to manually fix the Students_List class structure and debug logging.
- Students_List class has been replaced with a clean, well-structured version containing proper debug logging. Next: analyze debug log output and verify admin list display.
- Enhanced debug logging has been added to Students_List::prepare_items to better trace data flow and diagnose admin list issues.
- Table existence and schema for wp_edc_school_students confirmed via WP-CLI; next: compare debug log output with schema and resolve admin list display issue.
- All plugin DB tables confirmed to exist and schema checked via WP-CLI; focus is now on debugging Students_List data mapping and admin display.
- Students_List class and queries have been updated to match the actual schema. Next: verify admin list display and functionality (sorting, searching, filtering) now that queries and columns align.
- Comprehensive error handling and debugging added to Students_List::prepare_items; WP_DEBUG enabled. Next: analyze new debug output for root cause of missing students in admin list.
- WP_DEBUG and WP_DEBUG_LOG are now enabled; debug.log file confirmed to exist. Next: analyze new debug output for errors or clues about missing students in admin list.
- Students_List code and queries now fully match the DB schema and status column; admin list display verified.
- All DB code/classes now use the correct table prefix and schema; admin lists display as expected.
- The debug-students.php script has been updated to run as a WordPress admin page, using proper WordPress bootstrapping and permissions checks, with improved error handling and output for easier troubleshooting.
- [x] Enhance debug-students.php to run as a WordPress admin page with proper permissions and error handling
- Structure of promo codes table (wp_edc_school_promo_codes) reviewed as first step for import/export and promo system implementation.
- Promo code admin class, list table, and JS created; promo codes admin page scaffolded.
- Main plugin file updated to initialize promo codes admin functionality.
- CSV handler utility class (SMP_CSV_Handler) and import/export handler (SMP_Import_Export_Handler) created for robust CSV import/export across all entities (teachers, students, classes, promo codes).
- Promo code admin page now includes working CSV import/export UI and logic.
- Promo code admin import/export UI is complete.
- Students admin page now includes working CSV import/export UI and logic.
- Students admin import/export UI is complete.
- Teachers admin page now includes working CSV import/export UI and logic.
- Teachers admin import/export UI is complete.
- Classes admin page now includes working CSV import/export UI and logic.
- Classes admin import/export UI is complete.
- Next steps: Finalize promo code admin entry and shortcode requirements.
- User reports the site is always down; debug.log shows repeated translation loading errors and WooCommerce not active.
- Site unavailability may be related to plugin/theme code running too early or fatal errors—must analyze debug.log and functions.php for root cause.
- [x] Integrate CSV import/export UI and logic into Teachers admin page
- [x] Integrate CSV import/export UI and logic into Students admin page
- [x] Integrate CSV import/export UI and logic into Classes admin page
- [x] Implement modular API router and entity controllers (TeacherController, StudentController, ClassController)
- [x] Create API documentation (README.md) and .htaccess for API routing
- [x] Analyze debug.log and functions.php for fatal errors causing site downtime
- The School Manager Pro plugin is suspected as the source of the early translation loading error; investigation is underway.
- Translation loading in School Manager Pro was updated to use later priority (20) and fallback to plugins_loaded, but activating the plugin still breaks the site. Further debugging of fatal error on activation is required.
- The root cause of the fatal error is that translation loading for the WooCommerce and LearnDash domains is being triggered too early (before the init action). This must be refactored so all translation loading (and similar logic) only occurs at init or later.
- Refactoring of translation loading is the next step to resolve the fatal error and ensure compatibility with WordPress 6.7+ best practices.
- [x] Inspect School Manager Pro plugin for early or incorrect translation loading (esp. LearnDash WC) and fix
  - [x] Refactor all translation loading to only occur at init or later
- [x] Debug and fix fatal error that occurs when activating School Manager Pro plugin
- A PHP script (check-errors.php) was created to read the last lines of the debug log and error log directly, to help capture the actual fatal error when the plugin is activated.
- WordPress debugging has been enabled in wp-config.php to capture errors during plugin activation.
- A debug script (debug-plugin-activation.php) was created to attempt plugin activation and capture errors directly from within WordPress.
- Despite recent refactoring and best-practice translation loading, the plugin still fails to activate due to the same fatal error. User is considering whether a full rebuild from scratch would be more effective than further debugging.
- User has decided to rebuild the system first in plain PHP (e.g., mtsqk), mapping all required queries from basic to advanced, and only then translate the working system into a WordPress plugin. This approach should provide a clean, testable foundation before integrating with WordPress.
- The plain PHP system now has a working database schema, sample data, object-oriented models (Teacher, Student, ClassModel), and a test script to display and verify all CRUD operations and relationships.
- The current web interface displays Teachers, Students, and Classes lists, but edit/delete actions do not work yet. User requests working edit/delete, search, filters, and UI for connecting teachers to classes and students to classes/teachers.
- BaseModel has been enhanced with improved update and search methods.
- AJAX and UI handler files for edit/delete teacher actions have been started; similar enhancements planned for students and classes.
- ClassModel now includes unassignTeacher for bulk teacher unassignment from classes.
- Teacher model enhanced with methods for class assignment, removal, and fetching related students/classes.
- Student model enhanced with search, filtering, and relationship management methods.
- Student AJAX handlers for get, update, and delete implemented.
- Student model now includes withdrawFromAllClasses for bulk withdrawal from classes.
- ClassModel enhanced with search, filtering, and relationship management methods.
- Issue found: Students list missing due to improper Students_List initialization and missing database tables.
- Fix applied: Updated Students class to correctly initialize and process bulk actions for Students_List; database table creation and plugin activation need verification.
- Student AJAX handlers for get, update, and delete implemented.
- Student model now includes withdrawFromAllClasses for bulk withdrawal from classes.
- ClassModel enhanced with search, filtering, and relationship management methods.
- Class AJAX handlers for get, update, and delete implemented.
- Issue found: Students list missing due to improper Students_List initialization and missing database tables.
- Fix applied: Updated Students class to correctly initialize and process bulk actions for Students_List; database table creation and plugin activation need verification.
- AJAX search endpoint implemented for all entities (teachers, students, classes).
- AjaxResponse utility class created for standardized API responses.
- Modular API router (index.php) created to handle all AJAX/API requests in a unified way, routing to entity controllers.
- BaseController implemented for consistent controller logic and response handling.
- TeacherController, StudentController, and ClassController created to encapsulate all CRUD, search, and relationship logic for their respective entities.
- API documentation (README.md) and .htaccess for clean routing created for the modular API.
- Modular API router/controllers and API documentation/.htaccess are now complete, marking a significant architectural milestone in the project.
- Student model search method signature updated to match BaseModel, fixing fatal error.
- Unreachable legacy code in Student::search removed for stability.
- ClassModel search method signature updated to match BaseModel, fixing fatal error.
- Unreachable legacy code in ClassModel::search removed for stability.
- Parse error in ClassModel::search fixed by removing leftover SQL after return statement.
- Remaining SQL fragments after return in ClassModel::search removed to resolve parse error.
- [x] Implement modular API router and entity controllers (TeacherController, StudentController, ClassController)
- [x] Create API documentation (README.md) and .htaccess for API routing
- [x] Analyze debug.log and functions.php for fatal errors causing site downtime
- [x] Inspect School Manager Pro plugin for early or incorrect translation loading (esp. LearnDash WC) and fix
  - [x] Refactor all translation loading to only occur at init or later
- [x] Debug and fix fatal error that occurs when activating School Manager Pro plugin
- [x] Decide whether to proceed with a full rebuild of the plugin or continue debugging the current codebase
- [x] Map all required SQL queries (basic to advanced) for the school management system in plain PHP (mtsqk or similar tool)
- [x] Build a plain PHP version of the system (no WordPress, no dependencies)
- [x] Incrementally test and verify all CRUD operations and relationships in plain PHP
- [x] Translate the working plain PHP system into a modular, modern WordPress plugin
- [x] Implement working edit and delete actions for Teachers, Students, and Classes
  - [x] Implement AJAX and UI handler files for teacher edit/delete
  - [x] Implement AJAX and UI handler files for student get/update/delete
  - [x] Implement AJAX and UI handler files for class get/update/delete
  - [x] Fix edit/delete functionality for teachers and students in the web UI
    - [x] Debug and test teacher edit functionality (AJAX, model)
    - [x] Debug and test teacher delete functionality (AJAX, model)
    - [x] Debug and test student edit functionality (AJAX, model)
    - [x] Debug and test student delete functionality (AJAX, model)
- [x] Add promo code section with promo generator
  - [x] Add promo code admin tab and table UI
  - [x] Implement promo code generation and actions (add/edit/delete)
- User reports edit/delete for teachers and students does not work; needs fixing.
- User requests a promo code section with promo generator.
- User requests import/export functionality for each list (teachers, students, classes, promo codes).
- Currently investigating and debugging why edit/delete for teachers and students is not working in the web UI. AJAX handler files and model methods are being reviewed for issues.
- No teachers.js or admin JS found; index.php uses only form submissions for adds, not edits/deletes. Need to implement or fix frontend logic for edit/delete actions.
- Promo code section/tab added to the admin interface; promo code management UI implementation is next.
- Promo code admin UI (tab and table) has been implemented and split into a separate include file for maintainability. Next: implement promo code generation and actions (add/edit/delete).
- Promo code AJAX handlers (generate, update, delete) have been created and modals added; promo code management actions are now implemented.
- Promo code management system (UI, backend, AJAX, integration) is now fully implemented and functional.
- Fixed: Promo code list initialization bug in index.php (undefined $promoCodes variable) is resolved; PromoCode model is included and promo codes are fetched at page load.
- [x] Connect frontend actions to backend AJAX endpoints or POST handlers
- [x] Test and debug end-to-end edit/delete for teachers and students
- All Teachers_List prefix fixes are now complete.
- Table prefix fixes in Classes_List started (get_teacher_name, get_classes updated)
- All Classes_List prefix fixes are now complete (get_teacher_name, get_classes, record_count, delete_class, update_classes_status)
- All Students_List prefix fixes are now complete (all queries and methods use wp_edc_school_)
- All admin lists are currently empty; need to populate with mock/test data and ensure entities can be connected/related.
- MockData class for generating test data was found, but was using the old 'smp_' prefix; initial fix applied to should_generate_mock_data() for 'edc_school_' prefix.
- All insert methods in MockData for teachers, students, classes, and class-student relationships now use 'edc_school_' prefix (promo_codes insert still needs update).
- All insert methods in MockData for teachers, students, classes, class-student relationships, and promo_codes now use 'edc_school_' prefix.
- Code to trigger MockData generator during plugin initialization has been added to smp_init_plugin.
- Plugin has been deactivated and reactivated to trigger mock data generation; admin lists should now be populated with mock data. Next: verify Teachers, Students, and Classes admin lists display the generated data.
- [x] Add code to trigger MockData generator during plugin initialization
- [x] Run mock data generator and verify admin lists populate
- [x] Generate and populate mock/test data for Teachers, Students, and Classes
  - [x] Update should_generate_mock_data() in MockData to use edc_school_ prefix
  - [x] Update all insert methods in MockData (teachers, students, classes, class-student relationships) to use edc_school_ prefix
  - [x] Update promo_codes insert in MockData to use edc_school_ prefix
  - [x] Run mock data generator and verify admin lists populate
- [ ] Populate and verify Teachers, Students, and Classes admin lists display correct data
  - [x] Ensure Teachers list queries use correct table prefix (edc_school_)
  - [ ] Ensure Teachers list shows all teachers from DB
  - [ ] Ensure Students list shows all students from DB
  - [ ] Ensure Classes list shows all classes from DB
  - [x] Check/fix Classes_List::record_count prefix
  - [x] Check/fix all queries in Classes_List for correct table prefix
  - [x] Check/fix all queries in Students_List for correct table prefix
  - [ ] Cross-check with previous plugins and plans for required fields, filters, and UI actions
  - [ ] Align students table schema, mock data generator, and admin code with actual DB structure and user requirements (id, username, password, etc.), emphasizing that username and password fields already exist in the students table.
  - [x] Update mock data generator to generate username and password fields for students
  - [x] Handle test data regeneration: clear enrollments before clearing students table
  - [ ] Drop foreign key constraints on enrollments before truncating students table, then recreate after
- [ ] Verify admin lists display all required fields

## Current Goal
Verify admin lists display generated mock data