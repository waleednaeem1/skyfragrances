<?php
defined('SKYFR') || exit;

if (request_origin_is_foreign()) {
    json(['ok' => false, 'error' => 'origin'], 403);
}

json(['ok' => true, 'csrf' => csrf_token()]);
