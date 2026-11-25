(function () {
  'use strict';

  var doc = document;
  var body = doc.body;
  var csrf = (doc.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var base = body.getAttribute('data-base') || '';
  body.classList.add('is-enhanced');

  function on(selector, event, handler, opts) {
    doc.addEventListener(event, function (ev) {
      var target = ev.target.closest ? ev.target.closest(selector) : null;
      if (target) handler(ev, target);
    }, opts || false);
  }

  function qs(sel, root) { return (root || doc).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || doc).querySelectorAll(sel)); }

  function postJson(url, payload) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: JSON.stringify(payload || {})
    }).then(function (r) { return r.json().then(function (j) { j.status = r.status; return j; }); });
  }

  function postForm(url, formData, onProgress) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.setRequestHeader('X-CSRF-Token', csrf);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('Accept', 'application/json');
      if (xhr.upload && onProgress) xhr.upload.addEventListener('progress', function (ev) { if (ev.lengthComputable) onProgress(ev.loaded / ev.total); });
      xhr.onload = function () {
        var json = null;
        try { json = JSON.parse(xhr.responseText); } catch (e) { json = { ok: false, message: 'Unexpected response.' }; }
        json.status = xhr.status;
        resolve(json);
      };
      xhr.onerror = function () { reject(new Error('network')); };
      xhr.send(formData);
    });
  }

  window.SFAdmin = { postJson: postJson, postForm: postForm, csrf: csrf, base: base };

  on('[data-pw-toggle]', 'click', function (ev, button) {
    var input = doc.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    var reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    button.textContent = reveal ? 'Hide' : 'Show';
    button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
    input.focus();
  });

  on('[data-flash-close]', 'click', function (ev, button) {
    var bar = button.closest('[data-flash]');
    if (bar) bar.remove();
  });

  var palette = qs('[data-palette]');
  if (palette && typeof palette.showModal === 'function') {
    var paletteInput = qs('[data-palette-input]', palette);
    var paletteItems = qsa('[data-palette-item]', palette);
    var paletteActive = null;
    var isMac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
    if (!isMac) qsa('[data-palette-mod]').forEach(function (k) { k.textContent = 'Ctrl K'; });
    function paletteVisible() { return paletteItems.filter(function (item) { return !item.hidden && !item.closest('[hidden]'); }); }
    function paletteSetActive(item) {
      if (paletteActive) { paletteActive.classList.remove('is-active'); paletteActive.removeAttribute('aria-selected'); }
      paletteActive = item || null;
      if (paletteActive) {
        paletteActive.classList.add('is-active');
        paletteActive.setAttribute('aria-selected', 'true');
        paletteInput.setAttribute('aria-activedescendant', paletteActive.id);
        paletteActive.scrollIntoView({ block: 'nearest' });
      } else {
        paletteInput.removeAttribute('aria-activedescendant');
      }
    }
    paletteItems.forEach(function (item, i) { item.id = 'adm-palette-item-' + i; });
    function paletteFilter() {
      var query = paletteInput.value.trim();
      var needle = query.toLowerCase();
      paletteItems.forEach(function (item) {
        if (item.hasAttribute('data-palette-query-link')) return;
        item.hidden = needle !== '' && (item.getAttribute('data-keywords') || '').indexOf(needle) === -1;
      });
      qsa('[data-palette-group]', palette).forEach(function (group) {
        group.hidden = !qsa('[data-palette-item]', group).some(function (item) { return !item.hidden; });
      });
      var searchGroup = qs('[data-palette-search]', palette);
      if (searchGroup) {
        searchGroup.hidden = query === '';
        qsa('[data-palette-query]', searchGroup).forEach(function (span) { span.textContent = query; });
        qsa('[data-palette-query-link]', searchGroup).forEach(function (link) {
          if (!link.hasAttribute('data-base-href')) link.setAttribute('data-base-href', link.getAttribute('href'));
          link.setAttribute('href', link.getAttribute('data-base-href') + encodeURIComponent(query));
        });
      }
      var visible = paletteVisible();
      qs('[data-palette-empty]', palette).hidden = visible.length > 0;
      paletteSetActive(visible[0]);
    }
    function paletteOpen() {
      if (palette.open) return;
      paletteInput.value = '';
      paletteFilter();
      palette.showModal();
      paletteInput.focus();
    }
    function paletteClose() { if (palette.open) palette.close(); }
    paletteInput.addEventListener('input', paletteFilter);
    paletteInput.addEventListener('keydown', function (ev) {
      var visible = paletteVisible();
      var index = visible.indexOf(paletteActive);
      if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
        ev.preventDefault();
        if (visible.length === 0) return;
        var next = ev.key === 'ArrowDown' ? (index + 1) % visible.length : (index - 1 + visible.length) % visible.length;
        paletteSetActive(visible[next]);
      } else if (ev.key === 'Enter' && paletteActive) {
        ev.preventDefault();
        paletteActive.click();
      }
    });
    palette.addEventListener('mousemove', function (ev) {
      var item = ev.target.closest('[data-palette-item]');
      if (item && item !== paletteActive) paletteSetActive(item);
    });
    palette.addEventListener('click', function (ev) { if (ev.target === palette) paletteClose(); });
    on('[data-palette-open]', 'click', function () { paletteOpen(); });
    var leader = 0;
    doc.addEventListener('keydown', function (ev) {
      if ((ev.metaKey || ev.ctrlKey) && !ev.altKey && ev.key.toLowerCase() === 'k') {
        ev.preventDefault();
        if (palette.open) paletteClose(); else paletteOpen();
        return;
      }
      if (palette.open || ev.metaKey || ev.ctrlKey || ev.altKey || ev.target.closest('input, textarea, select, [contenteditable="true"]')) return;
      var key = ev.key.toLowerCase();
      if (key === 'g') { leader = Date.now(); return; }
      var target = null;
      if (Date.now() - leader < 1200) target = qs('[data-shortcut="' + key + '"]:not([data-shortcut="n"])', palette);
      else if (key === 'n') target = qs('[data-shortcut="n"]', palette);
      leader = 0;
      if (target) { ev.preventDefault(); window.location.href = target.href; }
    });
  }

  on('[data-side-toggle]', 'click', function (ev, button) {
    var side = button.closest('.adm-side');
    var open = !side.classList.contains('is-open');
    side.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  function syncShowWhen() {
    qsa('[data-show-when]').forEach(function (el) {
      var rule = el.getAttribute('data-show-when').split('=');
      var field = qs('[name="' + rule[0] + '"]:checked') || qs('select[name="' + rule[0] + '"]');
      el.hidden = !!field && field.value !== rule[1];
    });
  }
  doc.addEventListener('change', function (ev) {
    if (ev.target.name) syncShowWhen();
  });
  syncShowWhen();

  function closePopovers(except) {
    qsa('details[data-popover][open]').forEach(function (popover) {
      if (popover !== except) popover.removeAttribute('open');
    });
  }
  doc.addEventListener('click', function (ev) {
    closePopovers(ev.target.closest ? ev.target.closest('details[data-popover]') : null);
  });
  doc.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Escape') return;
    var open = qs('details[data-popover][open]');
    if (!open) return;
    open.removeAttribute('open');
    qs('summary', open).focus();
  });


  var dirty = false;
  on('form[data-guard]', 'input', function () { dirty = true; });
  on('form[data-guard]', 'change', function () { dirty = true; });
  window.addEventListener('beforeunload', function (ev) {
    if (!dirty) return;
    ev.preventDefault();
    ev.returnValue = '';
  });

  var dialog = qs('[data-confirm-dialog]');
  var pendingSubmit = null;
  function confirmClose() {
    if (!dialog) return;
    if (dialog.open) dialog.close();
    pendingSubmit = null;
  }
  function confirmOpen(source, proceed) {
    if (!dialog || typeof dialog.showModal !== 'function') {
      if (window.confirm(source.getAttribute('data-confirm'))) proceed();
      return;
    }
    var word = source.getAttribute('data-confirm-word') || '';
    var text = source.getAttribute('data-confirm') || 'Are you sure?';
    var label = source.getAttribute('data-confirm-label') || (word ? 'Yes, ' + word.toLowerCase() : 'Confirm');
    var danger = source.getAttribute('data-confirm-danger') === '1' || source.classList.contains('adm-btn--danger') || !!source.querySelector('.adm-btn--danger');
    qs('[data-confirm-title]', dialog).textContent = source.getAttribute('data-confirm-title') || text.split('\n')[0];
    qs('[data-confirm-text]', dialog).textContent = text.indexOf('\n') > -1 ? text.split('\n').slice(1).join('\n') : '';
    var wrap = qs('[data-confirm-word-wrap]', dialog);
    var input = qs('[data-confirm-word-input]', dialog);
    var ok = qs('[data-confirm-ok]', dialog);
    wrap.hidden = !word;
    qs('[data-confirm-word-show]', dialog).textContent = word;
    input.value = '';
    ok.textContent = label;
    ok.disabled = !!word;
    dialog.classList.toggle('is-danger', danger);
    pendingSubmit = { proceed: proceed, word: word };
    dialog.showModal();
    if (word) input.focus(); else ok.focus();
  }
  if (dialog) {
    qs('[data-confirm-word-input]', dialog).addEventListener('input', function (ev) {
      if (pendingSubmit) qs('[data-confirm-ok]', dialog).disabled = ev.target.value.trim() !== pendingSubmit.word;
    });
    qs('[data-confirm-cancel]', dialog).addEventListener('click', confirmClose);
    qs('[data-confirm-ok]', dialog).addEventListener('click', function () {
      if (!pendingSubmit) return;
      var proceed = pendingSubmit.proceed;
      var typed = qs('[data-confirm-word-input]', dialog).value.trim();
      confirmClose();
      proceed(typed);
    });
    dialog.addEventListener('click', function (ev) { if (ev.target === dialog) confirmClose(); });
    dialog.addEventListener('cancel', function () { pendingSubmit = null; });
  }
  function submitConfirmed(form, source, typed) {
    if (source && source.hasAttribute('data-confirm-word')) {
      var hidden = form.querySelector('input[name="confirm_word"]');
      if (!hidden) { hidden = doc.createElement('input'); hidden.type = 'hidden'; hidden.name = 'confirm_word'; form.appendChild(hidden); }
      hidden.value = typed || '';
    }
    if (source && source.tagName === 'BUTTON' && source.name) {
      var carrier = doc.createElement('input');
      carrier.type = 'hidden'; carrier.name = source.name; carrier.value = source.value;
      form.appendChild(carrier);
    }
    form.setAttribute('data-confirmed', '1');
    dirty = false;
    if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
  }
  on('form', 'submit', function (ev, form) {
    if (form.getAttribute('data-confirmed') === '1') { form.removeAttribute('data-confirmed'); return; }
    var source = ev.submitter && ev.submitter.hasAttribute('data-confirm') ? ev.submitter : (form.hasAttribute('data-confirm') ? form : null);
    if (!source && ev.submitter && ev.submitter.form === form && form.hasAttribute('data-bulk-form')) {
      source = ev.submitter.hasAttribute('data-confirm') ? ev.submitter : null;
    }
    if (source) {
      ev.preventDefault();
      confirmOpen(source, function (typed) { submitConfirmed(form, source, typed); });
      return;
    }
    dirty = false;
    var submit = ev.submitter || form.querySelector('button[type=submit]');
    if (!submit) return;
    setTimeout(function () { submit.disabled = true; submit.classList.add('is-loading'); }, 0);
    setTimeout(function () { submit.disabled = false; submit.classList.remove('is-loading'); }, 8000);
  });
  on('a[data-confirm]', 'click', function (ev, link) {
    ev.preventDefault();
    confirmOpen(link, function () { window.location.href = link.href; });
  });

  on('[data-filters-toggle]', 'click', function (ev, button) {
    var panel = qs('[data-filters-panel]', button.closest('[data-filters]'));
    if (!panel) return;
    var open = !panel.classList.contains('is-open');
    panel.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  var filterTimer = null;
  on('form[data-filters] [data-autosubmit]', 'change', function (ev, field) {
    var form = field.closest('form');
    clearTimeout(filterTimer);
    filterTimer = setTimeout(function () {
      if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
    }, 250);
  });

  on('[data-href]', 'click', function (ev, row) {
    if (ev.target.closest('a, button, input, select, textarea, label')) return;
    window.location.href = row.getAttribute('data-href');
  });

  function bulkRefresh(list) {
    var boxes = qsa('[data-select-row]', list);
    var seen = {};
    var count = 0;
    boxes.forEach(function (box) {
      var row = box.closest('.adm-table__row');
      if (row) row.classList.toggle('is-selected', box.checked);
      if (box.checked && !seen[box.value]) { seen[box.value] = true; count++; }
    });
    var bar = qs('[data-bulk-bar]', list);
    if (bar) {
      bar.hidden = count === 0;
      var label = qs('[data-bulk-count]', bar);
      if (label) label.textContent = count + ' selected';
    }
    var all = qs('[data-select-all]', list);
    if (all) {
      all.checked = boxes.length > 0 && count === boxes.length;
      all.indeterminate = count > 0 && count < boxes.length;
    }
  }
  on('[data-select-row]', 'change', function (ev, box) {
    bulkRefresh(box.closest('[data-list]'));
  });
  on('[data-bulk-clear]', 'click', function (ev, button) {
    var list = button.closest('[data-list]');
    qsa('[data-select-row], [data-select-all]', list).forEach(function (b) { b.checked = false; });
    bulkRefresh(list);
  });
  on('[data-select-all]', 'change', function (ev, all) {
    var list = all.closest('[data-list]');
    qsa('[data-select-row]', list).forEach(function (b) { b.checked = all.checked; });
    bulkRefresh(list);
  });
  on('form[data-bulk-form]', 'submit', function (ev, form) {
    var list = form.closest('[data-list]');
    var checked = qsa('[data-select-row]:checked', list);
    if (checked.length === 0) { ev.preventDefault(); return; }
    var seen = {};
    checked.forEach(function (box) {
      if (seen[box.value]) box.disabled = true; else seen[box.value] = true;
    });
    qsa('[data-select-row]:not(:checked)', list).forEach(function (b) { b.disabled = true; });
    setTimeout(function () { qsa('[data-select-row]', list).forEach(function (b) { b.disabled = false; }); }, 3000);
  }, true);

  on('[data-copy]', 'click', function (ev, button) {
    var text = button.getAttribute('data-copy');
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function () {
      var html = button.innerHTML;
      var label = button.querySelector('.u-sr-only');
      if (label) label.textContent = 'Copied'; else button.textContent = 'Copied';
      button.classList.add('is-copied');
      setTimeout(function () { button.innerHTML = html; button.classList.remove('is-copied'); }, 1500);
    });
  });

  qsa('[data-counter]').forEach(function (field) {
    var max = parseInt(field.getAttribute('maxlength') || '0', 10);
    if (!max) return;
    var warnAt = parseInt(field.getAttribute('data-counter-warn') || String(Math.floor(max * 0.9)), 10);
    var out = doc.createElement('p');
    out.className = 'adm-field__counter';
    out.setAttribute('aria-live', 'polite');
    var holder = field.closest('.adm-field');
    var top = holder ? qs('.adm-field__top', holder) : null;
    if (top) top.appendChild(out); else field.insertAdjacentElement('afterend', out);
    function update() {
      var len = field.value.length;
      out.textContent = len + ' / ' + max;
      out.classList.toggle('is-warn', len >= warnAt && len < max);
      out.classList.toggle('is-over', len >= max);
    }
    field.addEventListener('input', update);
    update();
  });

  qsa('[data-slug-from]').forEach(function (slug) {
    var source = doc.getElementById(slug.getAttribute('data-slug-from'));
    if (!source) return;
    var touched = slug.value !== '';
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    source.addEventListener('input', function () {
      if (touched) return;
      slug.value = source.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
  });

  function renumber(repeater) {
    var rows = qsa('[data-repeater-row]', repeater);
    rows.forEach(function (row, i) {
      qsa('[name]', row).forEach(function (field) {
        field.name = field.name.replace(/\[(?:__i__|\d+)\]/, '[' + i + ']');
      });
      qsa('[data-repeater-index]', row).forEach(function (el) { el.textContent = String(i + 1); });
      var up = qs('[data-move="up"]', row), down = qs('[data-move="down"]', row), remove = qs('[data-repeater-remove]', row);
      if (up) up.disabled = i === 0;
      if (down) down.disabled = i === rows.length - 1;
      if (remove) remove.disabled = rows.length <= 1;
    });
  }
  on('[data-repeater-add]', 'click', function (ev, button) {
    var repeater = button.closest('[data-repeater]') || doc.getElementById(button.getAttribute('data-repeater-add'));
    var template = qs('template[data-repeater-template]', repeater);
    if (!template) return;
    var container = qs('[data-repeater-rows]', repeater) || repeater;
    container.appendChild(template.content.cloneNode(true));
    renumber(repeater);
    dirty = true;
    var last = qsa('[data-repeater-row]', repeater).pop();
    var first = last && qs('input, select, textarea', last);
    if (first) first.focus();
  });
  on('[data-repeater-remove]', 'click', function (ev, button) {
    var row = button.closest('[data-repeater-row]');
    var repeater = row.closest('[data-repeater]');
    var proceed = function () { row.remove(); renumber(repeater); dirty = true; };
    if (button.hasAttribute('data-confirm')) confirmOpen(button, proceed); else proceed();
  });
  on('[data-move]', 'click', function (ev, button) {
    var item = button.closest('[data-repeater-row], [data-sortable-item]');
    if (!item) return;
    var sibling = button.getAttribute('data-move') === 'up' ? item.previousElementSibling : item.nextElementSibling;
    if (!sibling || !sibling.matches('[data-repeater-row], [data-sortable-item]')) return;
    if (button.getAttribute('data-move') === 'up') sibling.before(item); else sibling.after(item);
    var repeater = item.closest('[data-repeater]');
    if (repeater) { renumber(repeater); dirty = true; }
    var sortable = item.closest('[data-sortable]');
    if (sortable) sortableCommit(sortable);
    button.focus();
  });
  qsa('[data-repeater]').forEach(renumber);

  function sortableIds(sortable) {
    return qsa('[data-sortable-item]', sortable).map(function (el) { return el.getAttribute('data-id'); });
  }
  function sortableApply(sortable, ids) {
    var map = {};
    qsa('[data-sortable-item]', sortable).forEach(function (el) { map[el.getAttribute('data-id')] = el; });
    ids.forEach(function (id) { if (map[id]) sortable.appendChild(map[id]); });
    qsa('[data-sortable-item]', sortable).forEach(function (el, i) {
      el.classList.toggle('is-primary', i === 0);
      var up = qs('[data-move="up"]', el), down = qs('[data-move="down"]', el);
      if (up) up.disabled = i === 0;
      if (down) down.disabled = i === ids.length - 1;
    });
  }
  function sortableCommit(sortable) {
    var ids = sortableIds(sortable);
    sortableApply(sortable, ids);
    sortable.dispatchEvent(new CustomEvent('adm:reorder', { bubbles: true, detail: { ids: ids } }));
    var url = sortable.getAttribute('data-sortable-url');
    if (!url) return;
    sortable.setAttribute('aria-busy', 'true');
    postJson(url, { ids: ids }).then(function (res) {
      if (res && res.ok && Array.isArray(res.ids)) sortableApply(sortable, res.ids.map(String));
      else if (res && res.message) alert(res.message);
    }).catch(function () { alert('Could not save the new order. Check your connection.'); })
      .finally(function () { sortable.removeAttribute('aria-busy'); });
  }
  qsa('[data-sortable]').forEach(function (sortable) {
    var dragging = null;
    sortable.addEventListener('pointerdown', function (ev) {
      var handle = ev.target.closest('[data-sortable-handle]');
      if (!handle || ev.button !== 0) return;
      dragging = handle.closest('[data-sortable-item]');
      dragging.classList.add('is-dragging');
      dragging.setPointerCapture && handle.setPointerCapture(ev.pointerId);
      ev.preventDefault();
    });
    sortable.addEventListener('pointermove', function (ev) {
      if (!dragging) return;
      var under = doc.elementFromPoint(ev.clientX, ev.clientY);
      var over = under && under.closest('[data-sortable-item]');
      if (!over || over === dragging || over.parentNode !== sortable) return;
      var rect = over.getBoundingClientRect();
      var before = (ev.clientX - rect.left) < rect.width / 2 && Math.abs(ev.clientY - rect.top) < rect.height;
      if (before) over.before(dragging); else over.after(dragging);
    });
    function end() {
      if (!dragging) return;
      dragging.classList.remove('is-dragging');
      dragging = null;
      sortableCommit(sortable);
    }
    sortable.addEventListener('pointerup', end);
    sortable.addEventListener('pointercancel', end);
    sortableApply(sortable, sortableIds(sortable));
  });

  qsa('[data-uploader]').forEach(function (zone) {
    var input = qs('input[type=file]', zone);
    var url = zone.getAttribute('data-uploader');
    var tiles = doc.getElementById(zone.getAttribute('data-uploader-target'));
    var max = parseInt(zone.getAttribute('data-uploader-max') || '6291456', 10);
    var allowed = { 'image/jpeg': 1, 'image/png': 1, 'image/webp': 1 };
    if (!input || !url) return;
    function sniff(file) {
      return new Promise(function (resolve) {
        var reader = new FileReader();
        reader.onload = function () {
          var b = new Uint8Array(reader.result);
          var ok = (b[0] === 0xFF && b[1] === 0xD8) || (b[0] === 0x89 && b[1] === 0x50) || (b[8] === 0x57 && b[9] === 0x45 && b[10] === 0x42 && b[11] === 0x50);
          resolve(ok);
        };
        reader.onerror = function () { resolve(false); };
        reader.readAsArrayBuffer(file.slice(0, 12));
      });
    }
    function report(text) {
      var out = qs('[data-uploader-status]', zone);
      if (out) out.textContent = text;
    }
    function uploadOne(file) {
      if (!allowed[file.type]) { report(file.name + ': only JPG, PNG or WEBP.'); return Promise.resolve(); }
      if (file.size > max) { report(file.name + ': larger than ' + Math.round(max / 1048576) + ' MB.'); return Promise.resolve(); }
      return sniff(file).then(function (ok) {
        if (!ok) { report(file.name + ' is not a real image.'); return; }
        var li = doc.createElement('li');
        li.className = 'adm-tile';
        li.innerHTML = '<img class="adm-tile__img" alt=""><div class="adm-tile__progress"><div class="adm-tile__progress-bar"></div></div>';
        qs('img', li).src = URL.createObjectURL(file);
        if (tiles) tiles.appendChild(li);
        var fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('image', file);
        var bar = qs('.adm-tile__progress-bar', li);
        return postForm(url, fd, function (p) { bar.style.width = Math.round(p * 100) + '%'; }).then(function (res) {
          if (res.ok && res.html) { li.outerHTML = res.html; if (tiles && tiles.hasAttribute('data-sortable')) sortableApply(tiles, sortableIds(tiles)); report(''); }
          else { li.remove(); report(res.message || 'Upload failed.'); }
        }).catch(function () { li.remove(); report('Upload failed. Check your connection.'); });
      });
    }
    function handle(files) {
      var queue = Array.prototype.slice.call(files);
      (function next() { var f = queue.shift(); if (f) uploadOne(f).then(next); })();
    }
    input.addEventListener('change', function () { handle(input.files); input.value = ''; });
    zone.addEventListener('dragover', function (ev) { ev.preventDefault(); zone.classList.add('is-over'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('is-over'); });
    zone.addEventListener('drop', function (ev) { ev.preventDefault(); zone.classList.remove('is-over'); handle(ev.dataTransfer.files); });
  });

  var pw = doc.getElementById('new_password');
  if (pw) {
    pw.addEventListener('input', function () {
      var len = pw.value.length;
      var text = len === 0 ? '' : len < 12 ? 'Too short' : len < 16 ? 'Okay' : len < 24 ? 'Good' : 'Strong';
      var meter = qs('[data-strength]', pw.closest('.adm-field'));
      if (!meter) {
        meter = doc.createElement('p');
        meter.className = 'adm-note';
        meter.setAttribute('data-strength', '');
        meter.setAttribute('aria-live', 'polite');
        pw.closest('.adm-field').appendChild(meter);
      }
      meter.textContent = text;
    });
  }
})();
