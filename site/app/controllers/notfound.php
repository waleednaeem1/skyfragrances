<?php
defined('SKYFR') || exit;

$status = http_response_code();
abort(is_int($status) && $status >= 400 ? $status : 404);
