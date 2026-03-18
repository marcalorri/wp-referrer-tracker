(function () {
  var STORAGE_PREFIX = 'rt_';

  var TRACKING_FIELDS = [
    { key: 'source',       id: 'rt-source',       name: 'rt_source',       cls: 'js-rt-source' },
    { key: 'medium',       id: 'rt-medium',       name: 'rt_medium',       cls: 'js-rt-medium' },
    { key: 'campaign',     id: 'rt-campaign',     name: 'rt_campaign',     cls: 'js-rt-campaign' },
    { key: 'content',      id: 'rt-content',      name: 'rt_content',      cls: 'js-rt-content' },
    { key: 'term',         id: 'rt-term',         name: 'rt_term',         cls: 'js-rt-term' },
    { key: 'referrer',     id: 'rt-referrer',     name: 'rt_referrer',     cls: 'js-rt-referrer' },
    { key: 'landing_page', id: 'rt-landing-page', name: 'rt_landing_page', cls: 'js-rt-landing-page' },
    { key: 'gclid',        id: 'rt-gclid',        name: 'rt_gclid',        cls: 'js-rt-gclid' },
    { key: 'fbclid',       id: 'rt-fbclid',       name: 'rt_fbclid',       cls: 'js-rt-fbclid' },
    { key: 'msclkid',      id: 'rt-msclkid',      name: 'rt_msclkid',      cls: 'js-rt-msclkid' },
    { key: 'ttclid',       id: 'rt-ttclid',       name: 'rt_ttclid',       cls: 'js-rt-ttclid' },
    { key: 'li_fat_id',    id: 'rt-li-fat-id',    name: 'rt_li_fat_id',    cls: 'js-rt-li-fat-id' },
    { key: 'twclid',       id: 'rt-twclid',       name: 'rt_twclid',       cls: 'js-rt-twclid' },
    { key: 'epik',         id: 'rt-epik',         name: 'rt_epik',         cls: 'js-rt-epik' },
    { key: 'rdt_cid',      id: 'rt-rdt-cid',      name: 'rt_rdt_cid',      cls: 'js-rt-rdt-cid' }
  ];

  function getFromStorage(key) {
    var storageKey = STORAGE_PREFIX + key;
    try {
      var val = localStorage.getItem(storageKey);
      if (val) return val;
    } catch (e) {}
    try {
      var val2 = sessionStorage.getItem(storageKey);
      if (val2) return val2;
    } catch (e) {}
    try {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var c = cookies[i];
        while (c.charAt(0) === ' ') c = c.substring(1);
        if (c.indexOf(storageKey + '=') === 0) {
          return decodeURIComponent(c.substring(storageKey.length + 1));
        }
      }
    } catch (e) {}
    return '';
  }

  function setFieldValue(input, value) {
    if (!input || !value) return;
    if (input.value === value) return;
    input.value = value;
    input.setAttribute('value', value);
    try {
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {}
  }

  function fillFieldsFromStorage(scope) {
    var root = scope && scope.querySelectorAll ? scope : document;
    for (var i = 0; i < TRACKING_FIELDS.length; i++) {
      var field = TRACKING_FIELDS[i];
      var value = getFromStorage(field.key);
      if (!value) continue;

      var el = root.getElementById ? root.getElementById(field.id) : document.getElementById(field.id);
      if (el) setFieldValue(el, value);

      var byName = root.querySelectorAll('input[name="' + field.name + '"]');
      for (var n = 0; n < byName.length; n++) {
        setFieldValue(byName[n], value);
      }

      var byCls = root.querySelectorAll('input.' + field.cls);
      for (var c = 0; c < byCls.length; c++) {
        setFieldValue(byCls[c], value);
      }
    }
  }

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
    var inputs = scope.querySelectorAll('input[type="hidden"][class*="js-rt-"], input[type="hidden"][id^="rt-"], input[type="hidden"][name^="rt_"]');

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
        fillFieldsFromStorage(e.target);
        syncRtValues(e.target);
      },
      true
    );

    document.addEventListener(
      'click',
      function (e) {
        var el = e && e.target;
        if (!el) return;
        if (el.type !== 'submit' && !(el.tagName === 'BUTTON' && el.closest && el.closest('form'))) return;
        var form = el.closest ? el.closest('form') : null;
        if (form) {
          fillFieldsFromStorage(form);
          syncRtValues(form);
        }
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

    fillFieldsFromStorage(scope);
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

    var fills = 0;
    var interval = setInterval(function () {
      fillFieldsFromStorage(document);
      fills++;
      if (fills >= 10) clearInterval(interval);
    }, 500);
  });
})();
