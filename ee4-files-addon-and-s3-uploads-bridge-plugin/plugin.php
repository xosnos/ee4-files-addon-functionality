<?php

/*
Plugin Name: EE4 Files Addon Functionality Plugin
Plugin URI:  https://github.com/xosnos/ee4-files-addon-functionality
Description: Functionality plugin to improve the EE4 files addon, last updated in 2017. 
Author:      Steven Nguyen (UNAVSA-21 Associate Director), Alex Gobert ('24-'25 UNAVSA IT Director)
Author URI:  https://unavsa.org/
Version:     1.3.4
License:     GPL-3.0
License URI: https://opensource.org/license/gpl-3-0
*/

define('EE4_FILES_ADDON_UPLOAD_DIR', '/espresso_file_uploads');

// rename files to reduce filename collisions
add_filter('ssa_override_filename', 'normalize_filename');

function normalize_filename(string $filename) {
    $current_date = new DateTime('now', new DateTimeZone(wp_timezone_string()));
    $current_date_str = $current_date->format('Ymd-his'); // date as 20250101-130559 for January 1st, 2025 at 1:05:59 PM

    $offset = $current_date->format('O');
    $offset = str_replace('+', 'p', str_replace('-', 'm', $offset));

    return pathinfo($filename, PATHINFO_FILENAME) . '-' . $current_date_str . $offset . '.' . pathinfo($filename, PATHINFO_EXTENSION);
}

// save ee files addon files to a separate directory
// see Collins Mbaka's notes at https://developer.wordpress.org/reference/hooks/upload_dir/#user-contributed-notes
add_filter('ssa_change_file_upload_path', 'ee_change_dir');
function ee_change_dir($param) {
    $ee_dir = defined('EE4_FILES_ADDON_UPLOAD_DIR') ? EE4_FILES_ADDON_UPLOAD_DIR : '';

    $param['path'] = str_replace('/uploads', $ee_dir, $param['path']);
    $param['url'] = str_replace('/uploads', $ee_dir, $param['url']);;

    return $param;
}

// bridge compatibility of the EE4 files addon and humanmade/s3-uploads. See https://unavsait.atlassian.net/browse/UIT-123?atlOrigin=eyJpIjoiYWIyNjhjYzI2MWRhNDZjMDg3ZTlhZTMzYWJlNGY5OWQiLCJwIjoiaiJ9

add_action('init', 'add_extended_filter');

function add_extended_filter() {
    add_filter('FHEE__EE_SPCO_Reg_Step_Attendee_Information___generate_question_input__default', 'ssa_render_question_ext', 11, 4);
}

function ssa_render_question_ext($param1, $type, $question, $args) {
    require_once __DIR__ . "/classes/EE_SSA_File_Ext.php";

    if ($type == 'file') {
        return new EE_SSA_FILE_Ext($args);
    }
    
    return $param1;
}