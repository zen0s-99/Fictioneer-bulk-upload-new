<?php
/*
Plugin Name: Bulk Chapter Upload
Plugin URI: https://github.com/zen0s-99/Fictioneer-bulk-upload-new
Description: Bulk upload chapters from a zip file with password protection and expiration. Designed to integrate with themes using the Fictioneer framework.
Version: 1.3
Author: zen0s
Author URI: https://github.com/zen0s-99
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fictioneer-bulk-upload
Domain Path: /languages
*/

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;

/**
 * Log an error message if WP_DEBUG is enabled.
 *
 * @param string $message The message to log.
 */
function bcu_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Bulk Chapter Upload] ' . $message);
    }
}

/**
 * Add the admin menu item for the plugin.
 */
add_action('admin_menu', 'bcu_admin_menu');
function bcu_admin_menu() {
    add_menu_page(
        __('Bulk Chapter Upload', 'fictioneer-bulk-upload'),
        __('Bulk Chapter Upload', 'fictioneer-bulk-upload'),
        'manage_options',
        'bulk-chapter-upload',
        'bcu_render_upload_page',
        'dashicons-upload' // Added an icon
    );
}

/**
 * Render the main upload page for the plugin.
 */
function bcu_render_upload_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'fictioneer-bulk-upload'));
    }

    // Handle form submission
    if (isset($_POST['bcu_submit']) && !empty($_FILES['bcu_zip_file']['name'])) {
        bcu_handle_upload();
    }
    
    $stories = get_posts(array(
        'post_type' => 'fcn_story', // Assuming this is the correct CPT slug for stories
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'suppress_filters' => true, // Good practice for admin queries
    ));
    ?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <?php settings_errors('bulk_chapter_upload_notices'); // Use a unique slug for settings errors ?>

    <?php if (empty($stories)): ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php esc_html_e('No stories found. Please create a story (fcn_story) first.', 'fictioneer-bulk-upload'); ?></p>
    </div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data" id="bulk-chapter-upload-form">
        <?php wp_nonce_field('bulk_chapter_upload_action', 'bulk_chapter_upload_nonce'); ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="bcu_story_id"><?php esc_html_e('Select Story:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <select name="bcu_story_id" id="bcu_story_id" required>
                            <option value=""><?php esc_html_e('-- Select Story --', 'fictioneer-bulk-upload'); ?></option>
                            <?php foreach ($stories as $story): ?>
                            <option value="<?php echo esc_attr($story->ID); ?>">
                                <?php echo esc_html($story->post_title); ?> (ID: <?php echo esc_html($story->ID); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bcu_post_status"><?php esc_html_e('Chapter Status:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <select name="bcu_post_status" id="bcu_post_status" required>
                            <option value="draft"><?php esc_html_e('Draft', 'fictioneer-bulk-upload'); ?></option>
                            <option value="publish"><?php esc_html_e('Published', 'fictioneer-bulk-upload'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select whether to create chapters as drafts or publish them immediately. Published chapters are subject to theme processing via hooks.', 'fictioneer-bulk-upload'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bcu_zip_file"><?php esc_html_e('Upload Zip File:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="bcu_zip_file" id="bcu_zip_file" accept=".zip,application/zip,application/x-zip,application/x-zip-compressed" required>
                        <p class="description">
                            <?php esc_html_e('Upload a ZIP file containing text files (.txt). For chapters to be ordered correctly by the importer, filenames MUST end with "C" followed by a number and ".txt" (e.g., "Chapter C1.txt", "Story C02.txt", "File C100.txt"). Other naming formats will result in chapters being processed in a potentially unsorted order by this plugin.', 'fictioneer-bulk-upload'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="bcu_schedule_type"><?php esc_html_e('Schedule Type:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <select name="bcu_schedule_type" id="bcu_schedule_type">
                            <option value="none"><?php esc_html_e('No Scheduling', 'fictioneer-bulk-upload'); ?></option>
                            <option value="single"><?php esc_html_e('Single Date (All Chapters)', 'fictioneer-bulk-upload'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly Schedule', 'fictioneer-bulk-upload'); ?></option>
                        </select>
                    </td>
                </tr>
                <!-- Single Date Scheduling Options -->
                <tr class="bcu-schedule-options bcu-schedule-single" style="display: none;">
                    <th scope="row">
                        <label for="bcu_schedule_date"><?php esc_html_e('Schedule Date:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" name="bcu_schedule_date" id="bcu_schedule_date">
                        <p class="description"><?php esc_html_e('Base date and time for the first chapter if scheduling. Subsequent chapters will be offset by the delay.', 'fictioneer-bulk-upload'); ?></p>
                    </td>
                </tr>
                <tr class="bcu-schedule-options bcu-schedule-single" style="display: none;">
                    <th scope="row">
                        <label for="bcu_delay_value"><?php esc_html_e('Delay Between Chapters:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="bcu_delay_value" id="bcu_delay_value" min="0" value="0">
                        <select name="bcu_delay_unit" id="bcu_delay_unit">
                            <option value="minutes"><?php esc_html_e('Minutes', 'fictioneer-bulk-upload'); ?></option>
                            <option value="hours"><?php esc_html_e('Hours', 'fictioneer-bulk-upload'); ?></option>
                            <option value="days"><?php esc_html_e('Days', 'fictioneer-bulk-upload'); ?></option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Delay applied for each subsequent chapter if "Single Date" is chosen. This value is multiplied by the chapter index (0 for first, 1 for second, etc.) and added to the "Schedule Date".', 'fictioneer-bulk-upload'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="bcu-schedule-options bcu-schedule-single" style="display: none;">
                    <th scope="row">
                        <label for="bcu_expiry_start_date"><?php esc_html_e('Password Expiry Start Date:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" name="bcu_expiry_start_date" id="bcu_expiry_start_date">
                        <p class="description">
                            <?php esc_html_e('Optional: If using "Single Date" scheduling and password expiration, this date can serve as the base for expiration calculations. If not set, the chapter\'s publication/scheduled date is used as the base.', 'fictioneer-bulk-upload'); ?>
                        </p>
                    </td>
                </tr>
                <!-- Weekly Scheduling Options -->
                <tr class="bcu-schedule-options bcu-schedule-weekly" style="display: none;">
                    <th scope="row">
                        <label><?php esc_html_e('Publishing Days:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td>
                        <?php
                        $days = array('monday' => __('Monday', 'fictioneer-bulk-upload'), 'tuesday' => __('Tuesday', 'fictioneer-bulk-upload'), 'wednesday' => __('Wednesday', 'fictioneer-bulk-upload'), 'thursday' => __('Thursday', 'fictioneer-bulk-upload'), 'friday' => __('Friday', 'fictioneer-bulk-upload'), 'saturday' => __('Saturday', 'fictioneer-bulk-upload'), 'sunday' => __('Sunday', 'fictioneer-bulk-upload'));
                        foreach ($days as $value => $label): ?>
                        <label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="bcu_schedule_days[]" value="<?php echo esc_attr($value); ?>"> <?php echo esc_html($label); ?></label>
                        <?php endforeach; ?>
                        <p class="description"><?php esc_html_e('Select days when chapters should be published.', 'fictioneer-bulk-upload'); ?></p>
                    </td>
                </tr>
                <tr class="bcu-schedule-options bcu-schedule-weekly" style="display: none;">
                    <th scope="row">
                        <label for="bcu_weekly_time"><?php esc_html_e('Publishing Time:', 'fictioneer-bulk-upload'); ?></label>
                    </th>
                    <td><input type="time" name="bcu_weekly_time" id="bcu_weekly_time"></td>
                </tr>
                <!-- Password Field -->
                <tr>
                    <th scope="row"><label for="bcu_chapter_password"><?php esc_html_e('Chapter Password:', 'fictioneer-bulk-upload'); ?></label></th>
                    <td>
                        <input type="password" name="bcu_chapter_password" id="bcu_chapter_password" minlength="4" autocomplete="new-password">
                        <p class="description"><?php esc_html_e('Optional: Password to protect all chapters (4+ characters if used). Expiration only applies if a password is set.', 'fictioneer-bulk-upload'); ?></p>
                    </td>
                </tr>
                <!-- Expire Count Field -->
                <tr>
                    <th scope="row"><label for="bcu_expire_count"><?php esc_html_e('Password Expire In:', 'fictioneer-bulk-upload'); ?></label></th>
                    <td>
                        <input type="number" name="bcu_expire_count" id="bcu_expire_count" min="0" step="1" value="0">
                        <select name="bcu_expire_unit" id="bcu_expire_unit">
                            <option value="minutes"><?php esc_html_e('Minutes', 'fictioneer-bulk-upload'); ?></option>
                            <option value="hours"><?php esc_html_e('Hours', 'fictioneer-bulk-upload'); ?></option>
                            <option value="days" selected><?php esc_html_e('Days', 'fictioneer-bulk-upload'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Optional: Duration for password validity. Calculated as (this value * chapter number based on upload order, starting from 1). This total duration is added to the "Password Expiry Start Date" (if set for "Single Date" schedule) or to the chapter\'s actual publication/scheduled date. Requires a password to be set. 0 means no expiration.', 'fictioneer-bulk-upload'); ?></p>
                    </td>
                </tr>
                <!-- Chapter Categories -->
                <tr>
                    <th scope="row"><label for="bcu_chapter_categories"><?php esc_html_e('Chapter Categories:', 'fictioneer-bulk-upload'); ?></label></th>
                    <td>
                        <?php
                        $categories = get_categories(array('hide_empty' => false));
                        if (!empty($categories)) : ?>
                        <select name="bcu_chapter_categories[]" id="bcu_chapter_categories" multiple style="min-width:250px; min-height:100px; height: auto;">
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Select one or more categories for the chapters.', 'fictioneer-bulk-upload'); ?></p>
                        <?php else: ?>
                        <p><?php esc_html_e('No categories found.', 'fictioneer-bulk-upload'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(__('Upload Chapters', 'fictioneer-bulk-upload'), 'primary', 'bcu_submit'); ?>
    </form>
    <?php endif; ?>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    function toggleScheduleOptions() {
        $('.bcu-schedule-options').hide();
        var selectedType = $('#bcu_schedule_type').val();
        if (selectedType) {
            $('.bcu-schedule-' + selectedType).show();
        }
    }
    $('#bcu_schedule_type').change(toggleScheduleOptions);
    toggleScheduleOptions(); // Initial call on page load
});
</script>
<?php
}

/**
 * Handle the chapter upload process.
 */
function bcu_handle_upload() {
    // Verify nonce
    if (!isset($_POST['bulk_chapter_upload_nonce']) || !wp_verify_nonce($_POST['bulk_chapter_upload_nonce'], 'bulk_chapter_upload_action')) {
        wp_die(esc_html__('Security check failed.', 'fictioneer-bulk-upload'));
    }
    bcu_log_error('Starting upload process...');

    // Validate story ID
    $story_id = isset($_POST['bcu_story_id']) ? intval($_POST['bcu_story_id']) : 0;
    if (!$story_id || get_post_type($story_id) !== 'fcn_story') {
        bcu_log_error('Invalid story ID provided: ' . sanitize_text_field($_POST['bcu_story_id'] ?? 'N/A'));
        add_settings_error('bulk_chapter_upload_notices', 'invalid_story', __('Invalid story selected.', 'fictioneer-bulk-upload'), 'error');
        return;
    }

    // Validate file upload
    if (!isset($_FILES['bcu_zip_file']) || empty($_FILES['bcu_zip_file']['name']) || $_FILES['bcu_zip_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['bcu_zip_file']['error'] ?? 'Unknown';
        bcu_log_error('File upload error: ' . $error_code);
        add_settings_error('bulk_chapter_upload_notices', 'upload_error', __('File upload failed. Error code: ', 'fictioneer-bulk-upload') . $error_code, 'error');
        return;
    }

    $zip_file = $_FILES['bcu_zip_file'];
    
    // Prepare temporary directory for extraction
    $upload_dir_info = wp_upload_dir();
    $temp_dir_base = trailingslashit($upload_dir_info['basedir']); // Use basedir for more reliable path construction
    $temp_dir = $temp_dir_base . 'bulk-chapters-' . uniqid();
    
    if (!wp_mkdir_p($temp_dir)) {
        bcu_log_error('Failed to create temp directory: ' . $temp_dir);
        add_settings_error('bulk_chapter_upload_notices', 'dir_failed', __('Failed to create temporary directory.', 'fictioneer-bulk-upload'), 'error');
        return;
    }
    
    $zip_path = trailingslashit($temp_dir) . sanitize_file_name($zip_file['name']);

    if (!move_uploaded_file($zip_file['tmp_name'], $zip_path)) {
        bcu_log_error('Failed to move uploaded file to: ' . $zip_path);
        add_settings_error('bulk_chapter_upload_notices', 'upload_failed', __('Failed to move uploaded ZIP file.', 'fictioneer-bulk-upload'), 'error');
        bcu_cleanup_temp_files($temp_dir); // Clean up created directory
        return;
    }

    // Extract ZIP archive
    $zip = new ZipArchive;
    if ($zip->open($zip_path) !== TRUE) {
        bcu_log_error('Failed to open ZIP file. Path: ' . $zip_path . ' (ZipArchive error code: ' . $zip->status . ')');
        add_settings_error('bulk_chapter_upload_notices', 'zip_failed', __('Failed to open ZIP file. Ensure it is a valid .zip archive.', 'fictioneer-bulk-upload'), 'error');
        bcu_cleanup_temp_files($temp_dir, $zip_path); // Pass zip_path to ensure it's unlinked
        return;
    }
    $zip->extractTo($temp_dir);
    $zip->close();
    @unlink($zip_path); // Remove the zip file after extraction, it's no longer needed

    // Get and sort chapter files
    $chapter_files = glob(trailingslashit($temp_dir) . '*.txt');
    if (is_array($chapter_files) && !empty($chapter_files)) {
        usort($chapter_files, function($a, $b) {
            $pattern = '/C(\d+)\.txt$/i';
            preg_match($pattern, basename($a), $matchesA);
            preg_match($pattern, basename($b), $matchesB);
            $numA = isset($matchesA[1]) ? intval($matchesA[1]) : PHP_INT_MAX; // Sort non-matching last
            $numB = isset($matchesB[1]) ? intval($matchesB[1]) : PHP_INT_MAX;
            if ($numA === PHP_INT_MAX && $numB === PHP_INT_MAX) {
                return strnatcasecmp(basename($a), basename($b)); // Fallback for non-C-num files
            }
            return $numA - $numB;
        });
    } else {
        bcu_log_error('No .txt files found in ZIP archive in temp_dir: ' . $temp_dir);
        add_settings_error('bulk_chapter_upload_notices', 'no_files', __('No text files (.txt) found in the ZIP archive.', 'fictioneer-bulk-upload'), 'error');
        bcu_cleanup_temp_files($temp_dir);
        return;
    }

    $chapters_created_count = 0;
    $error_messages = array();
    $theme_function_missing_error_shown = false;

    // Get common post data from form
    $base_post_status_from_form = isset($_POST['bcu_post_status']) && in_array($_POST['bcu_post_status'], ['draft', 'publish']) ? $_POST['bcu_post_status'] : 'draft';
    $chapter_password_from_form = isset($_POST['bcu_chapter_password']) ? sanitize_text_field($_POST['bcu_chapter_password']) : '';
    $expire_count_from_form = isset($_POST['bcu_expire_count']) ? intval($_POST['bcu_expire_count']) : 0;
    $selected_categories = isset($_POST['bcu_chapter_categories']) && is_array($_POST['bcu_chapter_categories']) ? array_map('intval', $_POST['bcu_chapter_categories']) : array();

    // Validate password length if provided
    if (!empty($chapter_password_from_form) && strlen($chapter_password_from_form) < 4) {
        $error_messages[] = __('Password was less than 4 characters long and has been ignored. Chapters were created without password protection or expiration.', 'fictioneer-bulk-upload');
        $chapter_password_from_form = ''; // Clear password if invalid
        $expire_count_from_form = 0;   // Disable expiration if password is invalid
    }

    // Process each chapter file
    foreach ($chapter_files as $index => $file_path) {
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            $error_messages[] = sprintf(__('Failed to read file: %s', 'fictioneer-bulk-upload'), basename($file_path));
            continue;
        }

        $chapter_title = sanitize_file_name(basename($file_path, '.txt'));
        $current_chapter_effective_status = $base_post_status_from_form;
        $scheduled_datetime_object = bcu_get_scheduled_date($index, $_POST['bcu_schedule_type'] ?? 'none');

        $chapter_post_data = array(
            'post_title'    => sanitize_text_field($chapter_title),
            'post_content'  => wp_kses_post($file_content), // Or consider other kses profiles if needed
            'post_type'     => 'fcn_chapter', // Assuming this is the CPT for chapters
            'post_status'   => $current_chapter_effective_status,
            'post_author'   => get_current_user_id(),
            'post_password' => $chapter_password_from_form,
        );

        if ($scheduled_datetime_object instanceof DateTime) {
            $chapter_post_data['post_status'] = 'future';
            $chapter_post_data['post_date'] = $scheduled_datetime_object->format('Y-m-d H:i:s');
            // WordPress will handle post_date_gmt from local post_date for 'future' status
        }
        $current_chapter_effective_status = $chapter_post_data['post_status']; // Update effective status

        $new_chapter_id = wp_insert_post($chapter_post_data, true); // Pass true to return WP_Error on failure

        if (is_wp_error($new_chapter_id)) {
            bcu_log_error("Failed to create chapter '{$chapter_title}': " . $new_chapter_id->get_error_message());
            $error_messages[] = sprintf(__('Failed to create chapter %s: %s', 'fictioneer-bulk-upload'), esc_html($chapter_title), $new_chapter_id->get_error_message());
            continue;
        }

        // Post-creation meta and terms
        update_post_meta($new_chapter_id, 'fictioneer_chapter_story', $story_id); // Link to parent story

        if (!empty($selected_categories)) {
            wp_set_post_categories($new_chapter_id, $selected_categories);
        }

        // Handle password expiration
        if ($expire_count_from_form > 0 && !empty($chapter_password_from_form)) {
            $expire_unit = isset($_POST['bcu_expire_unit']) ? sanitize_text_field($_POST['bcu_expire_unit']) : 'days';
            $post_for_expiry_calc = get_post($new_chapter_id); 

            if ($post_for_expiry_calc && !empty($post_for_expiry_calc->post_date_gmt)) {
                $chapter_order_index = $index + 1; // 1-based index for multiplier
                $base_datetime_for_expiry_gmt_str = $post_for_expiry_calc->post_date_gmt;

                if (isset($_POST['bcu_schedule_type']) && $_POST['bcu_schedule_type'] === 'single' && !empty($_POST['bcu_expiry_start_date'])) {
                    $expiry_start_date_input_str = str_replace('T', ' ', $_POST['bcu_expiry_start_date']);
                    $site_timezone_obj = wp_timezone();
                    try {
                        $local_dt_obj = new DateTime($expiry_start_date_input_str, $site_timezone_obj);
                        $gmt_candidate_str = $local_dt_obj->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                        $base_datetime_for_expiry_gmt_str = $gmt_candidate_str; 
                    } catch (Exception $e) {
                        bcu_log_error('Invalid bcu_expiry_start_date (' . $_POST['bcu_expiry_start_date'] . '). Falling back to post date for expiry. Error: ' . $e->getMessage());
                    }
                }
                
                $final_expiration_datetime_gmt = null; 
                try {
                   $final_expiration_datetime_gmt = new DateTime($base_datetime_for_expiry_gmt_str, new DateTimeZone('UTC'));
                } catch (Exception $e_dt) {
                    bcu_log_error("CRITICAL: Failed to create DateTime for expiration for chapter {$new_chapter_id} from GMT string '{$base_datetime_for_expiry_gmt_str}'. Error: " . $e_dt->getMessage());
                }
                
                if ($final_expiration_datetime_gmt) { 
                    $total_increment_for_expiry = $expire_count_from_form * $chapter_order_index;
                    $date_interval_spec = '';
                    switch ($expire_unit) {
                        case 'minutes': $date_interval_spec = "PT{$total_increment_for_expiry}M"; break;
                        case 'hours':   $date_interval_spec = "PT{$total_increment_for_expiry}H"; break;
                        case 'days':    
                        default:        $date_interval_spec = "P{$total_increment_for_expiry}D"; break;
                    }
                    try {
                        $final_expiration_datetime_gmt->add(new DateInterval($date_interval_spec));
                        update_post_meta($new_chapter_id, 'fictioneer_post_password_expiration_date', $final_expiration_datetime_gmt->format('Y-m-d H:i:s'));
                    } catch (Exception $e_int) {
                         bcu_log_error("Error adding expiration interval '{$date_interval_spec}' for chapter {$new_chapter_id}. Error: " . $e_int->getMessage());
                    }
                }
            } else {
                 bcu_log_error("Could not retrieve post object or post_date_gmt for chapter ID {$new_chapter_id} to set expiration.");
            }
        } else { 
            delete_post_meta($new_chapter_id, 'fictioneer_post_password_expiration_date');
        }

        // Append to story using theme function (if available)
        // Note: 'publish' status is handled by the 'publish_fcn_chapter' hook calling bcu_handle_theme_chapter_submission
        if ($current_chapter_effective_status === 'draft' || $current_chapter_effective_status === 'future') {
            if (function_exists('fictioneer_append_chapter_to_story')) {
                fictioneer_append_chapter_to_story($new_chapter_id, $story_id, false); // $force = false
            } else {
                bcu_log_error("Theme function fictioneer_append_chapter_to_story() not found. Chapter {$new_chapter_id} (status: {$current_chapter_effective_status}) not appended to story {$story_id}.");
                if (!$theme_function_missing_error_shown) {
                    add_settings_error('bulk_chapter_upload_notices', 'theme_function_missing', __('The required theme function "fictioneer_append_chapter_to_story" is missing. Chapters may not be correctly associated with stories by this plugin. Please ensure your theme provides this functionality or chapters are associated manually.', 'fictioneer-bulk-upload'), 'warning');
                    $theme_function_missing_error_shown = true;
                }
            }
        }
        $chapters_created_count++;
    } // End foreach chapter file

    bcu_cleanup_temp_files($temp_dir);

    // Display success/error messages
    if ($chapters_created_count > 0) {
        add_settings_error('bulk_chapter_upload_notices', 'upload_success', sprintf(_n('%d chapter created successfully.', '%d chapters created successfully.', $chapters_created_count, 'fictioneer-bulk-upload'), $chapters_created_count), 'success');
    }
    if (!empty($error_messages)) {
        foreach ($error_messages as $error_msg) {
            add_settings_error('bulk_chapter_upload_notices', 'upload_partial_failure', $error_msg, 'error');
        }
    }
}

/**
 * Clean up temporary files and directory.
 *
 * @param string $temp_dir Path to the temporary directory.
 * @param string|null $zip_file_path Optional path to the uploaded ZIP file to also remove.
 */
function bcu_cleanup_temp_files($temp_dir, $zip_file_path = null) {
    if ($zip_file_path && file_exists($zip_file_path)) {
        @unlink($zip_file_path);
    }
    if (!empty($temp_dir) && is_dir($temp_dir)) { // Check if $temp_dir is not empty
        $files = glob(trailingslashit($temp_dir) . '*'); 
        if (is_array($files)) { // Ensure $files is an array
            foreach($files as $file_item){ 
              if(is_file($file_item)) {
                @unlink($file_item); 
              }
            }
        }
        @rmdir($temp_dir);
    }
}

/**
 * Display admin notices.
 * Hooked to 'admin_notices'.
 */
add_action('admin_notices', 'bcu_display_admin_notices');
function bcu_display_admin_notices() {
    // Only display on our plugin page, or use a more general approach if notices are for other pages too
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_bulk-chapter-upload') { // Check screen ID
        settings_errors('bulk_chapter_upload_notices');
    }
}

/**
 * Handle chapter submission via theme integration, typically hooked to 'publish_{custom_post_type}'.
 *
 * @param int $post_id The ID of the post being published.
 */
function bcu_handle_theme_chapter_submission($post_id) {
    static $already_processed_posts = []; 
    if (isset($already_processed_posts[$post_id])) {
        return; // Avoid recursion or double processing within the same request
    }
    $already_processed_posts[$post_id] = true;

    $post_object = get_post($post_id);
    if (!$post_object || $post_object->post_type !== 'fcn_chapter') { // Ensure it's the correct post type
        unset($already_processed_posts[$post_id]);
        return;
    }

    // Define log file path (consider making this more robust or configurable if needed)
    $upload_dir_info = wp_upload_dir();
    $log_file_path = trailingslashit($upload_dir_info['basedir']) . 'fictioneer_bulk_upload.log'; 

    $associated_story_id = get_post_meta($post_id, 'fictioneer_chapter_story', true);

    if (!empty($associated_story_id)) {
        $associated_story_id = intval($associated_story_id);
        $story_post_object = get_post($associated_story_id);

        if ($story_post_object && $story_post_object->post_type === 'fcn_story') {
            if (function_exists('fictioneer_append_chapter_to_story')) {
                fictioneer_append_chapter_to_story($post_id, $associated_story_id, true); // $force = true for publish hook
                file_put_contents($log_file_path, '['.date('Y-m-d H:i:s')."] HOOK: Theme function 'fictioneer_append_chapter_to_story' called. Chapter ID: {$post_id} to Story ID {$associated_story_id}" . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents($log_file_path, '['.date('Y-m-d H:i:s')."] HOOK ERROR: Theme function 'fictioneer_append_chapter_to_story' NOT FOUND for Chapter ID: {$post_id}" . PHP_EOL, FILE_APPEND);
                // Admin notice here is tricky as this is often a background process (cron for scheduled posts)
            }
        } else {
            file_put_contents($log_file_path, '['.date('Y-m-d H:i:s')."] HOOK: Invalid story ID ({$associated_story_id}) or story post type mismatch for chapter ID: {$post_id}" . PHP_EOL, FILE_APPEND);
        }
    } else {
        file_put_contents($log_file_path, '['.date('Y-m-d H:i:s')."] HOOK: 'fictioneer_chapter_story' meta NOT FOUND for chapter ID: {$post_id}" . PHP_EOL, FILE_APPEND);
    }
    unset($already_processed_posts[$post_id]);
}
// Hook to the specific CPT publish action for chapters
add_action('publish_fcn_chapter', 'bcu_handle_theme_chapter_submission', 10, 1);

/**
 * Calculate the scheduled date for a chapter based on its index and selected schedule type.
 *
 * @param int $chapter_index Zero-based index of the chapter in the batch.
 * @param string $schedule_type_slug Slug of the selected schedule type.
 * @return DateTime|null DateTime object in site's local timezone, or null if no schedule.
 */
function bcu_get_scheduled_date($chapter_index, $schedule_type_slug) {
    if (empty($schedule_type_slug) || $schedule_type_slug === 'none') {
        return null;
    }
    
    $site_timezone_object = wp_timezone(); // Get WordPress configured timezone object

    switch ($schedule_type_slug) {
        case 'single':
            if (empty($_POST['bcu_schedule_date'])) return null;
            try {
                // Input 'datetime-local' is assumed to be in the site's local timezone
                $base_datetime_local = new DateTime($_POST['bcu_schedule_date'], $site_timezone_object);
            } catch (Exception $e) {
                bcu_log_error("Invalid bcu_schedule_date format: " . $_POST['bcu_schedule_date'] . ". Error: " . $e->getMessage());
                return null;
            }

            $delay_value_per_step = !empty($_POST['bcu_delay_value']) ? intval($_POST['bcu_delay_value']) : 0;
            $delay_unit_slug = !empty($_POST['bcu_delay_unit']) ? $_POST['bcu_delay_unit'] : 'minutes';
            
            if ($delay_value_per_step > 0 && $chapter_index > 0) { // Apply delay only for chapters after the first
                $total_delay_for_this_chapter = $delay_value_per_step * $chapter_index; 
                $date_interval_string = '';
                switch ($delay_unit_slug) {
                    case 'hours':   $date_interval_string = "PT{$total_delay_for_this_chapter}H"; break;
                    case 'days':    $date_interval_string = "P{$total_delay_for_this_chapter}D";  break;
                    case 'minutes':
                    default:        $date_interval_string = "PT{$total_delay_for_this_chapter}M"; break;
                }
                try {
                    $base_datetime_local->add(new DateInterval($date_interval_string));
                } catch (Exception $e) {
                     bcu_log_error("Error adding schedule delay interval '{$date_interval_string}' for chapter index {$chapter_index}. Error: " . $e->getMessage());
                     // If interval fails, return the base_datetime_local without the problematic delay
                }
            }
            return $base_datetime_local; // DateTime object in site's local timezone
            
        case 'weekly':
            if (empty($_POST['bcu_schedule_days']) || !is_array($_POST['bcu_schedule_days']) || empty($_POST['bcu_weekly_time'])) {
                return null;
            }

            $selected_days_of_week = $_POST['bcu_schedule_days']; 
            list($sched_hours_str, $sched_minutes_str) = explode(':', $_POST['bcu_weekly_time']);
            $scheduled_hours = intval($sched_hours_str); 
            $scheduled_minutes = intval($sched_minutes_str);

            // Basic validation for time components
            if ($scheduled_hours < 0 || $scheduled_hours > 23 || $scheduled_minutes < 0 || $scheduled_minutes > 59) {
                bcu_log_error("Invalid bcu_weekly_time format: " . $_POST['bcu_weekly_time']);
                return null;
            }

            // Start checking from 'now' (in site's timezone)
            $current_check_datetime = new DateTime('now', $site_timezone_object);
            $current_check_datetime->setTime($scheduled_hours, $scheduled_minutes, 0); // Set to the desired publishing time
            
            // If today's slot at the specified time is already past, start search from tomorrow's slot to ensure future scheduling
            $now_datetime = new DateTime('now', $site_timezone_object);
            if ($current_check_datetime <= $now_datetime) {
                $current_check_datetime->modify('+1 day'); 
                // Time is already set, no need to set it again unless day modification changed it (it shouldn't here)
            }

            $chapters_found_for_schedule = 0;
            $iteration_safety_guard = 0; // Prevents infinite loops

            while ($chapters_found_for_schedule <= $chapter_index && $iteration_safety_guard < 730 ) { // Max ~2 years search
                $day_name_lowercase = strtolower($current_check_datetime->format('l')); // 'l' gives full day name, e.g., "monday"

                if (in_array($day_name_lowercase, $selected_days_of_week)) {
                    // Ensure the found slot is definitely in the future from the actual current time
                    if ($current_check_datetime > $now_datetime) {
                         if ($chapters_found_for_schedule == $chapter_index) {
                            return $current_check_datetime; // Found the correct slot for this chapter
                        }
                        $chapters_found_for_schedule++;
                    }
                }
                $current_check_datetime->modify('+1 day'); // Move to the same time on the next day
                $iteration_safety_guard++;
            }
            bcu_log_error("Could not find a suitable weekly schedule slot for chapter index {$chapter_index} within approximately 2 years.");
            break;
    }
    return null; // No valid schedule found or 'none' type
}

/**
 * Load plugin textdomain for internationalization.
 */
add_action('plugins_loaded', 'bcu_load_textdomain');
function bcu_load_textdomain() {
    load_plugin_textdomain('fictioneer-bulk-upload', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
