<?php
defined('SKYFR') || exit;
$search = $palette['search'];
unset($palette['search']);
?>
<dialog class="adm-palette" data-palette aria-labelledby="adm-palette-title">
  <div class="adm-palette__box">
    <h2 class="u-sr-only" id="adm-palette-title">Search and jump</h2>
    <div class="adm-palette__search">
      <?php partial('icon.php', ['name' => 'search', 'size' => 17, 'class' => 'adm-palette__icon']); ?>
      <input class="adm-palette__input" type="text" role="combobox" aria-expanded="true" aria-controls="adm-palette-list" aria-autocomplete="list" aria-label="Search orders, customers, products or type a command" placeholder="Search orders, customers, products or type a command…" autocomplete="off" spellcheck="false" data-palette-input>
      <kbd class="adm-kbd">Esc</kbd>
    </div>
    <div class="adm-palette__list" id="adm-palette-list" role="listbox" aria-label="Results" data-palette-list>
<?php foreach ($palette as $group => $items): if ($items === []) { continue; } ?>
      <div role="group" aria-label="<?= e($group) ?>" data-palette-group>
        <p class="adm-palette__heading" aria-hidden="true"><?= e($group) ?></p>
<?php foreach ($items as $item): ?>
        <a class="adm-palette__item" role="option" href="<?= e(url($item['href'])) ?>" data-palette-item data-keywords="<?= e(mb_strtolower($item['label'] . ' ' . $item['keywords'])) ?>"<?= $item['shortcut'] !== '' ? ' data-shortcut="' . e($item['shortcut']) . '"' : '' ?>>
          <?php partial('icon.php', ['name' => $item['icon'], 'size' => 16, 'class' => 'adm-palette__icon']); ?>
          <span class="adm-palette__label"><?= e($item['label']) ?></span>
<?php if ($item['shortcut'] !== ''): ?>
          <span class="adm-palette__keys" aria-hidden="true"><?php if ($item['shortcut'] !== 'n'): ?><kbd class="adm-kbd">G</kbd><?php endif; ?><kbd class="adm-kbd"><?= e(mb_strtoupper($item['shortcut'])) ?></kbd></span>
<?php endif; ?>
        </a>
<?php endforeach; ?>
      </div>
<?php endforeach; ?>
<?php if ($search !== []): ?>
      <div role="group" aria-label="Search" data-palette-search hidden>
        <p class="adm-palette__heading" aria-hidden="true">Search</p>
<?php foreach ($search as $item): ?>
        <a class="adm-palette__item" role="option" href="<?= e(url($item['href'])) ?>" data-palette-item data-palette-query-link>
          <?php partial('icon.php', ['name' => $item['icon'], 'size' => 16, 'class' => 'adm-palette__icon']); ?>
          <span class="adm-palette__label"><?= e($item['label']) ?> “<span data-palette-query></span>”</span>
        </a>
<?php endforeach; ?>
      </div>
<?php endif; ?>
      <p class="adm-palette__empty" data-palette-empty hidden>Nothing matches. Try an order number, a name or a page.</p>
    </div>
    <div class="adm-palette__foot" aria-hidden="true"><span><kbd class="adm-kbd">↑</kbd><kbd class="adm-kbd">↓</kbd> navigate</span><span><kbd class="adm-kbd">↵</kbd> open</span><span><kbd class="adm-kbd" data-palette-mod>⌘K</kbd> toggle</span></div>
  </div>
</dialog>
