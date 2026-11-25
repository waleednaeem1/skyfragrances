<?php
defined('SKYFR') || exit;
$steps = ['pending', 'confirmed', 'packing', 'shipped', 'delivered'];
$status = (string) ($status ?? 'pending');
$current = array_search($status, $steps, true);
$cancelled = $current === false;
?>
<ol class="adm-stepper<?= $cancelled ? ' is-cancelled' : '' ?>" aria-label="Order progress">
<?php foreach ($steps as $i => $step): $state = $cancelled ? 'todo' : ($i < $current ? 'done' : ($i === $current ? 'current' : 'todo')); ?>
  <li class="adm-stepper__step is-<?= e($state) ?>"<?= $state === 'current' ? ' aria-current="step"' : '' ?>>
    <span class="adm-stepper__bar" aria-hidden="true"></span>
    <span class="adm-stepper__label"><?php if ($state !== 'todo'): ?><?php partial('icon.php', ['name' => 'check', 'size' => 13]); ?><?php endif; ?><?= e(adm_order_status_label($step)) ?><span class="u-sr-only"><?= ['done' => ', done', 'current' => ', current step', 'todo' => ', not yet'][$state] ?></span></span>
  </li>
<?php endforeach; ?>
</ol>
