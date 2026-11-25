<?php
defined('SKYFR') || exit;
$hero = is_array($hero ?? null) ? $hero : [];
$products = array_values(is_array($hero['products'] ?? null) ? $hero['products'] : []);
while ($products !== [] && count($products) < 3) {
    $products[] = $products[count($products) % max(1, count($products))];
}
$products = array_slice($products, 0, 3);
$href = static fn (string $path, string $fallback): string => preg_match('#^https?://#i', $path) ? $path : url($path === '' ? $fallback : $path);
$imageUrl = static fn (string $value): string => $value !== '' && !str_contains($value, '..') ? (preg_match('#^https?://#i', $value) ? $value : url('/' . ltrim($value, '/'))) : '';
$photoDesktop = $imageUrl(trim((string) ($hero['image_desktop'] ?? '')));
$photoMobile = $imageUrl(trim((string) ($hero['image_mobile'] ?? '')));
$scrim = number_format(max(0, min(100, (int) ($hero['overlay_opacity'] ?? 60))) / 100, 2, '.', '');
$words = preg_split('/\s+/u', trim((string) ($hero['heading'] ?? 'More Than Just A Scent'))) ?: [];
$firstLine = max(1, intdiv(count($words), 2));
$subheading = trim((string) ($hero['subheading'] ?? ''));
$secondaryLabel = trim((string) ($hero['secondary_label'] ?? ''));
$trust = trim((string) ($hero['trust'] ?? ''));
$trails = [
    'M310,164 C 250,54 100,94 30,14',
    'M310,164 C 390,44 530,124 600,34',
    'M310,164 C 190,174 80,254 0,224',
    'M310,164 C 440,184 540,274 620,254',
];
mt_srand(1307);
$starGroups = array_fill(0, 5, []);
for ($star = 0; $star < 70; $star++) {
    $starGroups[$star % 5][] = [mt_rand(0, 1600), mt_rand(0, 940), mt_rand(5, 16) / 10];
}
$particles = [];
for ($particle = 0; $particle < 18; $particle++) {
    $particles[] = [mt_rand(0, 100), mt_rand(2, 4), mt_rand(9, 19), -mt_rand(0, 18)];
}
$puffs = [[293, 147, 34, 7, -150, -90, 0], [297, 151, 26, 5, 160, -110, .04], [288, 142, 44, 9, -70, -170, .08], [299, 153, 22, 4, 190, -30, .1], [295, 149, 30, 6, -200, 10, .06], [298, 152, 24, 5, 80, -200, .12], [290, 144, 40, 8, 0, -130, .02], [301, 155, 18, 4, -110, -230, .14], [300, 154, 20, 4, 130, 40, .1]];
$bursts = [[280, 134, 60, 15, -220, -140, 0], [285, 139, 50, 12, 230, -160, .05], [275, 129, 70, 18, -40, -260, .1], [290, 144, 40, 10, 260, 20, .08], [287, 141, 46, 12, -260, 30, .06], [292, 146, 36, 9, 120, -280, .12]];
$puff = static fn (array $p): string => '--x:' . $p[0] . 'px;--y:' . $p[1] . 'px;--s:' . $p[2] . 'px;--b:' . $p[3] . 'px;--dx:' . $p[4] . 'px;--dy:' . $p[5] . 'px;--delay:' . $p[6] . 's';
$botanicals = [
    ['citrus', 'near', ''],
    ['rose', 'near', ''],
    ['jasmine', 'mid', ''],
    ['saffron', 'front', ' bot--extra'],
    ['oud', 'front', ' bot--extra'],
    ['leaf', 'far', ''],
    ['jasmine-sm', 'far', ' bot--extra'],
    ['citrus-sm', 'far', ' bot--extra'],
];
?>
<section class="hero scene" aria-labelledby="hero-title" data-hero data-loop>
<?php if ($photoDesktop !== '' || $photoMobile !== ''): ?>
  <picture class="hero__photo" style="--scrim:<?= e($scrim) ?>">
<?php if ($photoDesktop !== '' && $photoMobile !== ''): ?>
    <source media="(max-width: 767px)" srcset="<?= e($photoMobile) ?>">
