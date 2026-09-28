<?php
defined('SKYFR') || exit;
$questions = is_array($questions ?? null) ? $questions : [];
$total = max(1, (int) ($total ?? count($questions)));
?>
<div class="container container--narrow">
  <?php partial('breadcrumb.php', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Scent Finder', 'url' => '']]]); ?>
  <header class="page-head page-head--center">
    <p class="u-track text-muted">Scent Finder</p>
    <h1 class="page-head__title h1" id="quiz-title"><?= e((string) $heading) ?></h1>
    <p class="page-head__intro lead measure">Five quick questions. No email, nothing stored — just three perfumes that agree with you.</p>
  </header>
</div>
<section class="section section--flush-top" aria-labelledby="quiz-title">
  <div class="container container--narrow">
    <form class="form js-validate js-quiz stack" method="get" action="<?= e((string) $resultUrl) ?>" data-motion="quiz">
      <div class="form__summary js-form-summary" role="alert" hidden></div>
      <p class="u-sr-only js-quiz-live" aria-live="polite"></p>
      <div class="quiz-deck">
<?php foreach ($questions as $index => $question): ?>
<?php $step = $index + 1; $fieldName = 'q' . $step; $percent = (int) round($step * 100 / $total); ?>
      <fieldset class="form__section stack" id="question-<?= e((string) $step) ?>" data-motion-step="<?= e((string) $step) ?>">
        <div class="progress" aria-hidden="true">
          <p class="progress__label"><span>Question <?= e((string) $step) ?> of <?= e((string) $total) ?></span><span><?= e((string) $percent) ?>%</span></p>
          <div class="progress__track"><div class="progress__fill" style="--fill:<?= e((string) $percent) ?>%"></div></div>
        </div>
        <legend class="form__legend h3"><?= e((string) $question['question']) ?></legend>
<?php if (trim((string) ($question['helper'] ?? '')) !== ''): ?>
        <p class="text-muted text-small"><?= e((string) $question['helper']) ?></p>
<?php endif; ?>
        <div class="field">
        <div class="choice-group choice-group--answers" role="radiogroup" aria-describedby="err-<?= e($fieldName) ?>">
<?php foreach ($question['options'] as $optionIndex => [$label, $copy, $icon]): ?>
<?php $value = $optionIndex + 1; ?>
          <label class="choice choice--answer">
            <input class="choice__input" id="<?= e($fieldName) ?>-<?= e((string) $value) ?>" type="radio" name="<?= e($fieldName) ?>" value="<?= e((string) $value) ?>"<?= $optionIndex === 0 ? ' required data-msg-required="Pick one answer to continue."' : '' ?>>
            <span class="choice__card">
              <span class="choice__dot" aria-hidden="true"></span>
              <?php partial('icon.php', ['name' => $icon, 'size' => 22, 'class' => 'choice__icon']); ?>
              <span class="choice__title"><?= e($label) ?></span>
              <span class="choice__text"><?= e($copy) ?></span>
            </span>
          </label>
<?php endforeach; ?>
        </div>
        <p class="field__error" id="err-<?= e($fieldName) ?>"></p>
        </div>
        <p class="cluster cluster--between text-small">
<?php if ($step > 1): ?>
          <a class="btn btn--text btn--sm" href="#question-<?= e((string) ($step - 1)) ?>"><span class="btn__label">← Back</span></a>
<?php else: ?>
          <span></span>
<?php endif; ?>
<?php if ($step < $total): ?>
          <a class="btn btn--text btn--sm" href="#question-<?= e((string) ($step + 1)) ?>"><span class="btn__label">Next →</span></a>
<?php endif; ?>
        </p>
      </fieldset>
<?php endforeach; ?>
      </div>
      <div class="form__actions">
        <button class="btn btn--primary btn--block" type="submit"><?php partial('icon.php', ['name' => 'compass', 'size' => 18, 'class' => 'btn__icon']); ?><span class="btn__label">Show my matches</span></button>
      </div>
    </form>
  </div>
</section>
