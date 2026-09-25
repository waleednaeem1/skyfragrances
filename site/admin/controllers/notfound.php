<?php
defined('SKYFR') || exit;

http_status(404);

render_admin('404.php', [
    'status' => 404,
    'heading' => 'Page not found',
    'message' => '',
    'requestedPath' => request_path(),
], [
    'title' => 'Page not found',
    'robots' => 'noindex, nofollow',
    'body_class' => 'admin t-light admin-notfound',
]);
