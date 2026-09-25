<?php
defined('SKYFR') || exit;
$messages = flash_take();
if ($messages === []) {
    return;
}
?>
<div class="flash-stack container">
<?php foreach ($messages as $flashItem): ?>
<?php
    $flashType = (string) ($flashItem['type'] ?? 'info');
    if (!in_array($flashType, ['success', 'error', 'info'], true)) {
        $flashType = 'info';
    }
    $flashRole = $flashType === 'error' ? 'alert' : 'status';
?>
  <div class="flash flash--<?= e($flashType) ?>" role="<?= e($flashRole) ?>"><?= e((string) ($flashItem['message'] ?? '')) ?></div>
<?php endforeach; ?>
</div>
