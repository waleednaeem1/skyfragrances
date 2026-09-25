<?php
defined('SKYFR') || exit;
$meters = is_array($meters ?? null) ? $meters : [];
if ($meters === []) {
    return;
}
?>
<div class="meters">
<?php foreach ($meters as $meter): ?>
  <div class="meter sf-reveal" role="img" aria-label="<?= e($meter['aria']) ?>">
    <div class="meter__head">
      <span class="meter__label"><?= e($meter['label']) ?></span>
      <span class="meter__value"><?= e($meter['word']) ?></span>
    </div>
    <div class="meter__track" aria-hidden="true">
<?php for ($i = 0; $i < 5; $i++): ?>
      <span class="meter__seg<?= $i < $meter['value'] ? ' is-filled' : '' ?>" style="--i:<?= e((string) $i) ?>"></span>
<?php endfor; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
