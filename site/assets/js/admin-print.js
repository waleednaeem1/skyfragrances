(function () {
  'use strict';
  var doc = document;
  function printNow() {
    try { window.print(); } catch (e) {}
  }
  doc.addEventListener('click', function (ev) {
    var button = ev.target.closest('[data-print]');
    if (!button) return;
    ev.preventDefault();
    printNow();
  });
  if (doc.body && doc.body.hasAttribute('data-autoprint')) {
    window.addEventListener('load', function () {
      window.setTimeout(printNow, 250);
    });
  }
})();
