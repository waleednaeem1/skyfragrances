(function () {
  'use strict';

  var doc = document;
  var api = window.SFAdmin || null;
  var nameField = doc.getElementById('name');

  function qs(sel, root) { return (root || doc).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(sel)); }
  function on(selector, event, handler) {
    doc.addEventListener(event, function (ev) {
      var target = ev.target.closest ? ev.target.closest(selector) : null;
      if (target) handler(ev, target);
    });
  }

  qsa('[data-nojs-only]').forEach(function (el) { el.hidden = true; });

  var dialog = qs('[data-confirm-dialog]');
  function ask(text, danger, proceed) {
    if (!dialog || typeof dialog.showModal !== 'function') {
      if (window.confirm(text)) proceed();
      return;
    }
    var lines = text.split('\n');
    qs('[data-confirm-title]', dialog).textContent = lines[0];
    qs('[data-confirm-text]', dialog).textContent = lines.slice(1).join('\n');
    qs('[data-confirm-word-wrap]', dialog).hidden = true;
    var ok = qs('[data-confirm-ok]', dialog);
    ok.textContent = danger ? 'Yes, delete' : 'Confirm';
    ok.disabled = false;
    dialog.classList.toggle('is-danger', !!danger);
    var done = false;
    function cleanup() { ok.removeEventListener('click', yes); dialog.removeEventListener('close', no); }
    function yes() { if (done) return; done = true; cleanup(); if (dialog.open) dialog.close(); proceed(); }
    function no() { if (done) return; done = true; cleanup(); }
    ok.addEventListener('click', yes);
    dialog.addEventListener('close', no);
    dialog.showModal();
    ok.focus();
  }

  function tilesApply(tiles, ids, deleted) {
    if (deleted) {
      var gone = qs('[data-sortable-item][data-id="' + deleted + '"]', tiles);
      if (gone) gone.remove();
    }
    var map = {};
    qsa('[data-sortable-item]', tiles).forEach(function (el) { map[el.getAttribute('data-id')] = el; });
    ids.forEach(function (id) { if (map[String(id)]) tiles.appendChild(map[String(id)]); });
    qsa('[data-sortable-item]', tiles).forEach(function (el, i) {
      el.classList.toggle('is-primary', i === 0);
      var badge = qs('[data-tile-primary-badge]', el);
      if (badge) badge.hidden = i !== 0;
      var up = qs('[data-move="up"]', el), down = qs('[data-move="down"]', el), star = qs('[data-image-primary]', el);
      if (up) up.disabled = i === 0;
      if (down) down.disabled = i === ids.length - 1;
      if (star) star.disabled = i === 0;
    });
    var empty = qs('[data-images-empty]');
    if (empty) empty.hidden = qsa('[data-sortable-item]', tiles).length > 0;
  }

  on('[data-image-action]', 'click', function (ev, button) {
    ev.preventDefault();
    if (!api) return;
    var tiles = button.closest('[data-sortable]');
    var url = button.getAttribute('data-image-action');
    var deleting = button.hasAttribute('data-image-delete');
    var run = function () {
      button.disabled = true;
      api.postJson(url, {}).then(function (res) {
        if (res && res.ok && Array.isArray(res.ids)) tilesApply(tiles, res.ids.map(String), deleting ? button.getAttribute('data-image-delete') : null);
        else alert((res && res.message) || 'That did not work. Reload and try again.');
      }).catch(function () { alert('Could not reach the server. Check your connection.'); })
        .finally(function () { button.disabled = false; });
    };
    if (deleting) ask(button.getAttribute('data-confirm') || 'Delete this photo?', true, run); else run();
  });

  qsa('[data-sortable]').forEach(function (tiles) {
    tiles.addEventListener('adm:reorder', function (ev) { tilesApply(tiles, ev.detail.ids, null); });
    var observer = new MutationObserver(function () {
      qsa('[data-sortable-item]', tiles).forEach(function (el, i) {
        var badge = qs('[data-tile-primary-badge]', el);
        if (badge) badge.hidden = i !== 0;
        var star = qs('[data-image-primary]', el);
        if (star) star.disabled = i === 0;
      });
      var empty = qs('[data-images-empty]');
      if (empty) empty.hidden = qsa('[data-sortable-item]', tiles).length > 0;
    });
    observer.observe(tiles, { childList: true });
  });

  function chips(input) {
    var out = doc.getElementById(input.getAttribute('data-notes-preview'));
    if (!out) return;
    var items = input.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    out.textContent = '';
    items.slice(0, 12).forEach(function (note) {
      var chip = doc.createElement('span');
      chip.className = 'adm-chip adm-chip--muted';
      chip.textContent = note;
      out.appendChild(chip);
    });
    if (items.length > 8) {
      var warn = doc.createElement('span');
      warn.className = 'adm-note';
      warn.textContent = 'Only the first 8 notes show on the product page.';
      out.appendChild(warn);
    }
  }
  qsa('[data-notes-preview]').forEach(function (input) {
    input.addEventListener('input', function () { chips(input); });
    chips(input);
  });

  var seo = qs('[data-seo-preview]');
  if (seo) {
    var shortField = doc.getElementById('short_description'), slugField = doc.getElementById('slug');
    var titleField = doc.getElementById('seo_title'), descField = doc.getElementById('seo_description'), familyField = doc.getElementById('scent_family');
    function refresh() {
      var name = nameField ? nameField.value.trim() : '';
      var family = familyField && familyField.value ? familyField.value + ' perfume' : 'luxury perfume';
      var title = titleField && titleField.value.trim() ? titleField.value.trim() : (name ? name + ' — ' + family : 'Product name — ' + family);
      title = title.replace(/\s*\|\s*Sky Fragrances\s*$/i, '');
      var desc = descField && descField.value.trim() ? descField.value.trim() : (shortField ? shortField.value.trim() : '');
      qs('[data-seo-title]', seo).textContent = title + ' | Sky Fragrances';
      qs('[data-seo-url]', seo).textContent = seo.getAttribute('data-seo-base') + '/product/' + ((slugField && slugField.value) || 'your-link-name');
      qs('[data-seo-desc]', seo).textContent = desc || 'Add a short description and it appears here.';
    }
    [nameField, shortField, slugField, titleField, descField, familyField].forEach(function (f) { if (f) f.addEventListener('input', refresh); if (f) f.addEventListener('change', refresh); });
    refresh();
  }

  var repeater = qs('[data-repeater="sizes"]');
  if (repeater) {
    function syncDefaults() {
      qsa('[data-repeater-row]', repeater).forEach(function (row, i) {
        var radio = qs('input[name="default_size"]', row);
        if (radio) radio.value = String(i);
      });
      if (!qs('input[name="default_size"]:checked', repeater)) {
        var first = qs('input[name="default_size"]', repeater);
        if (first) first.checked = true;
      }
    }
    new MutationObserver(syncDefaults).observe(qs('[data-repeater-rows]', repeater) || repeater, { childList: true });
    repeater.addEventListener('click', function (ev) { if (ev.target.closest('[data-move]')) setTimeout(syncDefaults, 0); });
    syncDefaults();
    on('[data-repeater="sizes"] [data-size-label], [data-repeater="sizes"] [data-size-ml]', 'change', function (ev, field) {
      var row = field.closest('[data-repeater-row]');
      var sku = qs('[data-size-sku]', row), label = qs('[data-size-label]', row), ml = qs('[data-size-ml]', row);
      if (!sku || sku.value.trim() !== '') return;
      var name = nameField ? nameField.value : '';
      var initials = name.split(/\s+/).map(function (w) { return w.replace(/[^a-z]/ig, '').charAt(0).toUpperCase(); }).join('').slice(0, 4) || 'SKU';
      var m = (ml && ml.value) || ((label && label.value.match(/(\d+)\s*ml/i) || [])[1]) || '';
      if (ml && !ml.value && m) ml.value = m;
      sku.value = 'SF-' + initials + '-' + (m ? ('000' + m).slice(-3) : 'S' + (qsa('[data-repeater-row]', repeater).indexOf(row) + 1));
    });
  }
})();
