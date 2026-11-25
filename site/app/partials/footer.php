<?php
defined('SKYFR') || exit;
$storeName = (string) setting('store_name', 'Sky Fragrances');
$storeTagline = (string) setting('store_tagline', 'More Than Just A Scent');
$hideNewsletter = (bool) ($hideNewsletter ?? false);
$footerBlurb = (string) setting('footer_blurb', '');
$addressLine = trim((string) setting('address_line', ''));
$contactEmail = trim((string) setting('contact_email', ''));
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', ''));
$socials = [
    ['instagram_url', 'instagram', 'Instagram'],
    ['facebook_url', 'facebook', 'Facebook'],
    ['tiktok_url', 'tiktok', 'TikTok'],
    ['youtube_url', 'youtube', 'YouTube'],
];
$columns = [
    ['id' => 'fc-shop', 'title' => 'Shop', 'links' => [
        ['/shop', 'Shop All'],
        ['/new-arrivals', 'New Arrivals'],
        ['/best-sellers', 'Best Sellers'],
        ['/for-him', 'For Him'],
        ['/for-her', 'For Her'],
        ['/unisex', 'Unisex'],
        ['/sale', 'Sale'],
    ]],
    ['id' => 'fc-help', 'title' => 'Help', 'links' => [
        ['/track', 'Track Order'],
        ['/shipping', 'Shipping'],
        ['/returns', 'Returns & Exchange'],
        ['/faq', 'FAQ'],
        ['/contact', 'Contact'],
    ]],
    ['id' => 'fc-about', 'title' => 'About', 'links' => [
        ['/about', 'About Us'],
        ['/scent-finder', 'Scent Finder'],
        ['/privacy', 'Privacy Policy'],
        ['/terms', 'Terms'],
    ]],
];
$paymentMethods = [];
foreach ([['cod_enabled', 'Cash on Delivery'], ['bank_enabled', 'Bank Transfer'], ['jazzcash_enabled', 'JazzCash'], ['easypaisa_enabled', 'Easypaisa']] as [$paymentKey, $paymentLabel]) {
    if (setting_bool($paymentKey, false)) {
        $paymentMethods[] = $paymentLabel;
    }
}
?>
<footer class="site-footer">
  <div class="site-footer__inner">
    <div class="site-footer__brand">
      <a class="site-footer__logo" href="<?= e(url('/')) ?>" aria-label="<?= e($storeName) ?> home">
        <img src="<?= e(asset(brand_asset('img/brand/monogram-transparent-256.png'))) ?>" alt="" width="46" height="46" loading="lazy" decoding="async">
      </a>
      <p class="site-footer__tagline"><?= e($storeTagline) ?></p>
<?php if ($footerBlurb !== '' || $contactEmail !== ''): ?>
      <p class="site-footer__blurb"><?= e($footerBlurb) ?><?php if ($footerBlurb !== '' && $contactEmail !== ''): ?><br><?php endif; ?><?php if ($contactEmail !== ''): ?><a class="u-link" href="<?= e('mailto:' . $contactEmail) ?>"><?= e($contactEmail) ?></a><?php endif; ?></p>
<?php endif; ?>
      <ul class="site-footer__social">
<?php foreach ($socials as [$socialKey, $socialIcon, $socialLabel]): ?>
<?php $socialUrl = trim((string) setting($socialKey, '')); ?>
<?php if ($socialUrl !== ''): ?>
        <li><a class="site-footer__social-link" href="<?= e($socialUrl) ?>" target="_blank" rel="noopener" aria-label="<?= e($storeName . ' on ' . $socialLabel) ?>"><?php partial('icon.php', ['name' => $socialIcon, 'size' => 18]); ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($whatsappDigits !== ''): ?>
        <li><a class="site-footer__social-link" href="<?= e('https://wa.me/' . $whatsappDigits) ?>" target="_blank" rel="noopener" aria-label="<?= e($storeName . ' on WhatsApp') ?>"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 18]); ?></a></li>
<?php endif; ?>
<?php if ($contactEmail !== ''): ?>
        <li><a class="site-footer__social-link" href="<?= e('mailto:' . $contactEmail) ?>" aria-label="<?= e('Email ' . $storeName) ?>"><?php partial('icon.php', ['name' => 'mail', 'size' => 18]); ?></a></li>
<?php endif; ?>
      </ul>
<?php if (!$hideNewsletter): ?>
      <div class="site-footer__newsletter">
        <?php partial('newsletter.php', ['variant' => 'compact', 'source' => 'footer', 'headingTag' => 'h2', 'heading' => 'The Sky List']); ?>
      </div>
<?php endif; ?>
    </div>
<?php foreach ($columns as $column): ?>
    <div class="site-footer__col">
      <h2 class="site-footer__heading"><button class="site-footer__toggle js-footer-toggle" type="button" aria-expanded="false" aria-controls="<?= e($column['id']) ?>"><?= e($column['title']) ?></button></h2>
      <ul class="site-footer__links" id="<?= e($column['id']) ?>">
<?php foreach ($column['links'] as [$linkPath, $linkLabel]): ?>
        <li><a class="site-footer__link" href="<?= e(url($linkPath)) ?>"><?= e($linkLabel) ?></a></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endforeach; ?>
  </div>
  <div class="site-footer__bottom">
    <p>&copy; <?= e(date('Y')) ?> <?= e($storeName) ?>. All rights reserved.<?= $addressLine !== '' ? ' · ' . e($addressLine) : '' ?></p>
<?php if ($paymentMethods !== []): ?>
    <p>We accept <?= e(implode(' · ', $paymentMethods)) ?></p>
<?php endif; ?>
    <p class="site-footer__credit">Designed &amp; built by <a class="u-link" href="https://devarko.com" target="_blank" rel="noopener">Devarko</a></p>
  </div>
  <div class="site-footer__wordmark" aria-hidden="true"><?= e($storeName) ?></div>
</footer>
