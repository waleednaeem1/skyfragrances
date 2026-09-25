<?php defined('SKYFR') || exit; ?>
<div class="sf-intro" id="sf-intro" aria-hidden="true">
  <div class="sf-intro__mist"></div>
  <div class="sf-intro__mist sf-intro__mist--b"></div>
  <canvas class="sf-intro__canvas" width="1" height="1"></canvas>
  <div class="sf-intro__center">
    <div class="sf-intro__mark-wrap">
      <img class="sf-intro__mark" src="<?= e(asset('img/brand/monogram-transparent-256.webp')) ?>" width="256" height="256" alt="" decoding="async" fetchpriority="high">
    </div>
    <p class="sf-intro__tag"><?= e((string) setting('tagline', 'More Than Just A Scent')) ?></p>
  </div>
  <button class="sf-intro__skip" type="button" data-intro-skip tabindex="-1">Skip</button>
</div>
