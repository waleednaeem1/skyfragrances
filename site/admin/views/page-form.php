<?php
defined('SKYFR') || exit;
$slug = (string) $page['slug'];
$formAction = '/admin/pages/' . $slug;
$fieldLabels = ['title' => 'Title', 'heading' => 'Heading', 'body' => $isFaq ? 'Questions' : 'Content', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description'];
$faqRow = static function (int $index, array $item, string $error, bool $isTemplate) use ($isFaq): void {
    $i = $isTemplate ? '__i__' : (string) $index;
    $n = $isTemplate ? '' : (string) ($index + 1);
    $qId = 'faq_' . ($isTemplate ? 'new' : $index) . '_q';
    $aId = 'faq_' . ($isTemplate ? 'new' : $index) . '_a';
    ?>
      <div class="adm-repeater__row<?= $error !== '' ? ' adm-field--error' : '' ?>" data-repeater-row id="faq_<?= e($isTemplate ? 'new' : (string) ($index + 1)) ?>">
        <div class="adm-repeater__head">
          <span class="adm-repeater__title">Question <span data-repeater-index><?= e($n) ?></span></span>
          <div class="adm-repeater__tools">
            <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="up" aria-label="Move up">&uarr;</button>
            <button type="button" class="adm-btn adm-btn--ghost adm-btn--sm" data-move="down" aria-label="Move down">&darr;</button>
            <button type="button" class="adm-btn adm-btn--danger adm-btn--sm" data-repeater-remove data-confirm="Remove this question?&#10;It disappears when you save." aria-label="Remove question">&times;</button>
          </div>
        </div>
<?php if ($error !== ''): ?>
        <p class="adm-field__error"><?= e($error) ?></p>
<?php endif; ?>
        <?php partial_admin('field.php', ['type' => 'text', 'name' => 'faq[' . $i . '][question]', 'id' => $qId, 'label' => 'Question', 'value' => (string) ($item['question'] ?? ''), 'maxlength' => 200, 'required' => true, 'placeholder' => 'How long does delivery take']); ?>
        <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'faq[' . $i . '][answer]', 'id' => $aId, 'label' => 'Answer', 'value' => (string) ($item['answer'] ?? ''), 'rows' => 4, 'required' => true, 'help' => 'Plain sentences. Leave a blank line to start a new paragraph.']); ?>
      </div>
    <?php
};
?>
<?php partial_admin('page-header.php', [
    'title' => $page['title'],
    'subtitle' => '/' . $slug . ($page['updated_at'] !== null ? ' · last edited ' . date_long($page['updated_at']) : ' · never edited'),
    'back' => '/admin/pages',
    'back_label' => 'Pages',
    'actions' => [['label' => 'View on site', 'href' => $siteUrl, 'external' => true]],
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">
  <p>Please fix the following:</p>
  <ul>
<?php foreach ($errors as $field => $message): ?>
    <li><a href="#<?= e($field) ?>"><?= e($fieldLabels[$field] ?? 'Question') ?>: <?= e($message) ?></a></li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<form method="post" action="<?= e(url($formAction)) ?>" class="adm-form" id="page-form" data-guard novalidate>
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Page</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'title', 'label' => 'Title', 'value' => $form['title'], 'required' => true, 'maxlength' => 160, 'error' => $errors['title'] ?? '', 'help' => 'Shown in the browser tab and as the page name in menus.']); ?>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'heading', 'label' => 'Heading', 'value' => $form['heading'], 'maxlength' => 160, 'error' => $errors['heading'] ?? '', 'placeholder' => $form['title'], 'help' => 'The big line at the top of the page. Blank uses the title.']); ?>
<?php if ($isSystem): ?>
    <p class="adm-note">This is one of the standard pages. It is always live because the shop links to it from the footer and checkout.</p>
<?php else: ?>
    <?php partial_admin('field.php', ['type' => 'checkbox', 'name' => 'is_active', 'label' => 'Live — visitors can open this page', 'value' => $form['is_active']]); ?>
<?php endif; ?>
  </div>
