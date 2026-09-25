<?php
defined('SKYFR') || exit;
$variant = (string) ($variant ?? 'band');
$source = (string) ($source ?? 'footer');
$source = in_array($source, ['footer', 'home', 'checkout'], true) ? $source : 'footer';
$fieldId = 'nl-email-' . $source;
$heading = trim((string) ($heading ?? setting('newsletter_heading', 'Join the Sky List')));
$text = trim((string) ($text ?? setting('newsletter_text', 'New arrivals, private offers and scent stories, straight to your inbox.')));
$ctaLabel = trim((string) ($ctaLabel ?? setting('newsletter_cta_label', 'Subscribe')));
$headingId = trim((string) ($headingId ?? ''));
$headingTag = (string) ($headingTag ?? 'h2');
$headingTag = in_array($headingTag, ['h2', 'h3'], true) ? $headingTag : 'h2';
$classes = 'newsletter' . ($variant === 'compact' ? ' newsletter--compact' : ' newsletter--band');
?>
<div class="<?= e($classes) ?>"<?= $variant === 'compact' ? '' : ' id="newsletter"' ?>>
<?php if ($heading !== ''): ?>
  <<?= $headingTag ?> class="<?= $variant === 'compact' ? 'h4' : 'h2' ?>"<?= $headingId !== '' ? ' id="' . e($headingId) . '"' : '' ?>><?= e($heading) ?></<?= $headingTag ?>>
<?php endif; ?>
<?php if ($text !== '' && $variant !== 'compact'): ?>
  <p class="text-muted"><?= e($text) ?></p>
<?php endif; ?>
  <form class="newsletter__form js-newsletter-form" action="<?= e(url('/api/newsletter')) ?>" method="post">
    <?= session_status() === PHP_SESSION_ACTIVE ? csrf_field() : '' ?>
    <input type="hidden" name="source" value="<?= e($source) ?>">
    <div class="field newsletter__field">
      <label class="field__label u-sr-only" for="<?= e($fieldId) ?>">Email address</label>
      <div class="field__control">
        <input class="field__input" id="<?= e($fieldId) ?>" name="email" type="email" inputmode="email" autocomplete="email" placeholder="Your email address" required aria-describedby="err-<?= e($fieldId) ?>">
      </div>
      <p class="field__error" id="err-<?= e($fieldId) ?>"></p>
    </div>
    <div class="field__honeypot" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
    <button class="btn btn--primary" type="submit"><span class="btn__label"><?= e($ctaLabel) ?></span></button>
  </form>
  <p class="newsletter__legal">One or two letters a month. Unsubscribe with a single tap, any time.</p>
</div>
