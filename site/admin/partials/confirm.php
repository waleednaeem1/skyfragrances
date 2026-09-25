<?php
defined('SKYFR') || exit;
?>
<dialog class="adm-confirm" data-confirm-dialog aria-labelledby="adm-confirm-title">
  <form method="dialog" class="adm-confirm__box">
    <div class="adm-sheet__handle" aria-hidden="true"></div>
    <h2 class="adm-confirm__title" id="adm-confirm-title" data-confirm-title>Are you sure?</h2>
    <p class="adm-confirm__text" data-confirm-text></p>
    <div class="adm-field" data-confirm-word-wrap hidden>
      <label class="adm-field__label" for="adm-confirm-word">Type <strong data-confirm-word-show></strong> to continue</label>
      <input class="adm-input" id="adm-confirm-word" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" data-confirm-word-input>
    </div>
    <div class="adm-confirm__actions">
      <button type="button" class="adm-btn adm-btn--ghost" data-confirm-cancel>Cancel</button>
      <button type="button" class="adm-btn adm-btn--gold" data-confirm-ok>Confirm</button>
    </div>
  </form>
</dialog>
