<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = (string) ($page['title'] ?? 'Sky Fragrances');
}
$bodyHtml = (string) ($body ?? '');
$sections = is_array($sections ?? null) ? $sections : [];
$stats = is_array($stats ?? null) ? $stats : [];
$isAbout = ($page['slug'] ?? '') === 'about';
$showUpdated = !empty($showUpdated) && trim((string) ($updatedAt ?? '')) !== '';
$policies = [['/shipping', 'Shipping & Delivery'], ['/returns', 'Returns & Exchange'], ['/privacy', 'Privacy Policy'], ['/terms', 'Terms & Conditions']];
$currentPath = '/' . (string) ($page['slug'] ?? '');
?>
<?php partial('page-hero.php', [
    'crumbs' => [['label' => 'Home', 'url' => '/'], ['label' => $pageHeading, 'url' => '']],
    'eyebrow' => $isAbout ? 'Our story' : ($showUpdated ? 'Last updated ' . $updatedAt : 'Sky Fragrances'),
    'title' => $pageHeading,
]); ?>
<?php if ($bodyHtml === ''): ?>
<section class="section">
  <div class="shell shell--prose">
    <p class="lead">This page is being written. Message us on WhatsApp if you have a question in the meantime.</p>
  </div>
</section>
<?php elseif ($isAbout): ?>
<section class="section">
  <div class="shell shell--prose">
    <div class="prose prose--story" data-reveal><?php echo $bodyHtml; ?></div>
  </div>
</section>
<?php if ($stats !== []): ?>
<section class="section section--ruled" aria-labelledby="promise-title">
  <div class="shell">
    <?php partial('section-header.php', ['eyebrow' => 'Our promise', 'title' => 'What you can count on', 'em' => 2, 'titleId' => 'promise-title']); ?>
    <dl class="stats" data-reveal>
<?php foreach ($stats as [$statValue, $statLabel]): ?>
      <div class="stats__item"><dt class="stats__value"><?= e($statValue) ?></dt><dd class="stats__label"><?= e($statLabel) ?></dd></div>
<?php endforeach; ?>
    </dl>
  </div>
</section>
<?php endif; ?>
<section class="cta-band" aria-labelledby="begin-title">
  <div class="shell" data-reveal>
    <h2 class="cta-band__title" id="begin-title">Not sure where to <em>begin?</em></h2>
    <div class="btn-row cluster--center">
      <a class="btn btn--primary" href="<?= e(url('/scent-finder')) ?>"><span class="btn__label">Take the Scent Finder<?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a>
      <a class="btn btn--ghost" href="<?= e(url('/best-sellers')) ?>"><span class="btn__label">Shop best sellers</span></a>
    </div>
  </div>
</section>
<?php else: ?>
<section class="section policy">
  <div class="shell policy__grid">
    <aside class="policy__aside">
<?php if (count($sections) > 1): ?>
      <nav class="policy__toc" aria-labelledby="toc-title">
        <p class="policy__label" id="toc-title">On this page</p>
        <ol class="policy__toc-list">
<?php foreach ($sections as $section): ?>
          <li><a href="#<?= e($section['id']) ?>"><?= e($section['label']) ?></a></li>
<?php endforeach; ?>
        </ol>
      </nav>
<?php endif; ?>
      <ul class="policy__related" aria-label="Other policies">
<?php foreach ($policies as [$policyPath, $policyLabel]): ?>
<?php if ($policyPath !== $currentPath): ?>
        <li><a class="btn btn--text btn--sm" href="<?= e(url($policyPath)) ?>"><span class="btn__label"><?= e($policyLabel) ?><?php partial('icon.php', ['name' => 'arrow-right', 'size' => 14]); ?></span></a></li>
<?php endif; ?>
<?php endforeach; ?>
      </ul>
    </aside>
    <div class="prose prose--policy"><?php echo $bodyHtml; ?></div>
  </div>
</section>
<?php endif; ?>
