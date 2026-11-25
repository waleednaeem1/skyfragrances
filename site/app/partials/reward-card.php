<?php
defined('SKYFR') || exit;
$reward = is_array($reward ?? null) ? $reward : null;
if ($reward === null || empty($reward['usable'])) {
    return;
}
?>
<div class="panel panel--accent reward-card">
  <p class="panel__eyebrow">A thank-you for your first order</p>
  <h2 class="panel__title"><?= e((string) $reward['label']) ?> your next order</h2>
  <p class="reward-card__code"><span class="order-number"><?= e((string) $reward['code']) ?></span> <button class="copy-btn js-copy" type="button" data-copy="<?= e((string) $reward['code']) ?>" aria-label="Copy code"><svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="9" y="9" width="11" height="11" rx="1.5"/><path d="M15 9V5.5A1.5 1.5 0 0 0 13.5 4h-8A1.5 1.5 0 0 0 4 5.5v8A1.5 1.5 0 0 0 5.5 15H9"/></svg></button></p>
  <p class="text-muted text-small"><?= e((string) $reward['terms']) ?> Enter the code at checkout.</p>
</div>
