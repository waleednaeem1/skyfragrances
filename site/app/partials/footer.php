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
  <div class="site-footer__inner container">
    <div class="site-footer__brand">
      <a class="site-footer__logo" href="<?= e(url('/')) ?>">
        <img src="<?= e(asset(brand_asset('img/brand/lockup-on-black-800.webp'))) ?>" alt="<?= e($storeName) ?>" width="120" height="120" loading="lazy" decoding="async">
      </a>
      <p class="site-footer__tagline"><?= e($storeTagline) ?></p>
<?php if ($footerBlurb !== ''): ?>
      <p class="site-footer__blurb"><?= e($footerBlurb) ?></p>
<?php endif; ?>
      <ul class="site-footer__social">
<?php foreach ($socials as [$socialKey, $socialIcon, $socialLabel]): ?>
<?php $socialUrl = trim((string) setting($socialKey, '')); ?>
<?php if ($socialUrl !== ''): ?>
        <li><a class="site-footer__social-link" href="<?= e($socialUrl) ?>" target="_blank" rel="noopener" aria-label="<?= e($storeName . ' on ' . $socialLabel) ?>"><?php partial('icon.php', ['name' => $socialIcon]); ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($whatsappDigits !== ''): ?>
        <li><a class="site-footer__social-link" href="<?= e('https://wa.me/' . $whatsappDigits) ?>" target="_blank" rel="noopener" aria-label="<?= e($storeName . ' on WhatsApp') ?>"><?php partial('icon.php', ['name' => 'whatsapp']); ?></a></li>
<?php endif; ?>
      </ul>
    </div>
<?php foreach ($columns as $column): ?>
    <div class="site-footer__col">
      <h2 class="site-footer__heading u-track"><button class="site-footer__toggle js-footer-toggle" type="button" aria-expanded="false" aria-controls="<?= e($column['id']) ?>"><?= e($column['title']) ?></button></h2>
      <ul class="site-footer__links" id="<?= e($column['id']) ?>">
<?php foreach ($column['links'] as [$linkPath, $linkLabel]): ?>
        <li><a class="site-footer__link" href="<?= e(url($linkPath)) ?>"><?= e($linkLabel) ?></a></li>
<?php endforeach; ?>
<?php if ($column['id'] === 'fc-help' && $contactEmail !== ''): ?>
        <li><a class="site-footer__link" href="<?= e('mailto:' . $contactEmail) ?>"><?= e($contactEmail) ?></a></li>
<?php endif; ?>
      </ul>
<?php if ($column['id'] === 'fc-about'): ?>
<?php if (!$hideNewsletter): ?>
      <div class="site-footer__newsletter">
        <?php partial('newsletter.php', ['variant' => 'compact', 'source' => 'footer', 'headingTag' => 'h3', 'heading' => 'The Sky List']); ?>
      </div>
<?php endif; ?>
<?php if ($paymentMethods !== []): ?>
      <p class="site-footer__payments"><span class="u-sr-only">We accept: </span><span aria-hidden="true"><?= e(implode(' · ', $paymentMethods)) ?></span><span class="u-sr-only"><?= e(implode(', ', $paymentMethods)) ?>.</span></p>
<?php endif; ?>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>
  <div class="site-footer__bottom container">
    <p class="site-footer__copy">&copy; <?= e(date('Y')) ?> <?= e($storeName) ?>. All rights reserved.</p>
<?php if ($addressLine !== ''): ?>
    <p class="site-footer__address"><?= e($addressLine) ?></p>
<?php endif; ?>
  </div>
</footer>
