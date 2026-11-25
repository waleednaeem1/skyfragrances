<?php
defined('SKYFR') || exit;
$tag = !empty($href) ? 'a' : 'div';
$delta = is_array($delta ?? null) ? $delta : null;
?>
<<?= $tag ?> class="adm-kpi adm-tone--<?= e((string) ($tone ?? 'c1')) ?><?= !empty($alert) ? ' is-alert' : '' ?>"<?= $tag === 'a' ? ' href="' . e(url((string) $href)) . '"' : '' ?>>
  <span class="adm-kpi__head">
<?php if (!empty($icon)): ?>
    <span class="adm-kpi__icon"><?php partial('icon.php', ['name' => (string) $icon, 'size' => 16]); ?></span>
<?php endif; ?>
    <span class="adm-kpi__label"><?= e((string) $label) ?></span>
  </span>
  <span class="adm-kpi__value"><?= e((string) $value) ?></span>
  <span class="adm-kpi__foot">
    <span class="adm-kpi__meta">
<?php if ($delta !== null): ?>
      <span class="adm-delta__pill adm-delta--<?= e((string) $delta['tone']) ?>"><?= e((string) $delta['text']) ?></span>
<?php endif; ?>
<?php if (!empty($note)): ?>
      <span class="adm-kpi__note"><?= e((string) $note) ?></span>
<?php endif; ?>
    </span>
<?php if (!empty($spark)): ?>
<?php partial_admin('sparkline.php', ['values' => $spark, 'tone' => $tone ?? 'c1']); ?>
<?php endif; ?>
  </span>
</<?= $tag ?>>
