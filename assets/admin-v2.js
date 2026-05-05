/* Layrix v2 UI — standalone vanilla JS for the v2 preview interface */
(function() {
  'use strict';

  var _autosaveTimer = null;

  function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  var _wrapEl = null;
  function wrap() { return _wrapEl || (_wrapEl = document.getElementById('ecf-v2-wrapper')); }

  /* ── Navigation ──────────────────────────────────────────────────────── */
  function ecfV2Go(id) {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('.v2-page').forEach(function(p) { p.classList.remove('v2-page--on'); });
    w.querySelectorAll('.v2-ni').forEach(function(n) { n.classList.remove('active'); });
    var page = w.querySelector('#ecf-v2-page-' + id);
    if (page) page.classList.add('v2-page--on');
    w.querySelectorAll('.v2-ni[data-v2-page="' + id + '"]').forEach(function(n) { n.classList.add('active'); });
    try { localStorage.setItem('ecf_v2_page', id); } catch(e) {}
  }
  window.ecfV2Go = ecfV2Go;

  /* ── Tabs ────────────────────────────────────────────────────────────── */
  function ecfV2Tab(group, id, btn) {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('[id^="v2-' + group + '-"]').forEach(function(p) { p.classList.remove('v2-tp--on'); });
    var el = w.querySelector('#v2-' + group + '-' + id);
    if (el) el.classList.add('v2-tp--on');
    btn.closest('.v2-tabs').querySelectorAll('.v2-tab').forEach(function(t) { t.classList.remove('v2-tab--on'); });
    btn.classList.add('v2-tab--on');
    // Scale-Parameter-Aside nur beim Skala-Tab anzeigen
    if (group === 'ty') {
      var aside = w.querySelector('#v2-ty-scale-aside');
      if (aside) aside.style.display = id === 'scale' ? '' : 'none';
    }
  }
  window.ecfV2Tab = ecfV2Tab;

  /* ── Color edit toggle ───────────────────────────────────────────────── */
  function ecfV2ToggleEdit(id) {
    var w = wrap();
    if (!w) return;
    var el = w.querySelector('#v2-edit-' + id);
    if (!el) return;
    var isOpen = el.classList.contains('v2-tr-edit--open');
    w.querySelectorAll('.v2-tr-edit').forEach(function(e) { e.classList.remove('v2-tr-edit--open'); });
    w.querySelectorAll('.v2-tr').forEach(function(t) { t.classList.remove('v2-tr--active'); });
    if (!isOpen) {
      el.classList.add('v2-tr-edit--open');
      var tr = w.querySelector('#v2-tr-' + id);
      if (tr) tr.classList.add('v2-tr--active');
      ecfV2UpdateAside(id);
    }
  }
  window.ecfV2ToggleEdit = ecfV2ToggleEdit;

  /* ── Live color from color picker ────────────────────────────────────── */
  function ecfV2LiveColor(id, val) {
    var w = wrap();
    if (!w) return;
    var sw     = w.querySelector('#v2-sw-'   + id);
    var esw    = w.querySelector('#v2-esw-'  + id);
    var inp    = w.querySelector('#v2-einp-' + id);
    var hex    = w.querySelector('#v2-hex-'  + id);
    var hidden = w.querySelector('#v2-val-'  + id);
    if (sw)     sw.style.background  = val;
    if (esw)    esw.style.background = val;
    if (inp)    inp.value = val;
    if (hex)    hex.textContent = val;
    if (hidden) hidden.value = val;
    ecfV2UpdateAside(id, val);
    try { ecfV2UpdateShadeStrip(id); } catch(ex) {}
  }
  window.ecfV2LiveColor = ecfV2LiveColor;

  /* ── Live color from hex input ───────────────────────────────────────── */
  function ecfV2LiveHex(id, val) {
    if (!/^#[0-9a-f]{6}$/i.test(val)) return;
    var w = wrap();
    if (!w) return;
    var sw     = w.querySelector('#v2-sw-'  + id);
    var esw    = w.querySelector('#v2-esw-' + id);
    var cp     = w.querySelector('#v2-cp-'  + id);
    var hidden = w.querySelector('#v2-val-' + id);
    if (sw)     sw.style.background  = val;
    if (esw)    esw.style.background = val;
    if (cp)     cp.value = val;
    if (hidden) hidden.value = val;
    ecfV2UpdateAside(id, val);
    try { ecfV2UpdateShadeStrip(id); } catch(ex) {}
  }
  window.ecfV2LiveHex = ecfV2LiveHex;

  /* ── Apply color from edit panel: write to hidden input + close ───────── */
  function ecfV2ApplyColor(id) {
    var w = wrap();
    if (!w) return;
    var picker = w.querySelector('#v2-cp-'    + id);
    var hexInp = w.querySelector('#v2-einp-'  + id);
    var hidden = w.querySelector('#v2-val-'   + id);
    var swatch = w.querySelector('#v2-sw-'    + id);
    var editSw = w.querySelector('#v2-esw-'   + id);
    var hexDsp = w.querySelector('#v2-hex-'   + id);

    var val = (hexInp && /^#[0-9a-f]{6}$/i.test(hexInp.value))
      ? hexInp.value
      : (picker ? picker.value : null);
    if (!val) return;

    if (swatch) swatch.style.background  = val;
    if (editSw) editSw.style.background  = val;
    if (hexDsp) hexDsp.textContent = val;
    if (picker) picker.value = val;
    if (hexInp) hexInp.value  = val;
    if (hidden) hidden.value  = val;

    ecfV2UpdateAside(id, val);
    ecfV2ToggleEdit(id);
    ecfV2ScheduleAutosave();
  }
  window.ecfV2ApplyColor = ecfV2ApplyColor;

  /* ── Save color (kept for back-compat, now delegates to Apply) ────────── */
  function ecfV2SaveColor(id) {
    ecfV2ApplyColor(id);
  }
  window.ecfV2SaveColor = ecfV2SaveColor;

  /* ── Update aside panel for selected color ───────────────────────────── */
  function ecfV2UpdateAside(id, val) {
    var w = wrap();
    if (!w) return;
    var sw    = w.querySelector('#v2-sw-' + id);
    var color = val || (sw ? sw.style.background : null);
    if (!color) return;
    var main  = w.querySelector('#v2-cp-main');
    var label = w.querySelector('#v2-cp-label');
    if (main)  { main.style.background = color; main.dataset.activeId = id; }
    if (label) {
      var _cl = (window.ecfAdmin && ecfAdmin.colorLabels) || {};
      label.textContent = _cl[id] || id;
    }
  }
  window.ecfV2UpdateAside = ecfV2UpdateAside;

  /* ── Shadow picker ───────────────────────────────────────────────────── */
  function ecfV2PickShadow(name, css, previewCss) {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('.v2-sh-row').forEach(function(r) { r.classList.remove('v2-sh-row--active'); });
    var target = event && event.currentTarget;
    if (target) target.classList.add('v2-sh-row--active');
    var fn = w.querySelector('#v2-sh-fname');
    var ft = w.querySelector('#v2-sh-ftoken');
    var fc = w.querySelector('#v2-sh-fcss');
    var ff = w.querySelector('#v2-sh-focus-card');
    if (fn) fn.textContent = name;
    if (ft) ft.textContent = '--ecf-shadow-' + name;
    if (fc) fc.textContent = css;
    if (ff) ff.style.boxShadow = previewCss;
    /* Highlight active utility row */
    w.querySelectorAll('.v2-sh-util-row').forEach(function(r) { r.classList.remove('v2-sh-util-row--active'); });
    var utilRow = w.querySelector('.v2-sh-util-row[data-sh-name="' + name + '"]');
    if (utilRow) utilRow.classList.add('v2-sh-util-row--active');
  }
  window.ecfV2PickShadow = ecfV2PickShadow;

  /* ── Copy shadow CSS token to clipboard ─────────────────────────────── */
  function ecfV2CopyShadowCSS(id) {
    var w = wrap();
    if (!w) return;
    var inp = w.querySelector('#v2-shval-' + id);
    if (!inp) return;
    var css = '--ecf-shadow-' + id + ': ' + inp.value + ';';
    if (navigator.clipboard) {
      navigator.clipboard.writeText(css).then(function() {
        ecfV2Toast('CSS kopiert', 'success');
      }).catch(function() {
        ecfV2Toast('Kopieren fehlgeschlagen', 'info');
      });
    } else {
      /* Fallback for older browsers */
      var ta = document.createElement('textarea');
      ta.value = css;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); ecfV2Toast('CSS kopiert', 'success'); }
      catch(e) { ecfV2Toast('Kopieren fehlgeschlagen', 'info'); }
      document.body.removeChild(ta);
    }
  }
  window.ecfV2CopyShadowCSS = ecfV2CopyShadowCSS;

  /* ── Copy arbitrary text to clipboard ───────────────────────────────── */
  function ecfV2CopyText(text) {
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function() {
        ecfV2Toast(i18n.copied || 'Kopiert', 'success');
      }).catch(function() {
        ecfV2Toast(i18n.copy_failed || 'Kopieren fehlgeschlagen', 'info');
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); ecfV2Toast(i18n.copied || 'Kopiert', 'success'); }
      catch(e) { ecfV2Toast(i18n.copy_failed || 'Kopieren fehlgeschlagen', 'info'); }
      document.body.removeChild(ta);
    }
  }
  window.ecfV2CopyText = ecfV2CopyText;

  /* ── Toast ───────────────────────────────────────────────────────────── */
  function ecfV2Toast(msg, type) {
    var el = document.getElementById('ecf-v2-toast');
    if (!el) return;
    el.textContent = (type === 'success' ? '✓ ' : 'ℹ ') + msg;
    el.className = 'v2-toast v2-toast--' + (type || 'info') + ' v2-toast--show';
    clearTimeout(el._ecfT);
    el._ecfT = setTimeout(function() { el.classList.remove('v2-toast--show'); }, 3000);
  }
  window.ecfV2Toast = ecfV2Toast;

  function ecfV2AutoSyncPrompt() {
    var existing = document.getElementById('ecf-v2-autosync-prompt');
    if (existing) return; // already visible
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var bar = document.createElement('div');
    bar.id = 'ecf-v2-autosync-prompt';
    bar.className = 'v2-autosync-prompt';
    bar.innerHTML =
      '<span class="v2-autosync-prompt__msg">' + escapeHtml(i18n.autosync_prompt_msg || '') + '</span>' +
      '<button type="button" class="v2-autosync-prompt__yes">' + escapeHtml(i18n.yes || '') + '</button>' +
      '<button type="button" class="v2-autosync-prompt__no">' + escapeHtml(i18n.no || '') + '</button>';
    document.body.appendChild(bar);
    setTimeout(function() { bar.classList.add('is-visible'); }, 10);
    bar.querySelector('.v2-autosync-prompt__yes').addEventListener('click', function() {
      /* Alle Auto-Sync-Checkboxen im DOM aktivieren (es gibt zwei) */
      document.querySelectorAll('input[name*="[elementor_auto_sync_enabled]"]').forEach(function(cb) {
        cb.checked = true;
        var tog = cb.nextElementSibling;
        if (tog) { tog.classList.remove('v2-tog--off'); tog.classList.add('v2-tog--on'); }
      });
      /* ecfAdmin-Flag sofort setzen damit kein erneuter Prompt folgt */
      if (window.ecfAdmin) ecfAdmin.elementorAutoSync = true;
      ecfV2Save && ecfV2Save(false);
      ecfV2Toast && ecfV2Toast(i18n.autosync_enabled_success || '', 'success');
      bar.remove();
    });
    bar.querySelector('.v2-autosync-prompt__no').addEventListener('click', function() {
      bar.remove();
    });
  }
  window.ecfV2AutoSyncPrompt = ecfV2AutoSyncPrompt;

  /* ── Collect form data → settings object ────────────────────────────── */
  function ecfV2CollectData() {
    var form = document.getElementById('ecf-v2-form');
    if (!form) return null;

    var root = {};

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(el) {
      if (el.disabled) return;
      if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;

      /* PHP bracket notation → key array */
      /* e.g. "ecf_framework_v50[colors][0][value]" → ["ecf_framework_v50","colors","0","value"] */
      var keys = el.name.replace(/\]/g, '').split('[');
      if (!keys.length) return;

      var cur = root;
      for (var i = 0; i < keys.length - 1; i++) {
        var k     = keys[i];
        var nextK = keys[i + 1];
        var nextIsNum = /^\d+$/.test(nextK);

        if (nextK === '') {
          /* PHP append-array notation name="...[]" — ensure slot is an array */
          if (!Array.isArray(cur[k])) cur[k] = [];
        } else if (cur[k] === undefined || cur[k] === null) {
          cur[k] = nextIsNum ? [] : {};
        }
        /* If navigating into an array and the next slot is an object placeholder */
        if (Array.isArray(cur[k]) && nextIsNum) {
          var idx = parseInt(nextK, 10);
          /* peek ahead: if the key after nextK exists and is non-numeric, the slot is an object */
          var afterNext = keys[i + 2];
          if (cur[k][idx] === undefined) {
            cur[k][idx] = (afterNext && !/^\d+$/.test(afterNext)) ? {} : '';
          }
        }
        cur = cur[k];
      }

      var lastK = keys[keys.length - 1];
      if (lastK === '') {
        /* PHP append-array: push instead of overwrite */
        if (Array.isArray(cur)) cur.push(el.value);
      } else {
        cur[lastK] = el.value;
      }
    });

    /* Strip the outer option-name wrapper (e.g. "ecf_framework_v50").
       The form may also contain WP meta fields (_wpnonce, action, etc.)
       so prefer the ecf_framework* key over a simple single-key check. */
    var topKeys = Object.keys(root);
    var settingsKey = topKeys.find(function(k) { return /^ecf_framework/.test(k); });
    if (settingsKey) return root[settingsKey];
    if (topKeys.length === 1) return root[topKeys[0]];
    return root;
  }
  window.ecfV2CollectData = ecfV2CollectData;

  /* ── Save via REST API ───────────────────────────────────────────────── */
  /* ── Typography Scale Live Preview ──────────────────────────────────── */
  function _getScaleParams() {
    return {
      minBase:  parseFloat((document.getElementById('v2-sp-min-base')  || {}).value) || 16,
      maxBase:  parseFloat((document.getElementById('v2-sp-max-base')  || {}).value) || 18,
      minRatio: parseFloat((document.getElementById('v2-sp-min-ratio') || {}).value) || 1.125,
      maxRatio: parseFloat((document.getElementById('v2-sp-max-ratio') || {}).value) || 1.25,
    };
  }

  function ecfV2UpdateScalePreview() {
    var container = document.getElementById('v2-scale-preview-tl');
    if (!container) return;

    var p = _getScaleParams();
    var minBase = p.minBase, maxBase = p.maxBase, minRatio = p.minRatio, maxRatio = p.maxRatio;

    var baseIndex = container.dataset.scaleBaseIndex || 'm';
    var stepsWrap = document.getElementById('v2-ty-steps-wrap');
    var stepInputs = stepsWrap ? stepsWrap.querySelectorAll('.v2-step-input') : [];
    var steps = [];
    stepInputs.forEach(function(inp) { if (inp.value) steps.push(inp.value); });
    if (!steps.length) steps = ['xs','s','m','l','xl','2xl','3xl','4xl'];

    var baseIdx = steps.indexOf(baseIndex);
    if (baseIdx < 0) baseIdx = Math.floor(steps.length / 2);

    var isDual = (minBase !== maxBase || minRatio !== maxRatio);
    var html = '';
    steps.forEach(function(step, i) {
      var exp    = i - baseIdx;
      var minPx  = Math.round(minBase * Math.pow(minRatio, exp) * 10) / 10;
      var maxPx  = Math.round(maxBase * Math.pow(maxRatio, exp) * 10) / 10;
      var isBase = (i === baseIdx);
      var sampleMin = minPx >= 28 ? 'The quick fox' : 'The quick fox jumps over the fence';
      var sampleMax = maxPx >= 28 ? 'The quick fox' : 'The quick fox jumps over the fence';
      html += '<div class="v2-ty-row' + (isBase ? ' v2-ty-row--active' : '') + (isDual ? ' v2-ty-row--dual' : '') + '">'
        + '<span class="v2-ty-step">' + step + '</span>'
        + '<span class="v2-ty-samples">'
        +   '<span class="v2-ty-sample v2-ty-sample--min" style="font-size:' + Math.min(minPx, 48) + 'px" title="Min ' + minPx.toFixed(1) + 'px">' + sampleMin + '</span>'
        + (isDual
          ? '<span class="v2-ty-sample v2-ty-sample--max" style="font-size:' + Math.min(maxPx, 48) + 'px" title="Max ' + maxPx.toFixed(1) + 'px">' + sampleMax + '</span>'
          : '')
        + '</span>'
        + '<span class="v2-ty-range">'
        + (isDual ? minPx.toFixed(0) + 'px<br>' + maxPx.toFixed(0) : minPx.toFixed(0) + ' / ' + maxPx.toFixed(0)) + 'px'
        + '</span>'
        + '</div>';
    });
    container.innerHTML = html;
    ecfV2UpdateScaleOverview();
  }
  window.ecfV2UpdateScalePreview = ecfV2UpdateScalePreview;

  function ecfV2UpdateScaleOverview() {
    var block = document.getElementById('v2-ty-scale-overview');
    if (!block) return;

    var p = _getScaleParams();
    var minBase = p.minBase, maxBase = p.maxBase, minRatio = p.minRatio, maxRatio = p.maxRatio;
    var isDual  = (minBase !== maxBase || minRatio !== maxRatio);

    var container = document.getElementById('v2-scale-preview-tl');
    var baseIndex = (container && container.dataset.scaleBaseIndex) || 'm';

    var stepsWrap = document.getElementById('v2-ty-steps-wrap');
    var steps = [];
    if (stepsWrap) {
      stepsWrap.querySelectorAll('.v2-step-input').forEach(function(inp) {
        if (inp.value) steps.push(inp.value);
      });
    }
    if (!steps.length) steps = ['xs','s','m','l','xl','2xl','3xl','4xl'];

    var baseIdx = steps.indexOf(baseIndex);
    if (baseIdx < 0) baseIdx = Math.floor(steps.length / 2);

    var minPxValues = steps.map(function(s, i) { return Math.round(minBase * Math.pow(minRatio, i - baseIdx)); });
    var maxPxValues = steps.map(function(s, i) { return Math.round(maxBase * Math.pow(maxRatio, i - baseIdx)); });
    var absMax = Math.max.apply(null, maxPxValues) || 1;

    var head = block.querySelector('.v2-as-head');
    var headHtml = head ? head.outerHTML : '<div class="v2-as-head">Scale Overview</div>';
    var rows = steps.map(function(step, i) {
      var pct  = Math.round(maxPxValues[i] / absMax * 100);
      var label = isDual ? minPxValues[i] + '→' + maxPxValues[i] + 'px' : minPxValues[i] + 'px';
      return '<div class="v2-sc-row">'
        + '<span class="v2-sc-lbl">' + step + '</span>'
        + '<div class="v2-sc-track"><div class="v2-sc-fill" style="width:' + pct + '%"></div></div>'
        + '<span class="v2-sc-px">' + label + '</span>'
        + '</div>';
    }).join('');
    block.innerHTML = headHtml + rows;
  }
  window.ecfV2UpdateScaleOverview = ecfV2UpdateScaleOverview;

  /* ── Spacing Live Preview (bars) ─────────────────────────────────────── */
  function ecfV2UpdateSpacingPreview() {
    var container = document.getElementById('v2-sp-preview-list');
    if (!container) return;

    var minBase  = parseFloat((document.getElementById('v2-spp-min-base')  || {}).value) || 16;
    var maxBase  = parseFloat((document.getElementById('v2-spp-max-base')  || {}).value) || 28;
    var minRatio = parseFloat((document.getElementById('v2-spp-min-ratio') || {}).value) || 1.25;
    var maxRatio = parseFloat((document.getElementById('v2-spp-max-ratio') || {}).value) || 1.414;

    var baseIndex = container.dataset.spBaseIndex || 'm';
    var stepsWrap = document.getElementById('v2-sp-steps-wrap');
    var stepInputs = stepsWrap ? stepsWrap.querySelectorAll('.v2-step-input') : [];
    var steps = [];
    stepInputs.forEach(function(inp) { if (inp.value) steps.push(inp.value); });
    if (!steps.length) steps = ['3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl'];

    var baseIdx = steps.indexOf(baseIndex);
    if (baseIdx < 0) baseIdx = Math.floor(steps.length / 2);

    /* Find max value for scaling the bars */
    var maxVal = 0;
    steps.forEach(function(s, i) {
      var exp = i - baseIdx;
      var mx = maxBase * Math.pow(maxRatio, exp);
      if (mx > maxVal) maxVal = mx;
    });

    var html = '';
    steps.forEach(function(step, i) {
      var exp   = i - baseIdx;
      var minPx = Math.round(minBase * Math.pow(minRatio, exp));
      var maxPx = Math.round(maxBase * Math.pow(maxRatio, exp));
      var isBase = (i === baseIdx);
      html += '<div class="v2-sp-row' + (isBase ? ' v2-sp-row--active' : '') + '">'
        + '<span class="v2-sp-lbl">' + step + '</span>'
        + '<div class="v2-sp-bars">'
        +   '<div class="v2-sp-bar"><div class="v2-sp-fill" style="width:' + minPx + 'px"></div></div>'
        +   '<div class="v2-sp-bar v2-sp-bar--max"><div class="v2-sp-fill" style="width:' + maxPx + 'px"></div></div>'
        + '</div>'
        + '<span class="v2-sp-px">' + minPx + ' / ' + maxPx + 'px</span>'
        + '</div>';
    });
    container.innerHTML = html;

    var head = document.getElementById('v2-sp-preview-head');
    if (head) head.textContent = 'Scale \u00b7 ' + minBase + '/' + maxBase + 'px \u00b7 Ratio ' + minRatio + '/' + maxRatio;

    ecfV2UpdateSpacingOverview();
  }
  window.ecfV2UpdateSpacingPreview = ecfV2UpdateSpacingPreview;

  function ecfV2UpdateSpacingOverview() {
    var block = document.getElementById('v2-sp-scale-overview');
    if (!block) return;

    var minBase  = parseFloat((document.getElementById('v2-spp-min-base')  || {}).value) || 16;
    var maxBase  = parseFloat((document.getElementById('v2-spp-max-base')  || {}).value) || 28;
    var minRatio = parseFloat((document.getElementById('v2-spp-min-ratio') || {}).value) || 1.25;
    var maxRatio = parseFloat((document.getElementById('v2-spp-max-ratio') || {}).value) || 1.414;
    var isDual   = (minBase !== maxBase || minRatio !== maxRatio);

    var container = document.getElementById('v2-sp-preview-list');
    var baseIndex = (container && container.dataset.spBaseIndex) || 'm';

    var stepsWrap = document.getElementById('v2-sp-steps-wrap');
    var steps = [];
    if (stepsWrap) {
      stepsWrap.querySelectorAll('.v2-step-input').forEach(function(inp) {
        if (inp.value) steps.push(inp.value);
      });
    }
    if (!steps.length) steps = ['3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl'];

    var baseIdx = steps.indexOf(baseIndex);
    if (baseIdx < 0) baseIdx = Math.floor(steps.length / 2);

    var minPxValues = steps.map(function(s, i) { return Math.max(1, Math.round(minBase * Math.pow(minRatio, i - baseIdx))); });
    var maxPxValues = steps.map(function(s, i) { return Math.max(1, Math.round(maxBase * Math.pow(maxRatio, i - baseIdx))); });
    var absMax = Math.max.apply(null, maxPxValues) || 1;

    var head = block.querySelector('.v2-as-head');
    var headHtml = head ? head.outerHTML : '<div class="v2-as-head">Scale Overview</div>';
    var rows = steps.map(function(step, i) {
      var pct   = Math.round(maxPxValues[i] / absMax * 100);
      var label = isDual ? minPxValues[i] + '→' + maxPxValues[i] + 'px' : minPxValues[i] + 'px';
      return '<div class="v2-sc-row">'
        + '<span class="v2-sc-lbl">' + step + '</span>'
        + '<div class="v2-sc-track"><div class="v2-sc-fill" style="width:' + pct + '%"></div></div>'
        + '<span class="v2-sc-px">' + label + '</span>'
        + '</div>';
    }).join('');
    block.innerHTML = headHtml + rows;
  }
  window.ecfV2UpdateSpacingOverview = ecfV2UpdateSpacingOverview;

  /* ── Root Font Impact Preview ────────────────────────────────────────── */
  function ecfV2UpdateRootFontImpact() {
    var list = document.getElementById('v2-rfi-list');
    if (!list) return;
    var sel = document.getElementById('v2-root-font-sel');
    var rootPct = sel ? parseFloat(sel.value) : 62.5;
    var basePx  = rootPct >= 99 ? 16 : 10; /* 100% = 16px, 62.5% = 10px */
    var remVals = [0.5, 1, 1.25, 1.5, 1.75, 2, 2.5, 3, 4];
    var html = '';
    remVals.forEach(function(r) {
      var px = Math.round(r * basePx * 10) / 10;
      html += '<div class="v2-as-row">'
        + '<span class="v2-as-k" style="font-family:var(--v2-mono);font-size:var(--v2-ui-base-fs, 13px)">' + r + 'rem</span>'
        + '<span class="v2-as-v" style="font-family:var(--v2-mono)">' + px + 'px</span>'
        + '</div>';
    });
    list.innerHTML = html;
  }
  window.ecfV2UpdateRootFontImpact = ecfV2UpdateRootFontImpact;

  /* ── Custom confirm dialog (replaces browser confirm()) ─────────────── */
  function ecfV2Confirm(message, opts) {
    return new Promise(function(resolve) {
      var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
      var isDanger = opts && opts.danger;
      var confirmLabel = (opts && opts.confirmLabel) || i18n.confirm_ok || 'OK';
      var cancelLabel  = (opts && opts.cancelLabel)  || i18n.confirm_cancel || 'Abbrechen';
      var el = document.createElement('div');
      el.innerHTML = '<div class="v2-confirm-overlay">'
        + '<div class="v2-confirm-box">'
        + '<div class="v2-confirm-msg"></div>'
        + '<div class="v2-confirm-actions">'
        + '<button type="button" class="v2-btn v2-btn--ghost v2-confirm-cancel">' + escapeHtml(cancelLabel) + '</button>'
        + '<button type="button" class="v2-btn ' + (isDanger ? 'v2-btn--danger' : 'v2-btn--primary') + ' v2-confirm-ok">' + escapeHtml(confirmLabel) + '</button>'
        + '</div></div></div>';
      var overlay = el.firstChild;
      overlay.querySelector('.v2-confirm-msg').textContent = message;
      document.body.appendChild(overlay);
      function close(result) {
        overlay.remove();
        resolve(result);
      }
      overlay.querySelector('.v2-confirm-ok').addEventListener('click', function() { close(true); });
      overlay.querySelector('.v2-confirm-cancel').addEventListener('click', function() { close(false); });
      overlay.addEventListener('click', function(e) { if (e.target === overlay) close(false); });
      overlay.querySelector('.v2-confirm-ok').focus();
    });
  }
  window.ecfV2Confirm = ecfV2Confirm;

  var PAGE_TO_SECTION = {
    colors: 'colors', radius: 'radius', typography: 'typography',
    spacing: 'spacing', shadows: 'shadows', variables: 'typography',
    settings: 'general', classes: 'utility_classes'
  };

  function ecfV2ResetModal(triggerBtn) {
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var activePage = (localStorage.getItem('ecf_v2_page') || '').toLowerCase();
    var preselected = PAGE_TO_SECTION[activePage] || null;

    var sections = [
      { id: 'colors',          label: i18n.reset_section_colors     || 'Farben' },
      { id: 'radius',          label: i18n.reset_section_radius     || 'Radius' },
      { id: 'typography',      label: i18n.reset_section_typography || 'Typografie' },
      { id: 'spacing',         label: i18n.reset_section_spacing    || 'Abstände' },
      { id: 'shadows',         label: i18n.reset_section_shadows    || 'Schatten' },
      { id: 'general',         label: i18n.reset_section_general    || 'Allgemeine Einstellungen' },
      { id: 'utility_classes', label: i18n.reset_section_classes    || 'Utility-Klassen' },
    ];

    var checkboxRows = sections.map(function(s) {
      var checked = (s.id === preselected) ? ' checked' : '';
      return '<label class="v2-reset-row">'
        + '<input type="checkbox" class="v2-reset-cb" value="' + s.id + '"' + checked + '>'
        + '<span>' + escapeHtml(s.label) + '</span>'
        + '</label>';
    }).join('');

    var title   = i18n.reset_modal_title   || 'Auf Grundeinstellungen zurücksetzen';
    var desc    = i18n.reset_modal_desc    || 'Wähle, welche Bereiche zurückgesetzt werden sollen:';
    var confirm = i18n.reset_modal_confirm || 'Auswahl zurücksetzen';
    var cancel  = i18n.confirm_cancel      || 'Abbrechen';

    var overlay = document.createElement('div');
    overlay.className = 'v2-modal-overlay v2-reset-overlay';
    overlay.innerHTML = '<div class="v2-modal-box v2-reset-modal">'
      + '<div class="v2-modal-head"><span>' + escapeHtml(title) + '</span>'
      + '<button type="button" class="v2-modal-close" aria-label="' + escapeHtml(cancel) + '">&#x2715;</button></div>'
      + '<div class="v2-modal-body">'
      + '<p class="v2-reset-desc">' + escapeHtml(desc) + '</p>'
      + '<div class="v2-reset-list">' + checkboxRows + '</div>'
      + '</div>'
      + '<div class="v2-modal-foot">'
      + '<button type="button" class="v2-btn v2-btn--ghost v2-reset-cancel">' + escapeHtml(cancel) + '</button>'
      + '<button type="button" class="v2-btn v2-btn--danger v2-reset-confirm">' + escapeHtml(confirm) + '</button>'
      + '</div></div>';

    document.body.appendChild(overlay);

    function closeModal() { overlay.remove(); }

    overlay.querySelector('.v2-modal-close').addEventListener('click', closeModal);
    overlay.querySelector('.v2-reset-cancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });

    overlay.querySelector('.v2-reset-confirm').addEventListener('click', function() {
      var checked = Array.from(overlay.querySelectorAll('.v2-reset-cb:checked')).map(function(cb) { return cb.value; });
      if (!checked.length) {
        var noneMsg = i18n.reset_modal_none || 'Bitte mindestens einen Bereich auswählen.';
        overlay.querySelector('.v2-reset-list').insertAdjacentHTML('beforebegin',
          '<p class="v2-reset-error">' + escapeHtml(noneMsg) + '</p>');
        return;
      }
      if (!window.ecfAdmin || !ecfAdmin.resetDefaultsUrl) return;
      closeModal();
      if (triggerBtn) triggerBtn.disabled = true;
      _setPill('saving', i18n.reset_defaults_running || '');
      fetch(ecfAdmin.resetDefaultsUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
        body: JSON.stringify({ sections: checked }),
      })
      .then(function(r) { return r.json(); })
      .then(function(data) { if (data && data.success) return ecfV2Sync(); })
      .then(function() { window.location.reload(); })
      .catch(function() {
        _setPill('error', i18n.network_error || '');
        if (triggerBtn) triggerBtn.disabled = false;
      });
    });
  }
  window.ecfV2ResetModal = ecfV2ResetModal;

  var _savedHideTimer = null;
  function _setPill(state, text) {
    var pill = document.getElementById('v2-autosave-pill');
    if (!pill) return;
    pill.className = 'v2-autosave-pill v2-autosave-pill--' + state;
    pill.textContent = text;
    clearTimeout(_savedHideTimer);
    if (state === 'saved') {
      var ls = document.getElementById('v2-last-saved');
      if (ls) {
        var now = new Date();
        var hh = now.getHours().toString().padStart(2, '0');
        var mm = now.getMinutes().toString().padStart(2, '0');
        var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
        ls.textContent = (i18n.last_saved_at || 'Gespeichert') + ' ' + hh + ':' + mm;
        ls.className = 'v2-last-saved';
      }
      _savedHideTimer = setTimeout(function() {
        pill.className = 'v2-autosave-pill v2-autosave-pill--hidden';
      }, 2500);
    }
  }

  function _checkDuplicateNames(type, selector) {
    var w = wrap();
    if (!w) return true;
    var inputs = w.querySelectorAll(selector);
    var seen = {};
    var dupes = [];
    inputs.forEach(function(inp) {
      var val = inp.value.trim();
      if (!val) return;
      if (seen[val]) { dupes.push(val); inp.classList.add('v2-si--error'); }
      else { seen[val] = true; inp.classList.remove('v2-si--error'); }
    });
    if (dupes.length) {
      ecfV2Toast('Doppelter ' + type + '-Name: ' + dupes[0], 'error');
      return false;
    }
    return true;
  }

  function ecfV2Save(silent) {
    if (!window.ecfAdmin || !ecfAdmin.restUrl) {
      ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.rest_unavailable) || 'REST API not available', 'info');
      return;
    }

    if (!_checkDuplicateNames('Farb-Token', '[data-v2-tl-color] input[type="text"][name*="[colors]"][name*="[name]"]')) return;
    if (!_checkDuplicateNames('Radius-Token', '[data-v2-tl-radius] input[type="text"][name*="[radius]"][name*="[name]"]')) return;

    var settings = ecfV2CollectData();
    if (!settings) return;

    var _si18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    _setPill('saving', _si18n.autosave_saving || '');

    /* Disable + label all save buttons */
    var saveButtons = (wrap() || document).querySelectorAll('[data-v2-save]');
    var origLabels = [];
    saveButtons.forEach(function(b, i) {
      origLabels[i] = b.textContent;
      b.disabled = true;
      b.textContent = _si18n.autosave_saving || '';
    });

    fetch(ecfAdmin.restUrl, {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce':   ecfAdmin.restNonce,
      },
      body: JSON.stringify({ settings: settings }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.success) {
        _setPill('saved', _si18n.autosave_saved || '');
        if (window.ecfAdmin && !ecfAdmin.elementorAutoSync) {
          ecfV2AutoSyncPrompt();
        }
      } else {
        _setPill('error', _si18n.autosave_failed || '');
      }
    })
    .catch(function() {
      _setPill('error', _si18n.network_error || '');
    })
    .finally(function() {
      saveButtons.forEach(function(b, i) {
        b.disabled = false;
        b.textContent = origLabels[i] || '';
      });
    });
  }
  window.ecfV2Save = ecfV2Save;

  /* ── Autosave (debounced 2 s) ────────────────────────────────────────── */
  function ecfV2ScheduleAutosave() {
    var _ai18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    _setPill('unsaved', _ai18n.autosave_unsaved || '');
    clearTimeout(_autosaveTimer);
    _autosaveTimer = setTimeout(function() { ecfV2Save(true); }, 800);
  }
  window.ecfV2ScheduleAutosave = ecfV2ScheduleAutosave;

  /* ── WCAG Kontrast-Checker ───────────────────────────────────────────── */
  (function() {
    function hexToRgb(hex) {
      var v = String(hex || '').trim().replace(/^#/, '');
      if (v.length === 3) v = v.replace(/(.)/g, '$1$1');
      if (!/^[0-9a-f]{6}$/i.test(v)) return null;
      return { r: parseInt(v.slice(0,2),16), g: parseInt(v.slice(2,4),16), b: parseInt(v.slice(4,6),16) };
    }
    function luminance(rgb) {
      var lin = function(c) { c /= 255; return c <= 0.04045 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4); };
      return 0.2126*lin(rgb.r) + 0.7152*lin(rgb.g) + 0.0722*lin(rgb.b);
    }
    function ratio(a, b) {
      var l1 = luminance(a), l2 = luminance(b);
      return (Math.max(l1,l2)+0.05) / (Math.min(l1,l2)+0.05);
    }
    function rating(r) {
      if (r >= 7)   return 'AAA';
      if (r >= 4.5) return 'AA';
      if (r >= 3)   return 'AA Large';
      return 'Fail';
    }

    var selFg = document.getElementById('v2-cc-fg');
    var selBg = document.getElementById('v2-cc-bg');
    var inpFg = document.getElementById('v2-cc-fg-c');
    var inpBg = document.getElementById('v2-cc-bg-c');
    var result = document.getElementById('v2-cc-result');
    var preview = document.getElementById('v2-cc-preview');
    var ratEl  = document.getElementById('v2-cc-ratio');
    var badEl  = document.getElementById('v2-cc-badge');
    if (!selFg || !selBg) return;

    function update() {
      var fg = selFg.value || inpFg.value;
      var bg = selBg.value || inpBg.value;
      var fgRgb = hexToRgb(fg), bgRgb = hexToRgb(bg);
      if (!fgRgb || !bgRgb) { result.style.display = 'none'; return; }
      var r = ratio(fgRgb, bgRgb);
      var rat = rating(r);
      preview.style.color = fg; preview.style.background = bg;
      ratEl.textContent = r.toFixed(2) + ':1';
      badEl.textContent = rat;
      badEl.className = 'v2-cc-badge v2-cc-badge--' + (rat !== 'Fail' ? 'pass' : 'fail');
      result.style.display = 'flex';
    }

    selFg.addEventListener('change', function() { if (selFg.value) inpFg.value = selFg.value; update(); });
    selBg.addEventListener('change', function() { if (selBg.value) inpBg.value = selBg.value; update(); });
    inpFg.addEventListener('input', function() { selFg.value = ''; update(); });
    inpBg.addEventListener('input', function() { selBg.value = ''; update(); });

    if (selFg.options.length > 1) selFg.selectedIndex = 1;
    if (selBg.options.length > 2) selBg.selectedIndex = 2;
    else if (selBg.options.length > 1) selBg.selectedIndex = 1;
    update();
  }());

  /* ── Harmonie-Generator (slot-lock UX) ──────────────────────────────── */
  (function() {
    var container = document.getElementById('v2-harmony-generator');
    if (!container) return;

    /* ── Farb-Konvertierungen ── */
    function hexToRgb(hex) {
      var v = String(hex || '').trim().replace(/^#/, '');
      if (v.length === 3) v = v.replace(/(.)/g, '$1$1');
      if (!/^[0-9a-f]{6}$/i.test(v)) return null;
      return { r: parseInt(v.slice(0,2),16), g: parseInt(v.slice(2,4),16), b: parseInt(v.slice(4,6),16) };
    }
    function rgbToHex(r, g, b) {
      var c = function(n) { return Math.max(0,Math.min(255,Math.round(n))).toString(16).padStart(2,'0'); };
      return '#' + c(r) + c(g) + c(b);
    }

    function hexToHsl(hex) {
      var v = String(hex || '').trim().replace(/^#/, '');
      if (v.length === 3) v = v.replace(/(.)/g, '$1$1');
      if (!/^[0-9a-f]{6}$/i.test(v)) return null;
      var r = parseInt(v.slice(0,2),16)/255, g = parseInt(v.slice(2,4),16)/255, b = parseInt(v.slice(4,6),16)/255;
      var max = Math.max(r,g,b), min = Math.min(r,g,b), h, s, l = (max+min)/2;
      if (max === min) { h = s = 0; } else {
        var d = max - min;
        s = l > 0.5 ? d/(2-max-min) : d/(max+min);
        switch(max) {
          case r: h = ((g-b)/d + (g<b?6:0))/6; break;
          case g: h = ((b-r)/d + 2)/6; break;
          default: h = ((r-g)/d + 4)/6;
        }
      }
      return { h: Math.round(h*360), s: Math.round(s*100), l: Math.round(l*100) };
    }

    function hslToHex(h, s, l) {
      h = ((h % 360) + 360) % 360; s = Math.max(0,Math.min(100,s)); l = Math.max(0,Math.min(100,l));
      var s1 = s/100, l1 = l/100;
      var c = (1 - Math.abs(2*l1-1)) * s1, x = c*(1 - Math.abs((h/60)%2 - 1)), m = l1 - c/2;
      var r, g, b;
      if      (h<60)  { r=c;g=x;b=0; } else if (h<120) { r=x;g=c;b=0; }
      else if (h<180) { r=0;g=c;b=x; } else if (h<240) { r=0;g=x;b=c; }
      else if (h<300) { r=x;g=0;b=c; } else            { r=c;g=0;b=x; }
      var toHex = function(n) { return Math.round((n+m)*255).toString(16).padStart(2,'0'); };
      return '#' + toHex(r) + toHex(g) + toHex(b);
    }

    /* ── Harmonie-Algorithmen ── */
    /* Slots: 0=Primär, 1=Sekundär, 2=Akzent, 3=Fläche (hell), 4=Text (dunkel) */
    function harmonyOffsets(mode) {
      var clamp = function(v,a,b) { return Math.max(a, Math.min(b, v)); };
      /* Fläche: heller Ton mit spürbarer Tönung (15–30% Sättigung, 92–96% Helligkeit)
         Text:   dunkler Ton mit spürbarer Tönung (18–35% Sättigung, 8–18% Helligkeit) */
      var surface = function(h,s) { return [h, clamp(s*0.35, 15, 30), 94]; };
      var text    = function(h,s) { return [h, clamp(s*0.40, 18, 35), 13]; };
      return {
        tetradic: function(h,s,l) {
          return [
            [h,s,l],
            [(h+90)%360,s,l],
            [(h+180)%360,s,l],
            surface(h,s),
            text(h,s)
          ];
        },
        monochromatic: function(h,s,l) {
          return [
            [h, s, l],
            [h, clamp(s*0.7,10,100), clamp(l+18,10,90)],
            [h, clamp(s*1.15,10,100), clamp(l-12,10,90)],
            surface(h,s),
            text(h,s)
          ];
        },
        complementary: function(h,s,l) {
          return [
            [h,s,l],
            [(h+180)%360,s,l],
            [(h+150)%360,clamp(s*0.85,20,100),clamp(l+5,20,80)],
            surface(h,s),
            text(h,s)
          ];
        },
        analogous: function(h,s,l) {
          return [
            [h,s,l],
            [(h+30)%360,s,l],
            [(h+330)%360,s,l],
            surface(h,s),
            text(h,s)
          ];
        },
        triadic: function(h,s,l) {
          return [
            [h,s,l],
            [(h+120)%360,s,l],
            [(h+240)%360,s,l],
            surface(h,s),
            text(h,s)
          ];
        },
        split: function(h,s,l) {
          return [
            [h,s,l],
            [(h+150)%360,s,l],
            [(h+210)%360,s,l],
            surface(h,s),
            text(h,s)
          ];
        }
      }[mode] || null;
    }

    /* ── State ── */
    var _mode = 'complementary';
    var _slots = Array.from(container.querySelectorAll('.v2-hg-slot'));
    var _activeSlot = null;
    var updatePreview = function() {}; /* wird später überschrieben */

    /* ── Kontrast-Textfarbe ── */
    function hexLuminance(hex) {
      var v = hex.replace(/^#/, '');
      if (v.length === 3) v = v.replace(/(.)/g, '$1$1');
      var r = parseInt(v.slice(0,2),16)/255, g = parseInt(v.slice(2,4),16)/255, b = parseInt(v.slice(4,6),16)/255;
      var lin = function(c) { return c <= 0.04045 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4); };
      return 0.2126*lin(r) + 0.7152*lin(g) + 0.0722*lin(b);
    }
    function contrastTextColor(hex) {
      return hexLuminance(hex) > 0.179 ? '#000000' : '#ffffff';
    }

    /* ── Slot-Farbe lesen / schreiben ── */
    function slotHex(slot) {
      return slot.querySelector('.v2-hg-sw-hex').textContent.trim();
    }

    function setSlotHex(slot, hex) {
      hex = hex.toLowerCase();
      var hexEl = slot.querySelector('.v2-hg-sw-hex');
      slot.querySelector('.v2-hg-sw-block').style.background = hex;
      hexEl.textContent = hex;
      hexEl.style.color = contrastTextColor(hex);
      slot.querySelector('.v2-hg-slot-picker').value = hex;
      var hexInp = slot.querySelector('.v2-hg-slot-hex');
      if (hexInp) hexInp.value = hex;
      updatePreview();
    }

    function populatePopover(slot, hex) {
      var rgb = hexToRgb(hex);
      var hsl = hexToHsl(hex);
      if (rgb) {
        slot.querySelectorAll('.v2-hg-rgb').forEach(function(inp) {
          inp.value = rgb[inp.dataset.ch];
        });
      }
      if (hsl) {
        slot.querySelectorAll('.v2-hg-hsl').forEach(function(inp) {
          inp.value = hsl[inp.dataset.ch];
        });
      }
    }

    function isLocked(slot) {
      return slot.dataset.hgLocked === '1';
    }

    /* ── Regenerierung: unlocked Slots aus dem ersten locked Slot ableiten ── */
    function regenerate() {
      var baseSlot = _slots.find(isLocked) || _slots[0];
      var hsl = hexToHsl(slotHex(baseSlot));
      if (!hsl) return;
      var fn = harmonyOffsets(_mode);
      if (!fn) return;
      var offsets = fn(hsl.h, hsl.s, hsl.l);
      _slots.forEach(function(slot, i) {
        if (isLocked(slot)) return;
        setSlotHex(slot, hslToHex(offsets[i][0], offsets[i][1], offsets[i][2]));
      });
    }

    /* ── Popover öffnen / schließen ── */
    function openPopover(slot) {
      if (_activeSlot && _activeSlot !== slot) closePopover(_activeSlot);
      _activeSlot = slot;
      slot.querySelector('.v2-hg-popover').hidden = false;
      slot.classList.add('is-open');
      populatePopover(slot, slotHex(slot));
      var hexInp = slot.querySelector('.v2-hg-slot-hex');
      hexInp.focus();
      hexInp.select();
    }
    function closePopover(slot) {
      slot.querySelector('.v2-hg-popover').hidden = true;
      slot.classList.remove('is-open');
      if (_activeSlot === slot) _activeSlot = null;
    }

    /* ── Events ── */
    _slots.forEach(function(slot) {
      /* Lock-Button */
      slot.querySelector('.v2-hg-lock').addEventListener('click', function(e) {
        e.stopPropagation();
        var locked = !isLocked(slot);
        slot.dataset.hgLocked = locked ? '1' : '0';
        slot.querySelector('.v2-hg-lock-open').style.display   = locked ? 'none' : '';
        slot.querySelector('.v2-hg-lock-closed').style.display = locked ? '' : 'none';
        slot.classList.toggle('is-locked', locked);
        if (locked) regenerate();
      });

      var picker = slot.querySelector('.v2-hg-slot-picker');
      var hexInp = slot.querySelector('.v2-hg-slot-hex');

      /* Swatch klicken → nativen Color-Picker IMMER öffnen + Popover öffnen.
         Kein Toggle, weil der OS-Picker den 2. Klick absorbiert (zum
         Selbst-Schließen) und dann der State out-of-sync wäre. Schließen
         passiert via Klick außerhalb (siehe document-click listener) oder ESC.
         Wir klicken das Label (.v2-hg-picker-btn), das den nativen Picker
         zuverlässig auslöst — robuster als showPicker(). */
      var pickerLabel = slot.querySelector('.v2-hg-picker-btn');
      function triggerNativePicker() {
        if (pickerLabel) { pickerLabel.click(); return; }
        try {
          if (typeof picker.showPicker === 'function') { picker.showPicker(); return; }
        } catch(ex) {}
        picker.click();
      }
      /* Popover hat inset:0 + z-index:6 → überdeckt den ganzen Swatch.
         Wir überspringen daher nur Klicks auf interaktive Popover-Elemente
         (Tabs, Inputs, Picker-Button), damit der Klick aufs Popover-Hintergrund
         als Toggle-Schließen wirkt. */
      function isInteractivePopoverEl(target) {
        return !!target.closest('.v2-hg-fmt, .v2-hg-picker-btn, .v2-hg-fmt-panel, input, button, label, select, textarea');
      }
      function toggleSlot(e) {
        if (e && isInteractivePopoverEl(e.target)) return;
        if (slot.classList.contains('is-open')) {
          closePopover(slot);
        } else {
          openPopover(slot);
          triggerNativePicker();
        }
      }
      slot.querySelector('.v2-hg-sw-block').addEventListener('click', toggleSlot);
      slot.querySelector('.v2-hg-sw-foot').addEventListener('click', toggleSlot);

      /* Format-Tabs */
      slot.querySelectorAll('.v2-hg-fmt').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
          e.stopPropagation();
          var fmt = tab.dataset.fmt;
          slot.querySelectorAll('.v2-hg-fmt').forEach(function(t) { t.classList.remove('is-active'); });
          tab.classList.add('is-active');
          slot.querySelectorAll('.v2-hg-fmt-panel').forEach(function(p) { p.hidden = true; });
          var panel = slot.querySelector('.v2-hg-fmt-panel[data-panel="' + fmt + '"]');
          if (panel) {
            panel.hidden = false;
            var first = panel.querySelector('input');
            if (first) { first.focus(); first.select(); }
          }
        });
      });

      /* Native Picker → live update */
      picker.addEventListener('input', function() {
        setSlotHex(slot, picker.value);
        populatePopover(slot, picker.value);
        if (isLocked(slot)) regenerate();
      });

      /* Hex-Input: nur bei Enter oder Blur anwenden */
      function applyHex(val) {
        val = val.trim();
        if (!/^#[0-9a-f]{6}$/i.test(val)) return false;
        setSlotHex(slot, val);
        populatePopover(slot, val);
        if (isLocked(slot)) regenerate();
        return true;
      }
      hexInp.addEventListener('blur', function() { applyHex(hexInp.value); });
      hexInp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (applyHex(hexInp.value)) {
            if (!isLocked(slot)) {
              slot.dataset.hgLocked = '1';
              slot.querySelector('.v2-hg-lock-open').style.display   = 'none';
              slot.querySelector('.v2-hg-lock-closed').style.display  = '';
              slot.classList.add('is-locked');
            }
            regenerate();
            closePopover(slot);
          }
        }
        if (e.key === 'Escape') closePopover(slot);
      });

      /* RGB-Inputs: bei Blur/Enter */
      function applyRgb() {
        var inputs = slot.querySelectorAll('.v2-hg-rgb');
        var vals = {};
        inputs.forEach(function(inp) { vals[inp.dataset.ch] = parseInt(inp.value, 10) || 0; });
        var hex = rgbToHex(vals.r, vals.g, vals.b);
        setSlotHex(slot, hex);
        populatePopover(slot, hex);
        if (isLocked(slot)) regenerate();
      }
      slot.querySelectorAll('.v2-hg-rgb').forEach(function(inp) {
        inp.addEventListener('blur', applyRgb);
        inp.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') { e.preventDefault(); applyRgb(); }
          if (e.key === 'Escape') closePopover(slot);
        });
      });

      /* HSL-Inputs: bei Blur/Enter */
      function applyHsl() {
        var inputs = slot.querySelectorAll('.v2-hg-hsl');
        var vals = {};
        inputs.forEach(function(inp) { vals[inp.dataset.ch] = parseFloat(inp.value) || 0; });
        var hex = hslToHex(vals.h, vals.s, vals.l);
        setSlotHex(slot, hex);
        populatePopover(slot, hex);
        if (isLocked(slot)) regenerate();
      }
      slot.querySelectorAll('.v2-hg-hsl').forEach(function(inp) {
        inp.addEventListener('blur', applyHsl);
        inp.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') { e.preventDefault(); applyHsl(); }
          if (e.key === 'Escape') closePopover(slot);
        });
      });
    });

    /* Klick außerhalb → Popover schließen */
    document.addEventListener('click', function(e) {
      if (_activeSlot && !_activeSlot.contains(e.target)) closePopover(_activeSlot);
    });

    /* ── Mode buttons ── */
    container.querySelectorAll('[data-hg-mode]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        container.querySelectorAll('[data-hg-mode]').forEach(function(b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        _mode = btn.dataset.hgMode;
        regenerate();
      });
    });

    /* ── Zufall-Button ── (delegated so re-renders / wizard overlays don't break it) */
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('#v2-hg-shuffle');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      /* Re-query slots at click time — _slots cache could be stale after re-renders. */
      var slots = Array.from(container.querySelectorAll('.v2-hg-slot'));
      if (!slots.length) return;
      var h = Math.floor(Math.random() * 360);
      var s = Math.floor(Math.random() * 30) + 55;  /* 55–85% */
      var l = Math.floor(Math.random() * 22) + 38;  /* 38–60% */
      var randomHex = hslToHex(h, s, l);
      var baseSlot = slots.find(function(sl) { return !isLocked(sl); }) || slots[0];
      if (!baseSlot) return;
      setSlotHex(baseSlot, randomHex);
      regenerate();
    });

    /* ── "In Palette übernehmen" ── */
    var applyBtn = document.getElementById('v2-hg-apply');
    if (applyBtn) {
      applyBtn.addEventListener('click', function() {
        var w = wrap();
        if (!w) return;
        var rows = Array.from(w.querySelectorAll('[data-v2-tl-color] .v2-tr'));
        _slots.forEach(function(slot, i) {
          var row = rows[i];
          if (!row) return;
          var id = row.id.replace('v2-tr-', '');
          var hex = slotHex(slot);
          var cp = w.querySelector('#v2-cp-' + id);
          var hi = w.querySelector('#v2-einp-' + id);
          if (cp) cp.value = hex;
          if (hi) hi.value = hex;
          ecfV2ApplyColor(id);
        });
        ecfV2Save && ecfV2Save(true);
        ecfV2Toast && ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.palette_applied) || 'Palette applied.', 'success');
      });
    }

    /* ── Helligkeit & Sättigung Slider ── */
    var _sliderL = 0, _sliderS = 0;
    var _baseHsl = [];  /* HSL-Werte bei Slider-Start */

    function sliderStartCapture() {
      _baseHsl = _slots.map(function(slot) { return hexToHsl(slotHex(slot)); });
    }
    function applySliders() {
      _slots.forEach(function(slot, i) {
        var base = _baseHsl[i];
        if (!base) return;
        var newL = Math.max(2, Math.min(97, base.l + _sliderL));
        var newS = Math.max(0, Math.min(100, base.s + _sliderS));
        setSlotHex(slot, hslToHex(base.h, newS, newL));
      });
      updatePreview();
    }

    var slLight = document.getElementById('v2-hg-sl-light');
    var slSat   = document.getElementById('v2-hg-sl-sat');
    var slLightVal = document.getElementById('v2-hg-sl-light-val');
    var slSatVal   = document.getElementById('v2-hg-sl-sat-val');

    if (slLight) {
      slLight.addEventListener('mousedown', sliderStartCapture);
      slLight.addEventListener('touchstart', sliderStartCapture);
      slLight.addEventListener('input', function() {
        _sliderL = parseInt(slLight.value, 10);
        if (slLightVal) slLightVal.textContent = (_sliderL > 0 ? '+' : '') + _sliderL;
        applySliders();
      });
    }
    if (slSat) {
      slSat.addEventListener('mousedown', sliderStartCapture);
      slSat.addEventListener('touchstart', sliderStartCapture);
      slSat.addEventListener('input', function() {
        _sliderS = parseInt(slSat.value, 10);
        if (slSatVal) slSatVal.textContent = (_sliderS > 0 ? '+' : '') + _sliderS;
        applySliders();
      });
    }

    /* Slider nach Regenerate zurücksetzen */
    var _origRegenerate = regenerate;
    regenerate = function() {
      _origRegenerate();
      _sliderL = 0; _sliderS = 0;
      if (slLight) slLight.value = 0;
      if (slSat)   slSat.value   = 0;
      if (slLightVal) slLightVal.textContent = '0';
      if (slSatVal)   slSatVal.textContent   = '0';
      updatePreview();
    };

    /* ── Live-Vorschau ── */
    updatePreview = function() {
      var pv = document.getElementById('v2-hg-preview');
      if (!pv) return;
      var cols = _slots.map(function(s) { return slotHex(s); });
      /* 0=Primär, 1=Sekundär, 2=Akzent, 3=Fläche, 4=Text */
      var primary = cols[0], secondary = cols[1], accent = cols[2], surface = cols[3], text = cols[4];
      pv.style.setProperty('--pv-primary',   primary);
      pv.style.setProperty('--pv-secondary', secondary);
      pv.style.setProperty('--pv-accent',    accent);
      pv.style.setProperty('--pv-surface',   surface);
      pv.style.setProperty('--pv-text',      text);
      pv.style.setProperty('--pv-text-on-primary',   contrastTextColor(primary));
      pv.style.setProperty('--pv-text-on-secondary', contrastTextColor(secondary));
      pv.style.setProperty('--pv-text-on-accent',    contrastTextColor(accent));
    }

    /* ── Bild-Import ── */
    var imgInput = document.getElementById('v2-hg-img-input');
    if (imgInput) {
      imgInput.addEventListener('change', function() {
        var file = imgInput.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
          var img = new Image();
          img.onload = function() {
            var canvas = document.createElement('canvas');
            var size = 80;
            canvas.width = size; canvas.height = size;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, size, size);
            var data = ctx.getImageData(0, 0, size, size).data;
            /* Pixel samplen */
            var pixels = [];
            for (var i = 0; i < data.length; i += 4) {
              if (data[i+3] < 128) continue;
              pixels.push([data[i], data[i+1], data[i+2]]);
            }
            /* K-Means mit k=5 */
            var k = 5, iters = 12;
            var centers = [];
            for (var ci = 0; ci < k; ci++) {
              centers.push(pixels[Math.floor(Math.random() * pixels.length)].slice());
            }
            for (var it = 0; it < iters; it++) {
              var clusters = Array.from({length: k}, function() { return []; });
              pixels.forEach(function(px) {
                var best = 0, bestD = Infinity;
                centers.forEach(function(c, ci) {
                  var d = (px[0]-c[0])*(px[0]-c[0])+(px[1]-c[1])*(px[1]-c[1])+(px[2]-c[2])*(px[2]-c[2]);
                  if (d < bestD) { bestD = d; best = ci; }
                });
                clusters[best].push(px);
              });
              centers = clusters.map(function(cl, ci) {
                if (!cl.length) return centers[ci];
                var avg = [0,0,0];
                cl.forEach(function(px) { avg[0]+=px[0]; avg[1]+=px[1]; avg[2]+=px[2]; });
                return [avg[0]/cl.length, avg[1]/cl.length, avg[2]/cl.length];
              });
            }
            /* Aufsteigende Helligkeit: Surface = hellste, Text = dunkelste, Rest = Mitte */
            var sorted = centers.slice().sort(function(a,b) {
              return (0.299*a[0]+0.587*a[1]+0.114*a[2]) - (0.299*b[0]+0.587*b[1]+0.114*b[2]);
            });
            var hexes = [
              rgbToHex(sorted[2][0],sorted[2][1],sorted[2][2]),
              rgbToHex(sorted[3][0],sorted[3][1],sorted[3][2]),
              rgbToHex(sorted[1][0],sorted[1][1],sorted[1][2]),
              rgbToHex(sorted[4][0],sorted[4][1],sorted[4][2]),
              rgbToHex(sorted[0][0],sorted[0][1],sorted[0][2])
            ];
            _slots.forEach(function(slot, i) {
              if (!isLocked(slot)) setSlotHex(slot, hexes[i]);
            });
            updatePreview();
            imgInput.value = '';
          };
          img.src = e.target.result;
        };
        reader.readAsDataURL(file);
      });
    }

    /* ── Init: Textfarbe für Standardfarben setzen + Vorschau ── */
    _slots.forEach(function(slot) {
      var hexEl = slot.querySelector('.v2-hg-sw-hex');
      if (hexEl) hexEl.style.color = contrastTextColor(slotHex(slot));
    });
    updatePreview();
  }());

  /* ── Class limit warning popup ───────────────────────────────────────── */
  function ecfV2ShowClassLimitWarning(total, limit) {
    if (document.getElementById('v2-class-limit-warn')) return;
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var title   = i18n.class_limit_warn_title || 'Elementor class limit reached';
    var body    = (i18n.class_limit_warn_body || 'You are using %1$s of %2$s Global Classes.')
                    .replace('%1$s', total).replace('%2$s', limit);
    var btnTxt  = i18n.class_limit_warn_ok || 'OK';

    var overlay = document.createElement('div');
    overlay.id = 'v2-class-limit-warn';
    overlay.className = 'v2-modal-overlay v2-clw-overlay';
    overlay.innerHTML = '<div class="v2-modal-box v2-clw-box">'
      + '<div class="v2-modal-head"><span class="v2-clw-icon">⚠</span><span>' + escapeHtml(title) + '</span></div>'
      + '<div class="v2-modal-body v2-clw-body"><p>' + escapeHtml(body) + '</p></div>'
      + '<div class="v2-modal-foot"><button type="button" class="v2-btn v2-btn--primary v2-clw-ok">' + escapeHtml(btnTxt) + '</button></div>'
      + '</div>';

    document.body.appendChild(overlay);

    function closeWarn() { overlay.remove(); }
    overlay.querySelector('.v2-clw-ok').addEventListener('click', closeWarn);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeWarn(); });
    document.addEventListener('keydown', function handler(e) {
      if (e.key === 'Escape') { closeWarn(); document.removeEventListener('keydown', handler); }
    });
  }

  /* ── REST Sync ───────────────────────────────────────────────────────── */
  function ecfV2Sync(btn) {
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    if (!window.ecfAdmin || !ecfAdmin.syncRestUrl) {
      _setPill('error', i18n.sync_not_available || '');
      return Promise.reject('no-url');
    }
    var orig = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = i18n.syncing || ''; }

    return fetch(ecfAdmin.syncRestUrl, {
      method:  'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce':   ecfAdmin.restNonce,
      },
      body: JSON.stringify({}),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.success) {
        var vr = data.variables || {};
        var cr = data.classes   || {};
        var parts = [];
        if (!vr.skipped) {
          var vtotal = (vr.created || 0) + (vr.updated || 0) + (vr.deleted || 0);
          parts.push((i18n.sync_variables || '%d').replace('%d', vtotal));
        }
        if (!cr.skipped) {
          var ctotal = (cr.created || 0) + (cr.updated || 0) + (cr.deleted || 0);
          parts.push((i18n.sync_classes_count || '%d').replace('%d', ctotal));
        }
        var label = parts.length
          ? (i18n.sync_label || '') + ' ' + parts.join(', ')
          : (i18n.elementor_synced || '');
        _setPill('saved', label);
        var snap = data.meta && data.meta.elementor_limit_snapshot;
        if (snap) {
          var cTotal = parseInt(snap.classes_total, 10);
          var cLimit = parseInt(snap.classes_limit, 10);
          if (cLimit > 0 && cTotal >= cLimit) ecfV2ShowClassLimitWarning(cTotal, cLimit);
        }
      } else {
        _setPill('error', i18n.elementor_sync_failed || '');
      }
      return data;
    })
    .catch(function() {
      _setPill('error', i18n.sync_network_error || '');
    })
    .finally(function() {
      if (btn) { btn.disabled = false; btn.textContent = orig; }
    });
  }
  window.ecfV2Sync = ecfV2Sync;

  /* ── Add/Remove rows (colors, radius, shadows) ──────────────────────── */
  function ecfV2AddRow(type) {
    var w = wrap();
    if (!w) return;
    var tl = w.querySelector('[data-v2-tl-' + type + ']');
    if (!tl) return;
    var rows = tl.querySelectorAll('.v2-tr');
    var idx = rows.length;
    /* Avoid collision after delete: use max existing new{N} index + 1 */
    rows.forEach(function(r) {
      var m = r.id && r.id.match(/v2-tr-new(\d+)/);
      if (m) { var n = parseInt(m[1], 10) + 1; if (n > idx) idx = n; }
    });
    var opt = (window.ecfAdmin && ecfAdmin.optionName) ? ecfAdmin.optionName : 'ecf_framework_v50';
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var html = '';
    if (type === 'color') {
      var id = 'new' + idx;
      html = '<div class="v2-tr" data-v2-row-type="color" id="v2-tr-' + id + '">'
        + '<div class="v2-tr-main">'
        + '<div class="v2-tr-sw" id="v2-sw-' + id + '" style="background:#6366f1"></div>'
        + '<div style="flex:1;min-width:0">'
        + '<input type="text" class="v2-si" name="' + opt + '[colors][' + idx + '][name]" value="" placeholder="colorname" style="width:100%">'
        + '</div>'
        + '<div class="v2-tr-meta">'
        + '<input type="hidden" name="' + opt + '[colors][' + idx + '][value]" id="v2-val-' + id + '" value="#6366f1">'
        + '<span id="v2-hex-' + id + '" class="v2-chip v2-chip--hi">#6366f1</span>'
        + '<button type="button" class="v2-edit-btn" onclick="event.stopPropagation();ecfV2ToggleEdit(\'' + id + '\')" style="margin-left:6px"><svg width="11" height="11" viewBox="0 0 13 13" fill="none"><path d="M8.5 2L11 4.5 5 10.5H2.5V8L8.5 2z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>'
        + '<button type="button" class="v2-edit-btn v2-edit-btn--danger" onclick="event.stopPropagation();var _t=this.closest(\'.v2-tr\');if(_t){_t.remove();ecfV2ScheduleAutosave();}" title="' + (i18n.remove || '') + '">×</button>'
        + '</div>'
        + '</div>'
        + '<div class="v2-tr-edit" id="v2-edit-' + id + '">'
        + '<div class="v2-color-edit-panel">'
        + '<div class="v2-color-edit-pickers">'
        + '<input type="color" class="v2-color-native" id="v2-cp-' + id + '" value="#6366f1" data-v2-color-id="' + id + '">'
        + '<div class="v2-color-edit-right">'
        + '<div class="v2-color-edit-preview" id="v2-esw-' + id + '" style="background:#6366f1"></div>'
        + '<input type="text" class="v2-si v2-color-hex-inp" id="v2-einp-' + id + '" value="#6366f1" maxlength="7" data-v2-color-id="' + id + '">'
        + '</div></div>'
        + '<div class="v2-edit-var-label">--ecf-color-<span class="v2-evar-name" id="v2-evar-' + id + '"></span></div>'
        + '<div style="display:flex;align-items:center;gap:8px;margin-top:4px">'
        + '<label class="v2-mini-label">Format</label>'
        + '<select class="v2-si v2-si--sm v2-select" name="' + opt + '[colors][' + idx + '][format]" style="max-width:90px">'
        + '<option value="hex">HEX</option><option value="rgb">RGB</option><option value="rgba">RGBA</option><option value="hsl">HSL</option><option value="hsla">HSLA</option>'
        + '</select></div>'
        + '<div class="v2-shade-controls" style="margin-top:10px;display:flex;flex-direction:column;gap:7px">'
        + '<div class="v2-shade-row"><label class="v2-shade-label">'
        + '<input type="checkbox" class="v2-shade-cb" data-shade-target="v2-sc-' + id + '" name="' + opt + '[colors][' + idx + '][generate_shades]" value="1">'
        + '<span>' + (i18n.generate_shades || '') + '</span></label>'
        + '<div class="v2-stepper v2-stepper--off" id="v2-sc-' + id + '">'
        + '<button type="button" class="v2-stepper-btn" data-stepper-target="v2-sc-inp-' + id + '" data-stepper-delta="-1">−</button>'
        + '<input type="number" class="v2-stepper-inp" id="v2-sc-inp-' + id + '" name="' + opt + '[colors][' + idx + '][shade_count]" value="4" min="1" max="10">'
        + '<button type="button" class="v2-stepper-btn" data-stepper-target="v2-sc-inp-' + id + '" data-stepper-delta="1">+</button>'
        + '</div></div>'
        + '<div class="v2-shade-row"><label class="v2-shade-label">'
        + '<input type="checkbox" class="v2-shade-cb" data-shade-target="v2-tc-' + id + '" name="' + opt + '[colors][' + idx + '][generate_tints]" value="1">'
        + '<span>' + (i18n.generate_tints || '') + '</span></label>'
        + '<div class="v2-stepper v2-stepper--off" id="v2-tc-' + id + '">'
        + '<button type="button" class="v2-stepper-btn" data-stepper-target="v2-tc-inp-' + id + '" data-stepper-delta="-1">−</button>'
        + '<input type="number" class="v2-stepper-inp" id="v2-tc-inp-' + id + '" name="' + opt + '[colors][' + idx + '][tint_count]" value="4" min="1" max="10">'
        + '<button type="button" class="v2-stepper-btn" data-stepper-target="v2-tc-inp-' + id + '" data-stepper-delta="1">+</button>'
        + '</div></div></div>'
        + '<div class="v2-color-edit-actions">'
        + '<button type="button" class="v2-btn v2-btn--primary" onclick="ecfV2ApplyColor(\'' + id + '\')">' + (i18n.apply || '') + '</button>'
        + '<button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2ToggleEdit(\'' + id + '\')">' + (i18n.cancel || '') + '</button>'
        + '<button type="button" class="v2-btn v2-btn--ghost" style="margin-left:auto;color:var(--v2-text3)" onclick="var _t=this.closest(\'.v2-tr\');if(_t){_t.remove();ecfV2ScheduleAutosave();}">✕ ' + (i18n.remove || '') + '</button>'
        + '</div></div></div></div>';
    } else if (type === 'radius') {
      html = '<div class="v2-tr v2-tr--radius" data-v2-row-type="radius">'
        + '<div class="v2-tr-main">'
        + '<div class="v2-tr-sw v2-tr-sw--radius" style="width:20px;height:20px;background:rgba(99,102,241,.3);border-radius:4px;flex-shrink:0"></div>'
        + '<input type="text" class="v2-si" name="' + opt + '[radius][' + idx + '][name]" value="" placeholder="token" style="max-width:90px">'
        + '<input type="text" class="v2-si v2-si--sm" name="' + opt + '[radius][' + idx + '][min]" value="4px" placeholder="4px" style="max-width:70px">'
        + '<input type="text" class="v2-si v2-si--sm" name="' + opt + '[radius][' + idx + '][max]" value="8px" placeholder="8px" style="max-width:70px">'
        + '<button type="button" class="v2-edit-btn" style="margin-left:auto;color:var(--v2-text3)" data-v2-remove-row="radius" data-v2-row-index="' + idx + '">✕</button>'
        + '</div></div>';
    } else if (type === 'shadow' || type === 'shadow-inner') {
      var sname = 'new' + idx;
      html = '<div class="v2-tr" data-v2-row-type="shadow">'
        + '<div class="v2-sh-row" onclick="ecfV2PickShadow(\'' + sname + '\',\'\',\'\')">'
        + '<div class="v2-sh-prev"><div style="width:20px;height:20px;border-radius:4px;background:rgba(255,255,255,.12)"></div></div>'
        + '<div style="flex:1;min-width:0">'
        + '<div class="v2-sh-name">' + sname + '</div>'
        + '<div class="v2-sh-css"></div>'
        + '</div>'
        + '<button type="button" class="v2-edit-btn" onclick="event.stopPropagation();ecfV2ToggleEdit(\'sh-' + sname + '\')">✎</button>'
        + '</div>'
        + '<div class="v2-tr-edit" id="v2-edit-sh-' + sname + '">'
        + '<div class="v2-shadow-edit-panel">'
        + '<label class="v2-sl">--ecf-shadow-' + sname + '</label>'
        + '<input type="hidden" name="' + opt + '[shadows][' + idx + '][name]" value="' + sname + '">'
        + '<input type="text" class="v2-si v2-shadow-inp" name="' + opt + '[shadows][' + idx + '][value]" id="v2-shval-' + sname + '" value="" placeholder="0 4px 16px rgba(0,0,0,.10)" style="width:100%" data-v2-shadow-id="' + sname + '">'
        + '<div class="v2-shadow-edit-preview" id="v2-shprev-' + sname + '"></div>'
        + '<div class="v2-color-edit-actions">'
        + '<button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2CopyShadowCSS(\'' + sname + '\')">' + (i18n.copy_css || '') + '</button>'
        + '<button type="button" class="v2-btn v2-btn--ghost" onclick="ecfV2ToggleEdit(\'sh-' + sname + '\')">' + (i18n.close || '') + '</button>'
        + '</div></div></div></div>';
    }
    if (!html) return;
    tl.insertAdjacentHTML('beforeend', html);
    /* Re-bind new native color pickers and shadow inputs */
    tl.querySelectorAll('.v2-color-native[data-v2-color-id]').forEach(function(cp) {
      if (cp._v2Bound) return;
      cp._v2Bound = true;
      cp.addEventListener('input', function() {
        var id  = cp.dataset.v2ColorId;
        var val = cp.value;
        ecfV2LiveColor(id, val);
        var w2 = wrap();
        if (!w2) return;
        var hidden = w2.querySelector('#v2-val-' + id);
        if (hidden) hidden.value = val;
      });
    });
    /* Name-input → update CSS variable label live */
    if (type === 'color') {
      var newRow = tl.lastElementChild;
      if (newRow) {
        var nameInp  = newRow.querySelector('.v2-tr-main input[type="text"]');
        var evarSpan = newRow.querySelector('#v2-evar-' + id);
        if (nameInp && evarSpan) {
          nameInp.addEventListener('input', function() {
            evarSpan.textContent = nameInp.value;
          });
        }
      }
    }
    tl.querySelectorAll('.v2-shadow-inp[data-v2-shadow-id]').forEach(function(inp) {
      if (inp._v2Bound) return;
      inp._v2Bound = true;
      inp.addEventListener('input', function() {
        var id = inp.dataset.v2ShadowId;
        var w2 = wrap();
        if (!w2) return;
        var prev = w2.querySelector('#v2-shprev-' + id);
        if (prev) prev.style.boxShadow = inp.value;
      });
    });
  }
  window.ecfV2AddRow = ecfV2AddRow;

  function ecfV2RemoveRow(type, idx) {
    var w = wrap();
    if (!w) return;
    var rows = w.querySelectorAll('[data-v2-remove-row="' + type + '"][data-v2-row-index="' + idx + '"]');
    rows.forEach(function(btn) {
      var tr = btn.closest('.v2-tr');
      if (tr) tr.remove();
    });
    ecfV2ScheduleAutosave();
  }
  window.ecfV2RemoveRow = ecfV2RemoveRow;

  /* ── Pairing Apply ───────────────────────────────────────────────────── */
  function ecfV2ApplyPairing(hf, bf) {
    var w = wrap();
    if (!w) return;
    /* Quote only single-family names; comma-separated stacks are used as-is */
    function fontStack(family, fallback) {
      if (!family) return '';
      return family.indexOf(',') === -1 ? "'" + family + "', " + fallback : family;
    }
    /* Update font family inputs that contain "primary"/"heading" */
    w.querySelectorAll('input[name*="[typography][fonts]"][name*="[value]"]').forEach(function(inp) {
      var nameInp = inp.closest('.v2-tr-main') && inp.closest('.v2-tr-main').querySelector('input[name*="[name]"]');
      if (!nameInp) {
        var tr = inp.closest('.v2-tr');
        if (tr) nameInp = tr.querySelector('input[name*="[name]"]');
      }
      var fname = nameInp ? nameInp.value : '';
      if (fname === 'primary')   { inp.value = fontStack(bf, 'sans-serif'); }
      if (fname === 'secondary') { inp.value = fontStack(hf, 'serif'); }
      /* Update live preview swatch */
      var sw = inp.closest('.v2-tr-main') && inp.closest('.v2-tr-main').querySelector('.v2-tr-sw');
      if (sw) sw.style.fontFamily = inp.value;
    });
    /* Update font preview */
    var ph = w.querySelector('#v2-fp-h, .v2-fp-h');
    var pb = w.querySelector('#v2-fp-body, .v2-fp-body');
    var ps = w.querySelector('#v2-fp-secondary, .v2-fp-secondary');
    if (ph) ph.style.fontFamily = fontStack(hf, 'serif');
    if (pb) pb.style.fontFamily = fontStack(bf, 'sans-serif');
    if (ps) ps.style.fontFamily = fontStack(hf, 'serif');
    _setPill('saved', 'Schriftkombination gespeichert');
    ecfV2ScheduleAutosave();
  }
  window.ecfV2ApplyPairing = ecfV2ApplyPairing;

  /* ── BEM Generator ───────────────────────────────────────────────────── */
  function ecfV2UpdateBEM() {
    var block = (document.getElementById('v2-bem-block') || {}).value || '';
    var elem  = (document.getElementById('v2-bem-elem')  || {}).value || '';
    var mod   = (document.getElementById('v2-bem-mod')   || {}).value || '';
    var res   = document.getElementById('v2-bem-result');
    if (!res) return;
    if (!block) {
      res.innerHTML = '<span style="opacity:.35">' + escapeHtml((ecfAdmin.i18n && ecfAdmin.i18n.bem_fill_block) || '← Fill block') + '</span>';
      return;
    }
    var lines = [block];
    if (elem)        lines.push(block + '__' + elem);
    if (elem && mod) lines.push(block + '__' + elem + '--' + mod);
    else if (mod)    lines.push(block + '--' + mod);
    res.innerHTML = lines.map(function(l) {
      return '<span class="v2-bem-sel">.' + l + '</span> <span class="v2-bem-brace">{ }</span>';
    }).join('\n');
  }
  window.ecfV2UpdateBEM = ecfV2UpdateBEM;

  function ecfV2CopyBEM() {
    var res = document.getElementById('v2-bem-result');
    if (!res) return;
    var text = res.textContent || res.innerText || '';
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function() { ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.bem_copied) || 'BEM copied', 'success'); });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.bem_copied) || 'BEM copied', 'success'); } catch(e) {}
      document.body.removeChild(ta);
    }
  }
  window.ecfV2CopyBEM = ecfV2CopyBEM;

  function ecfV2AddBEMToCustom() {
    var res = document.getElementById('v2-bem-result');
    if (!res) return;
    var classes = (res.textContent || res.innerText || '').split('\n').map(function(l) {
      return l.replace(/^\./, '').trim();
    }).filter(Boolean);
    var list = document.getElementById('v2-custom-class-list');
    if (!list) return;
    var opt = (window.ecfAdmin && ecfAdmin.optionName) ? ecfAdmin.optionName : 'ecf_framework_v50';
    var existing = list.querySelectorAll('input[type="text"]').length;
    classes.forEach(function(cls, i) {
      var idx = existing + i;
      var row = document.createElement('div');
      row.className = 'v2-tr v2-tr-main';
      row.style.cssText = 'gap:6px';
      row.innerHTML = '<input type="hidden" name="' + escapeHtml(opt) + '[starter_classes][custom][' + idx + '][enabled]" value="1">'
        + '<input type="text" class="v2-si" style="flex:1" name="' + escapeHtml(opt) + '[starter_classes][custom][' + idx + '][name]" value="' + escapeHtml(cls) + '" placeholder="my-custom-class">'
        + '<button type="button" class="v2-edit-btn" data-v2-remove-custom-class title="Remove">✕</button>';
      list.appendChild(row);
    });
    _updateCustomEmpty(list);
    _setPill('saved', (ecfAdmin.i18n && ecfAdmin.i18n.classes_added) || 'Classes added');
    ecfV2ScheduleAutosave();
  }
  window.ecfV2AddBEMToCustom = ecfV2AddBEMToCustom;

  /* ── Custom classes add/remove ───────────────────────────────────────── */
  function _updateCustomEmpty(list) {
    var empty = document.getElementById('v2-cc-empty');
    if (!empty || !list) return;
    var hasRows = list.querySelectorAll('input[type="text"]').length > 0;
    empty.style.display = hasRows ? 'none' : '';
  }

  function ecfV2AddCustomClass() {
    var list = document.getElementById('v2-custom-class-list');
    if (!list) return;
    var opt = (window.ecfAdmin && ecfAdmin.optionName) ? ecfAdmin.optionName : 'ecf_framework_v50';
    var idx = list.querySelectorAll('input[type="text"]').length;
    var row = document.createElement('div');
    row.className = 'v2-tr v2-tr-main';
    row.style.cssText = 'gap:6px';
    row.innerHTML = '<input type="hidden" name="' + opt + '[starter_classes][custom][' + idx + '][enabled]" value="1">'
      + '<input type="text" class="v2-si" style="flex:1" name="' + opt + '[starter_classes][custom][' + idx + '][name]" value="" placeholder="my-custom-class">'
      + '<button type="button" class="v2-edit-btn" data-v2-remove-custom-class title="Remove">✕</button>';
    list.appendChild(row);
    _updateCustomEmpty(list);
    row.querySelector('input[type="text"]').focus();
  }
  window.ecfV2AddCustomClass = ecfV2AddCustomClass;

  /* ── Scale steps add/remove ──────────────────────────────────────────── */
  var _stepSequences = {
    ty: ['5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl','7xl'],
    sp: ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl']
  };

  function ecfV2RemoveStep(group, val) {
    var wrap_id = group === 'ty' ? 'v2-ty-steps-wrap' : 'v2-sp-steps-wrap';
    var list_id = group === 'ty' ? 'v2-ty-steps-list' : 'v2-sp-steps-list';
    var wrapEl  = document.getElementById(wrap_id);
    var listEl  = document.getElementById(list_id);
    if (!wrapEl || !listEl) return;
    var hidden = wrapEl.querySelectorAll('.v2-step-input[data-step-group="' + group + '"]');
    if (hidden.length <= 2) { ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.min_steps_required) || 'At least 2 steps required', 'info'); return; }
    /* Remove matching hidden input */
    hidden.forEach(function(inp) { if (inp.value === val) inp.remove(); });
    /* Remove chip */
    listEl.querySelectorAll('.v2-step-chip[data-step-group="' + group + '"][data-step-val="' + val + '"]').forEach(function(c) { c.remove(); });
    ecfV2RefreshStepCount(group);
    if (group === 'ty') ecfV2UpdateScalePreview();
    ecfV2ScheduleAutosave();
  }
  window.ecfV2RemoveStep = ecfV2RemoveStep;

  function ecfV2AddStep(groupDir) {
    var parts = groupDir.split('-');
    var group = parts[0];
    var dir   = parts[1];
    var wrap_id = group === 'ty' ? 'v2-ty-steps-wrap' : 'v2-sp-steps-wrap';
    var list_id = group === 'ty' ? 'v2-ty-steps-list' : 'v2-sp-steps-list';
    var wrapEl  = document.getElementById(wrap_id);
    var listEl  = document.getElementById(list_id);
    if (!wrapEl || !listEl) return;
    var opt = (window.ecfAdmin && ecfAdmin.optionName) ? ecfAdmin.optionName : 'ecf_framework_v50';
    var field = group === 'ty' ? '[typography][scale][steps][]' : '[spacing][steps][]';
    var seq = _stepSequences[group] || [];
    var hidden = wrapEl.querySelectorAll('.v2-step-input[data-step-group="' + group + '"]');
    var current = [];
    hidden.forEach(function(inp) { current.push(inp.value); });
    var newVal;
    if (dir === 'smaller') {
      var first = current[0];
      var fi = seq.indexOf(first);
      newVal = fi > 0 ? seq[fi - 1] : null;
    } else {
      var last = current[current.length - 1];
      var li = seq.indexOf(last);
      newVal = li >= 0 && li < seq.length - 1 ? seq[li + 1] : null;
    }
    if (!newVal || current.indexOf(newVal) !== -1) {
      ecfV2Toast((ecfAdmin.i18n && ecfAdmin.i18n.no_more_steps) || 'No more steps available', 'info');
      return;
    }
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = opt + field;
    inp.className = 'v2-step-input';
    inp.dataset.stepGroup = group;
    inp.value = newVal;
    var chip = '<span class="v2-step-chip" data-step-group="' + group + '" data-step-val="' + newVal + '" '
      + 'style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:999px;font-size:var(--v2-ui-base-fs, 13px);background:rgba(255,255,255,.07);cursor:pointer" '
      + 'onclick="ecfV2RemoveStep(\'' + group + '\',\'' + newVal + '\')" title="Remove">' + newVal + ' <span style="opacity:.5">×</span></span>';
    if (dir === 'smaller') {
      wrapEl.insertBefore(inp, wrapEl.firstChild);
      listEl.insertAdjacentHTML('afterbegin', chip);
    } else {
      wrapEl.appendChild(inp);
      listEl.insertAdjacentHTML('beforeend', chip);
    }
    ecfV2RefreshStepCount(group);
    if (group === 'ty') ecfV2UpdateScalePreview();
    ecfV2ScheduleAutosave();
  }
  window.ecfV2AddStep = ecfV2AddStep;

  function ecfV2RefreshStepCount(group) {
    var wrap_id = group === 'ty' ? 'v2-ty-steps-wrap' : 'v2-sp-steps-wrap';
    var wrapEl = document.getElementById(wrap_id);
    if (!wrapEl) return;
    var count = wrapEl.querySelectorAll('.v2-step-input[data-step-group="' + group + '"]').length;
    if (group === 'ty') {
      /* Section header badge */
      var badge = document.getElementById('v2-ty-step-count-badge');
      if (badge) badge.textContent = count + ' steps';
      /* Scale tab badge */
      var w = wrap();
      if (w) {
        var tabBadge = w.querySelector('[data-v2-tab-group="ty"][data-v2-tab="scale"] .v2-tc');
        if (tabBadge) tabBadge.textContent = count;
      }
    }
  }
  window.ecfV2RefreshStepCount = ecfV2RefreshStepCount;

  /* ── CSS Export ──────────────────────────────────────────────────────── */
  function ecfV2ExportCSS() {
    var w = wrap();
    if (!w) return;
    var lines = [':root {'];
    w.querySelectorAll('[name*="[colors]"][name*="[value]"]').forEach(function(inp) {
      var tr = inp.closest('.v2-tr');
      var nameInp = tr && tr.querySelector('[name*="[colors]"][name*="[name]"]');
      var n = nameInp ? nameInp.value : '';
      if (n) lines.push('  --ecf-color-' + n + ': ' + inp.value + ';');
    });
    w.querySelectorAll('[name*="[radius]"][name*="[min]"]').forEach(function(inp) {
      var tr = inp.closest('.v2-tr');
      var nameInp = tr && tr.querySelector('[name*="[radius]"][name*="[name]"]');
      var n = nameInp ? nameInp.value : '';
      if (n) lines.push('  --ecf-radius-' + n + ': ' + inp.value + ';');
    });
    w.querySelectorAll('[name*="[shadows]"][name*="[value]"]').forEach(function(inp) {
      var tr = inp.closest('.v2-tr');
      var nameInp = tr && tr.querySelector('[name*="[shadows]"][name*="[name]"]');
      var n = nameInp ? nameInp.value : '';
      if (n) lines.push('  --ecf-shadow-' + n + ': ' + inp.value + ';');
    });
    lines.push('}');
    var css = lines.join('\n');
    if (navigator.clipboard) {
      navigator.clipboard.writeText(css).then(function() { ecfV2Toast('CSS kopiert', 'success'); });
    } else {
      var ta = document.createElement('textarea');
      ta.value = css;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); ecfV2Toast('CSS kopiert', 'success'); } catch(e) {}
      document.body.removeChild(ta);
    }
  }
  window.ecfV2ExportCSS = ecfV2ExportCSS;

  /* ── Import file preview ─────────────────────────────────────────────── */
  var _importedJson = null;

  /* ── Unit-aware Inputs ─────────────────────────────────────────────── */
  function ecfV2InitUnitInputs() {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('.v2-unit-input').forEach(function(box) {
      if (box.dataset.unitInit) return;
      box.dataset.unitInit = '1';
      var num = box.querySelector('.v2-unit-num');
      var sel = box.querySelector('.v2-unit-sel');
      var hid = box.querySelector('.v2-unit-hidden');
      if (!num || !sel || !hid) return;
      var remBase = parseFloat(box.dataset.remBase) || 16;
      var vwBase  = 1920; /* Annahme: Desktop-Standard */
      function toPx(val, unit) {
        var v = parseFloat(val) || 0;
        if (unit === 'px')  return v;
        if (unit === 'rem' || unit === 'em') return v * remBase;
        if (unit === '%')   return v / 100 * remBase;
        if (unit === 'vw')  return v / 100 * vwBase;
        return v;
      }
      function fromPx(px, unit) {
        if (unit === 'px')  return px;
        if (unit === 'rem' || unit === 'em') return px / remBase;
        if (unit === '%')   return px / remBase * 100;
        if (unit === 'vw')  return px / vwBase * 100;
        return px;
      }
      function round(v) { return Math.round(v * 10000) / 10000; }
      var lastUnit = sel.value;
      sel.addEventListener('change', function() {
        var px   = toPx(num.value, lastUnit);
        var conv = fromPx(px, sel.value);
        num.value = round(conv);
        lastUnit = sel.value;
        sync();
      });
      num.addEventListener('input', sync);
      function sync() {
        var v = (num.value === '' ? '0' : num.value);
        hid.value = v + sel.value;
        if (typeof ecfV2ScheduleAutosave === 'function') ecfV2ScheduleAutosave();
      }
    });
  }
  window.ecfV2InitUnitInputs = ecfV2InitUnitInputs;

  function ecfV2InitImportPreview() {
    var i18n    = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var fileInp = document.getElementById('v2-import-file');
    var preview = document.getElementById('v2-import-preview');
    var submit  = document.getElementById('v2-import-submit');
    if (!fileInp) return;
    fileInp.addEventListener('change', function() {
      _importedJson = null;
      var file = fileInp.files && fileInp.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function(e) {
        try {
          var data = JSON.parse(e.target.result);
          _importedJson = data;
          var settings = data.settings || data;
          var info = [];
          if (data.meta && data.meta.plugin_version) info.push('v' + data.meta.plugin_version);
          if (Array.isArray(settings.colors))  info.push((i18n.import_colors_count  || '%d').replace('%d', settings.colors.length));
          if (Array.isArray(settings.radius))  info.push((i18n.import_radius_count  || '%d').replace('%d', settings.radius.length));
          if (Array.isArray(settings.shadows)) info.push((i18n.import_shadows_count || '%d').replace('%d', settings.shadows.length));
          if (preview) { preview.textContent = info.join(' · ') || (i18n.import_json_loaded || ''); preview.style.display = ''; }
          if (submit)  { submit.style.display = ''; }
        } catch(err) {
          if (preview) { preview.textContent = i18n.import_invalid_json || ''; preview.style.display = ''; }
        }
      };
      reader.readAsText(file);
    });

    /* Intercept import submit → show selective modal instead of form submit */
    if (submit) {
      submit.removeAttribute('onclick');
      submit.addEventListener('click', function() {
        if (!_importedJson) return;
        var modal = document.getElementById('v2-import-modal');
        if (modal) { modal.style.display = 'flex'; }
      });
    }
  }

  /* ── Font search + import ────────────────────────────────────────────── */
  var _fontSearchTimer  = null;
  var _fontSelectedFamily = '';

  function ecfV2SearchFonts(q) {
    if (!window.ecfAdmin || !ecfAdmin.fontSearchRestUrl) return;
    var results = document.getElementById('v2-font-search-results');
    if (!results) return;
    results.innerHTML = '<div style="padding:8px 12px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">Searching…</div>';
    results.style.display = '';
    var url = ecfAdmin.fontSearchRestUrl + '?q=' + encodeURIComponent(q) + '&limit=30';
    fetch(url, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        results.innerHTML = '';
        if (!data || !data.groups || !data.groups.length) {
          results.innerHTML = '<div style="padding:8px 12px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">No results</div>';
          return;
        }
        data.groups.forEach(function(grp) {
          var gh = document.createElement('div');
          gh.style.cssText = 'padding:4px 10px 2px;font-size:var(--v2-btn-fs, 12px);text-transform:uppercase;letter-spacing:.06em;color:var(--v2-text3);border-bottom:1px solid var(--v2-border)';
          gh.textContent = grp.label;
          results.appendChild(gh);
          (grp.options || []).forEach(function(opt) {
            var item = document.createElement('div');
            item.style.cssText = 'padding:6px 12px;font-size:12px;cursor:pointer;transition:background .1s';
            item.textContent = opt.label;
            item.addEventListener('mouseenter', function() { item.style.background = 'rgba(255,255,255,.06)'; });
            item.addEventListener('mouseleave', function() { item.style.background = ''; });
            item.addEventListener('click', function() {
              _fontSelectedFamily = opt.source === 'library' ? opt.label : opt.label;
              var nameEl  = document.getElementById('v2-font-selected-name');
              var importBar = document.getElementById('v2-font-import-bar');
              var inp = document.getElementById('v2-font-search-inp');
              if (nameEl) nameEl.textContent = opt.label;
              if (importBar) importBar.style.display = 'flex';
              if (inp) inp.value = opt.label;
              results.style.display = 'none';
            });
            results.appendChild(item);
          });
        });
      })
      .catch(function() {
        results.innerHTML = '<div style="padding:8px 12px;font-size:var(--v2-ui-base-fs, 13px);color:var(--v2-text3)">Search error</div>';
      });
  }

  function ecfV2ImportFont(target) {
    if (!_fontSelectedFamily) { _setPill('error', (ecfAdmin.i18n && ecfAdmin.i18n.select_font_first) || 'Select a font first'); return; }
    if (!window.ecfAdmin || !ecfAdmin.fontImportRestUrl) return;
    _setPill('saving', 'Schrift wird importiert…');
    fetch(ecfAdmin.fontImportRestUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
      body: JSON.stringify({ family: _fontSelectedFamily, target: target }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.success) {
        var roleLabel = target === 'heading'
          ? (ecfAdmin.i18n && ecfAdmin.i18n.font_role_secondary ? ecfAdmin.i18n.font_role_secondary : 'sekundäre Schrift')
          : (ecfAdmin.i18n && ecfAdmin.i18n.font_role_primary   ? ecfAdmin.i18n.font_role_primary   : 'primäre Schrift');
        _setPill('saved', '"' + _fontSelectedFamily + '" ' + (ecfAdmin.i18n && ecfAdmin.i18n.font_saved_as ? ecfAdmin.i18n.font_saved_as : 'als') + ' ' + roleLabel + ' gespeichert');
        /* Update font input matching the target */
        var w = wrap();
        if (w && data.selectedValue) {
          w.querySelectorAll('input[name*="[typography][fonts]"][name*="[value]"]').forEach(function(inp) {
            var tr = inp.closest('.v2-tr');
            var nameInp = tr && tr.querySelector('input[name*="[name]"]');
            var fname = nameInp ? nameInp.value : '';
            if ((target === 'body' && fname === 'primary') || (target === 'heading' && fname === 'secondary')) {
              inp.value = data.selectedValue;
              var sw = tr && tr.querySelector('.v2-tr-sw');
              if (sw) sw.style.fontFamily = data.selectedValue;
              _ecfV2UpdateFontChip(fname, data.selectedValue);
            }
          });
          /* Refresh preview */
          var ph = w.querySelector('#v2-fp-h, .v2-fp-h');
          var pb = w.querySelector('#v2-fp-body, .v2-fp-body');
          var ps = w.querySelector('#v2-fp-secondary, .v2-fp-secondary');
          if (target === 'heading') {
            if (ph) ph.style.fontFamily = data.selectedValue;
          } else {
            if (pb) pb.style.fontFamily = data.selectedValue;
            if (ps) ps.style.fontFamily = data.selectedValue;
          }
        }
      } else {
        _setPill('error', (data && data.message) || 'Import fehlgeschlagen');
      }
    })
    .catch(function() { _setPill('error', 'Netzwerkfehler'); });
  }
  /* After import: update the font chip in the row */
  function _ecfV2UpdateFontChip(fname, value) {
    var chip = document.getElementById('v2-font-chip-' + fname);
    var hidden = document.getElementById('v2-font-val-' + fname);
    var sw = document.getElementById('v2-tr-font-' + fname);
    if (chip) chip.textContent = value.split(',')[0].replace(/['"]/g, '').trim();
    if (hidden) hidden.value = value;
    if (sw) {
      var aa = sw.querySelector('.v2-tr-sw--font');
      if (aa) aa.style.fontFamily = value;
    }
  }
  window.ecfV2ImportFont = ecfV2ImportFont;

  /* ── Inline font search per row ──────────────────────────────────────── */
  var _fontInlineTimers = {};
  function ecfV2SearchFontInline(q, fname) {
    clearTimeout(_fontInlineTimers[fname]);
    var results = document.getElementById('v2-fi-results-' + fname);
    if (!results) return;
    var delay = q ? 300 : 0;
    _fontInlineTimers[fname] = setTimeout(function() {
      if (!window.ecfAdmin || !ecfAdmin.fontSearchRestUrl) return;
      var url = ecfAdmin.fontSearchRestUrl + '?q=' + encodeURIComponent(q || '') + '&limit=200';
      fetch(url, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          results.innerHTML = '';
          var groups = data.groups || [];
          if (!groups.length) { results.style.display = 'none'; return; }
          var hasAny = groups.some(function(g) { return g.options && g.options.length; });
          if (!hasAny) { results.style.display = 'none'; return; }
          function sortOpts(opts) {
            return opts.slice().sort(function(a, b) {
              return a.label.replace(/\s+/g, '').localeCompare(b.label.replace(/\s+/g, ''), undefined, { sensitivity: 'base' });
            });
          }
          var hasConsent = localStorage.getItem('ecf_font_preview_consent') === '1';
          var currentFavs = (ecfAdmin.fontFavorites || []).slice();
          function isFav(label) { return currentFavs.some(function(f) { return f.toLowerCase() === label.toLowerCase(); }); }
          function makeStar(opt) {
            var star = document.createElement('button');
            star.type = 'button';
            var faved = isFav(opt.label);
            star.textContent = faved ? '★' : '☆';
            star.title = faved
              ? (ecfAdmin.i18n && ecfAdmin.i18n.font_fav_remove ? ecfAdmin.i18n.font_fav_remove : 'Aus Favoriten entfernen')
              : (ecfAdmin.i18n && ecfAdmin.i18n.font_fav_add    ? ecfAdmin.i18n.font_fav_add    : 'Zu Favoriten hinzufügen');
            star.style.cssText = 'flex-shrink:0;border:0;background:none;font-size:15px;line-height:1;padding:0 2px;cursor:pointer;color:' + (faved ? 'var(--v2-primary,#6366f1)' : 'var(--v2-text3)');
            star.addEventListener('mouseover', function() { star.style.color = 'var(--v2-primary,#6366f1)'; });
            star.addEventListener('mouseout',  function() { star.style.color = isFav(opt.label) ? 'var(--v2-primary,#6366f1)' : 'var(--v2-text3)'; });
            star.addEventListener('click', function(e) {
              e.stopPropagation();
              ecfV2ToggleFontFavorite(opt.label, fname);
            });
            return star;
          }
          function makeItem(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = 'display:flex;align-items:center;width:100%;text-align:left;padding:6px 12px;font-size:12px;border:0;background:none;color:var(--v2-text);cursor:pointer;border-bottom:1px solid var(--v2-border);gap:8px';
            var nameSpan = document.createElement('span');
            nameSpan.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
            nameSpan.textContent = opt.label;
            var previewSpan = document.createElement('span');
            previewSpan.style.cssText = 'font-size:22px;color:var(--v2-text2);flex-shrink:0;min-width:36px;text-align:right';
            previewSpan.textContent = 'Aa';
            if (opt.source !== 'library') {
              previewSpan.style.fontFamily = "'" + opt.label + "',serif";
            } else {
              previewSpan.dataset.previewFamily = opt.label;
              if (hasConsent) previewSpan.style.fontFamily = "'" + opt.label + "',serif";
            }
            btn.appendChild(nameSpan);
            btn.appendChild(previewSpan);
            if (opt.source === 'local') {
              var delBtn = document.createElement('button');
              delBtn.type = 'button';
              delBtn.textContent = '×';
              delBtn.title = ecfAdmin.i18n && ecfAdmin.i18n.delete ? ecfAdmin.i18n.delete : 'Delete';
              delBtn.style.cssText = 'flex-shrink:0;border:0;background:none;color:var(--v2-danger);font-size:16px;line-height:1;padding:0 4px;cursor:pointer;opacity:.7';
              delBtn.addEventListener('mouseover', function() { delBtn.style.opacity = '1'; });
              delBtn.addEventListener('mouseout',  function() { delBtn.style.opacity = '.7'; });
              delBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                ecfV2DeleteLocalFont(opt.label, fname);
              });
              btn.appendChild(delBtn);
            } else {
              btn.appendChild(makeStar(opt));
            }
            btn.addEventListener('mouseover', function() { btn.style.background = 'var(--v2-hover)'; });
            btn.addEventListener('mouseout',  function() { btn.style.background = 'none'; });
            btn.addEventListener('click', function() {
              var stackInp = document.getElementById('v2-font-stack-' + fname);
              if (stackInp) stackInp.value = "'" + opt.label + "'";
              results.style.display = 'none';
              var target = fname === 'secondary' ? 'heading' : 'body';
              ecfV2ApplyFontStack(fname, target);
            });
            return btn;
          }
          function loadFontPreviews(families) {
            if (!families.length) return;
            for (var i = 0; i < families.length; i += 50) {
              var chunk = families.slice(i, i + 50);
              var url = 'https://fonts.googleapis.com/css2?' + chunk.map(function(f) { return 'family=' + encodeURIComponent(f); }).join('&') + '&text=Aa&display=swap';
              var tag = document.createElement('link');
              tag.rel = 'stylesheet'; tag.href = url;
              document.head.appendChild(tag);
            }
          }
          function makeGroupLabel(text) {
            var div = document.createElement('div');
            div.style.cssText = 'padding:5px 12px 4px;font-size:var(--v2-btn-fs, 12px);text-transform:uppercase;letter-spacing:.07em;color:var(--v2-text3);background:var(--v2-surface2,rgba(255,255,255,.03));border-bottom:1px solid var(--v2-border)';
            div.textContent = text;
            return div;
          }
          function makeSep() {
            var s = document.createElement('div');
            s.style.cssText = 'height:1px;background:var(--v2-border);margin:2px 0';
            return s;
          }
          var TOP_FONTS = ['Roboto','Open Sans','Lato','Montserrat','Oswald','Raleway','PT Sans','Merriweather','Nunito','Playfair Display'];
          /* Local fonts first, then Google Fonts (core tokens excluded — circular/useless here) */
          var localGroups = groups.filter(function(g) { return g.options && g.options.length && (g.options[0] || {}).source === 'local'; });
          var libraryGroups = groups.filter(function(g) { return g.options && g.options.length && (g.options[0] || {}).source === 'library'; });
          var allLibraryOpts = [];
          libraryGroups.forEach(function(g) { allLibraryOpts = allLibraryOpts.concat(g.options || []); });
          /* Favorites section */
          var favOpts = currentFavs.map(function(name) {
            return allLibraryOpts.find(function(o) { return o.label.toLowerCase() === name.toLowerCase(); });
          }).filter(Boolean);
          if (favOpts.length) {
            results.appendChild(makeGroupLabel(ecfAdmin.i18n && ecfAdmin.i18n.font_group_favorites ? ecfAdmin.i18n.font_group_favorites : 'Meine Favoriten'));
            favOpts.forEach(function(opt) { results.appendChild(makeItem(opt)); });
            results.appendChild(makeSep());
          }
          localGroups.forEach(function(g) {
            results.appendChild(makeGroupLabel(g.label));
            sortOpts(g.options).forEach(function(opt) { results.appendChild(makeItem(opt)); });
          });
          if (localGroups.length) results.appendChild(makeSep());
          /* Popular section when no query and no/few favorites */
          var libraryFamilies = [];
          if (!q) {
            var popularOpts = TOP_FONTS.map(function(name) {
              return allLibraryOpts.find(function(o) { return o.label.toLowerCase() === name.toLowerCase(); });
            }).filter(Boolean);
            if (popularOpts.length) {
              results.appendChild(makeGroupLabel(ecfAdmin.i18n && ecfAdmin.i18n.font_group_popular ? ecfAdmin.i18n.font_group_popular : 'Beliebt'));
              popularOpts.forEach(function(opt) {
                results.appendChild(makeItem(opt));
                libraryFamilies.push(opt.label);
              });
              results.appendChild(makeSep());
            }
          }
          libraryGroups.forEach(function(g) {
            sortOpts(g.options).forEach(function(opt) {
              results.appendChild(makeItem(opt));
              if (opt.source === 'library') libraryFamilies.push(opt.label);
            });
          });
          if (libraryFamilies.length) {
            if (hasConsent) {
              loadFontPreviews(libraryFamilies);
            } else {
              var banner = document.createElement('div');
              banner.style.cssText = 'padding:10px 12px;font-size:var(--v2-ui-base-fs, 13px);line-height:1.5;color:var(--v2-text2);border-bottom:1px solid var(--v2-border);display:flex;flex-direction:column;gap:6px';
              var bannerText = document.createElement('span');
              bannerText.textContent = ecfAdmin.i18n && ecfAdmin.i18n.fontPreviewConsent
                ? ecfAdmin.i18n.fontPreviewConsent
                : 'Font previews connect to Google Fonts.';
              var bannerBtn = document.createElement('button');
              bannerBtn.type = 'button';
              bannerBtn.style.cssText = 'align-self:flex-start;padding:4px 10px;font-size:var(--v2-ui-base-fs, 13px);border:1px solid var(--v2-border);border-radius:4px;background:var(--v2-surface2,rgba(255,255,255,.06));color:var(--v2-text);cursor:pointer';
              bannerBtn.textContent = ecfAdmin.i18n && ecfAdmin.i18n.fontPreviewEnable
                ? ecfAdmin.i18n.fontPreviewEnable
                : 'Vorschauen aktivieren';
              bannerBtn.addEventListener('click', function() {
                localStorage.setItem('ecf_font_preview_consent', '1');
                hasConsent = true;
                results.querySelectorAll('[data-preview-family]').forEach(function(span) {
                  span.style.fontFamily = "'" + span.dataset.previewFamily + "',serif";
                });
                loadFontPreviews(libraryFamilies);
                banner.remove();
              });
              banner.appendChild(bannerText);
              banner.appendChild(bannerBtn);
              results.insertBefore(banner, results.firstChild);
            }
          }
          results.style.display = 'block';
        });
    }, delay);
  }
  window.ecfV2SearchFontInline = ecfV2SearchFontInline;

  function ecfV2ToggleFontEdit(fname) {
    var el = document.getElementById('v2-edit-font-' + fname);
    var isOpen = el && el.classList.contains('v2-tr-edit--open');
    ecfV2ToggleEdit('font-' + fname);
    if (!isOpen) ecfV2SearchFontInline('', fname);
  }
  window.ecfV2ToggleFontEdit = ecfV2ToggleFontEdit;

  function ecfV2ApplyFontStack(fname, target) {
    var stackInp = document.getElementById('v2-font-stack-' + fname);
    if (!stackInp) return;
    var val = stackInp.value.trim();
    if (!val) return;
    _ecfV2UpdateFontChip(fname, val);
    /* If it's a library font (wrapped in quotes), trigger import; else just apply locally */
    var isLibrary = /^'[^']+'$/.test(val);
    if (isLibrary && window.ecfAdmin && ecfAdmin.fontImportRestUrl) {
      _fontSelectedFamily = val.replace(/^'|'$/g, '');
      ecfV2ImportFont(target);
    } else {
      /* Apply font stack directly to hidden input + preview */
      var hidden = document.getElementById('v2-font-val-' + fname);
      if (hidden) hidden.value = val;
      var w = wrap();
      if (w) {
        var ph = w.querySelector('#v2-fp-h');
        var pb = w.querySelector('#v2-fp-body');
        var ps = w.querySelector('#v2-fp-secondary');
        if (target === 'heading' && ph) ph.style.fontFamily = val;
        if (target === 'body') { if (pb) pb.style.fontFamily = val; if (ps) ps.style.fontFamily = val; }
      }
      ecfV2UpdatePagePreview && ecfV2UpdatePagePreview(fname, val);
      ecfV2ShowVariantsHint && ecfV2ShowVariantsHint(fname, val.replace(/^'|'$/g,'').split(',')[0].trim());
      var fontLabel = val.replace(/^'|'$/g, '').split(',')[0].trim();
      var roleLabel = target === 'heading'
        ? (ecfAdmin.i18n && ecfAdmin.i18n.font_role_secondary ? ecfAdmin.i18n.font_role_secondary : 'sekundäre Schrift')
        : (ecfAdmin.i18n && ecfAdmin.i18n.font_role_primary   ? ecfAdmin.i18n.font_role_primary   : 'primäre Schrift');
      ecfV2ScheduleAutosave();
      _setPill('saved', '"' + fontLabel + '" ' + (ecfAdmin.i18n && ecfAdmin.i18n.font_saved_as ? ecfAdmin.i18n.font_saved_as : 'als') + ' ' + roleLabel + ' gespeichert');
    }
    ecfV2ToggleEdit('font-' + fname);
  }
  window.ecfV2ApplyFontStack = ecfV2ApplyFontStack;

  /* ── Seiten-Vorschau aktualisieren ──────────────────────────────────────── */
  function ecfV2UpdatePagePreview(fname, fontStack) {
    var isHeading = (fname === 'secondary');
    var headEls = ['v2-ty-pv-h1', 'v2-ty-pv-h2', 'v2-ty-pv-quote'];
    var bodyEls = ['v2-ty-pv-sub', 'v2-ty-pv-p', 'v2-ty-pv-cta', 'v2-ty-pv-ghost'];
    var targets = isHeading ? headEls : bodyEls;
    targets.forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.style.fontFamily = fontStack;
    });
  }
  window.ecfV2UpdatePagePreview = ecfV2UpdatePagePreview;

  /* ── Hell/Dunkel Toggle für Vorschau ────────────────────────────────────── */
  (function() {
    var btn = document.getElementById('v2-ty-theme-btn');
    var pv  = document.getElementById('v2-ty-page-pv');
    if (!btn || !pv) return;
    btn.addEventListener('click', function() {
      var dark = pv.classList.toggle('v2-ty-pv--dark');
      btn.dataset.theme = dark ? 'light' : 'dark';
      btn.textContent   = dark ? '☀' : '☾';
    });
  }());

  /* ── Font-Weight Slider ─────────────────────────────────────────────────── */
  (function() {
    document.querySelectorAll('.v2-ty-weight-slider').forEach(function(slider) {
      var fname = slider.dataset.fname;
      var valEl = document.getElementById('v2-fw-val-' + fname);
      slider.addEventListener('input', function() {
        var w = parseInt(slider.value, 10);
        if (valEl) valEl.textContent = w;
        /* Update Vorschau */
        var previewEl = document.getElementById(fname === 'secondary' ? 'v2-ty-pv-h1' : 'v2-ty-pv-p');
        if (previewEl) previewEl.style.fontWeight = w;
        var fpEl = document.getElementById(fname === 'secondary' ? 'v2-fp-h' : 'v2-fp-body');
        if (fpEl) fpEl.style.fontWeight = w;
        /* Update weight badge in font row */
        var row = document.getElementById('v2-tr-font-' + fname);
        if (row) {
          var badge = row.querySelector('.v2-tr-var[style*="tabular"]');
          if (badge) badge.textContent = w;
        }
        ecfV2ScheduleAutosave && ecfV2ScheduleAutosave();
      });
    });
  }());

  /* ── System-Font-Stack Buttons ──────────────────────────────────────────── */
  (function() {
    document.querySelectorAll('.v2-ty-sys-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var fname = btn.dataset.fontName;
        var stack = btn.dataset.stack;
        var stackInp = document.getElementById('v2-font-stack-' + fname);
        if (stackInp) stackInp.value = stack;
        _ecfV2UpdateFontChip(fname, stack);
        var hidden = document.getElementById('v2-font-val-' + fname);
        if (hidden) hidden.value = stack;
        ecfV2UpdatePagePreview(fname, stack);
        /* update simple preview too */
        var ph = document.getElementById('v2-fp-h');
        var pb = document.getElementById('v2-fp-body');
        var ps = document.getElementById('v2-fp-secondary');
        if (fname === 'secondary') { if (ph) ph.style.fontFamily = stack; }
        else { if (pb) pb.style.fontFamily = stack; if (ps) ps.style.fontFamily = stack; }
        /* variants hint */
        var hint = document.getElementById('v2-ty-variants-' + fname);
        if (hint) { hint.textContent = 'System-Font — kein Google Fonts Download'; hint.style.display = 'block'; }
        ecfV2ScheduleAutosave && ecfV2ScheduleAutosave();
      });
    });
  }());

  /* ── Lesbarkeits-Check ──────────────────────────────────────────────────── */
  (function() {
    function checkReadability(inputId, warnId) {
      var inp  = document.getElementById(inputId);
      var warn = document.getElementById(warnId);
      if (!inp || !warn) return;
      function update() {
        var val = parseFloat(inp.value);
        warn.style.display = (!isNaN(val) && val < 14) ? '' : 'none';
      }
      inp.addEventListener('input', update);
      inp.addEventListener('change', update);
      update();
    }
    checkReadability('v2-sp-min-base', 'v2-ty-read-warn-min');
    checkReadability('v2-sp-max-base', 'v2-ty-read-warn-max');
  }());

  /* ── Varianten-Hinweis bei Font-Auswahl ─────────────────────────────────── */
  function ecfV2ShowVariantsHint(fname, fontName) {
    var hint = document.getElementById('v2-ty-variants-' + fname);
    if (!hint) return;
    if (!fontName || fontName.startsWith('-apple') || fontName.startsWith('Georgia') || fontName.startsWith('Courier')) {
      hint.textContent = 'System-Font — kein Google Fonts Download';
    } else {
      hint.textContent = 'via Google Fonts — ~20–40 KB';
    }
    hint.style.display = 'block';
  }
  window.ecfV2ShowVariantsHint = ecfV2ShowVariantsHint;

  /* ── Tooltip für [data-ty-tip] ─────────────────────────────────────────── */
  (function () {
    var tip = document.createElement('div');
    tip.id = 'v2-ty-tooltip';
    tip.style.cssText = [
      'position:fixed',
      'z-index:99999',
      'pointer-events:none',
      'background:rgba(15,20,30,.92)',
      'color:#e2e8f0',
      'font-size:10.5px',
      'font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace',
      'line-height:1.4',
      'padding:5px 9px',
      'border-radius:5px',
      'border:1px solid rgba(255,255,255,.1)',
      'box-shadow:0 4px 12px rgba(0,0,0,.4)',
      'white-space:nowrap',
      'display:none',
      'transition:opacity .12s',
      'opacity:0',
    ].join(';');
    document.body.appendChild(tip);

    document.addEventListener('mouseover', function (e) {
      var el = e.target.closest('[data-ty-tip]');
      if (!el) return;
      var text = el.getAttribute('data-ty-tip');
      if (!text) return;
      tip.textContent = text;
      tip.style.display = 'block';
      positionTip(e);
      requestAnimationFrame(function () { tip.style.opacity = '1'; });
    });
    document.addEventListener('mousemove', function (e) {
      if (tip.style.display === 'none') return;
      if (!e.target.closest('[data-ty-tip]')) return;
      positionTip(e);
    });
    document.addEventListener('mouseout', function (e) {
      if (!e.target.closest('[data-ty-tip]')) return;
      tip.style.opacity = '0';
      tip.style.display = 'none';
    });

    function positionTip(e) {
      var margin = 8;
      var tw = tip.offsetWidth;
      var th = tip.offsetHeight;
      var x = e.clientX - tw / 2;
      var y = e.clientY - th - 10;
      /* clamp to viewport */
      x = Math.max(margin, Math.min(x, window.innerWidth - tw - margin));
      y = y < margin ? e.clientY + 16 : y;
      tip.style.left = x + 'px';
      tip.style.top  = y + 'px';
    }
  }());

  function ecfV2DeleteLocalFont(family, fname) {
    var confirmMsg = (ecfAdmin.i18n && ecfAdmin.i18n.local_font_delete_confirm)
      ? ecfAdmin.i18n.local_font_delete_confirm.replace('%s', family)
      : 'Delete "' + family + '"?';
    if (!confirm(confirmMsg)) return;
    fetch(ecfAdmin.restUrl, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var settings = data.settings || {};
        var fonts = ((settings.typography || {}).local_fonts || []);
        settings.typography = settings.typography || {};
        settings.typography.local_fonts = fonts.filter(function(f) {
          return (f.family || '').replace(/^'|'$/g, '').trim().toLowerCase() !== family.toLowerCase();
        });
        return fetch(ecfAdmin.restUrl, {
          method: 'POST',
          headers: { 'X-WP-Nonce': ecfAdmin.restNonce, 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
          body: JSON.stringify({ settings: settings }),
        });
      })
      .then(function(r) { return r.json(); })
      .then(function() {
        var searchInp = document.getElementById('v2-fi-search-' + fname);
        ecfV2SearchFontInline(searchInp ? searchInp.value : '', fname);
      });
  }
  window.ecfV2DeleteLocalFont = ecfV2DeleteLocalFont;

  function ecfV2ToggleFontFavorite(family, fname) {
    fetch(ecfAdmin.restUrl, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var settings = data.settings || {};
        settings.typography = settings.typography || {};
        var favs = (settings.typography.font_favorites || []).slice();
        var idx = favs.findIndex(function(f) { return f.toLowerCase() === family.toLowerCase(); });
        if (idx === -1) { favs.push(family); } else { favs.splice(idx, 1); }
        settings.typography.font_favorites = favs;
        return fetch(ecfAdmin.restUrl, {
          method: 'POST',
          headers: { 'X-WP-Nonce': ecfAdmin.restNonce, 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
          body: JSON.stringify({ settings: settings }),
        });
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.settings && data.settings.typography) {
          ecfAdmin.fontFavorites = data.settings.typography.font_favorites || [];
        }
        var searchInp = document.getElementById('v2-fi-search-' + fname);
        ecfV2SearchFontInline(searchInp ? searchInp.value : '', fname);
      });
  }
  window.ecfV2ToggleFontFavorite = ecfV2ToggleFontFavorite;

  /* ── Local fonts list ────────────────────────────────────────────────── */
  var _localFonts = (window.ecfAdmin && Array.isArray(ecfAdmin.localFonts)) ? ecfAdmin.localFonts.slice() : [];

  function ecfV2RenderLocalFonts() {
    var list = document.getElementById('v2-lf-list');
    var section = document.getElementById('v2-lf-section');
    if (!list) return;
    if (!_localFonts.length) {
      list.innerHTML = '<p class="v2-lf-empty">' + escapeHtml((ecfAdmin.i18n && ecfAdmin.i18n.no_local_fonts) || 'Noch keine eigenen Schriften hochgeladen.') + '</p>';
      if (section) section.style.display = 'none';
      return;
    }
    if (section) section.style.display = '';
    var grouped = {};
    _localFonts.forEach(function(f) {
      var fam = (f.family || '').trim();
      if (!fam) return;
      if (!grouped[fam]) grouped[fam] = [];
      grouped[fam].push(f);
    });
    list.innerHTML = '';
    Object.keys(grouped).forEach(function(family) {
      var row = document.createElement('div');
      row.className = 'v2-lf-row';
      var left = document.createElement('div');
      left.className = 'v2-lf-left';
      var preview = document.createElement('span');
      preview.className = 'v2-lf-aa';
      preview.textContent = 'Aa';
      preview.style.fontFamily = "'" + family + "',sans-serif";
      var info = document.createElement('div');
      info.className = 'v2-lf-info';
      var nameEl = document.createElement('span');
      nameEl.className = 'v2-lf-name';
      nameEl.textContent = family;
      var variants = grouped[family].map(function(f) {
        return (f.weight || '400') + (f.style === 'italic' ? 'i' : '');
      }).join(' · ');
      var varEl = document.createElement('span');
      varEl.className = 'v2-lf-var';
      varEl.textContent = variants;
      info.appendChild(nameEl);
      info.appendChild(varEl);
      left.appendChild(preview);
      left.appendChild(info);
      row.appendChild(left);
      var delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'v2-lf-del';
      delBtn.title = (ecfAdmin.i18n && ecfAdmin.i18n.delete) || 'Löschen';
      delBtn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
      delBtn.addEventListener('click', function() {
        ecfV2DeleteLocalFontByFamily(family);
      });
      row.appendChild(delBtn);
      list.appendChild(row);
    });
  }
  window.ecfV2RenderLocalFonts = ecfV2RenderLocalFonts;

  function ecfV2DeleteLocalFontByFamily(family) {
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var msg = (i18n.local_font_delete_confirm || 'Schrift "%s" löschen?').replace('%s', family);
    if (!confirm(msg)) return;
    fetch(ecfAdmin.restUrl, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var settings = data.settings || {};
        settings.typography = settings.typography || {};
        settings.typography.local_fonts = (settings.typography.local_fonts || []).filter(function(f) {
          return (f.family || '').trim().toLowerCase() !== family.toLowerCase();
        });
        _localFonts = settings.typography.local_fonts.slice();
        return fetch(ecfAdmin.restUrl, {
          method: 'POST',
          headers: { 'X-WP-Nonce': ecfAdmin.restNonce, 'Content-Type': 'application/json' },
          body: JSON.stringify({ settings: settings }),
        });
      })
      .then(function() { ecfV2RenderLocalFonts(); })
      .catch(function() {});
  }
  window.ecfV2DeleteLocalFontByFamily = ecfV2DeleteLocalFontByFamily;

  /* ── Font file upload ─────────────────────────────────────────────────── */
  (function() {
    var pickBtn  = document.getElementById('v2-uf-pick');
    var addBtn   = document.getElementById('v2-uf-add');
    var urlInp   = document.getElementById('v2-uf-url');
    var familyInp= document.getElementById('v2-uf-family');
    var weightSel= document.getElementById('v2-uf-weight');
    var styleSel = document.getElementById('v2-uf-style');
    var msg      = document.getElementById('v2-uf-msg');
    var fileInp  = document.getElementById('v2-uf-file');
    var dropArea = document.getElementById('v2-uf-drop');
    if (!addBtn) return;

    function showMsg(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.style.color = isError ? 'var(--v2-danger,#ef4444)' : 'var(--v2-success,#22c55e)';
      msg.style.display = 'block';
    }

    function nameFromFilename(filename) {
      return filename
        .replace(/[-_](regular|bold|light|medium|black|thin|\d{3}|normal|italic|oblique).*/i, '')
        .replace(/\.(woff2?|ttf|otf)$/i, '')
        .replace(/[-_]/g, ' ')
        .trim()
        .replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    }

    /* Upload file to WP Media Library via async-upload.php */
    function uploadFontFile(file, cb) {
      var fd = new FormData();
      fd.append('name', file.name);
      fd.append('action', 'upload-attachment');
      fd.append('_wpnonce', (window.ecfAdmin && ecfAdmin.mediaUploadNonce) || '');
      fd.append('async-upload', file);
      fetch((window.ecfAdmin && ecfAdmin.mediaUploadUrl) || '', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data && data.success && data.data && data.data.url) {
          cb(null, data.data.url, data.data.filename || file.name);
        } else {
          cb(new Error((data && data.data && data.data.message) || 'Upload failed'));
        }
      })
      .catch(cb);
    }

    /* Handle file selection (drag-drop or file input) */
    function handleFile(file) {
      if (!file) return;
      var allowed = /\.(ttf|otf|woff|woff2)$/i;
      if (!allowed.test(file.name)) {
        showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_invalid_format) || 'Nicht unterstütztes Format. Bitte TTF, OTF, WOFF oder WOFF2 wählen.', true);
        return;
      }
      if (dropArea) dropArea.classList.add('v2-uf-drop--busy');
      showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_uploading) || 'Schrift wird hochgeladen…', false);
      uploadFontFile(file, function(err, url, filename) {
        if (dropArea) dropArea.classList.remove('v2-uf-drop--busy');
        if (err) {
          showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_upload_error) || 'Upload fehlgeschlagen. Bitte prüfe die Dateiberechtigungen.', true);
          return;
        }
        if (urlInp) urlInp.value = url;
        if (familyInp && !familyInp.value) familyInp.value = nameFromFilename(filename);
        showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_uploaded_pick) || 'Hochgeladen! Familienname prüfen und „Schrift speichern" klicken.', false);
      });
    }

    /* Drag-and-drop */
    if (dropArea) {
      dropArea.addEventListener('dragover', function(e) { e.preventDefault(); dropArea.classList.add('v2-uf-drop--over'); });
      dropArea.addEventListener('dragleave', function()  { dropArea.classList.remove('v2-uf-drop--over'); });
      dropArea.addEventListener('drop', function(e) {
        e.preventDefault();
        dropArea.classList.remove('v2-uf-drop--over');
        var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        handleFile(file);
      });
    }

    /* File input change */
    if (fileInp) {
      fileInp.addEventListener('change', function() {
        handleFile(fileInp.files && fileInp.files[0]);
        fileInp.value = '';
      });
    }

    /* Media Library picker */
    if (pickBtn) {
      pickBtn.addEventListener('click', function() {
        if (!window.wp || !wp.media) {
          showMsg((ecfAdmin.i18n && ecfAdmin.i18n.media_library_unavailable) || 'Mediathek nicht verfügbar.', true);
          return;
        }
        var frame = wp.media({
          title: (ecfAdmin.i18n && ecfAdmin.i18n.choose_font) || 'Schriftdatei wählen',
          button: { text: (ecfAdmin.i18n && ecfAdmin.i18n.use_font) || 'Diese Datei verwenden' },
          library: { type: ['application/font-woff','font/woff','font/woff2','font/ttf','font/otf','application/octet-stream'] },
          multiple: false,
        });
        frame.on('select', function() {
          var att = frame.state().get('selection').first().toJSON();
          if (urlInp) urlInp.value = att.url || '';
          if (familyInp && !familyInp.value && att.filename) {
            familyInp.value = nameFromFilename(att.filename);
          }
        });
        frame.open();
      });
    }

    addBtn.addEventListener('click', function() {
      var url    = (urlInp ? urlInp.value : '').trim();
      var family = (familyInp ? familyInp.value : '').trim();
      var weight = weightSel ? weightSel.value : '400';
      var style  = styleSel  ? styleSel.value  : 'normal';
      if (!url)    { showMsg((ecfAdmin.i18n && ecfAdmin.i18n.select_file)             || 'Bitte eine Datei-URL eingeben oder hochladen.', true); return; }
      if (!family) { showMsg((ecfAdmin.i18n && ecfAdmin.i18n.local_font_name_required) || 'Bitte Familienname eingeben.', true); return; }
      addBtn.disabled = true;
      fetch(ecfAdmin.restUrl, { headers: { 'X-WP-Nonce': ecfAdmin.restNonce } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          var settings = data.settings || {};
          settings.typography = settings.typography || {};
          settings.typography.local_fonts = settings.typography.local_fonts || [];
          var slug = family.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
          var entry = { name: slug + '-' + weight, family: family, src: url, weight: weight, style: style, display: 'swap' };
          settings.typography.local_fonts.push(entry);
          _localFonts = settings.typography.local_fonts.slice();
          return fetch(ecfAdmin.restUrl, {
            method: 'POST',
            headers: { 'X-WP-Nonce': ecfAdmin.restNonce, 'Content-Type': 'application/json' },
            body: JSON.stringify({ settings: settings }),
          });
        })
        .then(function(r) { return r.json(); })
        .then(function() {
          showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_upload_success) || 'Schrift gespeichert.', false);
          if (urlInp) urlInp.value = '';
          if (familyInp) familyInp.value = '';
          addBtn.disabled = false;
          ecfV2RenderLocalFonts();
        })
        .catch(function() {
          showMsg((ecfAdmin.i18n && ecfAdmin.i18n.font_import_failed) || 'Schrift konnte nicht gespeichert werden.', true);
          addBtn.disabled = false;
        });
    });

    /* Initial render */
    ecfV2RenderLocalFonts();
  }());

  /* ── Font pairing category filter ────────────────────────────────────── */
  function ecfV2FilterPairings(cat, btn) {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('.v2-fp-card').forEach(function(c) {
      c.style.display = (cat === 'all' || c.dataset.category === cat) ? '' : 'none';
    });
    if (btn) {
      btn.closest('.v2-tabs').querySelectorAll('.v2-tab').forEach(function(t) { t.classList.remove('v2-tab--on'); });
      btn.classList.add('v2-tab--on');
    }
  }
  window.ecfV2FilterPairings = ecfV2FilterPairings;

  /* ── Preset category filter ───────────────────────────────────────────── */
  function ecfV2FilterPresets(cat, btn) {
    var w = wrap();
    if (!w) return;
    w.querySelectorAll('.v2-preset-card').forEach(function(c) {
      c.style.display = (cat === 'alle' || c.dataset.category === cat) ? '' : 'none';
    });
    if (btn) {
      btn.closest('.v2-tabs').querySelectorAll('.v2-tab').forEach(function(t) { t.classList.remove('v2-tab--on'); });
      btn.classList.add('v2-tab--on');
    }
  }
  window.ecfV2FilterPresets = ecfV2FilterPresets;

  /* ── Wizard ─────────────────────────────────────────────────────────── */
  var _wizardKey   = 'ecfV2WizardDone';
  var _wizardStep  = 0;
  var _wizardCallout = null;
  var _wizardNavPulse = null;
  var _wi = (ecfAdmin && ecfAdmin.i18n) ? ecfAdmin.i18n : {};
  var _wizardSteps = [
    { mode: 'modal',   page: null,         title: _wi.wiz_title_welcome  || 'Willkommen bei Layrix',      body: _wi.wiz_body_welcome  || 'Layrix verwaltet dein Design-System zentral und synct es zu Elementor. Diese Tour zeigt alle Bereiche — von den Tokens bis zum fertigen Widget im Editor.', next: _wi.wiz_next_start || 'Los geht\'s' },
    { mode: 'callout', page: 'colors',     title: _wi.wiz_title_colors   || 'Farben & Radius',            body: _wi.wiz_body_colors   || 'Markenfarben und Eckenradien definieren. Schnellstart: ein <strong>Stil-Preset</strong> anwenden.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'typography', title: _wi.wiz_title_typo     || 'Typografie',                 body: _wi.wiz_body_typo     || 'Schriftfamilien, Schriftgrößen-Skala und Zeilenhöhen festlegen.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'spacing',    title: _wi.wiz_title_spacing  || 'Abstände',                   body: _wi.wiz_body_spacing  || 'Spacing-System definieren — Basis-Abstände und Rhythmus. Layrix generiert die komplette Skala.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'shadows',    title: _wi.wiz_title_shadows  || 'Schatten',                   body: _wi.wiz_body_shadows  || 'Wiederverwendbare Schatten-Tokens — von dezent bis prominent.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'variables',  title: _wi.wiz_title_vars     || 'Variablen',                  body: _wi.wiz_body_vars     || 'Eigene CSS-Variablen für alles was nicht von Layrix abgedeckt ist (Animation-Dauern, z-Index etc.).', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'classes',    title: _wi.wiz_title_classes  || 'Klassen-Auswahl',            body: _wi.wiz_body_classes  || 'Globale Elementor-Klassen für wiederkehrende Styles. Aus der Library wählen oder eigene erzeugen.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'settings',   title: 'Klassen-Defaults',                                     body: '<strong>Plugin → Klassen-Defaults</strong>: Layrix-Werte für Button, Heading, Section und Container feinabstimmen — bestimmt was die Klassen automatisch tun (Padding, Schriftgröße, Eckenradius).',  next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'settings',   title: 'Auto-Klassen',                                         body: '<strong>Plugin → Allgemein → Auto-Klassen</strong>: Toggle aktivieren — neu eingefügte h1-h5, Buttons und Text-Links bekommen automatisch ihre Layrix-Klasse, sobald sie in Elementor reinkommen.', next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'sync',       title: _wi.wiz_title_sync     || 'Sync mit Elementor',         body: _wi.wiz_body_sync     || 'Alle Tokens und Klassen zu Elementor übertragen. Ab jetzt im Editor als globale Variablen und Klassen verfügbar.',                                                                  next: _wi.wiz_next || 'Weiter' },
    { mode: 'callout', page: 'cookbook',   title: 'Anwendung',                                            body: 'Karten-Übersicht aller Layrix-Klassen pro Kategorie. Klick auf eine Karte kopiert die Klasse — im Elementor-Editor ins CSS-Klassen-Feld einfügen.',                                                       next: _wi.wiz_next_done || 'Fertig' },
    { mode: 'toast',   page: null,         title: _wi.wiz_title_ready    || 'Du bist startklar!',        body: _wi.wiz_body_ready    || 'Alle Bereiche durch — Tokens definiert, Klassen gesynct, Auto-Klassen aktiv. Jetzt im Elementor-Editor bauen.' }
  ];

  function _wizardDots(activeIdx) {
    return _wizardSteps
      .filter(function(s) { return s.mode !== 'toast'; })
      .map(function(_, i) {
        return '<span class="v2-wiz-dot' + (i === activeIdx ? ' is-active' : '') + '"></span>';
      }).join('');
  }

  function _wizardCloseCallout() {
    if (_wizardCallout) { _wizardCallout.remove(); _wizardCallout = null; }
    if (_wizardNavPulse) { _wizardNavPulse.classList.remove('v2-wiz-nav-pulse'); _wizardNavPulse = null; }
    document.removeEventListener('keydown', _wizardEsc);
  }

  function _wizardEsc(e) {
    if (e.key === 'Escape') {
      try { sessionStorage.setItem(_wizardKey, '1'); } catch(ex) {}
      _wizardCloseCallout();
      var ov = document.querySelector('.v2-wiz-overlay');
      if (ov) ov.remove();
    }
  }

  function _wizardEnableDrag(callout) {
    var handle = callout.querySelector('.v2-wiz-callout__drag');
    if (!handle) return;
    var dragging = false, startX = 0, startY = 0, origLeft = 0, origTop = 0;
    handle.addEventListener('mousedown', function(e) {
      e.preventDefault();
      dragging = true;
      var rect = callout.getBoundingClientRect();
      startX = e.clientX; startY = e.clientY;
      origLeft = rect.left; origTop = rect.top;
      callout.style.transform = 'none';
      callout.dataset.dragged = '1';
      document.body.style.userSelect = 'none';
    });
    document.addEventListener('mousemove', function(e) {
      if (!dragging) return;
      var nx = origLeft + (e.clientX - startX);
      var ny = origTop  + (e.clientY - startY);
      /* Im Viewport halten (kleiner Rand) */
      var w = callout.offsetWidth, h = callout.offsetHeight;
      nx = Math.max(8, Math.min(window.innerWidth  - w - 8, nx));
      ny = Math.max(8, Math.min(window.innerHeight - h - 8, ny));
      callout.style.left = nx + 'px';
      callout.style.top  = ny + 'px';
      /* Pfeil ausblenden, sobald manuell verschoben — er zeigt sonst irgendwohin */
      var arrow = callout.querySelector('.v2-wiz-callout__arrow');
      if (arrow) arrow.style.display = 'none';
    });
    document.addEventListener('mouseup', function() {
      if (!dragging) return;
      dragging = false;
      document.body.style.userSelect = '';
    });
  }

  function _wizardPositionCallout(callout, anchor) {
    /* Callout immer oben im Topbar-Bereich, rechts neben der Nav. So
       überdeckt es nie die Page-Überschrift oder den Content. Der Pfeil
       zeigt nach links unten zum gepulsten Nav-Item. */
    var rect = anchor.getBoundingClientRect();
    var left = rect.right + 14;
    var top  = 16;
    callout.style.transform = 'none';
    callout.style.top  = top + 'px';
    callout.style.left = left + 'px';
    /* Pfeil dynamisch positionieren: vertikal so, dass er auf das Nav-Item zeigt. */
    var arrow = callout.querySelector('.v2-wiz-callout__arrow');
    if (arrow) {
      var ch = callout.offsetHeight || 200;
      var navCenter = rect.top + rect.height / 2;
      var arrowOffset = Math.max(16, Math.min(navCenter - top, ch - 20));
      arrow.style.top = arrowOffset + 'px';
      arrow.style.transform = 'rotate(45deg)';
    }
  }

  function _wizardShowStep(idx) {
    var step = _wizardSteps[idx];
    if (!step) return;
    _wizardStep = idx;

    if (step.mode === 'modal') {
      _wizardCloseCallout();
      if (document.querySelector('.v2-wiz-overlay')) return;
      var ov = document.createElement('div');
      ov.className = 'v2-wiz-overlay';
      ov.setAttribute('aria-modal', 'true');
      ov.setAttribute('role', 'dialog');
      var modal = document.createElement('div');
      modal.className = 'v2-wiz';
      modal.innerHTML = '<button type="button" class="v2-wiz__close" aria-label="' + (_wi.close || 'Close') + '">×</button>'
        + '<div class="v2-wiz__steps">' + _wizardDots(idx) + '</div>'
        + '<div class="v2-wiz__title"></div>'
        + '<div class="v2-wiz__body"></div>'
        + '<div class="v2-wiz__footer"><button type="button" class="v2-btn v2-btn--primary v2-wiz__next"></button></div>';
      modal.querySelector('.v2-wiz__title').textContent = step.title;
      modal.querySelector('.v2-wiz__body').textContent  = step.body;
      modal.querySelector('.v2-wiz__next').textContent  = step.next;
      ov.appendChild(modal);
      document.body.appendChild(ov);
      modal.querySelector('.v2-wiz__next').addEventListener('click', function() { ov.remove(); _wizardShowStep(idx + 1); });
      modal.querySelector('.v2-wiz__close').addEventListener('click', function() {
        try { sessionStorage.setItem(_wizardKey, '1'); } catch(ex) {}
        ov.remove();
      });
      document.addEventListener('keydown', _wizardEsc);

    } else if (step.mode === 'callout') {
      ecfV2Go(step.page);
      _wizardCloseCallout();
      var anchor = (wrap() || document).querySelector('.v2-ni[data-v2-page="' + step.page + '"]');
      if (!anchor) { _wizardShowStep(idx + 1); return; }
      anchor.classList.add('v2-wiz-nav-pulse');
      _wizardNavPulse = anchor;
      var cl = document.createElement('div');
      cl.className = 'v2-wiz-callout';
      cl.setAttribute('role', 'dialog');
      var hasBack = idx > 0;
      cl.innerHTML = '<div class="v2-wiz-callout__arrow"></div>'
        + '<div class="v2-wiz-callout__drag" title="' + (_wi.wiz_drag || 'Zum Verschieben ziehen') + '">'
        + '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><circle cx="5" cy="4" r="1.2"/><circle cx="11" cy="4" r="1.2"/><circle cx="5" cy="8" r="1.2"/><circle cx="11" cy="8" r="1.2"/><circle cx="5" cy="12" r="1.2"/><circle cx="11" cy="12" r="1.2"/></svg>'
        + '</div>'
        + '<div class="v2-wiz-callout__steps">' + _wizardDots(idx) + '</div>'
        + '<strong class="v2-wiz-callout__title"></strong>'
        + '<p class="v2-wiz-callout__body"></p>'
        + '<div class="v2-wiz-callout__footer">'
        + (hasBack ? '<button type="button" class="v2-btn v2-btn--ghost v2-wiz-callout__back">' + (_wi.wiz_back || 'Zurück') + '</button>' : '')
        + '<span class="v2-wiz-callout__spacer"></span>'
        + '<button type="button" class="v2-btn v2-btn--ghost v2-wiz-callout__skip">' + (_wi.wiz_skip || 'Überspringen') + '</button>'
        + '<button type="button" class="v2-btn v2-btn--primary v2-wiz-callout__next"></button>'
        + '</div>';
      cl.querySelector('.v2-wiz-callout__title').textContent = step.title;
      cl.querySelector('.v2-wiz-callout__body').innerHTML    = step.body;
      cl.querySelector('.v2-wiz-callout__next').textContent  = step.next;
      document.body.appendChild(cl);
      _wizardCallout = cl;
      _wizardPositionCallout(cl, anchor);
      window.addEventListener('resize', function() {
        if (_wizardCallout && !_wizardCallout.dataset.dragged) _wizardPositionCallout(cl, anchor);
      });
      document.addEventListener('keydown', _wizardEsc);
      cl.querySelector('.v2-wiz-callout__next').addEventListener('click', function() { _wizardShowStep(idx + 1); });
      cl.querySelector('.v2-wiz-callout__skip').addEventListener('click', function() {
        try { sessionStorage.setItem(_wizardKey, '1'); } catch(ex) {}
        _wizardCloseCallout();
      });
      if (hasBack) {
        cl.querySelector('.v2-wiz-callout__back').addEventListener('click', function() { _wizardShowStep(idx - 1); });
      }
      _wizardEnableDrag(cl);

    } else if (step.mode === 'toast') {
      _wizardCloseCallout();
      try { sessionStorage.setItem(_wizardKey, '1'); } catch(ex) {}
      var toast = document.createElement('div');
      toast.className = 'v2-wiz-toast';
      toast.innerHTML = '<span class="v2-wiz-toast__icon">🎉</span>'
        + '<div><strong></strong><span></span></div>'
        + '<button type="button" class="v2-wiz-toast__close" aria-label="' + (_wi.close || 'Close') + '">×</button>';
      toast.querySelector('strong').textContent = step.title;
      toast.querySelector('span:last-of-type').textContent  = step.body;
      document.body.appendChild(toast);
      setTimeout(function() { toast.classList.add('is-visible'); }, 50);
      var hideToast = function() {
        toast.classList.remove('is-visible');
        setTimeout(function() { toast.remove(); }, 400);
      };
      setTimeout(hideToast, 4000);
      toast.querySelector('.v2-wiz-toast__close').addEventListener('click', hideToast);
    }
  }

  function ecfV2StartWizard() {
    try { sessionStorage.removeItem(_wizardKey); } catch(ex) {}
    _wizardStep = 0;
    _wizardCloseCallout();
    var ov = document.querySelector('.v2-wiz-overlay');
    if (ov) ov.remove();
    _wizardShowStep(0);
  }
  window.ecfV2StartWizard = ecfV2StartWizard;

  /* ── Preset Selection Modal ──────────────────────────────────────────── */
  function ecfPresetModal(opts) {
    var payload  = opts.payload  || {};
    var gfH      = opts.gfHeading || '';
    var gfB      = opts.gfBody    || '';
    var onApply  = opts.onApply;
    var i18n     = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var g        = payload.general || {};
    var colors   = payload.colors  || {};
    var radius   = payload.radius  || {};
    var shadows  = payload.shadows || {};
    var spacing  = payload.spacing || {};
    var fonts    = payload.fonts   || {};

    var colorList = [];
    if (Array.isArray(colors)) {
      colors.forEach(function(r) { if (r.name && r.value) colorList.push({name:r.name, hex:r.value}); });
    } else {
      Object.keys(colors).forEach(function(n) { if (colors[n]) colorList.push({name:n, hex:colors[n]}); });
    }

    var headingFont = gfH || fonts.secondary || '';
    var bodyFont    = gfB || fonts.primary   || '';

    var sections = [];
    if (colorList.length) {
      var sw = colorList.slice(0,10).map(function(c){
        return '<span class="ecf-pm-swatch" style="background:'+c.hex+'" title="'+c.name+'"></span>';
      }).join('');
      sections.push({key:'colors', label:i18n.pm_colors||'Farben',
        detail:colorList.length+' '+(i18n.pm_tokens||'Token'), extra:'<div class="ecf-pm-swatches">'+sw+'</div>'});
    }
    if (headingFont) sections.push({key:'headingFont', label:i18n.pm_heading_font||'Überschrift-Schrift',
      detail:headingFont.split(',')[0].replace(/['"]/g,'').trim()});
    if (bodyFont)    sections.push({key:'bodyFont', label:i18n.pm_body_font||'Fließtext-Schrift',
      detail:bodyFont.split(',')[0].replace(/['"]/g,'').trim()});
    if (g.base_body_text_size) sections.push({key:'textSize', label:i18n.pm_text_size||'Fließtext-Größe',
      detail:g.base_body_text_size});
    if (g.base_text_color || g.base_background_color || g.link_color) {
      var bsw = [g.base_background_color, g.base_text_color, g.link_color].filter(Boolean).map(function(c){
        return '<span class="ecf-pm-swatch" style="background:'+c+'"></span>';
      }).join('');
      sections.push({key:'baseColors', label:i18n.pm_base_colors||'Basis-Farben',
        detail:i18n.pm_base_colors_detail||'Hintergrund, Text, Links',
        extra:'<div class="ecf-pm-swatches">'+bsw+'</div>'});
    }
    var cwRaw = g.content_max_width;
    if (cwRaw) {
      var cwVal = (typeof cwRaw === 'object') ? (cwRaw.value+(cwRaw.format||'')) : cwRaw;
      sections.push({key:'siteWidth', label:i18n.pm_site_width||'Website-Breite', detail:cwVal});
    }
    var rKeys = Array.isArray(radius) ? radius.length : Object.keys(radius).length;
    if (rKeys) sections.push({key:'radius', label:i18n.pm_radius||'Eckenradien',
      detail:rKeys+' '+(i18n.pm_tokens||'Token')});
    var shKeys = Array.isArray(shadows) ? shadows.length : Object.keys(shadows).length;
    if (shKeys) sections.push({key:'shadows', label:i18n.pm_shadows||'Schatten',
      detail:shKeys+' '+(i18n.pm_tokens||'Token')});
    if (Object.keys(spacing).length) sections.push({key:'spacing', label:i18n.pm_spacing||'Abstände',
      detail:i18n.pm_spacing_detail||'Fluid-Abstands-Skala'});

    var rows = sections.map(function(s){
      return '<label class="ecf-pm-section">'
        +'<input type="checkbox" class="ecf-pm-chk" data-key="'+s.key+'" checked>'
        +'<div class="ecf-pm-section__info">'
        +'<strong>'+s.label+'</strong>'
        +'<span>'+s.detail+'</span>'
        +(s.extra||'')
        +'</div></label>';
    }).join('');

    var title = opts.title || '';
    var desc  = opts.description || '';
    var el = document.createElement('div');
    el.innerHTML = '<div class="ecf-pm-backdrop">'
      +'<div class="ecf-pm" role="dialog" aria-modal="true">'
      +'<div class="ecf-pm-header"><div>'
      +'<h2>'+(i18n.pm_title||'Preset anwenden')+'</h2>'
      +(title ? '<p class="ecf-pm-subtitle">'+title+(desc?' — '+desc:'')+'</p>' : '')
      +'</div><button type="button" class="ecf-pm-close" aria-label="Schließen">×</button></div>'
      +'<div class="ecf-pm-body"><p class="ecf-pm-hint">'+(i18n.pm_hint||'Wähle aus, was du übernehmen möchtest:')+'</p>'
      +rows+'</div>'
      +'<div class="ecf-pm-footer">'
      +'<button type="button" class="ecf-pm-btn--ghost ecf-pm-cancel">'+(i18n.pm_cancel||'Abbrechen')+'</button>'
      +'<button type="button" class="ecf-pm-btn--primary ecf-pm-apply">'+(i18n.pm_apply||'Anwenden')+'</button>'
      +'</div></div></div>';
    var modal = el.firstChild;
    document.body.appendChild(modal);

    function close() { if (modal.parentNode) modal.parentNode.removeChild(modal); }
    modal.querySelector('.ecf-pm-close').addEventListener('click', close);
    modal.querySelector('.ecf-pm-cancel').addEventListener('click', close);
    modal.addEventListener('click', function(e){ if (e.target === modal) close(); });
    modal.querySelector('.ecf-pm-apply').addEventListener('click', function(){
      var filter = {};
      modal.querySelectorAll('.ecf-pm-chk').forEach(function(chk){ filter[chk.dataset.key] = chk.checked; });
      close();
      if (typeof onApply === 'function') onApply(filter);
    });
    modal.querySelector('.ecf-pm-apply').focus();
  }

  /* ── Apply Preset ────────────────────────────────────────────────────── */
  function ecfV2ApplyPreset(payload, btn, gfHeading, gfBody, filter) {
    if (!payload) return;
    var i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
    var f = filter || {};

    if (btn) { btn.disabled = true; btn.textContent = i18n.preset_applying || ''; }

    /* General settings */
    var g = payload.general || {};
    var w = wrap();
    if (!w) return;
    function setInp(name, val) {
      if (val === undefined || val === null) return;
      var el = w.querySelector('[name*="[' + name + ']"]');
      if (!el) return;
      el.value = val;
    }
    if (f.textSize !== false) {
      setInp('root_font_size', g.root_font_size);
      setInp('base_body_text_size', g.base_body_text_size);
      setInp('base_body_font_weight', g.base_body_font_weight);
    }
    if (f.baseColors !== false) {
      setInp('base_text_color', g.base_text_color);
      setInp('base_background_color', g.base_background_color);
      setInp('link_color', g.link_color);
      setInp('focus_color', g.focus_color);
    }

    /* Container / max-width */
    if (f.siteWidth !== false) {
      if (g.elementor_boxed_width) {
        var bwVal = typeof g.elementor_boxed_width === 'object' ? g.elementor_boxed_width.value : g.elementor_boxed_width;
        setInp('elementor_boxed_width', bwVal);
      }
      if (g.content_max_width) {
        var mwVal = typeof g.content_max_width === 'object' ? g.content_max_width.value : g.content_max_width;
        setInp('content_max_width', mwVal);
      }
    }

    /* Color tokens — handle both {name:hex} (built-in) and [{name,value}] (custom/import) */
    if (f.colors !== false) {
    var colors = payload.colors || {};
    var colorList = [];
    if (Array.isArray(colors)) {
      colors.forEach(function(row) {
        if (row.name && row.value) colorList.push({ name: row.name, hex: row.value });
      });
    } else {
      Object.keys(colors).forEach(function(n) {
        if (colors[n]) colorList.push({ name: n, hex: colors[n] });
      });
    }

    var colorTl    = w.querySelector('[data-v2-tl-color]');
    var conflicts  = [];

    colorList.forEach(function(c) {
      var cname = c.name;
      var chex  = c.hex;
      var hidden = w.querySelector('#v2-val-' + cname);

      if (hidden) {
        /* Existing named color row — update in place */
        ecfV2LiveColor(cname, chex);
        hidden.value = chex;
      } else {
        /* Check if a manually-added (unsaved) row already uses this name */
        var nameCollision = false;
        if (colorTl) {
          colorTl.querySelectorAll('input[type="text"][name*="[colors]"][name*="[name]"]').forEach(function(inp) {
            if (inp.value === cname) nameCollision = true;
          });
        }
        if (nameCollision) {
          conflicts.push(cname);
          return;
        }
        /* Add new color row with preset values */
        if (colorTl) {
          var beforeCount = colorTl.querySelectorAll('.v2-tr').length;
          ecfV2AddRow('color');
          var newId  = 'new' + beforeCount;
          var newRow = w.querySelector('#v2-tr-' + newId);
          if (newRow) {
            var nameInp = newRow.querySelector('input[type="text"][name*="[name]"]');
            if (nameInp) nameInp.value = cname;
            var evar = newRow.querySelector('#v2-evar-' + newId);
            if (evar) evar.textContent = cname;
          }
          ecfV2LiveColor(newId, chex);
          var newHidden = w.querySelector('#v2-val-' + newId);
          if (newHidden) newHidden.value = chex;
        }
      }
    });

    if (conflicts.length) {
      ecfV2Toast((i18n.preset_color_conflict || '').replace('%s', conflicts.join(', ')), 'error');
    }
    } /* end f.colors */

    /* Fonts */
    var fonts = payload.fonts || {};
    if (f.bodyFont !== false    && fonts.primary)   setInp('primary][value', fonts.primary);
    if (f.headingFont !== false && fonts.secondary)  setInp('secondary][value', fonts.secondary);

    /* Update font preview */
    var fpH = document.getElementById('v2-fp-h');
    var fpB = document.getElementById('v2-fp-body');
    var fpS = document.getElementById('v2-fp-secondary');
    if (f.headingFont !== false && fpH && fonts.secondary) fpH.style.fontFamily = fonts.secondary;
    if (f.bodyFont    !== false && fpB && fonts.primary)   fpB.style.fontFamily = fonts.primary;
    if (f.headingFont !== false && fpS && fonts.secondary) fpS.style.fontFamily = fonts.secondary;

    /* Spacing params */
    if (f.spacing !== false) {
      var sp = payload.spacing || {};
      ['min_base','max_base','min_ratio','max_ratio','base_index','min_vw','max_vw'].forEach(function(key) {
        if (sp[key] === undefined) return;
        /* Use [spacing][key] to avoid matching [typography][scale][key] */
        var el = w.querySelector('[name*="[spacing][' + key + ']"]');
        if (el) el.value = sp[key];
      });
    }

    /* ── Radius tokens — update existing, add missing ────────────────── */
    if (f.radius !== false) (function() {
      var raw = payload.radius || {};
      var list = [];
      if (Array.isArray(raw)) {
        raw.forEach(function(r) { if (r.name) list.push(r); });
      } else {
        Object.keys(raw).forEach(function(n) {
          var v = raw[n];
          if (typeof v === 'object') list.push({ name: n, min: v.min || '', max: v.max || '' });
          else list.push({ name: n, min: v, max: v });
        });
      }
      if (!list.length) return;
      var tl = w.querySelector('[data-v2-tl-radius]');
      if (!tl) return;
      list.forEach(function(r) {
        /* find existing row by name input value (PHP=hidden, JS-added=text) */
        var found = null;
        tl.querySelectorAll('.v2-tr').forEach(function(tr) {
          var ni = tr.querySelector('input[name*="[radius]"][name*="[name]"]');
          if (ni && ni.value === r.name) found = tr;
        });
        if (found) {
          var mi = found.querySelector('input[name*="[radius]"][name*="[min]"]');
          var ma = found.querySelector('input[name*="[radius]"][name*="[max]"]');
          if (mi) mi.value = r.min || '';
          if (ma) ma.value = r.max || '';
        } else {
          /* collision check */
          var collision = false;
          tl.querySelectorAll('input[name*="[radius]"][name*="[name]"]').forEach(function(ni) {
            if (ni.value === r.name) collision = true;
          });
          if (collision) return;
          ecfV2AddRow('radius');
          var newRow = tl.lastElementChild;
          if (newRow) {
            var ni2 = newRow.querySelector('input[type="text"][name*="[name]"]');
            var mi2 = newRow.querySelector('input[name*="[min]"]');
            var ma2 = newRow.querySelector('input[name*="[max]"]');
            if (ni2) ni2.value = r.name;
            if (mi2) mi2.value = r.min || '';
            if (ma2) ma2.value = r.max || '';
          }
        }
      });
    }());

    /* ── Shadow tokens — update existing, add missing ────────────────── */
    if (f.shadows !== false) (function() {
      var raw = payload.shadows || {};
      var list = [];
      if (Array.isArray(raw)) {
        raw.forEach(function(r) { if (r.name) list.push({ name: r.name, value: r.value || '' }); });
      } else {
        Object.keys(raw).forEach(function(n) { list.push({ name: n, value: raw[n] || '' }); });
      }
      if (!list.length) return;
      var tl      = w.querySelector('[data-v2-tl-shadow]');
      var tlInner = w.querySelector('[data-v2-tl-shadow-inner]');
      if (!tl) return;
      list.forEach(function(s) {
        var isInset = s.value.indexOf('inset') !== -1 || s.name.indexOf('inner') !== -1;
        var activeTl = (isInset && tlInner) ? tlInner : tl;
        /* find existing row in both tables */
        var found = null;
        var allTls = tlInner ? [tl, tlInner] : [tl];
        allTls.forEach(function(t) {
          t.querySelectorAll('.v2-tr').forEach(function(tr) {
            var ni = tr.querySelector('input[type="hidden"][name*="[shadows]"][name*="[name]"]');
            if (ni && ni.value === s.name) found = tr;
          });
        });
        if (found) {
          var vi = found.querySelector('.v2-shadow-inp[data-v2-shadow-id]');
          if (vi) {
            vi.value = s.value;
            var prev = w.querySelector('#v2-shprev-' + vi.dataset.v2ShadowId);
            if (prev) prev.style.boxShadow = s.value;
          }
        } else {
          /* collision check across both tables */
          var collision = false;
          allTls.forEach(function(t) {
            t.querySelectorAll('input[type="hidden"][name*="[shadows]"][name*="[name]"]').forEach(function(ni) {
              if (ni.value === s.name) collision = true;
            });
          });
          if (collision) return;
          ecfV2AddRow(isInset && tlInner ? 'shadow-inner' : 'shadow');
          var newRow = activeTl.lastElementChild;
          if (newRow) {
            var vi2      = newRow.querySelector('.v2-shadow-inp');
            var oldSname = vi2 ? vi2.id.replace('v2-shval-', '') : null;
            var ni2      = newRow.querySelector('input[type="hidden"][name*="[name]"]');
            if (ni2) ni2.value = s.name;
            if (vi2) {
              vi2.value = s.value;
              vi2.id = 'v2-shval-' + s.name;
              vi2.dataset.v2ShadowId = s.name;
            }
            if (oldSname) {
              var prevDiv  = newRow.querySelector('#v2-shprev-' + oldSname);
              if (prevDiv)  prevDiv.id = 'v2-shprev-' + s.name;
              var editDiv  = newRow.querySelector('#v2-edit-sh-' + oldSname);
              if (editDiv)  editDiv.id = 'v2-edit-sh-' + s.name;
              var shRowEl  = newRow.querySelector('.v2-sh-row');
              if (shRowEl)  shRowEl.setAttribute('onclick', "ecfV2PickShadow('" + s.name + "','','')");
              var editBtnEl = newRow.querySelector('.v2-sh-row .v2-edit-btn');
              if (editBtnEl) editBtnEl.setAttribute('onclick', "event.stopPropagation();ecfV2ToggleEdit('sh-" + s.name + "')");
              var actionBtns = newRow.querySelectorAll('.v2-color-edit-actions .v2-btn--ghost');
              if (actionBtns[0]) actionBtns[0].setAttribute('onclick', "ecfV2CopyShadowCSS('" + s.name + "')");
              if (actionBtns[1]) actionBtns[1].setAttribute('onclick', "ecfV2ToggleEdit('sh-" + s.name + "')");
            }
            var nameDiv = newRow.querySelector('.v2-sh-name');
            var label   = newRow.querySelector('.v2-shadow-edit-panel .v2-sl');
            if (nameDiv) nameDiv.textContent = s.name;
            if (label)   label.textContent   = '--ecf-shadow-' + s.name;
          }
        }
      });
    }());

    /* Google Font pairing */
    var applyGfH = f.headingFont !== false ? gfHeading : '';
    var applyGfB = f.bodyFont    !== false ? gfBody    : '';
    if (applyGfH || applyGfB) {
      ecfV2ApplyPairing(applyGfH || applyGfB, applyGfB || applyGfH);
      if (window.ecfAdmin && ecfAdmin.fontImportRestUrl) {
        [{family:applyGfH,target:'heading'},{family:applyGfB,target:'body'}].forEach(function(ff) {
          if (!ff.family) return;
          fetch(ecfAdmin.fontImportRestUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
            body: JSON.stringify({ family: ff.family, target: ff.target })
          });
        });
      }
    }

    /* Apply auto-classes toggles + layrix-class-defaults (custom presets carry
       the full sanitized settings — pick out keys that aren't covered by the
       built-in preset mapping above). */
    var autoKeys = ['auto_classes_enabled','auto_classes_headings','auto_classes_buttons','auto_classes_text_link','auto_classes_form'];
    autoKeys.forEach(function(key) {
      if (payload[key] === undefined) return;
      var cb = w.querySelector('input[type="checkbox"][name*="[' + key + ']"]');
      if (!cb) return;
      cb.checked = !!parseInt(payload[key], 10);
      var tog = cb.nextElementSibling;
      if (tog && tog.classList) {
        tog.classList.toggle('v2-tog--on',  cb.checked);
        tog.classList.toggle('v2-tog--off', !cb.checked);
      }
      cb.dispatchEvent(new Event('change', { bubbles: true }));
    });
    if (payload.layrix_class_defaults && typeof payload.layrix_class_defaults === 'object') {
      Object.keys(payload.layrix_class_defaults).forEach(function(cls) {
        var props = payload.layrix_class_defaults[cls];
        if (!props || typeof props !== 'object') return;
        Object.keys(props).forEach(function(propKey) {
          var sel = w.querySelector('select[name*="[layrix_class_defaults][' + cls + '][' + propKey + ']"]');
          if (sel) sel.value = props[propKey] || '';
        });
      });
    }

    /* Save + Sync after preset apply */
    _setPill('saving', i18n.autosave_saving || '');
    var settings = ecfV2CollectData();
    if (settings && window.ecfAdmin && ecfAdmin.restUrl) {
      fetch(ecfAdmin.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
        body: JSON.stringify({ settings: settings }),
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data && data.success) {
          _setPill('saved', i18n.preset_saved_syncing || '');
          return ecfV2Sync();
        }
      })
      .then(function() {
        if (btn) { btn.disabled = false; btn.textContent = i18n.preset_apply_btn || ''; }
      })
      .catch(function() {
        _setPill('error', i18n.preset_save_error || '');
        if (btn) { btn.disabled = false; btn.textContent = i18n.preset_apply_btn || ''; }
      });
    } else {
      if (btn) setTimeout(function() { btn.disabled = false; btn.textContent = i18n.preset_apply_btn || ''; }, 2000);
    }
  }
  window.ecfV2ApplyPreset = ecfV2ApplyPreset;

  /* ── Client-side shade/tint generation ──────────────────────────────── */
  function _hexToRgb(hex) {
    hex = hex.replace('#', '');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    return { r: parseInt(hex.substr(0,2),16), g: parseInt(hex.substr(2,2),16), b: parseInt(hex.substr(4,2),16) };
  }
  function _rgbToHex(r, g, b) {
    return '#' + [r,g,b].map(function(v){return ('0'+Math.round(v).toString(16)).slice(-2);}).join('');
  }
  function _genVariants(hex, count, toBlack) {
    var base = _hexToRgb(hex);
    var t = toBlack ? {r:0,g:0,b:0} : {r:255,g:255,b:255};
    var out = [];
    for (var i = 1; i <= count; i++) {
      var a = i / (count + 1);
      out.push(_rgbToHex(base.r + (t.r-base.r)*a, base.g + (t.g-base.g)*a, base.b + (t.b-base.b)*a));
    }
    return out;
  }
  function ecfV2UpdateShadeStrip(cname) {
    var tr = document.getElementById('v2-tr-' + cname);
    if (!tr) return;
    var hexInp = document.getElementById('v2-einp-' + cname);
    var hex = hexInp ? hexInp.value.trim() : '';
    if (!hex || !/^#[0-9a-fA-F]{6}$/.test(hex)) return;
    var scInp = document.getElementById('v2-sc-inp-' + cname);
    var tcInp = document.getElementById('v2-tc-inp-' + cname);
    var scCb  = tr.querySelector('.v2-shade-cb[data-shade-target="v2-sc-' + cname + '"]');
    var tcCb  = tr.querySelector('.v2-shade-cb[data-shade-target="v2-tc-' + cname + '"]');
    var shades = (scCb && scCb.checked) ? _genVariants(hex, parseInt(scInp&&scInp.value,10)||4, true)  : [];
    var tints  = (tcCb && tcCb.checked) ? _genVariants(hex, parseInt(tcInp&&tcInp.value,10)||4, false) : [];
    var strip = tr.querySelector('.v2-shade-strip');
    if (!shades.length && !tints.length) { if (strip) strip.style.display = 'none'; return; }
    if (!strip) {
      strip = document.createElement('div');
      strip.className = 'v2-shade-strip';
      var trEdit = tr.querySelector('.v2-tr-edit');
      if (trEdit) tr.insertBefore(strip, trEdit); else tr.appendChild(strip);
    }
    strip.style.display = '';
    function toSwatches(arr) {
      return arr.map(function(h){ return '<div class="v2-shade-sw" style="background:'+h+'" title="'+h+'"></div>'; }).join('');
    }
    var html = '';
    if (shades.length) html += '<div class="v2-shade-strip-row">' + toSwatches(shades) + '</div>';
    if (tints.length)  html += '<div class="v2-shade-strip-row">' + toSwatches(tints)  + '</div>';
    strip.innerHTML = html;
  }
  window.ecfV2UpdateShadeStrip = ecfV2UpdateShadeStrip;

  /* ── Init ────────────────────────────────────────────────────────────── */
  var _initDone = false;
  function init() {
    if (_initDone) return;
    _initDone = true;
    var w = wrap();
    if (!w) return;

    /* Inject "Classic design" back-button into every topbar-r */
    w.querySelectorAll('.v2-topbar-r').forEach(function(r) {
      if (r.querySelector('.v2-topbar-toggle')) return;
      var btn = document.createElement('button');
      btn.className = 'v2-topbar-toggle';
      btn.setAttribute('data-ecf-ui-toggle', '');
      btn.innerHTML = '<svg width="10" height="10" viewBox="0 0 13 13" fill="none"><path d="M9 2L4 6.5 9 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg> ' + ((_wi.classic_view) || 'Classic view');
      btn.title = (_wi.classic_view_title) || 'Switch to classic admin interface (Layrix v1)';
      r.insertBefore(btn, r.firstChild);
    });

    /* Navigation */
    w.querySelectorAll('.v2-ni[data-v2-page]').forEach(function(n) {
      n.addEventListener('click', function() { ecfV2Go(n.dataset.v2Page); });
    });

    /* Restore last active page after reload */
    (function() {
      var saved;
      try { saved = localStorage.getItem('ecf_v2_page'); } catch(e) {}
      var validPage = saved && w.querySelector('#ecf-v2-page-' + saved);
      ecfV2Go(validPage ? saved : 'colors');
    })();

    /* Tabs */
    w.querySelectorAll('.v2-tab[data-v2-tab-group]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        ecfV2Tab(btn.dataset.v2TabGroup, btn.dataset.v2Tab, btn);
      });
    });

    /* Topbar overflow-menu — toggle dropdown, close on outside click,
       close after picking an item (so the user goes straight into the
       action without a stale-open menu). */
    w.querySelectorAll('[data-v2-actions-toggle]').forEach(function(toggle) {
      var menu = toggle.parentElement;
      if (!menu) return;
      var dropdown = menu.querySelector('.v2-actions-menu__dropdown');
      if (!dropdown) return;
      toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        var open = !dropdown.hasAttribute('hidden');
        if (open) { dropdown.setAttribute('hidden', ''); toggle.setAttribute('aria-expanded', 'false'); }
        else      { dropdown.removeAttribute('hidden'); toggle.setAttribute('aria-expanded', 'true'); }
      });
      dropdown.querySelectorAll('.v2-actions-menu__item').forEach(function(item) {
        item.addEventListener('click', function() {
          dropdown.setAttribute('hidden', '');
          toggle.setAttribute('aria-expanded', 'false');
        });
      });
    });
    document.addEventListener('click', function(e) {
      w.querySelectorAll('.v2-actions-menu__dropdown:not([hidden])').forEach(function(dd) {
        if (!dd.parentElement.contains(e.target)) {
          dd.setAttribute('hidden', '');
          var t = dd.parentElement.querySelector('[data-v2-actions-toggle]');
          if (t) t.setAttribute('aria-expanded', 'false');
        }
      });
    });

    /* Theme-Style-Importer modal — read kit on open, apply selected fields */
    (function() {
      var modal   = document.getElementById('ecf-kit-import-modal');
      if (!modal) return;
      var loading = document.getElementById('ecf-kit-import-loading');
      var empty   = document.getElementById('ecf-kit-import-empty');
      var fields  = document.getElementById('ecf-kit-import-fields');
      var tbody   = document.getElementById('ecf-kit-import-tbody');
      var toggle  = document.getElementById('ecf-kit-toggle-all');
      var applyBtn= document.getElementById('ecf-kit-import-apply');
      var nonce   = (window.ecfAdmin && window.ecfAdmin.restNonce) || '';
      // Derive REST base from the existing settings URL (always present).
      var base    = (window.ecfAdmin && window.ecfAdmin.restUrl) || '/wp-json/ecf-framework/v1/settings';
      var url     = base.replace(/\/settings$/, '/');

      function open() {
        modal.style.display = 'flex';
        loading.style.display = '';
        empty.style.display   = 'none';
        fields.style.display  = 'none';
        applyBtn.disabled     = true;
        tbody.innerHTML       = '';
        fetch(url + 'kit-import-preview', {
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': nonce }
        }).then(function(r){ return r.json(); }).then(function(data) {
          loading.style.display = 'none';
          if (!data || !data.available) { empty.style.display = ''; return; }
          var hasAny = false;
          Object.keys(data.fields || {}).forEach(function(path) {
            var f = data.fields[path];
            if (!f.value) return;
            hasAny = true;
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--v2-border)';
            var preview = '';
            if (f.type === 'color') {
              preview = '<span style="display:inline-flex;align-items:center;gap:8px"><span style="display:inline-block;width:18px;height:18px;border-radius:4px;border:1px solid rgba(255,255,255,.15);background:'+f.value+'"></span><code style="font-family:var(--v2-mono);font-size:11px">'+f.value+'</code></span>';
            } else {
              preview = '<code style="font-family:var(--v2-mono);font-size:11px">'+escapeHtml(f.value)+'</code>';
            }
            tr.innerHTML = '<td style="padding:8px 4px;vertical-align:middle"><input type="checkbox" data-kit-field="'+path+'" checked></td>' +
                           '<td style="padding:8px 4px;vertical-align:middle">'+escapeHtml(f.label)+'</td>' +
                           '<td style="padding:8px 4px;vertical-align:middle">'+preview+'</td>';
            tbody.appendChild(tr);
          });
          if (hasAny) { fields.style.display = ''; applyBtn.disabled = false; }
          else        { empty.style.display  = ''; }
        }).catch(function(err) {
          loading.textContent = 'Fehler: ' + err;
        });
      }

      function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

      function close() { modal.style.display = 'none'; }

      w.querySelectorAll('[data-ecf-kit-import]').forEach(function(b) {
        b.addEventListener('click', open);
      });
      w.querySelectorAll('[data-ecf-kit-close]').forEach(function(b) {
        b.addEventListener('click', close);
      });
      modal.addEventListener('click', function(e) { if (e.target === modal) close(); });

      toggle.addEventListener('change', function() {
        tbody.querySelectorAll('input[type=checkbox]').forEach(function(cb) { cb.checked = toggle.checked; });
      });

      applyBtn.addEventListener('click', function() {
        var accept = [];
        tbody.querySelectorAll('input[data-kit-field]:checked').forEach(function(cb) {
          accept.push(cb.getAttribute('data-kit-field'));
        });
        if (!accept.length) return;
        applyBtn.disabled = true;
        applyBtn.textContent = 'Importiere…';
        fetch(url + 'kit-import', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
          body: JSON.stringify({ accept: accept })
        }).then(function(r){ return r.json(); }).then(function(d) {
          if (d && d.success) {
            applyBtn.textContent = '✓ ' + (d.written || 0) + ' Felder übernommen';
            setTimeout(function() { window.location.reload(); }, 700);
          } else {
            applyBtn.disabled = false;
            applyBtn.textContent = 'Fehler — nochmal versuchen';
          }
        }).catch(function(err) {
          applyBtn.disabled = false;
          applyBtn.textContent = 'Netzwerk-Fehler';
        });
      });
    })();

    /* Jump-to-tab buttons inside the Aktive overview — switch to the
       management tab for the corresponding class category. */
    w.querySelectorAll('[data-v2-jump-tab]').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var target = btn.getAttribute('data-v2-jump-tab');
        var tab = w.querySelector('.v2-tab[data-v2-tab-group="cl"][data-v2-tab="' + target + '"]');
        if (tab) tab.click();
      });
    });

    /* Live-search filter inside class panels (Starter/Extra/Utility).
       Hides rows whose class name OR description doesn't include the
       query, then collapses category headers whose rows are all hidden.
       Scales the UX once the library hits hundreds of classes. */
    w.querySelectorAll('.v2-cl-search').forEach(function(input) {
      var panelId = input.getAttribute('data-v2-cl-search');
      var panel   = w.querySelector('#' + panelId);
      if (!panel) return;
      var emptyMsg = w.querySelector('[data-v2-cl-empty="' + panelId + '"]');
      input.addEventListener('input', function() {
        var q = (input.value || '').trim().toLowerCase();
        var anyVisible = false;
        var visibleByCat = {};
        panel.querySelectorAll('.v2-cl-row[data-v2-cl-cat]').forEach(function(row) {
          var name = row.getAttribute('data-v2-cl-name') || '';
          var desc = row.getAttribute('data-v2-cl-desc') || '';
          var match = q === '' || name.indexOf(q) >= 0 || desc.indexOf(q) >= 0;
          row.style.display = match ? '' : 'none';
          if (match) {
            anyVisible = true;
            var cat = row.getAttribute('data-v2-cl-cat');
            visibleByCat[cat] = (visibleByCat[cat] || 0) + 1;
          }
        });
        panel.querySelectorAll('.v2-cl-group-head[data-v2-cl-cat]').forEach(function(head) {
          var cat = head.getAttribute('data-v2-cl-cat');
          head.style.display = visibleByCat[cat] ? '' : 'none';
        });
        if (emptyMsg) emptyMsg.style.display = (q !== '' && !anyVisible) ? '' : 'none';
      });
    });

    /* Bulk-toggle "Alle ein / Alle aus" per category header — clicks each
       checkbox in the same panel that shares the data-v2-cl-cat value, but
       only if it differs from the desired state (so save-dirty tracking
       sees one change per row, not no-ops). Respects the current search
       filter: hidden rows aren't touched. */
    w.querySelectorAll('[data-v2-cl-bulk]').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var desiredOn = btn.getAttribute('data-v2-cl-bulk') === 'on';
        var cat       = btn.getAttribute('data-v2-cl-cat');
        var panel     = btn.closest('.v2-tp');
        if (!panel) return;
        panel.querySelectorAll('.v2-cl-row[data-v2-cl-cat="' + cat + '"]').forEach(function(row) {
          if (row.style.display === 'none') return;
          var cb = row.querySelector('input.v2-tog-cb');
          if (!cb) return;
          if (cb.checked !== desiredOn) {
            cb.checked = desiredOn;
            cb.dispatchEvent(new Event('change', { bubbles: true }));
            var pill = row.querySelector('.v2-tog');
            if (pill) {
              pill.classList.toggle('v2-tog--on',  desiredOn);
              pill.classList.toggle('v2-tog--off', !desiredOn);
            }
          }
        });
      });
    });

    w.querySelectorAll('[data-v2-preset-filter]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        ecfV2FilterPresets(btn.dataset.v2PresetFilter, btn);
      });
    });

    /* Scale-Aside beim Start: nur anzeigen wenn Skala-Tab aktiv */
    (function() {
      var aside = w.querySelector('#v2-ty-scale-aside');
      if (!aside) return;
      var activeTab = w.querySelector('.v2-tab--on[data-v2-tab-group="ty"]');
      if (!activeTab || activeTab.dataset.v2Tab !== 'scale') {
        aside.style.display = 'none';
      }
    })();

    /* Prevent native form submission (ENTER key etc.) — save always via fetch */
    var form = document.getElementById('ecf-v2-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        ecfV2Save(false);
      });
    }

    /* Save buttons */
    w.querySelectorAll('[data-v2-save]').forEach(function(btn) {
      btn.addEventListener('click', function() { ecfV2Save(false); });
    });

    /* Reset buttons */
    w.querySelectorAll('[data-v2-reset]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var _ri18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
        ecfV2Confirm(_ri18n.discard_confirm || '').then(function(ok) {
          if (ok) window.location.reload();
        });
      });
    });

    /* Reset to plugin defaults */
    w.querySelectorAll('[data-v2-reset-defaults]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        ecfV2ResetModal(btn);
      });
    });

    /* Sync buttons */
    w.querySelectorAll('[data-v2-sync]').forEach(function(btn) {
      btn.addEventListener('click', function() { ecfV2Sync(btn); });
    });

    /* Native color pickers → live-update alle UI-Elemente + Autosave beim Schließen */
    w.querySelectorAll('.v2-color-native[data-v2-color-id]').forEach(function(cp) {
      cp.addEventListener('input', function() {
        var id  = cp.dataset.v2ColorId;
        var val = cp.value;
        ecfV2LiveColor(id, val);
        var w2 = wrap();
        if (!w2) return;
        var hidden = w2.querySelector('#v2-val-' + id);
        if (hidden) hidden.value = val;
      });
      cp.addEventListener('change', function() {
        var id  = cp.dataset.v2ColorId;
        var val = cp.value;
        ecfV2LiveColor(id, val);
        var w2 = wrap();
        if (!w2) return;
        var hidden = w2.querySelector('#v2-val-' + id);
        if (hidden) hidden.value = val;
        ecfV2ScheduleAutosave();
      });
    });

    /* Hex text inputs → update all elements live */
    w.querySelectorAll('.v2-color-hex-inp[data-v2-color-id]').forEach(function(inp) {
      inp.addEventListener('input', function() {
        var id  = inp.dataset.v2ColorId;
        var val = inp.value;
        if (!/^#[0-9a-f]{6}$/i.test(val)) return;
        ecfV2LiveHex(id, val);
        var w2 = wrap();
        if (!w2) return;
        var hexChip = w2.querySelector('#v2-hex-' + id);
        if (hexChip) hexChip.textContent = val;
      });
    });

    /* Color name input → update row labels live (PHP-rendered + JS-added rows) */
    w.addEventListener('input', function(e) {
      var inp = e.target;
      if (!inp.matches || !inp.matches('input[name*="[colors]"][name*="[name]"]')) return;
      var val = inp.value;
      var tr = inp.closest('.v2-tr');
      if (!tr) return;
      var trName = tr.querySelector('.v2-tr-name');
      if (trName) trName.textContent = val;
      var trVar = tr.querySelector('.v2-tr-var');
      if (trVar) trVar.textContent = '--ecf-color-' + val;
      var evar = tr.querySelector('.v2-evar-name');
      if (evar) evar.textContent = val;
      /* Warn about characters sanitize_key() will strip */
      var hasInvalid = /[^a-z0-9_-]/.test(val);
      /* Warn about duplicate names */
      var hasDupe = false;
      if (val && !hasInvalid) {
        w.querySelectorAll('input[name*="[colors]"][name*="[name]"]').forEach(function(other) {
          if (other !== inp && other.value === val) hasDupe = true;
        });
      }
      var isError = hasInvalid || hasDupe;
      inp.classList.toggle('v2-inp--invalid', isError);
      var warn = inp.parentElement && inp.parentElement.querySelector('.v2-name-warn');
      var _i18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
      var warnMsg = hasDupe ? (_i18n.token_name_duplicate || '') : (hasInvalid ? (_i18n.token_name_invalid_chars || '') : '');
      if (!warn && isError && warnMsg) {
        warn = document.createElement('span');
        warn.className = 'v2-name-warn';
        warn.textContent = warnMsg;
        inp.parentElement.appendChild(warn);
      } else if (warn && isError && warnMsg) {
        warn.textContent = warnMsg;
      } else if (warn && !isError) {
        warn.remove();
      }
    });

    /* Shadow inputs → live preview */
    w.querySelectorAll('.v2-shadow-inp[data-v2-shadow-id]').forEach(function(inp) {
      inp.addEventListener('input', function() {
        var id   = inp.dataset.v2ShadowId;
        var w2   = wrap();
        if (!w2) return;
        var prev = w2.querySelector('#v2-shprev-' + id);
        var css  = w2.querySelector('#v2-sh-fcss');
        if (prev) prev.style.boxShadow = inp.value;
        if (css)  css.textContent      = inp.value;
        ecfV2ScheduleAutosave();
      });
    });

    /* Toggle checkboxes → visual span */
    w.querySelectorAll('.v2-tog-cb').forEach(function(cb) {
      cb.addEventListener('change', function() {
        var label = cb.closest('.v2-tog-label');
        var span = label ? label.querySelector('.v2-tog') : null;
        if (span) {
          span.classList.toggle('v2-tog--on', cb.checked);
          span.classList.toggle('v2-tog--off', !cb.checked);
        }
        /* Show sync-needed dot if toggling a class checkbox */
        if (cb.closest('#ecf-v2-page-classes')) {
          var dot = document.getElementById('v2-sync-dot');
          if (dot) dot.style.display = '';
        }
        ecfV2ScheduleAutosave();
      });
    });

    /* Generic form inputs → schedule autosave */
    w.querySelectorAll('.v2-si').forEach(function(inp) {
      inp.addEventListener('change', function() { ecfV2ScheduleAutosave(); });
    });
    w.querySelectorAll('select.v2-select').forEach(function(sel) {
      sel.addEventListener('change', function() { ecfV2ScheduleAutosave(); });
    });

    /* Add row buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-add-row]');
      if (!btn) return;
      var type = btn.dataset.v2AddRow;
      ecfV2AddRow(type);
      if (type === 'color' && window.ecfAdmin && ecfAdmin.restUrl) {
        var _ari18n = ecfAdmin.i18n || {};
        _setPill('saving', _ari18n.autosave_saving || '');
        var data = ecfV2CollectData();
        if (data) {
          fetch(ecfAdmin.restUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
            body: JSON.stringify({ settings: data }),
          })
          .then(function(r) { return r.json(); })
          .then(function(resp) { if (resp && resp.success) return ecfV2Sync(); })
          .catch(function() { _setPill('error', (_ari18n.network_error || '')); });
        }
      }
    });

    /* Remove row buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-remove-row]');
      if (btn) {
        var tr = btn.closest('.v2-tr');
        if (tr) { tr.remove(); ecfV2ScheduleAutosave(); }
      }
    });

    /* Pairing Apply buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-apply-pairing]');
      if (btn) ecfV2ApplyPairing(btn.dataset.heading || '', btn.dataset.body || '');
    });

    /* Step add buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-step-add]');
      if (btn) ecfV2AddStep(btn.dataset.v2StepAdd);
    });

    /* Custom class add button */
    var addClsBtn = w.querySelector('#v2-add-custom-class');
    if (addClsBtn) addClsBtn.addEventListener('click', ecfV2AddCustomClass);

    /* Custom class remove buttons (delegated) */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-remove-custom-class]');
      if (btn) {
        var row = btn.closest('.v2-tr, [class*="v2-tr"]');
        if (row) {
          row.remove();
          _updateCustomEmpty(document.getElementById('v2-custom-class-list'));
          ecfV2ScheduleAutosave();
        }
      }
    });

    /* Export CSS buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-export-css]');
      if (btn) ecfV2ExportCSS();
    });

    /* Font family text inputs → live preview update */
    w.addEventListener('input', function(e) {
      var inp = e.target;
      if (!inp.name || inp.name.indexOf('[typography][fonts]') === -1 || inp.name.indexOf('[value]') === -1) return;
      var tr = inp.closest('.v2-tr');
      var nameInp = tr && tr.querySelector('input[name*="[name]"]');
      var fname = nameInp ? nameInp.value : '';
      var val = inp.value;
      var sw = tr && tr.querySelector('.v2-tr-sw');
      if (sw) sw.style.fontFamily = val;
      var ph = w.querySelector('#v2-fp-h, .v2-fp-h');
      var pb = w.querySelector('#v2-fp-body, .v2-fp-body');
      var ps = w.querySelector('#v2-fp-secondary, .v2-fp-secondary');
      if (fname === 'primary')   { if (pb) pb.style.fontFamily = val; }
      if (fname === 'secondary') { if (ph) ph.style.fontFamily = val; if (ps) ps.style.fontFamily = val; }
    });

    /* Color pickers with data-v2-sync-text → sync to text input */
    w.querySelectorAll('[data-v2-sync-text]').forEach(function(cp) {
      cp.addEventListener('input', function() {
        var targetId = cp.dataset.v2SyncText;
        var txt = document.getElementById(targetId);
        if (txt) { txt.value = cp.value; ecfV2ScheduleAutosave(); }
      });
    });
    /* Reverse: text input → color picker */
    w.querySelectorAll('input[id^="v2-gctext-"]').forEach(function(txt) {
      txt.addEventListener('input', function() {
        if (!/^#[0-9a-f]{6}$/i.test(txt.value)) return;
        var cp = txt.previousElementSibling;
        if (cp && cp.type === 'color') cp.value = txt.value;
      });
    });

    /* Font search input */
    var fsi = document.getElementById('v2-font-search-inp');
    var fsb = document.getElementById('v2-font-search-btn');
    if (fsi) {
      fsi.addEventListener('input', function() {
        clearTimeout(_fontSearchTimer);
        var q = fsi.value.trim();
        if (!q) { var r = document.getElementById('v2-font-search-results'); if (r) r.style.display = 'none'; return; }
        _fontSearchTimer = setTimeout(function() { ecfV2SearchFonts(q); }, 350);
      });
      fsi.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); ecfV2SearchFonts(fsi.value.trim()); }
      });
    }
    if (fsb) fsb.addEventListener('click', function() { ecfV2SearchFonts((document.getElementById('v2-font-search-inp') || {}).value || ''); });

    /* Font import buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-font-import]');
      if (btn) ecfV2ImportFont(btn.dataset.v2FontImport);
    });

    /* Typography scale live preview */
    w.querySelectorAll('[data-v2-scale-param]').forEach(function(inp) {
      inp.addEventListener('input', ecfV2UpdateScalePreview);
      inp.addEventListener('change', ecfV2UpdateScalePreview);
    });
    /* Custom ratio pickers: replace native selects with tile grid */
    w.querySelectorAll('select[data-v2-scale-param="min_ratio"], select[data-v2-scale-param="max_ratio"]').forEach(function(sel) {
      sel.style.display = 'none';
      var wrapper = document.createElement('div');
      wrapper.className = 'v2-ratio-picker';
      Array.from(sel.options).forEach(function(opt) {
        var parts = opt.textContent.split(' \u2014 ');
        var num  = parts[0].trim();
        var name = parts[1] ? parts[1].trim() : '';
        var btn  = document.createElement('button');
        btn.type = 'button';
        btn.className = 'v2-ratio-tile' + (opt.selected ? ' v2-ratio-tile--on' : '');
        btn.dataset.value = opt.value;
        btn.innerHTML = '<span class="v2-ratio-num">' + num + '</span>'
          + (name ? '<span class="v2-ratio-name">' + name + '</span>' : '');
        btn.addEventListener('click', function() {
          sel.value = opt.value;
          wrapper.querySelectorAll('.v2-ratio-tile').forEach(function(t) { t.classList.remove('v2-ratio-tile--on'); });
          btn.classList.add('v2-ratio-tile--on');
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          ecfV2ScheduleAutosave();
        });
        wrapper.appendChild(btn);
      });
      sel.parentNode.insertBefore(wrapper, sel.nextSibling);
    });
    ecfV2UpdateScalePreview();

    /* Spacing bars live preview */
    w.querySelectorAll('[data-v2-sp-param]').forEach(function(inp) {
      inp.addEventListener('input', ecfV2UpdateSpacingPreview);
      inp.addEventListener('change', ecfV2UpdateSpacingPreview);
    });
    ecfV2UpdateSpacingPreview();

    /* Root font impact preview */
    var rfs = document.getElementById('v2-root-font-sel');
    if (rfs) rfs.addEventListener('change', ecfV2UpdateRootFontImpact);
    ecfV2UpdateRootFontImpact();

    /* rem→px Popover */
    var _rfiBtn = document.getElementById('v2-rfi-btn');
    var _rfiPop = document.getElementById('v2-rfi-popover');
    var _rfiClose = document.getElementById('v2-rfi-close');
    if (_rfiBtn && _rfiPop) {
      _rfiBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        ecfV2UpdateRootFontImpact();
        var rect = _rfiBtn.getBoundingClientRect();
        _rfiPop.style.top  = (rect.bottom + 6 + window.scrollY) + 'px';
        _rfiPop.style.left = Math.max(8, rect.left - 180 + rect.width) + 'px';
        _rfiPop.style.display = _rfiPop.style.display === 'none' ? 'block' : 'none';
      });
      if (_rfiClose) _rfiClose.addEventListener('click', function() { _rfiPop.style.display = 'none'; });
      document.addEventListener('click', function(e) {
        if (_rfiPop.style.display !== 'none' && !_rfiPop.contains(e.target) && e.target !== _rfiBtn) {
          _rfiPop.style.display = 'none';
        }
      });
    }

    /* Bind click directly on each color row's main area → open edit panel +
       native color picker. Only for color rows (skip font / radius / lh). */
    w.querySelectorAll('.v2-tr[id^="v2-tr-"]').forEach(function(tr) {
      if (tr.id.indexOf('v2-tr-font-') === 0) return;
      var sw = tr.querySelector('.v2-tr-sw');
      if (!sw) return;
      if (sw.classList.contains('v2-tr-sw--font')   ||
          sw.classList.contains('v2-tr-sw--radius') ||
          sw.classList.contains('v2-tr-sw--lh')) return;
      var main = tr.querySelector('.v2-tr-main');
      if (!main) return;
      var id = tr.id.replace('v2-tr-', '');
      main.style.cursor = 'pointer';
      main.addEventListener('click', function(e) {
        if (e.target.closest('.v2-tr-meta')) return;
        if (e.target.closest('input, select, textarea, button, label, a')) return;
        var editPanel = document.getElementById('v2-edit-' + id);
        var alreadyOpen = editPanel && editPanel.classList.contains('v2-tr-edit--open');
        if (!alreadyOpen) ecfV2ToggleEdit(id);
        var openPicker = function() {
          var cp = document.getElementById('v2-cp-' + id);
          if (!cp) return;
          try { if (typeof cp.showPicker === 'function') { cp.showPicker(); return; } } catch(ex) {}
          cp.click();
        };
        if (alreadyOpen) openPicker();
        else setTimeout(openPicker, 60);
      });
    });

    /* Shadow utility row click → update preview */
    w.addEventListener('click', function(e) {
      var row = e.target.closest('.v2-sh-util-row');
      if (!row) return;
      var name = row.dataset.shName;
      var css  = row.dataset.shCss;
      if (name && css) ecfV2PickShadow(name, css, css);
    });

    /* Aside swatch click → open edit panel + trigger color picker */
    var cpMain = w.querySelector('#v2-cp-main');
    if (cpMain) cpMain.addEventListener('click', function() {
      var id = cpMain.dataset.activeId;
      if (!id) return;
      var editPanel = document.getElementById('v2-edit-' + id);
      var alreadyOpen = editPanel && editPanel.classList.contains('v2-tr-edit--open');
      if (!alreadyOpen) ecfV2ToggleEdit(id);
      setTimeout(function() {
        var cp = document.getElementById('v2-cp-' + id);
        if (cp) cp.click();
      }, 30);
    });

    /* Shade strip click → open edit panel (same as pencil icon) */
    w.addEventListener('click', function(e) {
      var strip = e.target.closest('.v2-shade-strip');
      if (!strip) return;
      var tr = strip.closest('.v2-tr');
      if (!tr) return;
      var id = tr.id.replace('v2-tr-', '');
      if (id) ecfV2ToggleEdit(id);
    });

    /* Shade/Tint stepper buttons — update count + live-render strip */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('.v2-stepper-btn');
      if (!btn) return;
      var targetId = btn.dataset.stepperTarget;
      var delta    = parseInt(btn.dataset.stepperDelta, 10) || 0;
      var inp = document.getElementById(targetId);
      if (!inp) return;
      var val = Math.min(10, Math.max(1, (parseInt(inp.value, 10) || 4) + delta));
      inp.value = val;
      /* Extract color name from input id (v2-sc-inp-<cname> or v2-tc-inp-<cname>) */
      var cname = targetId.replace(/^v2-[st]c-inp-/, '');
      if (cname) try { ecfV2UpdateShadeStrip(cname); } catch(ex) {}
      ecfV2ScheduleAutosave();
    });

    /* Shade/Tint checkboxes — toggle stepper + live-render strip */
    w.addEventListener('change', function(e) {
      var cb = e.target.closest('.v2-shade-cb');
      if (!cb) return;
      var stepperId = cb.dataset.shadeTarget;
      var stepper = stepperId ? document.getElementById(stepperId) : null;
      if (!stepper) return;
      stepper.classList.toggle('v2-stepper--off', !cb.checked);
      /* Update shade strip live */
      var cname = stepperId.replace(/^v2-[st]c-/, '');
      if (cname) try { ecfV2UpdateShadeStrip(cname); } catch(ex) {}
      ecfV2ScheduleAutosave();
    });

    /* Preset info buttons — click popover */
    (function() {
      var popup = null;
      function closePopup() {
        if (popup && popup.parentNode) popup.parentNode.removeChild(popup);
        popup = null;
      }
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-v2-preset-info]');
        if (btn) {
          e.stopPropagation();
          closePopup();
          var raw = btn.dataset.v2PresetInfo;
          var info; try { info = JSON.parse(raw); } catch(x) { return; }
          var _ii18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
          var rows = [];
          if (info.heading) rows.push('<div class="v2-ip-row"><span class="v2-ip-lbl">' + escapeHtml(_ii18n.info_heading_font || 'Heading') + '</span><span>' + escapeHtml(info.heading) + '</span></div>');
          if (info.body)    rows.push('<div class="v2-ip-row"><span class="v2-ip-lbl">' + escapeHtml(_ii18n.info_body_font    || 'Body')    + '</span><span>' + escapeHtml(info.body)    + '</span></div>');
          if (info.primary) rows.push('<div class="v2-ip-row"><span class="v2-ip-lbl">Primary</span><span style="display:flex;align-items:center;gap:4px"><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + escapeHtml(info.primary) + '"></i>' + escapeHtml(info.primary) + '</span></div>');
          if (info.accent)  rows.push('<div class="v2-ip-row"><span class="v2-ip-lbl">Accent</span><span style="display:flex;align-items:center;gap:4px"><i style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + escapeHtml(info.accent) + '"></i>' + escapeHtml(info.accent) + '</span></div>');
          popup = document.createElement('div');
          popup.className = 'v2-info-popup';
          popup.innerHTML = rows.join('');
          document.body.appendChild(popup);
          var r = btn.getBoundingClientRect();
          var pw = 200;
          var left = Math.min(r.left + window.scrollX, window.innerWidth + window.scrollX - pw - 8);
          popup.style.cssText = 'position:absolute;top:' + (r.bottom + window.scrollY + 6) + 'px;left:' + left + 'px;width:' + pw + 'px;z-index:9999';
          return;
        }
        closePopup();
      });
    }());

    /* Preset apply buttons */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-apply-preset]');
      if (!btn) return;
      var payloadStr = btn.dataset.v2PresetPayload;
      try {
        var payload   = JSON.parse(payloadStr);
        var gfHeading = btn.dataset.v2PresetGfHeading    || '';
        var gfBody    = btn.dataset.v2PresetGfBody        || '';
        var title     = btn.dataset.v2PresetTitle         || '';
        var desc      = btn.dataset.v2PresetDescription   || '';
        ecfPresetModal({
          payload: payload, gfHeading: gfHeading, gfBody: gfBody,
          title: title, description: desc,
          onApply: function(filter) { ecfV2ApplyPreset(payload, btn, gfHeading, gfBody, filter); }
        });
      } catch(ex) {}
    });

    /* Add Line Height row */
    var lhAddBtn = document.getElementById('v2-lh-add-btn');
    if (lhAddBtn) lhAddBtn.addEventListener('click', function() {
      var list = document.getElementById('v2-lh-list');
      if (!list) return;
      var opt2 = (window.ecfAdmin && ecfAdmin.optionName) || 'ecf_framework_v50';
      var idx2 = list.querySelectorAll('.v2-tr').length;
      list.insertAdjacentHTML('beforeend',
        '<div class="v2-tr" data-v2-row-type="lineheight"><div class="v2-tr-main">'
        + '<div class="v2-tr-sw v2-tr-sw--font" style="font-size:12px;font-weight:600">Ag</div>'
        + '<input type="text" class="v2-si" name="' + opt2 + '[typography][leading][' + idx2 + '][name]" value="" placeholder="name" style="max-width:90px">'
        + '<div class="v2-tr-meta">'
        + '<input type="text" class="v2-si v2-si--sm" name="' + opt2 + '[typography][leading][' + idx2 + '][value]" value="1.5" placeholder="1.5" style="width:62px">'
        + '<button type="button" class="v2-edit-btn" data-v2-remove-token-row style="color:var(--v2-text3)">✕</button>'
        + '</div></div></div>'
      );
      ecfV2ScheduleAutosave();
    });

    /* Add Letter Spacing row */
    var lsAddBtn = document.getElementById('v2-ls-add-btn');
    if (lsAddBtn) lsAddBtn.addEventListener('click', function() {
      var list = document.getElementById('v2-ls-list');
      if (!list) return;
      var opt2 = (window.ecfAdmin && ecfAdmin.optionName) || 'ecf_framework_v50';
      var idx2 = list.querySelectorAll('.v2-tr').length;
      list.insertAdjacentHTML('beforeend',
        '<div class="v2-tr" data-v2-row-type="letterspacing"><div class="v2-tr-main">'
        + '<div class="v2-tr-sw v2-tr-sw--font" style="font-size:13px;font-weight:600">Aa</div>'
        + '<input type="text" class="v2-si" name="' + opt2 + '[typography][tracking][' + idx2 + '][name]" value="" placeholder="name" style="max-width:90px">'
        + '<div class="v2-tr-meta">'
        + '<input type="text" class="v2-si v2-si--sm" name="' + opt2 + '[typography][tracking][' + idx2 + '][value]" value="0.04em" placeholder="0.04em" style="width:72px">'
        + '<button type="button" class="v2-edit-btn" data-v2-remove-token-row style="color:var(--v2-text3)">✕</button>'
        + '</div></div></div>'
      );
      ecfV2ScheduleAutosave();
    });

    /* Remove generic token rows (line-height, letter-spacing) */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-remove-token-row]');
      if (!btn) return;
      var row = btn.closest('.v2-tr');
      if (row) { row.remove(); ecfV2ScheduleAutosave(); }
    });

    /* Remove local font row */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-remove-local-font]');
      if (!btn) return;
      var row = btn.closest('.v2-tr[data-lf-idx]');
      if (row) {
        row.querySelectorAll('input[type="hidden"]').forEach(function(inp) { inp.disabled = true; });
        row.style.opacity = '0.3';
        row.style.pointerEvents = 'none';
        ecfV2ScheduleAutosave();
      }
    });

    /* Wizard starten button */
    w.addEventListener('click', function(e) {
      if (e.target.closest('[data-v2-wizard-start]')) ecfV2StartWizard();
    });
    /* Auto-start wizard on first visit */
    try { if (!sessionStorage.getItem(_wizardKey)) _wizardShowStep(0); } catch(ex) {}

    /* Changelog modal — move to body so display:none on ecf-main doesn't block it */
    w.addEventListener('click', function(e) {
      if (!e.target.closest('[data-ecf-open-changelog-modal]')) return;
      var modal = document.querySelector('[data-ecf-changelog-modal]');
      if (!modal) return;
      if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
      }
      modal.removeAttribute('hidden');
      modal.classList.add('is-open');
    });
    document.addEventListener('click', function(e) {
      if (e.target.closest('[data-ecf-close-changelog-modal]')) {
        var modal = document.querySelector('[data-ecf-changelog-modal]');
        if (modal) { modal.setAttribute('hidden', ''); modal.classList.remove('is-open'); }
      }
    });

    /* Import file preview */
    ecfV2InitImportPreview();

    /* Unit-aware inputs (px/rem/em/%/vw mit Auto-Konvertierung) */
    ecfV2InitUnitInputs();

    /* Init aside with first color */
    var firstSw = w.querySelector('.v2-tr-sw');
    if (firstSw && firstSw.style.background) {
      ecfV2UpdateAside('primary', firstSw.style.background);
    }

    /* Smart Recommendations apply */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-apply-rec-payload]');
      if (btn) ecfV2ApplyRecPayload(btn);
    });

    /* Variables search */
    var varSearch = document.getElementById('v2-var-search');
    if (varSearch) {
      varSearch.addEventListener('input', function() { ecfV2FilterVars(this.value); });
    }

    /* Variable edit modal open */
    w.addEventListener('click', function(e) {
      var btn = e.target.closest('.v2-var-edit-btn');
      if (btn) ecfV2OpenVarModal(btn);
    });

    /* Variable modal close */
    document.addEventListener('click', function(e) {
      if (e.target.closest('[data-v2-var-modal-close]')) ecfV2CloseVarModal();
      if (e.target.id === 'v2-var-modal') ecfV2CloseVarModal();
    });

    /* Variable modal save */
    var varSaveBtn = document.getElementById('v2-var-modal-save');
    if (varSaveBtn) varSaveBtn.addEventListener('click', ecfV2SaveVarModal);

    /* Variable modal input color preview update */
    var varInp = document.getElementById('v2-var-modal-input');
    if (varInp) {
      varInp.addEventListener('input', function() {
        var preview = document.getElementById('v2-var-modal-color-preview');
        if (preview && preview.style.display !== 'none') preview.style.background = this.value;
      });
    }

    /* BEM chip checkboxes */
    w.addEventListener('change', function(e) {
      var cb = e.target.closest('.v2-bem-chip-cb');
      if (!cb) return;
      var checked = w.querySelectorAll('.v2-bem-chip-cb:checked');
      var mods = [];
      checked.forEach(function(c) { mods.push(c.dataset.bemMod); });
      var mEl = document.getElementById('v2-bem-mod');
      if (mEl) mEl.value = mods.join('-');
      ecfV2UpdateBEM();
    });

    /* ── Custom preset: Save ─────────────────────────────────────────────── */
    var saveCustomBtn = document.getElementById('v2-save-custom-preset');
    if (saveCustomBtn && window.ecfAdmin && ecfAdmin.customPresetsRestUrl) {
      var _cpI18n = (ecfAdmin.i18n || {});
      saveCustomBtn.addEventListener('click', function() {
        var name = prompt(_cpI18n.custom_preset_save_prompt || '');
        if (!name || !name.trim()) return;
        name = name.trim();
        var snapshot = ecfV2CollectData();
        if (!snapshot) { ecfV2Toast(_cpI18n.preset_save_error || '', 'error'); return; }
        saveCustomBtn.disabled = true;
        saveCustomBtn.textContent = _cpI18n.autosave_saving || '';
        fetch(ecfAdmin.customPresetsRestUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
          body: JSON.stringify({ name: name, snapshot: snapshot }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data || !data.success) { ecfV2Toast(_cpI18n.custom_preset_save_error || '', 'error'); return; }
          var cpId      = data.id;
          var cpName    = escapeHtml(data.name || name);
          var cpCreated = escapeHtml(data.created || '');
          var container = document.getElementById('v2-custom-preset-cards');
          if (container && cpId) {
            var payloadStr = JSON.stringify(snapshot).replace(/"/g, '&quot;');
            container.insertAdjacentHTML('beforeend',
              '<div class="v2-preset-card v2-preset-card--custom" data-category="eigene" data-cp-id="' + cpId + '">'
              + '<div class="v2-preset-swatch v2-preset-swatch--custom">'
              + '<div style="font-size:var(--v2-ui-base-fs, 13px);font-weight:600;color:var(--v2-text2);letter-spacing:.03em">' + (_cpI18n.custom_preset_label || '') + '</div>'
              + '<div style="font-size:13px;font-weight:700;color:var(--v2-text);margin-top:4px">' + cpName + '</div>'
              + '<div style="font-size:var(--v2-btn-fs, 12px);color:var(--v2-text3);margin-top:4px">' + cpCreated + '</div>'
              + '</div>'
              + '<div class="v2-preset-body">'
              + '<div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">'
              + '<span class="v2-preset-tone">' + (_cpI18n.custom_preset_category || '') + '</span>'
              + '<span class="v2-preset-title">' + cpName + '</span>'
              + '</div>'
              + '<button type="button" class="v2-btn v2-btn--primary" style="width:100%;font-size:var(--v2-ui-base-fs, 13px);padding:5px 8px"'
              + ' data-v2-apply-preset="' + cpId + '" data-v2-preset-payload="' + payloadStr + '">' + (_cpI18n.preset_apply_btn || '') + '</button>'
              + '<button type="button" class="v2-btn v2-btn--ghost" style="width:100%;font-size:var(--v2-ui-base-fs, 13px);padding:4px 8px;margin-top:4px;color:var(--v2-text3)"'
              + ' data-v2-delete-custom-preset="' + cpId + '">' + (_cpI18n.delete || '') + '</button>'
              + '</div></div>'
            );
          }
          /* Update count badge */
          var badge = document.getElementById('v2-custom-preset-count');
          if (badge) badge.textContent = parseInt(badge.textContent || '0', 10) + 1;
          ecfV2FilterPresets('eigene', null);
          ecfV2Toast(_cpI18n.custom_preset_saved || '', 'success');
        })
        .catch(function() { ecfV2Toast(_cpI18n.custom_preset_save_network_error || '', 'error'); })
        .finally(function() {
          saveCustomBtn.disabled = false;
          saveCustomBtn.textContent = _cpI18n.custom_preset_save_btn_reset || '';
        });
      });
    }

    /* ── Custom preset: Delete ───────────────────────────────────────────── */
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-v2-delete-custom-preset]');
      if (!btn) return;
      var id = btn.dataset.v2DeleteCustomPreset;
      var _di18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
      if (!id) return;
      ecfV2Confirm(_di18n.custom_preset_delete_confirm || '', { danger: true }).then(function(ok) {
        if (!ok || !window.ecfAdmin || !ecfAdmin.customPresetsRestUrl) return;
        fetch(ecfAdmin.customPresetsRestUrl + '/' + encodeURIComponent(id), {
          method: 'DELETE',
          headers: { 'X-WP-Nonce': ecfAdmin.restNonce },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data || !data.success) { ecfV2Toast(_di18n.custom_preset_delete_error || '', 'error'); return; }
          var card = btn.closest('[data-cp-id="' + id + '"]');
          if (card) card.remove();
          var badge = document.getElementById('v2-custom-preset-count');
          if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent || '1', 10) - 1);
          ecfV2Toast(_di18n.custom_preset_deleted || '', 'success');
        })
        .catch(function() { ecfV2Toast(_di18n.custom_preset_network_error || '', 'error'); });
      });
    });

    /* ── Import modal ────────────────────────────────────────────────────── */
    var importModal = document.getElementById('v2-import-modal');
    if (importModal) {
      /* Section key map: checkbox value → settings object keys */
      var _importSectionKeys = {
        colors:  ['colors'],
        fonts:   ['typography'],
        radius:  ['radius'],
        shadows: ['shadows'],
        spacing: ['spacing'],
        general: null, /* null = everything except other sections */
      };
      var _allSectionKeys = ['colors', 'typography', 'radius', 'shadows', 'spacing'];

      function _closeImportModal() {
        importModal.style.display = 'none';
      }

      document.getElementById('v2-import-modal-cancel') && document.getElementById('v2-import-modal-cancel').addEventListener('click', _closeImportModal);
      document.getElementById('v2-import-modal-close')  && document.getElementById('v2-import-modal-close').addEventListener('click', _closeImportModal);
      importModal.addEventListener('click', function(e) { if (e.target === importModal) _closeImportModal(); });

      var importModalSubmit = document.getElementById('v2-import-modal-submit');
      if (importModalSubmit) {
        importModalSubmit.addEventListener('click', function() {
          if (!_importedJson) { _closeImportModal(); return; }

          var importedSettings = _importedJson.settings || _importedJson;
          var current = ecfV2CollectData() || {};
          var merged  = Object.assign({}, current);

          /* Which sections did the user check? */
          var checked = [];
          importModal.querySelectorAll('input[name="v2_import_section"]:checked').forEach(function(cb) {
            checked.push(cb.value);
          });

          checked.forEach(function(sec) {
            var keys = _importSectionKeys[sec];
            if (keys === null) {
              /* general = everything except all known section keys */
              Object.keys(importedSettings).forEach(function(k) {
                if (_allSectionKeys.indexOf(k) === -1) {
                  merged[k] = importedSettings[k];
                }
              });
            } else {
              keys.forEach(function(k) {
                if (importedSettings[k] !== undefined) merged[k] = importedSettings[k];
              });
            }
          });

          var _ii18n = (window.ecfAdmin && ecfAdmin.i18n) || {};
          if (!window.ecfAdmin || !ecfAdmin.restUrl) {
            ecfV2Toast(_ii18n.import_no_rest || '', 'error');
            return;
          }

          _closeImportModal();
          importModalSubmit.disabled = true;
          importModalSubmit.textContent = _ii18n.import_saving || '';
          _setPill('saving', _ii18n.import_saving || '');

          fetch(ecfAdmin.restUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
            body: JSON.stringify({ settings: merged }),
          })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (!data || !data.success) { throw new Error(_ii18n.import_failed || ''); }
            _setPill('saved', _ii18n.import_saved_syncing || '');
            return ecfV2Sync();
          })
          .then(function(syncData) {
            var vr = (syncData && syncData.variables) || {};
            var cr = (syncData && syncData.classes)   || {};
            var parts = [];
            if (!vr.skipped) parts.push((_ii18n.sync_variables || '%d').replace('%d', (vr.created||0)+(vr.updated||0)+(vr.deleted||0)));
            if (!cr.skipped) parts.push((_ii18n.sync_classes_count || '%d').replace('%d', (cr.created||0)+(cr.updated||0)+(cr.deleted||0)));
            var syncPart = parts.length ? ' (' + (_ii18n.import_synced || '%s').replace('%s', parts.join(', ')) + ')' : '';
            ecfV2Toast((_ii18n.import_completed || '') + syncPart + ' — ' + (_ii18n.import_reloading || ''), 'success');
            setTimeout(function() { window.location.reload(); }, 2200);
          })
          .catch(function(err) {
            var msg = (err && err.message) ? err.message : (_ii18n.import_network_error || '');
            _setPill('error', msg);
          })
          .finally(function() {
            importModalSubmit.disabled = false;
            importModalSubmit.textContent = _ii18n.import_modal_btn || '';
          });
        });
      }
    }
  }

  /* ── Smart Recommendations apply ────────────────────────────────────── */
  function ecfV2ApplyRecPayload(btn) {
    var raw = btn && btn.dataset && btn.dataset.v2ApplyRecPayload;
    if (!raw) return;
    var payload;
    try { payload = JSON.parse(raw); } catch(e) { return; }
    ecfV2ApplyPreset(payload, btn);
  }

  /* ── Variables page search ────────────────────────────────────────────── */
  function ecfV2FilterVars(query) {
    var q = (query || '').toLowerCase().trim();
    var tbody = document.getElementById('v2-vr-all-tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr[data-varname]');
    var shown = 0;
    rows.forEach(function(row) {
      var name = (row.dataset.varname || '').toLowerCase();
      var val  = (row.querySelector('.v2-vval') || {}).textContent || '';
      var match = !q || name.indexOf(q) !== -1 || val.toLowerCase().indexOf(q) !== -1;
      row.style.display = match ? '' : 'none';
      if (match) shown++;
    });
    var cnt = document.getElementById('v2-var-search-count');
    if (cnt) cnt.textContent = q ? (shown + ' Treffer') : '';
  }

  /* ── Variable edit modal ─────────────────────────────────────────────── */
  var _varModalState = {};

  function ecfV2OpenVarModal(btn) {
    var type  = btn.dataset.varType;
    var name  = btn.dataset.varName;
    var value = btn.dataset.varValue;
    var maxVal = btn.dataset.varMax || '';
    var modal = document.getElementById('v2-var-modal');
    if (!modal) return;
    document.getElementById('v2-var-modal-var-name').textContent = '--ecf-' + type + '-' + name;
    var mainInp = document.getElementById('v2-var-modal-input');
    var radiusRow = document.getElementById('v2-var-modal-radius-row');
    var preview = document.getElementById('v2-var-modal-color-preview');
    if (type === 'radius') {
      if (mainInp) mainInp.style.display = 'none';
      if (radiusRow) {
        radiusRow.style.display = '';
        var minInp = document.getElementById('v2-var-modal-input-min');
        var maxInp = document.getElementById('v2-var-modal-input-max');
        if (minInp) minInp.value = value || '';
        if (maxInp) maxInp.value = maxVal || value || '';
      }
      if (preview) preview.style.display = 'none';
    } else {
      if (mainInp) { mainInp.style.display = ''; mainInp.value = value || ''; }
      if (radiusRow) radiusRow.style.display = 'none';
      if (preview) {
        preview.style.display = type === 'color' ? '' : 'none';
        if (type === 'color') preview.style.background = value || '';
      }
    }
    _varModalState = { type: type, name: name, row: btn.closest('tr') };
    modal.removeAttribute('hidden');
    setTimeout(function() {
      var focusInp = type === 'radius'
        ? document.getElementById('v2-var-modal-input-min')
        : document.getElementById('v2-var-modal-input');
      if (focusInp) focusInp.focus();
    }, 50);
  }

  function ecfV2CloseVarModal() {
    var modal = document.getElementById('v2-var-modal');
    if (modal) modal.setAttribute('hidden', '');
    _varModalState = {};
  }

  function ecfV2SaveVarModal() {
    var row  = _varModalState.row;
    var type = _varModalState.type;
    var name = _varModalState.name;
    if (!row) { ecfV2CloseVarModal(); return; }
    var newVal, newMax;
    if (type === 'radius') {
      var minInp = document.getElementById('v2-var-modal-input-min');
      var maxInp = document.getElementById('v2-var-modal-input-max');
      newVal = minInp ? minInp.value.trim() : '';
      newMax = maxInp ? maxInp.value.trim() : newVal;
      if (!newVal) { ecfV2CloseVarModal(); return; }
    } else {
      var inp = document.getElementById('v2-var-modal-input');
      if (!inp) return;
      newVal = inp.value.trim();
      if (!newVal) { ecfV2CloseVarModal(); return; }
    }
    /* Update variables-table display */
    var valCell = row.querySelector('.v2-vval');
    if (valCell) valCell.textContent = type === 'radius'
      ? (newVal + (newMax && newMax !== newVal ? '–' + newMax : ''))
      : newVal;
    var editBtn = row.querySelector('.v2-var-edit-btn');
    if (editBtn) {
      editBtn.dataset.varValue = newVal;
      if (type === 'radius') editBtn.dataset.varMax = newMax;
    }
    /* Sync back to the actual form section so autosave picks it up */
    var w2 = wrap();
    if (w2) {
      if (type === 'color') {
        ecfV2LiveColor(name, newVal);
      } else if (type === 'shadow') {
        var shInp = w2.querySelector('#v2-shval-' + name);
        if (shInp) {
          shInp.value = newVal;
          var shPrev = w2.querySelector('#v2-shprev-' + name);
          if (shPrev) shPrev.style.boxShadow = newVal;
        }
      } else if (type === 'radius') {
        var radiusTl = w2.querySelector('[data-v2-tl-radius]');
        if (radiusTl) {
          radiusTl.querySelectorAll('.v2-tr').forEach(function(tr) {
            var ni = tr.querySelector('input[name*="[radius]"][name*="[name]"]');
            if (ni && ni.value === name) {
              var mi = tr.querySelector('input[name*="[radius]"][name*="[min]"]');
              if (mi) mi.value = newVal;
              var mx = tr.querySelector('input[name*="[radius]"][name*="[max]"]');
              if (mx) mx.value = newMax || newVal;
            }
          });
        }
      }
    }
    _setPill('saved', 'Wert aktualisiert');
    ecfV2CloseVarModal();
    ecfV2ScheduleAutosave();
  }

  /* ── BEM preset apply ────────────────────────────────────────────────── */
  function ecfV2ApplyBEMPreset(val) {
    if (!val) return;
    var parts = val.split('|');
    var block = parts[0] || '';
    var elem  = parts[1] || '';
    var mod   = parts[2] || '';
    var bEl = document.getElementById('v2-bem-block');
    var eEl = document.getElementById('v2-bem-elem');
    var mEl = document.getElementById('v2-bem-mod');
    if (bEl) bEl.value = block;
    if (eEl) eEl.value = elem;
    if (mEl) mEl.value = mod;
    document.querySelectorAll('.v2-bem-chip-cb').forEach(function(cb) { cb.checked = false; });
    ecfV2UpdateBEM();
  }
  window.ecfV2ApplyBEMPreset = ecfV2ApplyBEMPreset;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* Re-init when toggle switches to v2 (jQuery event from index.js) */
  if (window.jQuery) {
    jQuery(document).on('ecf:v2:show', init);
  }

  /* ══════════════════════════════════════════════════════════════════════
     VERSIONSVERLAUF — snapshot settings to localStorage before each save
  ══════════════════════════════════════════════════════════════════════ */

  var HISTORY_KEY = 'ecf_v2_history';
  var HISTORY_MAX = 8;

  function _snapshotHistory(settings) {
    try {
      var hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
      hist.unshift({ ts: Date.now(), data: settings });
      if (hist.length > HISTORY_MAX) hist = hist.slice(0, HISTORY_MAX);
      localStorage.setItem(HISTORY_KEY, JSON.stringify(hist));
    } catch (e) {}
  }

  function _formatTs(ts) {
    var d = new Date(ts);
    var now = Date.now();
    var diff = Math.round((now - ts) / 1000);
    if (diff < 60) return 'Gerade eben';
    if (diff < 3600) return 'Vor ' + Math.round(diff / 60) + ' Min.';
    if (diff < 86400) return 'Vor ' + Math.round(diff / 3600) + ' Std.';
    return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' })
      + ' ' + d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
  }

  var _diffMode = false;
  var _diffSelected = [];

  function _flattenSettings(obj, prefix) {
    var out = {};
    prefix = prefix || '';
    Object.keys(obj || {}).forEach(function(k) {
      var val = obj[k];
      var key = prefix ? prefix + '.' + k : k;
      if (val && typeof val === 'object' && !Array.isArray(val)) {
        var nested = _flattenSettings(val, key);
        Object.keys(nested).forEach(function(nk) { out[nk] = nested[nk]; });
      } else {
        out[key] = String(val == null ? '' : val);
      }
    });
    return out;
  }

  function _diffSnapshots(a, b) {
    var fa = _flattenSettings(a);
    var fb = _flattenSettings(b);
    var changes = [];
    var allKeys = Object.keys(Object.assign({}, fa, fb));
    allKeys.forEach(function(k) {
      if (fa[k] !== fb[k]) {
        changes.push({ key: k, from: fa[k] !== undefined ? fa[k] : '(neu)', to: fb[k] !== undefined ? fb[k] : '(entfernt)' });
      }
    });
    return changes;
  }

  function _renderDiff(idxA, idxB) {
    var hist;
    try { hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); } catch(e) { return; }
    var a = hist[idxA], b = hist[idxB];
    if (!a || !b) return;
    var changes = _diffSnapshots(a.data, b.data);
    var el = document.getElementById('v2-history-diff-result');
    if (!changes.length) {
      el.innerHTML = '<div class="v2-diff-empty">Keine Unterschiede zwischen den beiden Versionen.</div>';
    } else {
      var html = '<div class="v2-diff-header">'
        + '<span class="v2-diff-label">' + _formatTs(a.ts) + '</span>'
        + '<span class="v2-diff-arrow">→</span>'
        + '<span class="v2-diff-label">' + _formatTs(b.ts) + '</span>'
        + '<span class="v2-diff-count">' + changes.length + ' Änderung' + (changes.length !== 1 ? 'en' : '') + '</span>'
        + '</div>';
      html += '<div class="v2-diff-list">';
      changes.slice(0, 40).forEach(function(c) {
        var shortKey = c.key.split('.').slice(-2).join('.');
        html += '<div class="v2-diff-row">'
          + '<span class="v2-diff-key" title="' + escapeHtml(c.key) + '">' + escapeHtml(shortKey) + '</span>'
          + '<span class="v2-diff-from">' + escapeHtml(String(c.from).slice(0, 40)) + '</span>'
          + '<span class="v2-diff-arr">→</span>'
          + '<span class="v2-diff-to">' + escapeHtml(String(c.to).slice(0, 40)) + '</span>'
          + '</div>';
      });
      if (changes.length > 40) html += '<div class="v2-diff-more">… und ' + (changes.length - 40) + ' weitere</div>';
      html += '</div>';
      el.innerHTML = html;
    }
    el.style.display = 'block';
  }

  function _renderHistoryList() {
    var list = document.getElementById('v2-history-list');
    if (!list) return;
    var hist;
    try { hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); } catch(e) { hist = []; }
    if (!hist.length) {
      list.innerHTML = '<div style="padding:16px;text-align:center">'
        + '<div style="font-size:22px;margin-bottom:8px;opacity:.3">🕐</div>'
        + '<div style="color:var(--v2-text2);font-size:12px;font-weight:600;margin-bottom:6px">' + ((_wi.history_empty) || 'No entries yet') + '</div>'
        + '<div style="color:var(--v2-text3);font-size:var(--v2-ui-base-fs, 13px);line-height:1.5">' + ((_wi.history_hint) || 'Every time you save, a snapshot is created. You can restore up to 8 versions.') + '</div>'
        + '</div>';
      return;
    }
    var html = '';
    hist.forEach(function(entry, i) {
      var isSelected = _diffSelected.indexOf(i) !== -1;
      html += '<div class="v2-history-entry' + (isSelected ? ' v2-history-entry--selected' : '') + '" data-v2-hist-idx="' + i + '">'
        + '<div class="v2-history-ts">' + _formatTs(entry.ts) + '</div>'
        + (_diffMode
          ? '<button type="button" class="v2-btn v2-btn--ghost v2-btn--xs v2-diff-sel-btn' + (isSelected ? ' is-active' : '') + '" data-v2-diff-idx="' + i + '">'
            + (isSelected ? ('✓ ' + ((_wi.selected) || 'Selected')) : ((_wi.select) || 'Select')) + '</button>'
          : '<button type="button" class="v2-btn v2-btn--ghost v2-btn--xs" data-v2-restore-idx="' + i + '">' + ((_wi.restore) || 'Restore') + '</button>')
        + '</div>';
    });
    list.innerHTML = html;
  }

  function _restoreSnapshot(idx) {
    var hist;
    try { hist = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); } catch(e) { return; }
    var entry = hist[idx];
    if (!entry || !entry.data) return;
    if (!window.ecfAdmin || !ecfAdmin.restUrl) return;
    _setPill('saving', 'Wiederherstelle…');
    fetch(ecfAdmin.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ecfAdmin.restNonce },
      body: JSON.stringify({ settings: entry.data }),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data && data.success) {
        _setPill('saved', 'Wiederhergestellt — Seite lädt neu…');
        setTimeout(function() { location.reload(); }, 1200);
      } else {
        _setPill('error', 'Fehler beim Wiederherstellen');
      }
    })
    .catch(function() { _setPill('error', 'Netzwerkfehler'); });
    document.getElementById('v2-history-modal').hidden = true;
  }

  /* Hook save to snapshot before sending */
  var _origSave = ecfV2Save;
  ecfV2Save = function(silent) {
    var settings = ecfV2CollectData();
    if (settings) _snapshotHistory(settings);
    return _origSave(silent);
  };
  window.ecfV2Save = ecfV2Save;

  /* History modal open/close + diff mode */
  document.addEventListener('click', function(e) {
    if (e.target.closest('#v2-history-trigger')) {
      _diffMode = false; _diffSelected = [];
      _renderHistoryList();
      document.getElementById('v2-history-modal').hidden = false;
      document.getElementById('v2-history-diff-hint').style.display = 'none';
      document.getElementById('v2-history-diff-result').style.display = 'none';
      var tog = document.getElementById('v2-history-diff-toggle');
      if (tog) tog.classList.remove('is-active');
    }
    if (e.target.closest('#v2-history-close') || e.target.id === 'v2-history-modal') {
      document.getElementById('v2-history-modal').hidden = true;
    }
    if (e.target.closest('#v2-history-diff-toggle')) {
      _diffMode = !_diffMode;
      _diffSelected = [];
      e.target.closest('#v2-history-diff-toggle').classList.toggle('is-active', _diffMode);
      document.getElementById('v2-history-diff-hint').style.display = _diffMode ? 'block' : 'none';
      document.getElementById('v2-history-diff-result').style.display = 'none';
      _renderHistoryList();
    }
    var restoreBtn = e.target.closest('[data-v2-restore-idx]');
    if (restoreBtn) {
      _restoreSnapshot(parseInt(restoreBtn.dataset.v2RestoreIdx, 10));
    }
    var diffBtn = e.target.closest('[data-v2-diff-idx]');
    if (diffBtn) {
      var idx = parseInt(diffBtn.dataset.v2DiffIdx, 10);
      var pos = _diffSelected.indexOf(idx);
      if (pos !== -1) {
        _diffSelected.splice(pos, 1);
      } else if (_diffSelected.length < 2) {
        _diffSelected.push(idx);
      }
      _renderHistoryList();
      if (_diffSelected.length === 2) {
        _renderDiff(_diffSelected[0], _diffSelected[1]);
      } else {
        document.getElementById('v2-history-diff-result').style.display = 'none';
      }
    }
  });

  /* ══════════════════════════════════════════════════════════════════════
     GLOBALSUCHE — Cmd/Ctrl+K
  ══════════════════════════════════════════════════════════════════════ */

  var _searchIndex = null;

  function _buildSearchIndex() {
    var idx = [];
    var w = wrap();
    if (!w) return idx;

    /* Pages */
    w.querySelectorAll('.v2-ni[data-v2-page]').forEach(function(btn) {
      idx.push({
        type: 'page',
        label: btn.textContent.trim(),
        page: btn.dataset.v2Page,
        el: btn,
      });
    });

    /* Token rows */
    w.querySelectorAll('.v2-tr').forEach(function(row) {
      var nameEl = row.querySelector('.v2-tr-name');
      var varEl  = row.querySelector('.v2-tr-var');
      if (!nameEl) return;
      var page = (row.closest('.v2-page') || {}).id;
      if (page) page = page.replace('ecf-v2-page-', '');
      idx.push({
        type: 'token',
        label: nameEl.textContent.trim(),
        sub: varEl ? varEl.textContent.trim() : '',
        page: page || 'colors',
        el: row,
      });
    });

    /* Class names */
    w.querySelectorAll('[data-ecf-class-name], .ecf-class-name, .v2-cls-name').forEach(function(el) {
      var page = (el.closest('.v2-page') || {}).id;
      if (page) page = page.replace('ecf-v2-page-', '');
      idx.push({
        type: 'class',
        label: el.textContent.trim(),
        page: page || 'classes',
        el: el,
      });
    });

    _searchIndex = idx;
    return idx;
  }

  function _renderSearchResults(query) {
    var results = document.getElementById('v2-search-results');
    if (!results) return;
    if (!query.trim()) { results.innerHTML = ''; return; }
    var idx = _searchIndex || _buildSearchIndex();
    var q = query.toLowerCase();
    var matches = idx.filter(function(item) {
      return item.label.toLowerCase().indexOf(q) >= 0 || (item.sub && item.sub.toLowerCase().indexOf(q) >= 0);
    }).slice(0, 8);

    if (!matches.length) {
      results.innerHTML = '<div class="v2-search-empty">Keine Ergebnisse für "' + escapeHtml(query) + '"</div>';
      return;
    }

    var icons = { page: '◉', token: '◈', class: '◻' };
    results.innerHTML = matches.map(function(item, i) {
      var hi = item.label.replace(new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'), '<mark>$1</mark>');
      return '<button type="button" class="v2-search-result" data-search-idx="' + i + '">'
        + '<span class="v2-search-icon">' + (icons[item.type] || '·') + '</span>'
        + '<span class="v2-search-label">' + hi + '</span>'
        + (item.sub ? '<span class="v2-search-sub">' + escapeHtml(item.sub) + '</span>' : '')
        + '</button>';
    }).join('');

    results._matches = matches;
  }

  function _openSearch() {
    _searchIndex = null; /* rebuild on each open */
    var modal = document.getElementById('v2-search-modal');
    if (!modal) return;
    modal.hidden = false;
    var inp = document.getElementById('v2-search-input');
    if (inp) { inp.value = ''; inp.focus(); }
    document.getElementById('v2-search-results').innerHTML = '';
  }

  function _closeSearch() {
    var modal = document.getElementById('v2-search-modal');
    if (modal) modal.hidden = true;
  }

  /* Keyboard shortcuts — capture phase so WP core Cmd+K doesn't also fire */
  document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      e.stopImmediatePropagation();
      _openSearch();
    }
    if (e.key === 'Escape') { _closeSearch(); }
  }, true);

  /* Search trigger button */
  document.addEventListener('click', function(e) {
    if (e.target.closest('#v2-search-trigger')) _openSearch();
    if (e.target.id === 'v2-search-modal') _closeSearch();
    var resultBtn = e.target.closest('[data-search-idx]');
    if (resultBtn) {
      var idx = parseInt(resultBtn.dataset.searchIdx, 10);
      var list = document.getElementById('v2-search-results')._matches;
      if (list && list[idx]) {
        var item = list[idx];
        ecfV2Go(item.page);
        _closeSearch();
        if (item.el) {
          setTimeout(function() {
            item.el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            item.el.classList.add('v2-search-highlight');
            setTimeout(function() { item.el.classList.remove('v2-search-highlight'); }, 1500);
          }, 150);
        }
      }
    }
  });

  document.addEventListener('input', function(e) {
    if (e.target.id === 'v2-search-input') _renderSearchResults(e.target.value);
  });

  /* ── Live Preview ───────────────────────────────────────────────────── */
  function _updatePreviewVars() {
    var styleEl = document.getElementById('v2-pv-vars');
    if (!styleEl) return;
    var w = wrap();
    if (!w) return;
    var vars = '';

    /* Colors */
    w.querySelectorAll('.v2-color-hex-inp[data-v2-color-id]').forEach(function(inp) {
      var name = inp.dataset.v2ColorId;
      var val  = inp.value.trim();
      if (name && val) vars += '--pv-color-' + name + ':' + val + ';';
    });

    /* Radius */
    w.querySelectorAll('.v2-tr--radius').forEach(function(row) {
      var nameInp = row.querySelector('input[name*="[name]"]');
      var minInp  = row.querySelector('input[name*="[min]"]');
      if (!nameInp || !minInp) return;
      var name = nameInp.value.trim();
      var val  = minInp.value.trim();
      if (name && val) vars += '--pv-radius-' + name + ':' + val + ';';
    });

    styleEl.textContent = '#v2-pv-canvas{' + vars + '}';
    _updatePreviewScale();
  }

  function _updatePreviewScale() {
    var container = document.getElementById('v2-pv-scale');
    if (!container) return;
    var minBase  = parseFloat((document.getElementById('v2-sp-min-base')  || {}).value) || 16;
    var maxBase  = parseFloat((document.getElementById('v2-sp-max-base')  || {}).value) || 18;
    var minRatio = parseFloat((document.getElementById('v2-sp-min-ratio') || {}).value) || 1.125;
    var maxRatio = parseFloat((document.getElementById('v2-sp-max-ratio') || {}).value) || 1.25;
    var stepsWrap = document.getElementById('v2-ty-steps-wrap');
    var steps = [];
    if (stepsWrap) stepsWrap.querySelectorAll('.v2-step-input').forEach(function(inp) { if (inp.value) steps.push(inp.value); });
    if (!steps.length) steps = ['xs','s','m','l','xl','2xl','3xl','4xl'];
    var baseIdx = Math.floor(steps.length / 2);
    var html = '';
    steps.slice().reverse().forEach(function(step, revIdx) {
      var i = steps.length - 1 - revIdx;
      var exp = i - baseIdx;
      var px = Math.round(Math.max(minBase, maxBase) * Math.pow(Math.max(minRatio, maxRatio), exp) * 10) / 10;
      var clampedPx = Math.min(px, 52);
      html += '<div class="v2-pv-scale-row" style="font-size:' + clampedPx + 'px">'
        + '<span class="v2-pv-scale-step">' + step + '</span>'
        + '<span class="v2-pv-scale-sample">The quick brown fox</span>'
        + '<span class="v2-pv-scale-px">' + px.toFixed(0) + 'px</span>'
        + '</div>';
    });
    container.innerHTML = html;
  }

  /* Trigger update when preview page becomes visible (MutationObserver) */
  var _pvPage = document.getElementById('ecf-v2-page-preview');
  if (_pvPage && window.MutationObserver) {
    new MutationObserver(function(mutations) {
      mutations.forEach(function(m) {
        if (m.attributeName === 'class' && _pvPage.classList.contains('v2-page--on')) {
          _updatePreviewVars();
        }
      });
    }).observe(_pvPage, { attributes: true, attributeFilter: ['class'] });
  }

  /* Refresh button + dark toggle */
  document.addEventListener('click', function(e) {
    if (e.target.closest('#v2-pv-refresh')) _updatePreviewVars();
    if (e.target.closest('#v2-pv-dark-tog, #v2-pv-dark-cb')) {
      var canvas = document.getElementById('v2-pv-canvas');
      var cb = document.getElementById('v2-pv-dark-cb');
      if (!canvas || !cb) return;
      cb.checked = !cb.checked;
      canvas.classList.toggle('v2-pv-canvas--dark', cb.checked);
      var tog = document.getElementById('v2-pv-dark-tog');
      if (tog) tog.classList.toggle('v2-tog--on', cb.checked);
    }
  });

  /* ── Dark Mode Color Picker Sync ───────────────────────────────────── */
  document.addEventListener('input', function(e) {
    var cp = e.target;
    if (cp.dataset.v2DarkColorId) {
      var id  = cp.dataset.v2DarkColorId;
      var val = cp.value;
      var hexInp = document.querySelector('[data-v2-dark-hex-id="' + id + '"]');
      if (hexInp) hexInp.value = val;
      ecfV2ScheduleAutosave();
      return;
    }
    if (cp.dataset.v2DarkHexId) {
      var id2  = cp.dataset.v2DarkHexId;
      var val2 = cp.value;
      if (!/^#[0-9a-f]{6}$/i.test(val2)) return;
      var picker = document.querySelector('[data-v2-dark-color-id="' + id2 + '"]');
      if (picker) picker.value = val2;
    }
  });

  /* ── Konflikterkennung beim Sync ────────────────────────────────────── */
  var _syncConflicts = [];
  var _pendingSyncBtn = null;
  var _pendingSyncForm = null;

  function _doSync(btn, form) {
    if (form) { form.submit(); } else { ecfV2Sync(btn); }
  }

  function _runSyncAfterCheck(btn, form) {
    if (!window.ecfAdmin || !ecfAdmin.elementorValuesRestUrl) {
      _doSync(btn, form);
      return;
    }
    fetch(ecfAdmin.elementorValuesRestUrl, {
      headers: { 'X-WP-Nonce': ecfAdmin.restNonce },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data || !data.available || !data.values) {
        _doSync(btn, form);
        return;
      }
      var elValues = data.values;
      var settings = ecfV2CollectData();
      var colors   = (settings && settings.colors) ? settings.colors : [];
      var conflicts = [];
      colors.forEach(function(row) {
        var name = (row.name || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
        var layrixVal = (row.value || '').toLowerCase();
        var elKey = 'ecf-color-' + name;
        if (elValues[elKey] && elValues[elKey].toLowerCase() !== layrixVal) {
          conflicts.push({ name: name, layrix: layrixVal, elementor: elValues[elKey] });
        }
      });
      if (!conflicts.length) {
        _doSync(btn, form);
        return;
      }
      _syncConflicts = conflicts;
      _pendingSyncBtn = btn;
      _pendingSyncForm = form;
      var listEl = document.getElementById('v2-conflict-list');
      if (listEl) {
        listEl.innerHTML = conflicts.map(function(c) {
          return '<div class="v2-conflict-row">'
            + '<span class="v2-conflict-name">--ecf-color-' + escapeHtml(c.name) + '</span>'
            + '<span class="v2-conflict-vals">'
            + '<span class="v2-conflict-swatch" style="background:' + escapeHtml(c.layrix) + '" title="Layrix"></span>'
            + escapeHtml(c.layrix)
            + ' <span class="v2-conflict-arrow">→</span> '
            + '<span class="v2-conflict-swatch" style="background:' + escapeHtml(c.elementor) + '" title="Elementor"></span>'
            + escapeHtml(c.elementor)
            + '</span>'
            + '</div>';
        }).join('');
      }
      var modal = document.getElementById('v2-conflict-modal');
      if (modal) modal.hidden = false;
    })
    .catch(function() {
      _doSync(btn, form);
    });
  }

  /* Wire up conflict modal buttons */
  document.addEventListener('click', function(e) {
    if (e.target.id === 'v2-conflict-cancel' || e.target.id === 'v2-conflict-cancel2') {
      var modal = document.getElementById('v2-conflict-modal');
      if (modal) modal.hidden = true;
      _pendingSyncBtn = null;
    }
    if (e.target.id === 'v2-conflict-confirm') {
      var modal2 = document.getElementById('v2-conflict-modal');
      if (modal2) modal2.hidden = true;
      _doSync(_pendingSyncBtn, _pendingSyncForm);
      _pendingSyncBtn = null;
      _pendingSyncForm = null;
    }
  });

  /* Intercept sync form submit for conflict detection */
  document.querySelectorAll('form').forEach(function(frm) {
    var hidden = frm.querySelector('input[name="action"][value="ecf_native_sync"]');
    if (!hidden) return;
    frm.addEventListener('submit', function(e) {
      e.preventDefault();
      _runSyncAfterCheck(null, frm);
    });
  });

  /* Also wire [data-v2-sync] buttons (REST-based sync) */
  document.querySelectorAll('[data-v2-sync]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopImmediatePropagation();
      _runSyncAfterCheck(btn, null);
    }, true);
  });

  /* Page-load class limit check */
  (function() {
    if (!window.ecfAdmin) return;
    var cTotal = parseInt(ecfAdmin.classesTotal, 10);
    var cLimit = parseInt(ecfAdmin.classesLimit, 10);
    if (cLimit > 0 && cTotal >= cLimit) ecfV2ShowClassLimitWarning(cTotal, cLimit);
  }());

  /* Live UI font settings preview */
  (function() {
    var wrapper = document.getElementById('ecf-v2-wrapper');
    if (!wrapper) return;

    var fontHint = document.getElementById('v2-ui-font-hint');

    function bindLive(selector, cssVar, transform) {
      var el = document.querySelector(selector);
      if (!el) return;
      el.addEventListener('input', function() {
        var val = transform ? transform(el.value) : el.value;
        if (val) wrapper.style.setProperty(cssVar, val);
      });
    }

    bindLive('[name$="[ui_font_family]"]',    '--v2-font',        function(v) {
      if (fontHint) fontHint.textContent = v || 'Plus Jakarta Sans';
      return v || "'Plus Jakarta Sans', system-ui, sans-serif";
    });
    bindLive('[name$="[ui_base_font_size]"]', '--v2-ui-base-fs',  function(v) { return parseInt(v, 10) + 'px'; });
    bindLive('[name$="[ui_nav_font_size]"]',  '--v2-ui-nav-fs',   function(v) { return parseInt(v, 10) + 'px'; });
    bindLive('[name$="[ui_btn_font_size]"]',  '--v2-btn-fs',      function(v) { return parseInt(v, 10) + 'px'; });
  }());

  /* ── Auto-Klassen master toggle: enable/disable per-widget rows ──── */
  (function() {
    var master = document.getElementById('v2-auto-classes-master');
    var table  = document.getElementById('v2-auto-classes-table');
    if (!master || !table) return;
    function sync() {
      var on = master.checked;
      table.classList.toggle('is-disabled', !on);
      table.querySelectorAll('input.v2-tog-cb').forEach(function(cb) {
        cb.disabled = !on;
      });
    }
    master.addEventListener('change', sync);
    sync();
  }());

  /* ── Cookbook: copy recipe snippets ──────────────────────────────── */
  (function() {
    function copyText(text, onDone) {
      var fallback = function() {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); onDone(); } catch (_) {}
        document.body.removeChild(ta);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(onDone).catch(fallback);
      } else {
        fallback();
      }
    }

    document.addEventListener('click', function(e) {
      // Recipe card with data-v2-copy — copy the class string
      var target = e.target.closest('[data-v2-copy]');
      if (!target) return;
      var cls = target.getAttribute('data-v2-copy') || '';
      copyText(cls, function() {
        target.classList.add('is-copied');
        setTimeout(function() { target.classList.remove('is-copied'); }, 1200);
      });
    });
  }());

}());
