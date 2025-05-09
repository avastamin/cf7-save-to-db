<?php
/**
 * Plugin Name: Save Submissions to DB for Contact Form 7
 * Description: Save submissions from Contact Form 7 to the database.
 * Version: 1.0.0
 * Requires Plugins: contact-form-7
 * Author: Ruhul Amin
 * Contributors: ruhul105
 * Author URI: https://ruhulamin.me
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CF7_Save_To_DB
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CF7_SAVE_TO_DB_VERSION', '1.0.0');
define('CF7_SAVE_TO_DB_FILE', __FILE__);
define('CF7_SAVE_TO_DB_PATH', plugin_dir_path(__FILE__));
define('CF7_SAVE_TO_DB_URL', plugin_dir_url(__FILE__));

// Load plugin text domain
function cfdb7_save_to_db_load_textdomain() {
    load_plugin_textdomain('cf7-to-db', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cfdb7_save_to_db_load_textdomain');

// Activation hook with error handling
function cfdb7_save_to_db_create_table(): void {
    global $wpdb;
    
    try {
        $table_name = $wpdb->prefix . 'cf7_submissions';
        $charset_collate = $wpdb->get_charset_collate();
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Creating table with dbDelta
        $sql = $wpdb->prepare(
            "CREATE TABLE {$table_name} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                form_id mediumint(9) NOT NULL,
                form_name varchar(255) NOT NULL,
                submission_data longtext NOT NULL,
                submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                KEY submitted_at (submitted_at)
            ) {$charset_collate}"
        );
        // phpcs:enable

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if (!empty($wpdb->last_error)) {
            throw new Exception($wpdb->last_error);
        }
        
        add_option('cfdb7_save_to_db_version', CF7_SAVE_TO_DB_VERSION);
    } catch (Exception $e) {
        wp_die('Error creating database table: ' . esc_html($e->getMessage()));
    }
}
register_activation_hook(CF7_SAVE_TO_DB_FILE, 'cfdb7_save_to_db_create_table');

// Save submission with improved security and validation
function cfdb7_save_to_db_submission($contact_form): void {
    global $wpdb;
    
    try {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            throw new Exception('No submission instance found');
        }

        $form_data = wp_unslash($submission->get_posted_data());
        $form_id = absint($contact_form->id());
        $form_name = sanitize_text_field($contact_form->title());
        
        // Validate required data
        if (empty($form_data) || empty($form_id) || empty($form_name)) {
            throw new Exception('Required submission data missing');
        }

        $table_name = $wpdb->prefix . 'cf7_submissions';
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Using wpdb insert method
        $result = $wpdb->insert(
            $table_name,
            [
                'form_id' => $form_id,
                'form_name' => $form_name,
                'submission_data' => wp_json_encode($form_data),
                'submitted_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );
        // phpcs:enable

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Use WordPress logging function instead of error_log
            if (function_exists('wp_debug_log')) {
                wp_debug_log('CF7 Save to DB Error: ' . $e->getMessage());
            }
        }
    }
}
add_action('wpcf7_before_send_mail', 'cfdb7_save_to_db_submission');

// Admin menu registration
function cfdb7_save_to_db_admin_menu(): void {
    // Check if Contact Form 7 is active
    if (!defined('WPCF7_VERSION')) {
        // If CF7 is not active, add as top level menu
        add_menu_page(
            __('CF7 Submissions', 'cf7-to-db'),
            __('CF7 Submissions', 'cf7-to-db'),
            'manage_options',
            'cf7-submissions',
            'cfdb7_save_to_db_render_admin_page',
            'dashicons-feedback',
            20
        );
        return;
    }

    // Add as submenu under Contact Form 7
    add_submenu_page(
        'wpcf7', // Parent slug (Contact Form 7)
        __('Submissions', 'cf7-to-db'),
        __('Submissions', 'cf7-to-db'),
        'manage_options',
        'cf7-submissions',
        'cfdb7_save_to_db_render_admin_page'
    );
}
add_action('admin_menu', 'cfdb7_save_to_db_admin_menu');

// Admin page rendering
function cfdb7_save_to_db_render_admin_page(): void {
    // Security checks
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'cf7-to-db'));
    }

    // Nonce verification for filters
    if (isset($_GET['form_id']) || isset($_GET['paged'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'cf7_submissions_filter')) {
            wp_die(esc_html__('Security check failed.', 'cf7-to-db'));
        }
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_submissions';
    $current_form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Get forms with prepared statement
    $forms = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT form_id, form_name FROM %i ORDER BY form_name ASC",
            $table_name
        )
    );

    // Get submissions
    if ($current_form_id) {
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE form_id = %d ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $table_name,
                $current_form_id,
                $per_page,
                $offset
            )
        );
        
        $total_items = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE form_id = %d",
                $table_name,
                $current_form_id
            )
        );
    } else {
        $submissions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $table_name,
                $per_page,
                $offset
            )
        );
        
        $total_items = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM %i",
                $table_name
            )
        );
    }

    $total_pages = ceil($total_items / $per_page);
    require_once CF7_SAVE_TO_DB_PATH . 'templates/admin-page.php';
}

// Enqueue admin assets
function cfdb7_save_to_db_admin_assets($hook): void {
    // Check both possible page hooks
    if ('contact_page_cf7-submissions' !== $hook && 'toplevel_page_cf7-submissions' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'cf7-to-db-admin',
        CF7_SAVE_TO_DB_URL . 'assets/css/admin.css',
        [],
        CF7_SAVE_TO_DB_VERSION
    );

    wp_enqueue_script(
        'cf7-to-db-admin',
        CF7_SAVE_TO_DB_URL . 'assets/js/admin.js',
        ['jquery'],
        CF7_SAVE_TO_DB_VERSION,
        true
    );

    wp_localize_script('cf7-to-db-admin', 'cf7SaveToDb', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cf7_save_to_db_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'cfdb7_save_to_db_admin_assets');

// AJAX handler for getting submission details
function cfdb7_save_to_db_get_submission_details() {
    // Check nonce for security
    check_ajax_referer('cf7_save_to_db_nonce');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access', 403);
        return;
    }

    // Get and validate submission ID
    $submission_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (!$submission_id) {
        wp_send_json_error('Invalid submission ID', 400);
        return;
    }

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_submissions';
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Query built with prepare
        $submission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $submission_id
            )
        );
        // phpcs:enable

        if (!$submission) {
            wp_send_json_error('Submission not found', 404);
            return;
        }

        // Decode the submission data
        $submission_data = json_decode($submission->submission_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding submission data');
        }

        // Format the data for display
        $formatted_data = [
            'Form' => $submission->form_name,
            'Submitted At' => wp_date(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($submission->submitted_at)
            )
        ];

        // Add form fields data
        foreach ($submission_data as $key => $value) {
            // Skip empty values and internal fields
            if (empty($value) || str_starts_with($key, '_')) {
                continue;
            }

            // Handle array values (like checkboxes or file uploads)
            if (is_array($value)) {
                if (isset($value['url'])) {
                    // This is already a file URL
                    $value = sprintf(
                        '<img src="%s" alt="%s" style="max-width: 200px; height: auto;" />', 
                        esc_url($value['url']),
                        esc_attr($key)
                    );
                } else {
                    // Check if this is a file ID (format: file-{number}: {hash})
                    $first_value = is_array($value) ? reset($value) : $value;
                    if (preg_match('/^file-\d+: [a-f0-9]+$/', $first_value)) {
                        // Extract file ID
                        $file_id = intval(substr($first_value, 5, strpos($first_value, ':') - 5));
                        $attachment_url = wp_get_attachment_url($file_id);
                        
                        if ($attachment_url) {
                            // Check if it's an image
                            $mime_type = get_post_mime_type($file_id);
                            if (strpos($mime_type, 'image/') === 0) {
                                $value = sprintf(
                                    '<img src="%s" alt="%s" style="max-width: 200px; height: auto;" />', 
                                    esc_url($attachment_url),
                                    esc_attr($key)
                                );
                            } else {
                                // For non-image files, show a download link
                                $value = sprintf(
                                    '<a href="%s" target="_blank" class="button">%s</a>', 
                                    esc_url($attachment_url),
                                    esc_html__('Download File', 'cf7-to-db')
                                );
                            }
                        } else {
                            $value = esc_html__('File not found', 'cf7-to-db');
                        }
                    } else {
                        // Regular array value (like checkbox)
                        $value = implode(', ', array_map('esc_html', $value));
                    }
                }
            }

            $formatted_data[ucwords(str_replace(['_', '-'], ' ', $key))] = $value;
        }

        wp_send_json_success($formatted_data);

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), 500);
    }
}
add_action('wp_ajax_get_submission_details', 'cfdb7_save_to_db_get_submission_details');

// Update submission handler
function cfdb7_save_to_db_update_submission() {
    check_ajax_referer('cf7_save_to_db_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access', 403);
        return;
    }

    $submission_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    
    // First validate that we have data
    if (!isset($_POST['data'])) {
        wp_send_json_error('Missing submission data', 400);
        return;
    }

    // First unslash the raw data
    $data = wp_kses( wp_unslash( $_POST['data'] ), array( 'strong' => [], 'em' => [], 'a' => [ 'href' => [] ] ) );

    // Then validate it's an array
    if (!is_array($raw_input)) {
        wp_send_json_error('Invalid submission data format', 400);
        return;
    }

    // Sanitize the input data
    $submission_data = [];
    foreach ($raw_input as $key => $value) {
        // Sanitize the key
        $sanitized_key = sanitize_key($key);
        
        // Sanitize the value based on type
        if (is_array($value)) {
            $submission_data[$sanitized_key] = array_map('sanitize_text_field', $value);
        } else {
            $submission_data[$sanitized_key] = sanitize_text_field($value);
        }
    }

    if (!$submission_id || empty($submission_data)) {
        wp_send_json_error('Invalid submission data', 400);
        return;
    }

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_submissions';

        // Get existing submission
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Query built with prepare
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $submission_id
            )
        );
        // phpcs:enable

        if (!$existing) {
            throw new Exception('Submission not found');
        }

        // Merge new data with existing data
        $existing_data = json_decode($existing->submission_data, true);
        if (!is_array($existing_data)) {
            throw new Exception('Invalid existing submission data');
        }

        foreach ($submission_data as $key => $value) {
            $field_key = sanitize_key(strtolower(str_replace(' ', '_', $key)));
            if (isset($existing_data[$field_key])) {
                $existing_data[$field_key] = $value; // Already sanitized above
            }
        }

        // Update the submission
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Using wpdb update method
        $result = $wpdb->update(
            $table_name,
            ['submission_data' => wp_json_encode($existing_data)],
            ['id' => $submission_id],
            ['%s'],
            ['%d']
        );
        // phpcs:enable

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        wp_send_json_success();

    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (function_exists('wp_debug_log')) {
                wp_debug_log('CF7 Save to DB Error: ' . $e->getMessage());
            }
        }
        wp_send_json_error(
            esc_html__('An error occurred while processing your request.', 'cf7-to-db'),
            500
        );
    }
}
add_action('wp_ajax_update_submission', 'cfdb7_save_to_db_update_submission');

// Delete submission handler
function cfdb7_save_to_db_delete_submission() {
    check_ajax_referer('cf7_save_to_db_nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized access', 403);
        return;
    }

    $submission_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (!$submission_id) {
        wp_send_json_error('Invalid submission ID', 400);
        return;
    }

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_submissions';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Query built with prepare
        $result = $wpdb->delete(
            $table_name,
            ['id' => $submission_id],
            ['%d']
        );
        // phpcs:enable

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        wp_send_json_success();

    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), 500);
    }
}
add_action('wp_ajax_delete_submission', 'cfdb7_save_to_db_delete_submission');

