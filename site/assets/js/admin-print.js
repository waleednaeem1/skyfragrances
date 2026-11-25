(function () {
  'use strict';
  var doc = document;
  var receiptWidth = doc.body ? doc.body.getAttribute('data-receipt') : null;
  function remember(key, value) {
    try { window.localStorage.setItem(key, value); } catch (e) {}
  }
  function recall(key) {
    try { return window.localStorage.getItem(key); } catch (e) { return null; }
  }
  function fitReceipt() {
    var receipt = doc.querySelector('.receipt');
    if (!receiptWidth || !receipt) return;
    var heightMm = Math.ceil(receipt.getBoundingClientRect().height * 25.4 / 96) + 4;
    var style = doc.getElementById('receipt-page');
    if (!style) {
      style = doc.createElement('style');
      style.id = 'receipt-page';
      doc.head.appendChild(style);
    }
    style.textContent = '@media print { @page { size: ' + receiptWidth + 'mm ' + heightMm + 'mm; margin: 0; } }';
  }
  function printNow() {
    fitReceipt();
    try { window.print(); } catch (e) {}
  }
  if (receiptWidth) {
    var params = new URLSearchParams(window.location.search);
    if (!params.has('w') && recall('sf-receipt-width') === '58' && receiptWidth !== '58') {
      params.set('w', '58');
      window.location.replace(window.location.pathname + '?' + params.toString());
      return;
    }
    window.addEventListener('beforeprint', fitReceipt);
  }
  doc.addEventListener('click', function (ev) {
    var size = ev.target.closest('[data-receipt-width]');
    if (size) {
      remember('sf-receipt-width', size.getAttribute('data-receipt-width'));
      return;
    }
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
