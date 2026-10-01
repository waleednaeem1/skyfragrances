(function () {
  var root = document.documentElement;
  var tag = document.currentScript;
  var page = tag ? tag.getAttribute('data-page') || '' : '';
  var nav = navigator;
  var conn = nav.connection || {};
  var rules = {
    desktopMin: 1024,
    lowMemory: 2,
    lowCores: 2,
    slowTypes: ['slow-2g', '2g', '3g'],
    desktopGpu: { minMemory: 4, minCores: 4 }
  };
  var flags = { loader: 'desktop' };

  function matches(query) {
    return !!(window.matchMedia && window.matchMedia(query).matches);
  }

  function classify() {
    var reduced = matches('(prefers-reduced-motion: reduce)');
    var fine = matches('(hover: hover) and (pointer: fine)');
    var wide = matches('(min-width: ' + rules.desktopMin + 'px)');
    var slow = !!conn.saveData || rules.slowTypes.indexOf(String(conn.effectiveType || '')) !== -1;
    var weak = (nav.deviceMemory > 0 && nav.deviceMemory <= rules.lowMemory) || (nav.hardwareConcurrency > 0 && nav.hardwareConcurrency <= rules.lowCores);
    var desktop = wide && fine;
    var gpuWeak = (nav.deviceMemory > 0 && nav.deviceMemory < rules.desktopGpu.minMemory) || (nav.hardwareConcurrency > 0 && nav.hardwareConcurrency < rules.desktopGpu.minCores);
    return {
      device: reduced ? 'reduced' : desktop ? 'desktop' : (slow || weak) ? 'low' : 'mobile',
      reduced: reduced,
      touch: !fine,
      slow: slow,
      gpu: gpuWeak ? 'weak' : 'ok'
    };
  }

  function applyClass(info) {
    root.classList.remove('sf-reduce', 'motion--desktop', 'motion--mobile', 'motion--low');
    root.classList.add(info.reduced ? 'sf-reduce' : 'motion--' + info.device);
    if (info.device !== 'mobile') {
      root.classList.remove('motion--mobile-high');
    }
  }

  function introMode(info) {
    var loader = flags.loader;
    try {
      var stored = JSON.parse(localStorage.getItem('sfMotionFlags') || '{}') || {};
      if (stored.loader !== undefined) {
        loader = stored.loader;
      }
    } catch (e) {}
    if (page !== 'home' || info.reduced || loader === false || (loader === 'desktop' && info.device !== 'desktop')) {
      return '';
    }
    try {
      if (sessionStorage.getItem('sfIntro')) {
        return '';
      }
      sessionStorage.setItem('sfIntro', '1');
    } catch (e) {
      return '';
    }
    return info.device === 'desktop' && !info.slow && info.gpu !== 'weak' ? 'full' : 'calm';
  }

  var info = classify();
  applyClass(info);
  var mode = introMode(info);
  if (mode) {
    root.classList.add('sf-intro-' + mode);
    var src = tag ? tag.getAttribute('data-intro') : '';
    if (src) {
      var script = document.createElement('script');
      script.src = src;
      script.async = false;
      document.head.appendChild(script);
    }
  }
  window.SF_GATE = { rules: rules, flags: flags, classify: classify, applyClass: applyClass, info: info, intro: mode, theme: root.getAttribute('data-theme') === 'light' ? 'light' : 'dark' };
})();
