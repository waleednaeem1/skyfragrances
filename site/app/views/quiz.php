<?php
defined('SKYFR') || exit;
$questions = is_array($questions ?? null) ? $questions : [];
$total = max(1, (int) ($total ?? count($questions)));
?>
<section class="finder" aria-labelledby="quiz-title">
  <div class="finder__mist" aria-hidden="true" data-loop><span></span><span></span></div>
  <div class="shell finder__grid">
    <div class="finder__main">
      <?php partial('breadcrumb.php', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Scent Finder', 'url' => '']]]); ?>
      <p class="eyebrow">Scent Finder</p>
      <h1 class="finder__title" id="quiz-title"><?= em_tail((string) $heading) ?></h1>
      <p class="finder__intro">Five quick questions. No email, nothing stored — just three perfumes that agree with you.</p>
      <form class="form js-validate js-quiz finder__form" method="get" action="<?= e((string) $resultUrl) ?>" data-quiz>
        <div class="form__summary js-form-summary" role="alert" hidden></div>
        <p class="u-sr-only js-quiz-live" aria-live="polite"></p>
<?php foreach ($questions as $index => $question): ?>
<?php $step = $index + 1; $fieldName = 'q' . $step; ?>
        <fieldset class="quiz-step" id="question-<?= e((string) $step) ?>" data-quiz-step>
          <div class="quiz-step__progress" aria-hidden="true">
            <p class="quiz-step__count"><span>Question <?= e((string) $step) ?> of <?= e((string) $total) ?></span><span><?= e((string) round($step * 100 / $total)) ?>%</span></p>
            <span class="quiz-step__track"><span class="quiz-step__fill" style="--fill:<?= e((string) round($step / $total, 3)) ?>"></span></span>
          </div>
          <legend class="quiz-step__question"><?= e((string) $question['question']) ?></legend>
<?php if (trim((string) ($question['helper'] ?? '')) !== ''): ?>
          <p class="quiz-step__helper"><?= e((string) $question['helper']) ?></p>
<?php endif; ?>
          <div class="field">
            <div class="quiz-options" role="radiogroup" aria-describedby="err-<?= e($fieldName) ?>">
<?php foreach ($question['options'] as $optionIndex => [$label, $copy]): ?>
<?php $value = $optionIndex + 1; ?>
              <label class="quiz-option">
                <input class="quiz-option__input" id="<?= e($fieldName) ?>-<?= e((string) $value) ?>" type="radio" name="<?= e($fieldName) ?>" value="<?= e((string) $value) ?>"<?= $optionIndex === 0 ? ' required data-msg-required="Pick one answer to continue."' : '' ?>>
                <span class="quiz-option__card">
                  <span class="quiz-option__letter" aria-hidden="true"><?= e(chr(65 + $optionIndex)) ?></span>
                  <span class="quiz-option__text"><span class="quiz-option__title"><?= e($label) ?></span><span class="quiz-option__copy"><?= e($copy) ?></span></span>
                  <span class="quiz-option__dot" aria-hidden="true"></span>
                </span>
              </label>
<?php endforeach; ?>
            </div>
            <p class="field__error" id="err-<?= e($fieldName) ?>"></p>
          </div>
          <div class="quiz-step__nav">
<?php if ($step > 1): ?>
            <a class="btn btn--text btn--sm" href="#question-<?= e((string) ($step - 1)) ?>" data-quiz-back><span class="btn__label"><?php partial('icon.php', ['name' => 'arrow-left', 'size' => 16]); ?>Back</span></a>
<?php else: ?>
            <span></span>
<?php endif; ?>
<?php if ($step < $total): ?>
            <a class="btn btn--ghost btn--sm" href="#question-<?= e((string) ($step + 1)) ?>" data-quiz-next><span class="btn__label">Next<?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></a>
<?php endif; ?>
          </div>
        </fieldset>
<?php endforeach; ?>
        <div class="form__actions finder__submit">
          <button class="btn btn--primary btn--block" type="submit"><span class="btn__label">Show my matches<?php partial('icon.php', ['name' => 'arrow-right', 'size' => 16]); ?></span></button>
        </div>
      </form>
    </div>
    <div class="finder__visual" aria-hidden="true" data-loop>
      <span class="finder__glow"></span>
      <span class="finder__orbit"><i class="tone--citrus"></i><i class="tone--floral"></i><i class="tone--amber"></i><i class="tone--oud"></i><i class="tone--green"></i></span>
      <span class="finder__bottle"><?php partial('bottle.php', ['name' => '?', 'tone' => 'gold']); ?></span>
    </div>
  </div>
</section>
