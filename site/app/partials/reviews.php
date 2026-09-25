<?php
defined('SKYFR') || exit;
$reviews = is_array($reviews ?? null) ? $reviews : [];
$rating = is_array($rating ?? null) ? $rating : ['count' => 0, 'average' => 0.0, 'average_display' => '', 'histogram' => []];
$reviewsVisible = (int) ($reviewsVisible ?? 8);
$productName = (string) ($productName ?? 'this perfume');
$reviewAction = (string) ($reviewAction ?? '');
$trapField = (string) ($trapField ?? '');
$total = count($reviews);
$starPath = 'M12 2.6l2.85 6.05 6.65.8-4.9 4.6 1.3 6.55L12 17.35l-5.9 3.25 1.3-6.55-4.9-4.6 6.65-.8L12 2.6z';
?>
<header class="section-header sf-reveal">
  <p class="section-header__eyebrow">What customers say</p>
  <h2 class="section-header__title h2" id="reviews-title">Customer Reviews</h2>
</header>
<?php if ($rating['count'] >= 1): ?>
<div class="review-summary">
  <div class="review-summary__score">
    <span class="review-summary__num"><?= e($rating['average_display']) ?></span>
    <div>
      <?php partial('stars.php', ['rating' => $rating['average'], 'count' => $rating['count'], 'size' => 'md', 'showCount' => false]); ?>
      <p class="review-summary__count">Based on <?= e((string) $rating['count']) ?> <?= $rating['count'] === 1 ? 'review' : 'reviews' ?></p>
    </div>
  </div>
  <div class="review-summary__bars">
<?php foreach ($rating['histogram'] as $bar): ?>
    <div class="review-summary__bar"><span><?= e((string) $bar['star']) ?></span><span class="review-summary__track"><span class="review-summary__fill" style="--fill:<?= e((string) $bar['percent']) ?>%"></span></span><span><?= e((string) $bar['count']) ?></span></div>
<?php endforeach; ?>
  </div>
</div>
<ol class="review-list<?= $total > $reviewsVisible ? ' is-collapsed' : '' ?>" id="review-list">
<?php foreach ($reviews as $review): ?>
  <li class="review">
    <div class="review__head">
      <?php partial('stars.php', ['rating' => $review['rating'], 'count' => 0, 'showCount' => false]); ?>
<?php if ($review['title'] !== ''): ?>
      <h3 class="review__title"><?= e($review['title']) ?></h3>
<?php endif; ?>
    </div>
    <p class="review__body" id="rv-<?= e((string) $review['id']) ?>"><?= nl2br(e($review['body'])) ?></p>
<?php if ($review['is_long']): ?>
    <button class="btn btn--text review__more js-expand" type="button" aria-controls="rv-<?= e((string) $review['id']) ?>" aria-expanded="false"><span class="btn__label">Read more</span></button>
<?php endif; ?>
    <p class="review__meta"><span class="review__author"><?= e($review['author']) ?></span><?php if ($review['city'] !== ''): ?><span><?= e($review['city']) ?></span><?php endif; ?><span><time datetime="<?= e($review['date_iso']) ?>"><?= e($review['date']) ?></time></span></p>
  </li>
<?php endforeach; ?>
</ol>
<?php if ($total > $reviewsVisible): ?>
<button class="btn btn--ghost js-show-all-reviews" type="button" aria-controls="review-list"><span class="btn__label">Show all <?= e((string) $total) ?> reviews</span></button>
<?php endif; ?>
<?php else: ?>
<div class="empty-state">
  <p class="empty-state__title">No reviews yet.</p>
  <p class="empty-state__text">Be the first to review <?= e($productName) ?>.</p>
</div>
<?php endif; ?>
<div class="accordion" id="review-form-wrap">
  <div class="accordion__item">
    <h3 class="accordion__title h4"><button class="accordion__trigger js-accordion-trigger" type="button" aria-expanded="<?= $rating['count'] >= 1 ? 'false' : 'true' ?>" aria-controls="review-form-panel" id="review-form-trigger">Write a review<span class="accordion__glyph" aria-hidden="true"></span></button></h3>
    <div class="accordion__panel" id="review-form-panel" role="region" aria-labelledby="review-form-trigger">
      <div class="accordion__inner">
        <div class="accordion__body">
          <form class="form js-validate" id="review-form" method="post" action="<?= e($reviewAction) ?>">
            <?= csrf_field() ?>
            <?= $trapField ?>
            <div class="form__summary js-form-summary" role="alert" hidden></div>
            <div class="field" id="rating-field">
              <p class="field__label" id="rating-label">Your rating</p>
              <div class="star-input" role="radiogroup" aria-labelledby="rating-label" aria-describedby="err-rating">
<?php for ($star = 5; $star >= 1; $star--): ?>
                <label class="star-input__label"><input class="star-input__radio" type="radio" name="rating" value="<?= e((string) $star) ?>" required data-msg-required="Please choose a rating from 1 to 5."><svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="<?= $starPath ?>"/></svg><span class="u-sr-only"><?= e((string) $star) ?> <?= $star === 1 ? 'star' : 'stars' ?></span></label>
<?php endfor; ?>
              </div>
              <p class="field__error" id="err-rating"></p>
            </div>
            <div class="form__row">
              <div class="field">
                <label class="field__label" for="f-customer_name">Your name</label>
                <div class="field__control"><input class="field__input" id="f-customer_name" name="customer_name" type="text" required minlength="2" maxlength="60" pattern="[A-Za-zÀ-ɏ؀-ۿ .'\-]+" autocomplete="name" aria-describedby="err-customer_name" data-msg-required="Please enter your name." data-msg-pattern="Letters, spaces, dots, apostrophes and hyphens only."></div>
                <p class="field__error" id="err-customer_name"></p>
              </div>
              <div class="field">
                <label class="field__label" for="f-customer_city">City</label>
                <div class="field__control"><input class="field__input" id="f-customer_city" name="customer_city" type="text" required minlength="2" maxlength="60" autocomplete="address-level2" aria-describedby="err-customer_city" data-msg-required="Please enter your city."></div>
                <p class="field__error" id="err-customer_city"></p>
              </div>
            </div>
            <div class="field">
              <label class="field__label" for="f-title">Title <span class="field__optional">optional</span></label>
              <div class="field__control"><input class="field__input" id="f-title" name="title" type="text" maxlength="80" aria-describedby="err-title"></div>
              <p class="field__error" id="err-title"></p>
            </div>
            <div class="field">
              <label class="field__label" for="f-body">Your review</label>
              <div class="field__control"><textarea class="field__input field__input--textarea" id="f-body" name="body" rows="5" required minlength="20" maxlength="1500" aria-describedby="err-body cnt-body" data-maxlength-count="cnt-body" data-msg-required="Please write at least a sentence about the perfume." data-msg-short="Please write at least a sentence about the perfume."></textarea></div>
              <div class="field__hint-row">
                <p class="field__hint">How does it smell, how long does it last, and where do you wear it?</p>
                <p class="field__count" id="cnt-body"></p>
              </div>
              <p class="field__error" id="err-body"></p>
            </div>
            <div class="form__actions">
              <button class="btn btn--primary" type="submit"><span class="btn__label">Submit review</span></button>
              <p class="text-small text-muted">Reviews are checked by our team before they appear.</p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