<?php endif; ?>
    <img src="<?= e($photoDesktop !== '' ? $photoDesktop : $photoMobile) ?>" alt="" width="1920" height="1080" fetchpriority="high" decoding="async">
  </picture>
<?php endif; ?>
  <div class="hero__sky depth depth--far" aria-hidden="true">
    <svg class="hero__stars" viewBox="0 0 1600 940" preserveAspectRatio="xMidYMid slice" focusable="false">
<?php foreach ($starGroups as $starGroup): ?>
      <g><?php foreach ($starGroup as [$cx, $cy, $r]): ?><circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>"/><?php endforeach; ?></g>
<?php endforeach; ?>
    </svg>
  </div>
  <div class="hero__mist" aria-hidden="true"><span></span><span></span><span></span></div>
  <div class="hero__particles" aria-hidden="true">
<?php foreach ($particles as [$px, $ps, $pd, $pdelay]): ?>
    <span style="--x:<?= $px ?>%;--s:<?= $ps ?>px;--d:<?= $pd ?>s;--delay:<?= $pdelay ?>s"></span>
<?php endforeach; ?>
  </div>
  <div class="hero__botanicals" aria-hidden="true">
<?php foreach ($botanicals as [$kind, $depth, $extra]): ?>
    <span class="bot bot--<?= $kind ?> depth depth--<?= $depth . $extra ?>"><span class="bot__bob"><span class="bot__art"><?php if (str_starts_with($kind, 'rose')): ?><i></i><i></i><i></i><i></i><i></i><i></i><?php elseif (str_starts_with($kind, 'jasmine')): ?><i></i><i></i><i></i><i></i><i></i><b></b><?php elseif ($kind === 'saffron'): ?><i></i><i></i><i></i><?php endif; ?></span></span></span>
<?php endforeach; ?>
  </div>
  <div class="hero__inner">
    <div class="hero__copy depth depth--text">
      <p class="hero__eyebrow">Eau de parfum · Made for Pakistan</p>
      <h1 class="hero__title" id="hero-title">
<?php foreach ($words as $index => $word): ?>
<?php if ($index === $firstLine): ?>
        <br>
<?php endif; ?>
        <span class="hero__word"><span class="hero__word-in<?= $index >= $firstLine ? ' is-gold' : '' ?>" style="--i:<?= $index ?>"><?= $index === count($words) - 1 ? '<em>' . e($word) . '</em>' : e($word) ?></span></span>
<?php endforeach; ?>
      </h1>
<?php if ($subheading !== ''): ?>
      <p class="hero__lede"><?= e($subheading) ?></p>
<?php endif; ?>
      <div class="hero__actions">
        <a class="btn btn--primary" href="<?= e($href(trim((string) ($hero['cta_url'] ?? '')), '/shop')) ?>"><span class="btn__label"><?= e((string) ($hero['cta_label'] ?? 'Explore the Collections')) ?></span></a>
<?php if ($secondaryLabel !== ''): ?>
        <a class="btn btn--ghost" href="<?= e($href(trim((string) ($hero['secondary_url'] ?? '')), '/scent-finder')) ?>"><span class="btn__label"><?= e($secondaryLabel) ?><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a>
<?php endif; ?>
      </div>
<?php if ($trust !== ''): ?>
      <p class="hero__trust"><?= e($trust) ?></p>
<?php endif; ?>
<?php if ($products !== []): ?>
      <div class="hero__now">
        <span class="hero__now-label" aria-hidden="true">Now spraying</span>
        <div class="hero__now-body">
          <div class="hero__captions">
<?php foreach ($products as $slot => $product): ?>
            <p class="hero__caption is-slot-<?= $slot ?>" data-hero-caption data-href="<?= e($product['url']) ?>" data-name="<?= e($product['name']) ?>"<?= $slot > 0 ? ' aria-hidden="true"' : '' ?>>
              <span class="hero__caption-name"><?= e($product['name']) ?></span>
              <span class="hero__caption-meta"><?= e(implode(' · ', array_filter([$product['gender'], $product['notes'][0] ?? '']))) ?><?php if ($product['family'] !== ''): ?> · <span class="hero__caption-family tone--<?= e($product['tone']) ?>"><?= e($product['family']) ?></span><?php endif; ?></span>
            </p>
