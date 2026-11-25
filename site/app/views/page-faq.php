<?php
defined('SKYFR') || exit;
$pageHeading = trim((string) ($heading ?? ''));
if ($pageHeading === '') {
    $pageHeading = (string) ($page['title'] ?? 'Frequently Asked Questions');
}
$items = is_array($faqItems ?? null) ? $faqItems : [];
$bodyHtml = (string) ($body ?? '');
$whatsappDigits = preg_replace('/\D+/', '', (string) setting('whatsapp', ''));
?>
<?php partial('page-hero.php', [
    'crumbs' => [['label' => 'Home', 'url' => '/'], ['label' => $pageHeading, 'url' => '']],
    'eyebrow' => 'Help',
    'title' => $pageHeading,
    'intro' => 'Delivery, payment, tracking and exchanges — the questions we hear most.',
]); ?>
<section class="section">
  <div class="shell shell--prose">
<?php if ($items === []): ?>
<?php if ($bodyHtml !== ''): ?>
    <div class="prose"><?php echo $bodyHtml; ?></div>
<?php else: ?>
    <p class="text-muted">No questions yet. Ask us anything on WhatsApp.</p>
<?php endif; ?>
<?php else: ?>
    <div class="accordion accordion--faq">
<?php foreach ($items as $index => $item): ?>
<?php $n = $index + 1; ?>
      <div class="accordion__item">
        <h2 class="accordion__title">
          <button class="accordion__trigger js-accordion-trigger" type="button" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="faq-p-<?= e((string) $n) ?>" id="faq-t-<?= e((string) $n) ?>"><?= e($item['question']) ?><span class="accordion__glyph" aria-hidden="true"></span></button>
        </h2>
        <div class="accordion__panel" id="faq-p-<?= e((string) $n) ?>" role="region" aria-labelledby="faq-t-<?= e((string) $n) ?>">
          <div class="accordion__inner">
            <div class="accordion__body prose"><?php echo $item['answer']; ?></div>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
    <div class="faq-help">
      <p class="faq-help__text">Still wondering about <em>something?</em></p>
      <div class="cluster">
<?php if ($whatsappDigits !== ''): ?>
        <a class="btn btn--ghost btn--whatsapp btn--sm" href="https://wa.me/<?= e($whatsappDigits) ?>?text=<?= e(rawurlencode('Assalam-o-Alaikum, I have a question about Sky Fragrances.')) ?>" target="_blank" rel="noopener"><?php partial('icon.php', ['name' => 'whatsapp', 'size' => 18]); ?><span class="btn__label">Ask on WhatsApp</span></a>
<?php endif; ?>
        <a class="btn btn--text btn--sm" href="<?= e(url('/contact')) ?>"><span class="btn__label">Contact us</span></a>
      </div>
    </div>
  </div>
</section>
