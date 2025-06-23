<?php
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
if (function_exists('zen_register_admin_page')) {
    if (!zen_page_key_exists('toolsBildverschieber')) {
        // Add Bildverschieber to Tools menu
        zen_register_admin_page('toolsBildverschieber', 'BOX_TOOLS_BILDVERSCHIEBER','FILENAME_BILDVERSCHIEBER', '', 'tools', 'Y', 160);
    }
}