<?php endforeach; ?>
          </div>
          <div class="hero__segments" aria-hidden="true"><?php foreach ($products as $slot => $product): ?><span class="hero__segment"><span class="is-slot-<?= $slot ?>"></span></span><?php endforeach; ?></div>
        </div>
        <a class="hero__now-link" href="<?= e($products[0]['url']) ?>" data-hero-link aria-label="<?= e('Shop ' . $products[0]['name']) ?>"><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 18]); ?></a>
      </div>
<?php endif; ?>
    </div>
<?php if ($products !== []): ?>
    <div class="hero__stage-wrap">
      <div class="hero__stage" data-hero-stage>
        <span class="hero__beam" aria-hidden="true"></span>
        <div class="hero__aura depth depth--far" aria-hidden="true">
<?php foreach ($products as $slot => $product): ?>
          <span class="hero__glow tone--<?= e($product['tone']) ?> is-slot-<?= $slot ?>"></span>
<?php endforeach; ?>
          <span class="hero__ring"><?php for ($dot = 0; $dot < 12; $dot++): ?><i style="--a:<?= $dot * 30 ?>deg"></i><?php endfor; ?></span>
          <span class="hero__ring hero__ring--dash"></span>
        </div>
        <div class="hero__trails depth depth--mid" aria-hidden="true">
          <svg class="hero__trail-svg" viewBox="0 0 620 720" width="620" height="720" focusable="false">
<?php foreach ($trails as $trail => $path): ?>
            <path class="hero__trail" d="<?= e($path) ?>" style="--i:<?= $trail ?>"/>
<?php endforeach; ?>
          </svg>
<?php foreach ($products as $slot => $product): ?>
          <span class="hero__notes is-slot-<?= $slot ?>">
<?php foreach ($product['notes'] as $noteIndex => $note): ?>
            <span class="hero__note hero__note--t<?= intdiv($noteIndex, 2) ?><?= $noteIndex % 2 ? ' hero__note--b' : '' ?>"><?= e($note) ?> ✦</span>
<?php endforeach; ?>
          </span>
<?php endforeach; ?>
          <span class="hero__smoke"><i></i><i></i><i></i><i></i><i></i></span>
        </div>
        <div class="hero__bottle-wrap">
          <div class="hero__tilt">
            <div class="hero__float">
<?php foreach ($products as $slot => $product): ?>
              <span class="hero__bottle is-slot-<?= $slot ?>"><?php partial('bottle.php', ['name' => $product['name'], 'tone' => $product['tone']]); ?></span>
<?php endforeach; ?>
            </div>
          </div>
        </div>
        <span class="hero__floor" aria-hidden="true"></span>
        <span class="hero__shadow" aria-hidden="true"></span>
        <div class="hero__reflection" aria-hidden="true">
<?php foreach ($products as $slot => $product): ?>
          <span class="hero__bottle is-slot-<?= $slot ?>"><?php partial('bottle.php', ['name' => $product['name'], 'tone' => $product['tone']]); ?></span>
<?php endforeach; ?>
        </div>
        <div class="hero__spray" aria-hidden="true">
<?php foreach ($puffs as $p): ?>
          <i style="<?= e($puff($p)) ?>"></i>
<?php endforeach; ?>
        </div>
        <div class="hero__burst" aria-hidden="true">
<?php foreach ($bursts as $p): ?>
          <i style="<?= e($puff($p)) ?>"></i>
<?php endforeach; ?>
        </div>
        <button class="hero__spray-target" type="button" data-hero-spray aria-label="Spray the fragrance"></button>
        <button class="hero__spray-pill" type="button" data-hero-spray><span class="hero__spray-dot" aria-hidden="true"></span>Tap to spray</button>
      </div>
    </div>
<?php endif; ?>
  </div>
  <div class="hero__cue" aria-hidden="true">Scroll<span></span></div>
</section>
<?php if (!empty($hero['notes'])): ?>
<div class="marquee" aria-hidden="true" data-loop>
  <div class="marquee__track">
<?php for ($copy = 0; $copy < 2; $copy++): ?>
<?php foreach ($hero['notes'] as $note): ?>
    <span class="marquee__item"><?= e($note) ?><span class="marquee__star">✦</span></span>
<?php endforeach; ?>
<?php endfor; ?>
  </div>
</div>
<?php endif; ?>
