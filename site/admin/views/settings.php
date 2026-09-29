<?php
defined('SKYFR') || exit;
$inputType = static fn (array $def): string => match ($def['type']) {
    'url' => !empty($def['relative']) ? 'text' : 'url',
    'baseurl' => 'url',
    'cidr' => 'text',
    'int' => 'int',
    'money' => 'money',
    'email' => 'email',
    'phone' => 'phone',
    'textarea' => 'textarea',
    default => 'text',
};
$imagePreview = static function (string $path): string {
    $path = ltrim($path, '/');
    if ($path === '' || !is_file(APP_ROOT . '/' . $path)) {
        return '';
    }
    return str_starts_with($path, 'assets/') ? asset(substr($path, 7)) : url('/' . $path) . '?v=' . (int) @filemtime(APP_ROOT . '/' . $path);
};
partial_admin('page-header.php', ['title' => 'Settings', 'subtitle' => 'Each tab saves on its own. Nothing here changes an order that already exists.']);
partial_admin('tabs.php', ['tabs' => $tabs, 'label' => 'Settings sections']);
?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  Please fix the following:
  <ul>
<?php foreach ($errors as $key => $message): ?>
    <li><a href="#<?= e($key) ?>"><?= e($fields[$key]['label'] ?? $key) ?></a>: <?= e($message) ?></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="adm-form" id="settings-form" enctype="multipart/form-data" novalidate data-guard>
  <?= csrf_field() ?>
  <input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="hidden" name="action" value="save">
  <div class="adm-form__section">
    <h2 class="adm-form__section-title"><?= e($tabLabel) ?></h2>
<?php foreach ($fields as $key => $def): $value = (string) ($values[$key] ?? ''); $error = (string) ($errors[$key] ?? ''); $help = (string) ($def['help'] ?? ''); ?>
<?php if (in_array($def['type'], ['secret', 'https', 'capability'], true)): continue; endif; ?>
<?php if ($def['type'] === 'readonly'): ?>
    <div class="adm-field">
      <span class="adm-field__label"><?= e($def['label']) ?></span>
      <p class="adm-mono"><?= e($key === 'install_completed_at' ? ($value === '' ? 'Unknown' : date_long($value)) : ($value === '' ? '—' : $value)) ?></p>
<?php if ($help !== ''): ?>
      <p class="adm-field__help"><?= e($help) ?></p>
<?php endif; ?>
    </div>
<?php elseif ($def['type'] === 'bool'): ?>
<?php partial_admin('field.php', ['type' => 'checkbox', 'name' => $key, 'id' => $key, 'label' => $def['label'], 'value' => $value === '1', 'error' => $error, 'help' => $help]); ?>
<?php elseif ($def['type'] === 'image'): $preview = $imagePreview($value); ?>
    <div class="adm-field<?= $error !== '' ? ' adm-field--error' : '' ?>" id="<?= e($key) ?>">
      <label class="adm-field__label" for="image_<?= e($key) ?>"><?= e($def['label']) ?></label>
      <div class="adm-media">
<?php if ($preview !== ''): ?>
        <img class="adm-thumb" src="<?= e($preview) ?>" alt="" width="64" height="64">
<?php endif; ?>
        <div class="adm-media__body">
          <input class="adm-input" type="file" id="image_<?= e($key) ?>" name="image_<?= e($key) ?>" accept="image/jpeg,image/png,image/webp"<?= $error !== '' ? ' aria-invalid="true"' : '' ?>>
          <p class="adm-media__meta"><?= $value === '' ? 'No image set.' : 'Current: ' . e(basename($value)) ?> · JPG, PNG or WEBP up to <?= e(upload_human_size(UPLOAD_MAX_PRODUCT_BYTES)) ?>.</p>
<?php if ($value !== '' && $value !== (string) ($defaults[$key] ?? '')): ?>
          <label class="adm-check"><input type="checkbox" class="adm-check__input" name="remove_<?= e($key) ?>" value="1"><span class="adm-check__box" aria-hidden="true"></span><span class="adm-check__label">Remove and go back to the default</span></label>
<?php endif; ?>
        </div>
      </div>
<?php if ($error !== ''): ?>
      <p class="adm-field__error"><?= e($error) ?></p>
<?php endif; ?>
<?php if ($help !== ''): ?>
      <p class="adm-field__help"><?= e($help) ?></p>
<?php endif; ?>
    </div>
