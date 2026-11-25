<?php
defined('SKYFR') || exit;
$moneyHtml = static function (string $value, array $extra = []): string {
    ob_start();
    partial_admin('money.php', ['value' => $value] + $extra);
    return (string) ob_get_clean();
};
?>
<section class="adm-card adm-card--flush" id="items">
  <div class="adm-card__head"><h2 class="adm-card__title">Items</h2></div>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <caption class="u-sr-only">Order items</caption>
      <thead><tr><th scope="col" class="adm-table__th">Product</th><th scope="col" class="adm-table__th">SKU</th><th scope="col" class="adm-table__th adm-table__th--right">Unit</th><th scope="col" class="adm-table__th adm-table__th--right">Qty</th><th scope="col" class="adm-table__th adm-table__th--right">Line</th></tr></thead>
      <tbody>
<?php foreach ($items as $item): ?>
        <tr class="adm-table__row">
          <td class="adm-table__td"><div class="adm-media"><img class="adm-thumb adm-thumb--sm" src="<?= e($item['image_url']) ?>" alt="" width="48" loading="lazy"><div class="adm-media__body"><p class="adm-media__title"><?php if (!$item['product_deleted']): ?><a href="<?= e(url('/product/' . $item['product_slug'])) ?>" target="_blank" rel="noopener"><?= e((string) $item['product_name']) ?></a><?php else: ?><?= e((string) $item['product_name']) ?> <span class="adm-muted">product deleted</span><?php endif; ?></p><p class="adm-media__meta"><?= e((string) $item['size_label']) ?></p></div></div></td>
          <td class="adm-table__td adm-mono"><?= e((string) $item['sku']) ?></td>
          <td class="adm-table__td adm-table__td--right adm-table__td--money"><?= $moneyHtml((string) $item['unit_price_charged']) ?><?php if ($item['live_price'] !== null): ?><br><span class="adm-note">now <?= e(money($item['live_price'])) ?></span><?php endif; ?></td>
          <td class="adm-table__td adm-table__td--right adm-num"><?= (int) $item['quantity'] ?></td>
          <td class="adm-table__td adm-table__td--right adm-table__td--money"><?= $moneyHtml((string) $item['line_total'], ['strong' => true]) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
