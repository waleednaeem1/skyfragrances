<?php
defined('SKYFR') || exit;
$stepLink = static function (?string $href, string $icon, string $label): void {
    if ($href === null) {
        echo '<span class="adm-period__step is-disabled" aria-hidden="true">';
        partial('icon.php', ['name' => $icon, 'size' => 18]);
        echo '</span>';
        return;
    }
    echo '<a class="adm-period__step" href="' . e(url($href)) . '" aria-label="' . e($label) . '">';
    partial('icon.php', ['name' => $icon, 'size' => 18]);
    echo '</a>';
};
?>
<div class="adm-period">
  <nav class="adm-period__ranges" aria-label="Show figures by">
<?php foreach ($picker['ranges'] as $range): ?>
    <a class="adm-period__range<?= $range['active'] ? ' is-active' : '' ?>" href="<?= e(url($range['href'])) ?>"<?= $range['active'] ? ' aria-current="true"' : '' ?>><?= e($range['label']) ?></a>
<?php endforeach; ?>
  </nav>
  <div class="adm-period__nav">
    <?php $stepLink($picker['prev'], 'chevron-left', 'Previous period'); ?>
    <details class="adm-dates" data-popover>
      <summary class="adm-dates__toggle" aria-label="Choose dates, showing <?= e($picker['label']) ?>">
        <?php partial('icon.php', ['name' => 'calendar', 'size' => 18]); ?>
        <span class="adm-dates__label"><?= e($picker['label']) ?></span>
        <?php partial('icon.php', ['name' => 'chevron-down', 'size' => 16]); ?>
      </summary>
      <div class="adm-dates__panel">
        <ul class="adm-dates__presets">
<?php foreach ($picker['presets'] as $preset): ?>
          <li><a class="adm-dates__preset<?= $preset['active'] ? ' is-active' : '' ?>" href="<?= e(url($preset['href'])) ?>"<?= $preset['active'] ? ' aria-current="true"' : '' ?>><span><?= e($preset['label']) ?></span><?php if ($preset['active']) { partial('icon.php', ['name' => 'check', 'size' => 16]); } ?></a></li>
<?php endforeach; ?>
        </ul>
        <form class="adm-dates__custom" method="get" action="<?= e(url($picker['action'])) ?>">
          <input type="hidden" name="range" value="custom">
          <p class="adm-dates__title">Custom range</p>
          <div class="adm-dates__fields">
            <label class="adm-dates__field"><span>From</span><input class="adm-input" type="date" name="from" value="<?= e($picker['from']) ?>" max="<?= e($picker['max']) ?>" required></label>
            <label class="adm-dates__field"><span>To</span><input class="adm-input" type="date" name="to" value="<?= e($picker['to']) ?>" max="<?= e($picker['max']) ?>" required></label>
          </div>
          <button type="submit" class="adm-btn adm-btn--gold adm-btn--block">Show these dates</button>
        </form>
      </div>
    </details>
    <?php $stepLink($picker['next'], 'chevron-right', 'Next period'); ?>
  </div>
</div>