<?php else: ?>
<?php
if ($key === 'base_url') {
    $help = 'You opened this page at ' . $panelOrigin . ' — use that unless you know why not. ' . $help;
}
if ($key === 'free_shipping_threshold' && money_paisa($value === '' ? '0' : str_replace(',', '', $value)) === 0) {
    $help = 'Delivery is always free right now. ' . $help;
}
$fieldArgs = ['type' => $inputType($def), 'name' => $key, 'id' => $key, 'label' => $def['label'], 'value' => $def['type'] === 'money' && str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value, 'error' => $error, 'help' => $help, 'required' => !empty($def['required'])];
if (isset($def['maxlength'])) {
    $fieldArgs['maxlength'] = (int) $def['maxlength'];
}
if (isset($def['rows'])) {
    $fieldArgs['rows'] = (int) $def['rows'];
}
if ($def['type'] === 'int') {
    $fieldArgs['attr'] = ['min' => (string) ($def['min'] ?? 0), 'max' => (string) ($def['max'] ?? 999)];
}
if ($key === 'base_url') {
    $fieldArgs['placeholder'] = $panelOrigin;
}
partial_admin('field.php', $fieldArgs);
?>
<?php endif; ?>
<?php endforeach; ?>
  </div>
</form>
<?php
$actionButtons = [['label' => 'Save ' . $tabLabel, 'variant' => 'gold', 'type' => 'submit', 'name' => 'action', 'value' => 'save', 'attr' => ['form' => 'settings-form']]];
$wordingKeys = is_array($wordingKeys ?? null) ? $wordingKeys : [];
if ($wordingKeys !== []) {
    $wordingLabels = array_map(static fn (string $key): string => (string) ($fields[$key]['label'] ?? $key), $wordingKeys);
    $actionButtons[] = ['label' => 'Restore default wording', 'variant' => 'ghost', 'post' => '/admin/settings/restore-wording', 'fields' => ['tab' => $tab], 'confirm' => 'Restore the default wording on the ' . $tabLabel . " tab?\nPuts the original Sky Fragrances text back into: " . implode(', ', $wordingLabels) . '. Everything else on this tab stays as it is, and anything typed but not saved is lost.', 'confirm_label' => 'Restore the wording'];
}
partial_admin('action-bar.php', ['buttons' => $actionButtons, 'note' => 'Saves this tab only']);
?>
<?php if ($tab === 'advanced'): ?>
<div class="adm-card">
  <div class="adm-card__head"><h2 class="adm-card__title">Server</h2></div>
  <p class="adm-muted">These change how the server behaves. Each one asks before it acts.</p>
  <ul class="adm-list">
    <li>
      <span>Preview key during maintenance<br><span class="adm-mono"><?= e((string) ($values['maintenance_bypass'] ?? '') === '' ? 'Not set' : (string) $values['maintenance_bypass']) ?></span><br><span class="adm-note">Type this on the closed page to see the shop while it is off.</span></span>
      <span class="adm-cluster">
<?php if ((string) ($values['maintenance_bypass'] ?? '') !== ''): ?>
        <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-copy="<?= e((string) $values['maintenance_bypass']) ?>">Copy</button>
<?php endif; ?>
<?php partial_admin('button.php', ['label' => 'New key', 'variant' => 'ghost', 'size' => 'sm', 'post' => '/admin/settings', 'fields' => ['tab' => 'advanced', 'action' => 'regenerate_bypass'], 'confirm' => "Generate a new preview key?\nThe old key stops working straight away.", 'confirm_label' => 'Generate']); ?>
      </span>
    </li>
    <li>
      <span>HTTPS redirect<br><?php partial_admin('badge.php', ['status' => $httpsPermanent ? 'active' : 'pending', 'label' => $httpsPermanent ? 'Permanent (301)' : 'Temporary (302)', 'small' => true]); ?><br><span class="adm-note"><?= $httpsPermanent ? 'Browsers remember to use https:// for this shop.' : 'Press this once the padlock shows in your browser. It checks the certificate first.' ?></span></span>
      <span>
<?php if (!$httpsPermanent): ?>
<?php partial_admin('button.php', ['label' => 'Make HTTPS permanent', 'variant' => 'gold', 'size' => 'sm', 'post' => '/admin/settings/https-permanent', 'disabled' => $httpsLocal, 'confirm' => "Make the HTTPS redirect permanent?\nOnly do this when https:// already works with a valid padlock. It cannot be undone from the panel.", 'confirm_label' => 'Check and switch']); ?>
<?php if ($httpsLocal): ?><span class="adm-note">Open the panel over https:// on the live address first.</span><?php endif; ?>
<?php endif; ?>
      </span>
    </li>
    <li>
      <span>WebP images<br><?php partial_admin('badge.php', ['status' => (string) ($values['images_webp_enabled'] ?? '1') === '1' ? 'active' : 'inactive', 'label' => (string) ($values['images_webp_enabled'] ?? '1') === '1' ? 'Available' : 'JPEG only', 'small' => true]); ?><br><span class="adm-note">Re-check after a hosting change, then regenerate images from Tools.</span></span>
      <span><?php partial_admin('button.php', ['label' => 'Re-scan', 'variant' => 'ghost', 'size' => 'sm', 'post' => '/admin/settings', 'fields' => ['tab' => 'advanced', 'action' => 'rescan']]); ?></span>
    </li>
  </ul>
</div>
<?php endif; ?>
