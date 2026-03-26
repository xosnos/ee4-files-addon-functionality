<?php

require_once __DIR__ . '/../../files-addon-for-event-espresso-4/EE_FILE_Validation_Strategy.php';

class EE_FILE_Validation_Strategy_Ext extends EE_FILE_Validation_Strategy {
    public function validate($normalized_value) {
        if ($normalized_value == '') {
            return true;
        }

        global $wpdb;
        $qid = $this->_input->name();

        $table = $wpdb->prefix . 'esp_question_allowed_ext';

        $ext = $wpdb->get_var("SELECT ext FROM $table WHERE qst_id='$qid'");
        $ex = ($ext != '')? explode(',',$ext) : '';

        $filetype = wp_check_filetype($normalized_value);
        $data = parse_url($normalized_value);

        // FIX 1: If the value doesn't parse as a full URL (e.g. a bare filename was
        // passed instead of a hosted URL after upload), fail validation immediately
        // rather than hitting a PHP notice on $data['host'] being undefined.
        if (empty($data) || !isset($data['host']) || $data['host'] === '') {
            throw new EE_Validation_Error($this->get_validation_error_message(), 'regex');
        }

        $host = $data['host'];

        // FIX 2: Use parse_url on S3_UPLOADS_BUCKET_URL to extract only the host
        // portion. The old str_replace('https://', '', ...) approach left any path
        // suffix attached (e.g. "media.unavsa.org/some/path"), causing strpos() to
        // fail when comparing against just "media.unavsa.org".
        if (defined('S3_UPLOADS_BUCKET_URL')) {
            $bucket_parts = parse_url(S3_UPLOADS_BUCKET_URL);
            $server = isset($bucket_parts['host']) ? $bucket_parts['host'] : str_replace('https://', '', S3_UPLOADS_BUCKET_URL);
        } else {
            $server = $_SERVER['SERVER_NAME'];
        }

        $allowed = ($ex != '') ? $ex : array('gif','png' ,'jpg','jpeg','bmp');

        $extn = $filetype['ext'];
        if (!in_array($extn, $allowed) || strpos($host, $server) === FALSE) {
            throw new EE_Validation_Error($this->get_validation_error_message(), 'regex');
        }

        // validating with grandparent. cannot validate with parent because we need to overwrite the functionality of the parent class to be compatible with s3-uploads
        // from https://stackoverflow.com/a/8212262
        EE_Validation_Strategy_Base::validate($normalized_value);

    }
}
