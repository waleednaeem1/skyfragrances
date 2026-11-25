<?php
defined('SKYFR') || exit;
$notes = is_array($notes ?? null) ? $notes : [];
$tiers = [
    'top' => ['label' => 'Top', 'hint' => 'The first 15 minutes', 'items' => $notes['top'] ?? []],
    'heart' => ['label' => 'Heart', 'hint' => 'The next few hours', 'items' => $notes['heart'] ?? []],
    'base' => ['label' => 'Base', 'hint' => 'What lingers', 'items' => $notes['base'] ?? []],
];
$tiers = array_filter($tiers, static fn (array $tier): bool => $tier['items'] !== []);
if ($tiers === []) {
    return;
}
?>
<ol class="pyramid" aria-label="Scent notes" data-inview>
<?php foreach ($tiers as $key => $tier): ?>
  <li class="pyramid__tier pyramid__tier--<?= e($key) ?>">
    <span class="pyramid__label"><strong><?= e($tier['label']) ?></strong><small><?= e($tier['hint']) ?></small></span>
    <ul class="pyramid__notes" aria-label="<?= e($tier['label']) ?> notes">
<?php foreach ($tier['items'] as $note): ?>
      <li class="pyramid__note"><?= e($note) ?></li>
<?php endforeach; ?>
    </ul>
  </li>
<?php endforeach; ?>
</ol>
