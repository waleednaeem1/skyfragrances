<?php
defined('SKYFR') || exit;
$status = (int) ($status ?? 404);
$heading = (string) ($heading ?? 'Page not found');
$message = (string) ($message ?? '');
?>
<?php partial_admin('empty.php', [
    'title' => $heading,
    'text' => $message !== '' ? $message : 'There is nothing at ' . (string) ($requestedPath ?? request_path()) . '.',
    'action' => ['label' => 'Back to dashboard', 'href' => '/admin', 'variant' => 'ghost'],
]); ?>
<?php if ($status >= 500): ?>
<p class="adm-note">If this keeps happening, note the time and tell the developer.</p>
<?php endif; ?>
