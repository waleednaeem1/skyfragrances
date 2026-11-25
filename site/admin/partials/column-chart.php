<?php
defined('SKYFR') || exit;
$bars = $chart['bars'];
$count = count($bars);
$top = (int) $chart['top'];
?>
<figure class="adm-chart">
  <figcaption class="u-sr-only"><?= e($chart['caption']) ?></figcaption>
  <div class="adm-chart__frame">
    <div class="adm-chart__axis" aria-hidden="true">
<?php foreach ($chart['ticks'] as $tick): ?>
      <span style="bottom: <?= e((string) $tick['pos']) ?>%"><?= e($tick['label']) ?></span>
<?php endforeach; ?>
    </div>
    <div class="adm-chart__plot">
      <div class="adm-chart__grid" aria-hidden="true">
<?php foreach ($chart['ticks'] as $tick): ?>
        <span style="bottom: <?= e((string) $tick['pos']) ?>%"></span>
<?php endforeach; ?>
      </div>
      <ol class="adm-chart__bars">
<?php foreach ($bars as $index => $bar):
    $height = $top > 0 ? max(0, round($bar['amount'] / $top * 100, 2)) : 0;
    $edge = $index < $count * 0.2 ? ' is-start' : ($index >= $count * 0.8 ? ' is-end' : '');
    $summary = $bar['tip'] . ': ' . $bar['value'] . ($chart['money'] ? ', ' . $bar['orders'] . ' order' . ($bar['orders'] === 1 ? '' : 's') : '');
    $tag = $bar['href'] !== null && !$bar['future'] ? 'a' : 'span';
?>
        <li class="adm-chart__slot<?= $edge ?><?= $bar['future'] ? ' is-future' : '' ?>">
<?php if ($bar['future']): ?>
          <span class="adm-chart__hit"></span>
<?php else: ?>
          <<?= $tag ?> class="adm-chart__hit" style="--h: <?= e((string) $height) ?>%"<?= $tag === 'a' ? ' href="' . e(url((string) $bar['href'])) . '"' : ' tabindex="0" role="img"' ?> aria-label="<?= e($summary) ?>">
            <span class="adm-chart__bar<?= $bar['amount'] > 0 && $height < 1 ? ' is-tiny' : '' ?>"></span>
            <span class="adm-chart__tip" aria-hidden="true"><strong><?= e($bar['value']) ?></strong><?= $chart['money'] ? e($bar['orders'] . ' order' . ($bar['orders'] === 1 ? '' : 's')) . ' · ' : '' ?><?= e($bar['tip']) ?></span>
          </<?= $tag ?>>
<?php endif; ?>
<?php if ($bar['axis'] !== ''): ?>
          <span class="adm-chart__x" aria-hidden="true"><?= e($bar['axis']) ?></span>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ol>
<?php if ($top === 0): ?>
      <p class="adm-chart__empty">No sales in this period.</p>
<?php endif; ?>
    </div>
  </div>
  <details class="adm-chart__table">
    <summary>Show as a table</summary>
    <table>
      <thead><tr><th scope="col"><?= e($chart['unit_label']) ?></th><th scope="col" class="adm-chart__num">Orders</th><th scope="col" class="adm-chart__num">Revenue</th></tr></thead>
      <tbody>
<?php foreach ($bars as $bar): if ($bar['future']) { continue; } ?>
        <tr><th scope="row"><?= e($bar['tip']) ?></th><td class="adm-chart__num"><?= e((string) $bar['orders']) ?></td><td class="adm-chart__num"><?= e($bar['value']) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </details>
</figure>
