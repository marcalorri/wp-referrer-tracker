(function () {
  var STORAGE_PREFIX = 'rt_';

  var TRACKING_FIELDS = [
    { key: 'source',       ids: ['rt-source',      'rt_source'],       name: 'rt_source',       cls: 'js-rt-source' },
    { key: 'medium',       ids: ['rt-medium',      'rt_medium'],       name: 'rt_medium',       cls: 'js-rt-medium' },
    { key: 'campaign',     ids: ['rt-campaign',    'rt_campaign'],     name: 'rt_campaign',     cls: 'js-rt-campaign' },
    { key: 'content',      ids: ['rt-content',     'rt_content'],      name: 'rt_content',      cls: 'js-rt-content' },
    { key: 'term',         ids: ['rt-term',        'rt_term'],         name: 'rt_term',         cls: 'js-rt-term' },
    { key: 'referrer',     ids: ['rt-referrer',    'rt_referrer'],     name: 'rt_referrer',     cls: 'js-rt-referrer' },
    { key: 'landing_page', ids: ['rt-landing-page','rt_landing_page'], name: 'rt_landing_page', cls: 'js-rt-landing-page' },
    { key: 'gclid',        ids: ['rt-gclid',       'rt_gclid'],        name: 'rt_gclid',        cls: 'js-rt-gclid' },
    { key: 'fbclid',       ids: ['rt-fbclid',      'rt_fbclid'],       name: 'rt_fbclid',       cls: 'js-rt-fbclid' },
    { key: 'msclkid',      ids: ['rt-msclkid',     'rt_msclkid'],      name: 'rt_msclkid',      cls: 'js-rt-msclkid' },
    { key: 'ttclid',       ids: ['rt-ttclid',      'rt_ttclid'],       name: 'rt_ttclid',       cls: 'js-rt-ttclid' },
    { key: 'li_fat_id',    ids: ['rt-li-fat-id',   'rt_li_fat_id'],    name: 'rt_li_fat_id',    cls: 'js-rt-li-fat-id' },
    { key: 'twclid',       ids: ['rt-twclid',      'rt_twclid'],       name: 'rt_twclid',       cls: 'js-rt-twclid' },
    { key: 'epik',         ids: ['rt-epik',        'rt_epik'],         name: 'rt_epik',         cls: 'js-rt-epik' },
    { key: 'rdt_cid',      ids: ['rt-rdt-cid',     'rt_rdt_cid'],      name: 'rt_rdt_cid',      cls: 'js-rt-rdt-cid' }
  ];

  function parseLocalStorageValue(raw) {
    if (!raw) return '';
    try {
      var parsed = JSON.parse(raw);
      if (parsed && typeof parsed === 'object' && 'v' in parsed) {
        if (parsed.e && parsed.e > 0 && Date.now() > parsed.e) {
          return null;
        }
        return parsed.v || '';
      }
      return raw;
    } catch (e) {
      return raw;
    }
  }

  function getFromStorage(key) {
    var storageKey = STORAGE_PREFIX + key;
    try {
      var raw = localStorage.getItem(storageKey);
      if (raw) {
        var val = parseLocalStorageValue(raw);
        if (val === null) {
          localStorage.removeItem(storageKey);
        } else if (val) {
          return val;
        }
      }
    } catch (e) {}
    try {
      var val2 = sessionStorage.getItem(storageKey);
      if (val2) return val2;
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

      for (var j = 0; j < field.ids.length; j++) {
        var el = root.getElementById ? root.getElementById(field.ids[j]) : document.getElementById(field.ids[j]);
        if (el) setFieldValue(el, value);
      }

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
      if (fills >= 20) clearInterval(interval);
    }, 500);
  });
})();