<?php if ($isFaq): ?>
  <div class="adm-form__section" id="body">
    <h2 class="adm-form__section-title">Questions and answers</h2>
    <p class="adm-note">Each question becomes an expandable row on the FAQ page and is sent to Google as FAQ data. Keep answers short and factual.</p>
<?php if (isset($errors['body'])): ?>
    <p class="adm-field__error"><?= e($errors['body']) ?></p>
<?php endif; ?>
    <div data-repeater>
      <div data-repeater-rows>
<?php foreach ($faqItems === [] ? [['question' => '', 'answer' => '']] : $faqItems as $index => $item): ?>
<?php $faqRow((int) $index, $item, (string) ($errors['faq_' . ($index + 1)] ?? ''), false); ?>
<?php endforeach; ?>
      </div>
      <template data-repeater-template><?php $faqRow(0, ['question' => '', 'answer' => ''], '', true); ?></template>
      <button type="button" class="adm-btn adm-btn--ghost" data-repeater-add>Add a question</button>
    </div>
  </div>
<?php else: ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Content</h2>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'body', 'label' => 'Page content', 'value' => $form['body'], 'rows' => 18, 'required' => true, 'error' => $errors['body'] ?? '', 'attr' => ['spellcheck' => 'true'], 'help' => 'Simple HTML. Allowed: <p> <h2> <h3> <strong> <em> <ul> <ol> <li> <a href> <br> <blockquote>. Anything else is stripped on save, so pasted Word formatting will not survive.']); ?>
    <details class="adm-card adm-card--flush">
      <summary class="adm-card__title">Formatting cheat sheet</summary>
      <ul class="adm-list">
        <li class="adm-list__item"><span class="adm-mono">&lt;p&gt;A paragraph.&lt;/p&gt;</span></li>
        <li class="adm-list__item"><span class="adm-mono">&lt;h2&gt;A section heading&lt;/h2&gt;</span></li>
        <li class="adm-list__item"><span class="adm-mono">&lt;strong&gt;bold&lt;/strong&gt;</span> and <span class="adm-mono">&lt;em&gt;italic&lt;/em&gt;</span></li>
        <li class="adm-list__item"><span class="adm-mono">&lt;ul&gt;&lt;li&gt;A bullet&lt;/li&gt;&lt;/ul&gt;</span></li>
        <li class="adm-list__item"><span class="adm-mono">&lt;a href="/shipping"&gt;a link&lt;/a&gt;</span> — site pages, https:// or mailto: only</li>
      </ul>
    </details>
  </div>
<?php endif; ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Search engines</h2>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'seo_title', 'label' => 'SEO title', 'value' => $form['seo_title'], 'maxlength' => 160, 'error' => $errors['seo_title'] ?? '', 'placeholder' => $form['title'] . ' | Sky Fragrances', 'help' => 'What Google shows as the blue link. Blank uses the title. Aim for under 60 characters.', 'attr' => ['data-counter-warn' => '60']]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'SEO description', 'value' => $form['seo_description'], 'rows' => 3, 'maxlength' => 255, 'error' => $errors['seo_description'] ?? '', 'help' => 'The grey line under the link in search results. Aim for 120–160 characters.', 'attr' => ['data-counter-warn' => '160']]); ?>
  </div>
</form>
<?php
$actionButtons = [['label' => 'Save page', 'variant' => 'gold', 'attr' => ['form' => 'page-form']]];
if (!empty($hasDefault)) {
    $actionButtons[] = ['label' => 'Restore default text', 'variant' => 'ghost', 'post' => '/admin/pages/' . $slug . '/restore-default', 'confirm' => "Restore the default text on this page?\nThe wording on /" . $slug . " goes back to the original Sky Fragrances text, and anything typed here but not saved is lost. Nothing else changes.", 'confirm_label' => 'Restore the default text'];
}
$actionButtons[] = ['label' => 'Cancel', 'href' => '/admin/pages', 'variant' => 'text'];
partial_admin('action-bar.php', ['buttons' => $actionButtons]);
?>
