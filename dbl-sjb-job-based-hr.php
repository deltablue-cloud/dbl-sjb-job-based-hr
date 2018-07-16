<?php
/*
 * Plugin Name: Job based HR recipients
 * Description: Allow different HR recipients on a per job basis for the Simple Job Board plugin.
 * Version: 1.0.0
 * Author: DeltaBlue
 * Author URI: http://www.delta.blue
 * License: MIT
 * License URI: http://opensource.org/licenses/mit-license.html
 * Text Domain: dbl-sjb-job-based-hr
 * Domain Path: /languages
*/

/**
 * @file
 *
 * PLEASE do NOT use this as an example of 'How to code a wordpress plugin'!
 * It is just a quick plugin that serves a single and extremely simple purpose.
 * You should definitely use an OO approach.
 */

define('DBL_SJB_HR_EMAILS', 'dbl_sjb_hr_emails');
define('DBL_SJB_HR_EMAILS_NONCE', 'dbl_sjb_hr_emails_nonce');

/**
 * Helper
 *
 * @param $str
 *
 * @return bool
 */
function dbl_sjb_hr_emails_empty_str($str) {
    return !isset($str) || $str === '';
}


/**
 * Load the plugin text domain for translation.
 */
function dbl_sjb_hr_emails_load_plugin_textdomain() {
    load_plugin_textdomain('dbl-sjb-job-based-hr', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'dbl_sjb_hr_emails_load_plugin_textdomain');


/**
 * Add a meta box to the Job post type using dbl_sjb_hr_emails_field() as
 * callback.
 */
function dbl_sjb_hr_emails_meta_box() {
    if (is_plugin_active('simple-job-board/simple-job-board.php')) {
        add_meta_box(
            'dbl-sjb-hr-emails',
            __('HR emails', 'dbl-sjb-job-based-hr'),
            'dbl_sjb_hr_emails_field',
            'jobpost'
        );
    }
}


/**
 * The callback function used in dbl_sjb_hr_emails_meta_box()
 *
 * @param WP_Post $post
 *
 * @see dbl_sjb_hr_emails_meta_box()
 */
function dbl_sjb_hr_emails_field($post) {
    $field_value = get_post_meta($post->ID, DBL_SJB_HR_EMAILS, true);
    wp_nonce_field(DBL_SJB_HR_EMAILS_NONCE, DBL_SJB_HR_EMAILS_NONCE);

    echo '<div class="simple-job-board-metabox">'
        .'  <p class="metabox-field">'
        .'    <label for="'. DBL_SJB_HR_EMAILS . '">' . __('Override HR emails', 'dbl-sjb-job-based-hr') . '</label>'
        .'    <input id="' . DBL_SJB_HR_EMAILS . '" name="'. DBL_SJB_HR_EMAILS .'" type="text" value="' . esc_attr($field_value) .'" />'
        .'    <span>' . __('Separate multiple emails with a space!', 'dbl-sjb-job-based-hr') . '</span>'
        .'  </p>'
        .'</div>'
    ;
}
add_action('add_meta_boxes', 'dbl_sjb_hr_emails_meta_box');


/**
 * Save the field defined in dbl_sjb_hr_emails_field().
 *
 * @param int $post_id
 *
 * @see dbl_sjb_hr_emails_field()
 */
function dbl_sjb_hr_emails_save($post_id) {
    $post = get_post($post_id);
    $is_revision = wp_is_post_revision($post_id);

    // Do not save meta for a revision or on autosave.
    if ($post->post_type != 'jobpost' || $is_revision) {
        return;
    }

    // Do not save meta if fields are not present,
    // like during a restore.
    if (!isset($_POST[DBL_SJB_HR_EMAILS])) {
        return;
    }

    // Secure with nonce field check
    if(!check_admin_referer(DBL_SJB_HR_EMAILS_NONCE, DBL_SJB_HR_EMAILS_NONCE)) {
        return;
    }

    // Clean up data.
    $field_value = trim($_POST[DBL_SJB_HR_EMAILS]);

    // Do the saving and deleting.
    if (!dbl_sjb_hr_emails_empty_str($field_value)) {
        update_post_meta($post_id, DBL_SJB_HR_EMAILS, $field_value);
    } elseif(dbl_sjb_hr_emails_empty_str($field_value)) {
        delete_post_meta($post_id, DBL_SJB_HR_EMAILS);
    }
}
add_action('save_post', 'dbl_sjb_hr_emails_save');


/**
 * Modify Email Address of HR Notification Receiver
 *
 * @param string $hr_email HR Email
 * @param int $post_id HR Email
 * @return string  $hr_email Modified HR Email | Multiple HR Emails
 */
function dbl_sjb_hr_emails_to($hr_email, $post_id) {
    if (FALSE != get_option('settings_hr_emails') && '' != get_option('settings_hr_emails')) {
        if ($parent_id = wp_get_post_parent_id($post_id)) {
            $field_value = get_post_meta($parent_id, DBL_SJB_HR_EMAILS, true);
            if (!empty($field_value)) {
                // String to array conversion for mail validation.
                $hr_emails = explode(" ", str_replace(',', '', $field_value));

                // Email validation.
                foreach ($hr_emails as $email) {
                    if (is_email($email)) {
                        $emails[] = $email;
                    }
                }

                // Array to string conversion for mail senders $to format.
                $hr_email = implode(',', $emails);
            }
        }
    }
    return $hr_email;
}
add_filter('sjb_hr_notification_to', 'dbl_sjb_hr_emails_to', 20, 2);


/**
 * Display dependency warning.
 */
function dbl_sjb_dependency__warning() {
    if (!is_plugin_active('simple-job-board/simple-job-board.php')) {
        $class = 'notice notice-warning';
        $message = __('You will need to activate the "Simple Job Board" plugin to make use of the "Job based HR recipients" plugin!', 'dbl-sjb-job-based-hr');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
}
add_action('admin_notices', 'dbl_sjb_dependency__warning');
