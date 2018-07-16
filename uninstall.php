<?php

// If uninstall.php is not called by WordPress, bail out!
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_post_meta_by_key(DBL_SJB_HR_EMAILS);
