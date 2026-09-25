<?php
defined('SKYFR') || exit;
$notes = is_array($notes ?? null) ? $notes : [];
$tiers = [
    'top' => ['label' => 'Top notes', 'items' => $notes['top'] ?? []],
    'heart' => ['label' => 'Heart notes', 'items' => $notes['heart'] ?? []],
    'base' => ['label' => 'Base notes', 'items' => $notes['base'] ?? []],
];
$tiers = array_filter($tiers, static fn (array $tier): bool => $tier['items'] !== []);
if ($tiers === []) {
    return;
}
?>
<ol class="pyramid sf-reveal" aria-label="Scent notes">
<?php foreach ($tiers as $key => $tier): ?>
  <li class="pyramid__tier pyramid__tier--<?= e($key) ?>">
    <span class="pyramid__label"><?= e($tier['label']) ?></span>
    <ul class="pyramid__notes">
<?php foreach ($tier['items'] as $note): ?>
      <li class="pyramid__note"><?= e($note) ?></li>
<?php endforeach; ?>
    </ul>
  </li>
<?php endforeach; ?>
</ol>
