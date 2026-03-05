(function () {
  function getRtClasses(el) {
    if (!el || !el.classList) return [];
    var classes = [];
    el.classList.forEach(function (c) {
      if (typeof c === 'string' && c.indexOf('js-rt-') === 0) {
        classes.push(c);
      }
    });
    return classes;
  }

  function syncRtValues(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var inputs = scope.querySelectorAll('input[type="hidden"][class*="js-rt-"]');

    for (var i = 0; i < inputs.length; i++) {
      var input = inputs[i];
      var attrValue = input.getAttribute('value');
      if (attrValue !== null && attrValue !== '' && input.value !== attrValue) {
        input.value = attrValue;
      }

      if (input.value !== '' && input.getAttribute('value') !== input.value) {
        input.setAttribute('value', input.value);
      }

      try {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } catch (e) {
      }
    }
  }

  function bindSubmitSync() {
    document.addEventListener(
      'submit',
      function (e) {
        if (!e || !e.target || !e.target.querySelectorAll) return;
        syncRtValues(e.target);
      },
      true
    );
  }

  function applyRtClasses(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var nodes = scope.querySelectorAll('[class*="js-rt-"]');

    for (var i = 0; i < nodes.length; i++) {
      var node = nodes[i];
      var rtClasses = getRtClasses(node);
      if (!rtClasses.length) continue;

      var targets;
      if (node.matches && node.matches('input,select,textarea')) {
        targets = [node];
      } else {
        targets = node.querySelectorAll('input,select,textarea');
      }

      for (var t = 0; t < targets.length; t++) {
        var target = targets[t];
        for (var c = 0; c < rtClasses.length; c++) {
          if (!target.classList.contains(rtClasses[c])) {
            target.classList.add(rtClasses[c]);
          }
        }
      }
    }

    syncRtValues(scope);
  }

  function startObserver() {
    if (!document.body || !window.MutationObserver) return;

    var scheduled = false;
    var observer = new MutationObserver(function () {
      if (scheduled) return;
      scheduled = true;
      setTimeout(function () {
        scheduled = false;
        applyRtClasses(document);
      }, 50);
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  window.addEventListener('DOMContentLoaded', function () {
    applyRtClasses(document);
    startObserver();
    bindSubmitSync();
  });
})();
