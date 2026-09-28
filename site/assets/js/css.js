(function () {
  var links = document.querySelectorAll('link[rel="stylesheet"][data-media]');
  function activate(link) {
    var target = link.getAttribute('data-media') || 'all';
    if (link.media !== target) {
      link.media = target;
    }
  }
  function isLoaded(link) {
    try {
      return !!(link.sheet && link.sheet.cssRules && link.sheet.cssRules.length);
    } catch (error) {
      return false;
    }
  }
  Array.prototype.forEach.call(links, function (link) {
    if (isLoaded(link)) {
      activate(link);
      return;
    }
    link.addEventListener('load', function () { activate(link); });
    link.addEventListener('error', function () { activate(link); });
  });
})();
