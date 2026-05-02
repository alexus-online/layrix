/**
 * Layrix Atomic Section editor registration + auto-class injection.
 */
(function() {
  'use strict';

  var TYPE = 'e-layrix-section';
  var auto = window.ecfAutoClasses || null;

  /* ────────────────────────────────────────────────────────────────────
   * Section element type registration (so default_children fires)
   * ────────────────────────────────────────────────────────────────── */
  var registered = false;
  function tryRegister() {
    if (registered) return true;
    if (typeof window.elementor === 'undefined') return false;
    if (!window.elementor.elementsManager) return false;
    var modules = window.elementor.modules || {};
    var elementsModules = modules.elements || {};
    var types = elementsModules.types || {};
    var views = elementsModules.views || {};
    if (!types.AtomicElementBase) return false;
    if (typeof views.createAtomicElementBase !== 'function') return false;
    try {
      var View = views.createAtomicElementBase(TYPE);
      window.elementor.elementsManager.registerElementType(new types.AtomicElementBase(TYPE, View));
      registered = true;
      return true;
    } catch (err) {
      var msg = (err && err.message) || '';
      if (/already registered/i.test(msg)) {
        registered = true;
        return true;
      }
      window.console && window.console.error('[Layrix] atomic registration failed', err);
      return true;
    }
  }
  if (!tryRegister()) {
    if (window.elementor && typeof window.elementor.on === 'function') {
      window.elementor.on('panel:init', tryRegister);
      window.elementor.on('preview:loaded', tryRegister);
    }
    var attempts = 0;
    var interval = setInterval(function() {
      if (tryRegister() || attempts++ > 50) clearInterval(interval);
    }, 200);
  }

  /* ────────────────────────────────────────────────────────────────────
   * Auto-class injection for e-heading + e-button
   * ────────────────────────────────────────────────────────────────── */
  function getElementKey(container) {
    if (!container || !container.model || !container.model.get) return null;
    var widgetType = container.model.get('widgetType');
    if (widgetType) return widgetType;
    return container.model.get('elType') || null;
  }
  function isHeading(c) { return getElementKey(c) === 'e-heading'; }
  function isButton(c)  { return getElementKey(c) === 'e-button'; }

  function getCurrentClassValues(container) {
    if (!container || !container.settings || !container.settings.toJSON) return [];
    var raw = container.settings.toJSON();
    if (!raw || !raw.classes) return [];
    if (Array.isArray(raw.classes)) return raw.classes.slice();
    if (raw.classes.value && Array.isArray(raw.classes.value)) return raw.classes.value.slice();
    return [];
  }

  function setClassValues(container, values) {
    if (!window.$e || !window.$e.run) return;
    try {
      window.$e.run('document/elements/settings', {
        container: container,
        settings: {
          classes: { '$$type': 'classes', 'value': values }
        }
      });
    } catch (err) {
      window.console && window.console.warn('[Layrix] set classes failed', err);
    }
  }

  function ensureClassPresent(container, classId) {
    if (!classId) return;
    var values = getCurrentClassValues(container);
    if (values.indexOf(classId) >= 0) return;
    values.push(classId);
    setClassValues(container, values);
  }

  function unwrapTag(tag) {
    if (!tag) return null;
    if (typeof tag === 'string') return tag.toLowerCase();
    if (typeof tag === 'object' && tag.value) return String(tag.value).toLowerCase();
    return null;
  }

  function getHeadingClassId(container) {
    if (!auto || !auto.headingsEnabled) return null;
    if (!container || !container.settings) return null;
    var rawTag = container.settings.get ? container.settings.get('tag') : null;
    var tag = unwrapTag(rawTag) || 'h1';
    var ids = auto.headingClassIds || {};
    return ids[tag] || null;
  }

  function applyAutoClassIfApplicable(container) {
    if (!auto || !auto.masterEnabled) return;
    if (isHeading(container)) {
      var headingId = getHeadingClassId(container);
      if (headingId) ensureClassPresent(container, headingId);
      return;
    }
    if (isButton(container)) {
      if (auto.buttonsEnabled && auto.buttonClassId) {
        ensureClassPresent(container, auto.buttonClassId);
      }
    }
  }

  /* When the heading tag changes, swap the matching ecf-heading-N class.
     The class itself carries the typography props (synced via Layrix). */
  function syncHeadingClassOnTagChange(container) {
    if (!auto || !auto.masterEnabled || !auto.headingsEnabled) return;
    if (!isHeading(container)) return;
    var allIds = Object.keys(auto.headingClassIds || {}).map(function(k) {
      return auto.headingClassIds[k];
    });
    var current = getCurrentClassValues(container);
    var desired = getHeadingClassId(container);
    var stripped = current.filter(function(id) { return allIds.indexOf(id) < 0; });
    var next = desired ? stripped.concat([desired]) : stripped;
    var same = current.length === next.length && current.every(function(v, i) { return v === next[i]; });
    if (!same) setClassValues(container, next);
  }

  /* Recursively walk the document tree, applying auto-class + tag watcher.
     Typography/width values come from the synced global classes themselves
     (ecf-heading-N, ecf-container-boxed) — no local style injection here. */
  function visit(container) {
    if (!container) return;
    applyAutoClassIfApplicable(container);
    if (isHeading(container) && container.settings && !container.__layrixTagWatcher) {
      container.settings.on('change:tag', function() {
        syncHeadingClassOnTagChange(container);
      });
      container.__layrixTagWatcher = true;
    }
    var kids = container.children;
    if (kids && kids.length) {
      kids.forEach(visit);
    }
  }

  function scanCurrentDocument() {
    if (!window.elementor || !window.elementor.documents) return;
    var doc = window.elementor.documents.getCurrent && window.elementor.documents.getCurrent();
    if (!doc || !doc.container) return;
    visit(doc.container);
  }

  /* ────────────────────────────────────────────────────────────────────
   * Wiring
   * ────────────────────────────────────────────────────────────────── */
  function setupCommandListener() {
    if (!window.$e || !window.$e.commands || typeof window.$e.commands.on !== 'function') return false;
    window.$e.commands.on('run:after', function(component, command) {
      if (command === 'document/elements/create' ||
          command === 'document/elements/duplicate' ||
          command === 'document/elements/paste' ||
          command === 'document/elements/import') {
        setTimeout(scanCurrentDocument, 50);
      }
    });
    return true;
  }

  function init() {
    if (!auto) return;
    setupCommandListener();
    setTimeout(scanCurrentDocument, 200);
  }

  if (window.elementor && typeof window.elementor.on === 'function') {
    window.elementor.on('panel:init', function() { setTimeout(init, 50); });
    window.elementor.on('preview:loaded', function() { setTimeout(init, 100); });
    window.elementor.on('document:loaded', function() { setTimeout(scanCurrentDocument, 100); });
  }
  setTimeout(init, 600);
}());
