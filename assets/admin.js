jQuery(function($){
  var i18n = (typeof ecfAdmin !== 'undefined' && ecfAdmin.i18n) ? ecfAdmin.i18n : {};
  var spacingPreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.spacingPreview) ? ecfAdmin.spacingPreview : {};
  var typePreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.typePreview) ? ecfAdmin.typePreview : {};
  var typePreviewTexts = (typeof ecfAdmin !== 'undefined' && ecfAdmin.typePreviewTexts) ? ecfAdmin.typePreviewTexts : {};
  var radiusPreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.radiusPreview) ? ecfAdmin.radiusPreview : {};
  var restUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.restUrl) ? ecfAdmin.restUrl : '';
  var syncRestUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.syncRestUrl) ? ecfAdmin.syncRestUrl : '';
  var fontImportRestUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.fontImportRestUrl) ? ecfAdmin.fontImportRestUrl : '';
  var fontSearchRestUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.fontSearchRestUrl) ? ecfAdmin.fontSearchRestUrl : '';
  var layoutRestUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.layoutRestUrl) ? ecfAdmin.layoutRestUrl : '';
  var restNonce = (typeof ecfAdmin !== 'undefined' && ecfAdmin.restNonce) ? ecfAdmin.restNonce : '';
  var adminDesign = (typeof ecfAdmin !== 'undefined' && ecfAdmin.adminDesign) ? ecfAdmin.adminDesign : {};
  var layoutOrders = (typeof ecfAdmin !== 'undefined' && ecfAdmin.layoutOrders) ? ecfAdmin.layoutOrders : {};
  var layoutColumns = (typeof ecfAdmin !== 'undefined' && ecfAdmin.layoutColumns) ? ecfAdmin.layoutColumns : {};
  var defaultLayoutOrders = {};
  var defaultLayoutColumns = {};
  var fontLibrary = (typeof ecfAdmin !== 'undefined' && Array.isArray(ecfAdmin.fontLibrary)) ? ecfAdmin.fontLibrary : [];
  var fontSearchTimers = {};
  var fontSearchRequests = {};
  i18n.copy    = String(i18n.copy || '');
  i18n.copied  = String(i18n.copied || '');

  function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function round(value, precision) {
    var factor = Math.pow(10, precision || 0);
    return Math.round(value * factor) / factor;
  }

  function formatNumber(value, precision) {
    var rounded = round(value, precision || 0);
    return String(rounded).replace(/\.0+$|(\.\d*[1-9])0+$/, '$1');
  }

  function hexToRgb(hex) {
    var value = String(hex || '').trim().replace(/^#/, '');
    if (value.length === 3) {
      value = value.replace(/(.)/g, '$1$1');
    }
    if (value.length === 4) {
      value = value.replace(/(.)/g, '$1$1');
    }
    if (value.length === 8) {
      value = value.slice(0, 6);
    }
    if (!/^[0-9a-f]{6}$/i.test(value)) return null;
    return {
      r: parseInt(value.slice(0, 2), 16),
      g: parseInt(value.slice(2, 4), 16),
      b: parseInt(value.slice(4, 6), 16)
    };
  }

  function parseAlphaFromHex(value) {
    var hex = String(value || '').trim().replace(/^#/, '');
    if (hex.length === 4) {
      return parseInt(hex.charAt(3) + hex.charAt(3), 16) / 255;
    }
    if (hex.length === 8) {
      return parseInt(hex.slice(6, 8), 16) / 255;
    }
    return 1;
  }

  function componentToHex(value) {
    return clamp(Math.round(value), 0, 255).toString(16).padStart(2, '0');
  }

  function rgbToHex(rgb) {
    if (!rgb) return '';
    return '#' + componentToHex(rgb.r) + componentToHex(rgb.g) + componentToHex(rgb.b);
  }

  function alphaToHex(alpha) {
    return componentToHex(clamp((alpha == null ? 1 : alpha) * 255, 0, 255));
  }

  function rgbToHsl(rgb) {
    if (!rgb) return null;
    var r = clamp(rgb.r, 0, 255) / 255;
    var g = clamp(rgb.g, 0, 255) / 255;
    var b = clamp(rgb.b, 0, 255) / 255;
    var max = Math.max(r, g, b);
    var min = Math.min(r, g, b);
    var h, s;
    var l = (max + min) / 2;
    var d = max - min;

    if (d === 0) {
      h = 0;
      s = 0;
    } else {
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r:
          h = ((g - b) / d) + (g < b ? 6 : 0);
          break;
        case g:
          h = ((b - r) / d) + 2;
          break;
        default:
          h = ((r - g) / d) + 4;
          break;
      }
      h = h * 60;
    }

    return {
      h: round(h, 1),
      s: round(s * 100, 1),
      l: round(l * 100, 1)
    };
  }

  function hueToRgb(p, q, t) {
    if (t < 0) t += 1;
    if (t > 1) t -= 1;
    if (t < 1 / 6) return p + (q - p) * 6 * t;
    if (t < 1 / 2) return q;
    if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
    return p;
  }

  function hslToRgb(hsl) {
    if (!hsl) return null;
    var h = ((((hsl.h % 360) + 360) % 360) / 360);
    var s = clamp(hsl.s, 0, 100) / 100;
    var l = clamp(hsl.l, 0, 100) / 100;
    var r, g, b;

    if (s === 0) {
      r = g = b = l;
    } else {
      var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      var p = 2 * l - q;
      r = hueToRgb(p, q, h + 1 / 3);
      g = hueToRgb(p, q, h);
      b = hueToRgb(p, q, h - 1 / 3);
    }

    return {
      r: Math.round(r * 255),
      g: Math.round(g * 255),
      b: Math.round(b * 255)
    };
  }

  function parseRgbValue(value) {
    var match = String(value || '').trim().match(/^rgba?\s*\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i);
    if (!match) return null;
    return {
      r: clamp(parseFloat(match[1]), 0, 255),
      g: clamp(parseFloat(match[2]), 0, 255),
      b: clamp(parseFloat(match[3]), 0, 255),
      a: match[4] === undefined || match[4] === '' ? 1 : clamp(parseFloat(match[4]), 0, 1)
    };
  }

  function parseHslValue(value) {
    var match = String(value || '').trim().match(/^hsla?\s*\(\s*([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)%\s*,\s*([+-]?\d+(?:\.\d+)?)%(?:\s*,\s*([+-]?\d*(?:\.\d+)?))?\s*\)$/i);
    if (!match) return null;
    return {
      h: parseFloat(match[1]),
      s: clamp(parseFloat(match[2]), 0, 100),
      l: clamp(parseFloat(match[3]), 0, 100),
      a: match[4] === undefined || match[4] === '' ? 1 : clamp(parseFloat(match[4]), 0, 1)
    };
  }

  function parseHexValue(value) {
    var match = String(value || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i);
    if (!match) return null;
    var rgb = hexToRgb(match[1]);
    if (!rgb) return null;
    rgb.a = parseAlphaFromHex(match[1]);
    return rgb;
  }

  function parseDisplayColor(value, format) {
    if (format === 'rgb' || format === 'rgba') return parseRgbValue(value);
    if (format === 'hsl' || format === 'hsla') {
      var hsl = parseHslValue(value);
      if (!hsl) return null;
      var rgb = hslToRgb(hsl);
      rgb.a = hsl.a == null ? 1 : hsl.a;
      return rgb;
    }
    return parseHexValue(value);
  }

  function detectStoredFormat(value) {
    var normalized = String(value || '').trim().toLowerCase();
    if (/^#[0-9a-f]{8}$/.test(normalized)) return 'hexa';
    if (/^#[0-9a-f]{6}$/.test(normalized)) return 'hex';
    if (/^rgba\(/.test(normalized)) return 'rgba';
    if (/^rgb\(/.test(normalized)) return 'rgb';
    if (/^hsla\(/.test(normalized)) return 'hsla';
    if (/^hsl\(/.test(normalized)) return 'hsl';
    return 'hex';
  }

  function formatColorValue(hex, format) {
    var parsed = null;
    if (hex && typeof hex === 'object') {
      parsed = {
        r: hex.r,
        g: hex.g,
        b: hex.b,
        a: hex.a == null ? 1 : hex.a
      };
    } else {
      parsed = parseHexValue(hex) || parseRgbValue(hex);
      if (!parsed && (/^hsl/i).test(String(hex || '').trim())) {
        parsed = parseDisplayColor(hex, 'hsla');
      }
    }
    var rgb = parsed;
    if (!rgb) return '';
    var alpha = rgb.a == null ? 1 : clamp(rgb.a, 0, 1);
    if (format === 'rgb') {
      return 'rgb(' + formatNumber(rgb.r, 0) + ', ' + formatNumber(rgb.g, 0) + ', ' + formatNumber(rgb.b, 0) + ')';
    }
    if (format === 'rgba') {
      return 'rgba(' + formatNumber(rgb.r, 0) + ', ' + formatNumber(rgb.g, 0) + ', ' + formatNumber(rgb.b, 0) + ', ' + formatNumber(alpha, 3) + ')';
    }
    if (format === 'hsl') {
      var hsl = rgbToHsl(rgb);
      return 'hsl(' + formatNumber(hsl.h, 1) + ', ' + formatNumber(hsl.s, 1) + '%, ' + formatNumber(hsl.l, 1) + '%)';
    }
    if (format === 'hsla') {
      var hsla = rgbToHsl(rgb);
      return 'hsla(' + formatNumber(hsla.h, 1) + ', ' + formatNumber(hsla.s, 1) + '%, ' + formatNumber(hsla.l, 1) + '%, ' + formatNumber(alpha, 3) + ')';
    }
    if (format === 'hexa') {
      return rgbToHex(rgb).toUpperCase() + alphaToHex(alpha).toUpperCase();
    }
    return rgbToHex(rgb).toUpperCase();
  }

  function mixRgb(a, b, amount) {
    return {
      r: Math.round(a.r + (b.r - a.r) * amount),
      g: Math.round(a.g + (b.g - a.g) * amount),
      b: Math.round(a.b + (b.b - a.b) * amount),
      a: a.a == null ? 1 : a.a
    };
  }

  function colorGeneratorItems(baseRgb, type, count) {
    var items = [];
    var target = type === 'tints'
      ? { r: 255, g: 255, b: 255, a: baseRgb.a == null ? 1 : baseRgb.a }
      : { r: 0, g: 0, b: 0, a: baseRgb.a == null ? 1 : baseRgb.a };
    var safeCount = clamp(parseInt(count, 10) || 6, 4, 10);

    for (var i = 1; i <= safeCount; i++) {
      var amount = i / (safeCount + 1);
      var rgb = mixRgb(baseRgb, target, amount);
      items.push({
        label: (type === 'tints' ? 'tint-' : 'shade-') + i,
        value: rgbToHex(rgb).toUpperCase()
      });
    }

    return items;
  }

  function updateColorRowDisplay($row) {
    var hex = $row.find('.ecf-color-value-input').val() || $row.find('.ecf-color-field').val();
    var format = $row.find('.ecf-color-format-select').val() || 'hex';
    var displayValue = formatColorValue(hex, format);
    $row.find('.ecf-color-value-display').val(displayValue);
    updateColorDetail($row, displayValue);
  }

  function updateColorDetail($row, displayValue) {
    var $detail = $row.find('.ecf-color-detail').first();
    if (!$detail.length) return;
    var name = $.trim($row.find('[data-ecf-slug-field="token"]').first().val() || 'name');
    var value = displayValue || $row.find('.ecf-color-value-input').val() || $row.find('.ecf-color-field').val() || '#000000';
    var parsed = parseDisplayColor(value, $row.find('.ecf-color-format-select').val() || 'hex') || parseHexValue(value) || parseRgbValue(value);
    var hex = parsed ? rgbToHex(parsed).toUpperCase() : '#000000';
    var token = '--ecf-color-' + (name || 'name');
    $detail.find('.ecf-color-detail__preview').css('--ecf-color-detail-base', hex);
    $detail.find('.ecf-color-detail__meta strong').text(token);
    $detail.find('.ecf-color-detail__meta code').text(value || hex);
    renderColorGeneratedPalette($detail, parsed || { r: 0, g: 0, b: 0, a: 1 });
  }

  function renderColorGeneratedPalette($detail, baseRgb) {
    var enabled = [];
    $detail.find('[data-ecf-color-generate]').each(function() {
      var $input = $(this);
      if ($input.is(':checked')) {
        enabled.push($input.attr('data-ecf-color-generate'));
      }
    });

    var items = [];
    enabled.forEach(function(type) {
      var count = $detail.find('[data-ecf-color-count="' + type + '"]').val();
      items = items.concat(colorGeneratorItems(baseRgb, type, count));
    });

    if (!items.length) {
      items = [{ label: 'base', value: rgbToHex(baseRgb).toUpperCase() }];
    }

    var previewHtml = items.map(function(item) {
      return '<span style="background:' + escapeHtml(item.value) + ';" data-tip="' + escapeHtml(item.label + ': ' + item.value) + '"></span>';
    }).join('');

    var chipHtml = items.map(function(item) {
      var token = ($detail.find('.ecf-color-detail__meta strong').text() || '--ecf-color-name') + '-' + item.label;
      return '<button type="button" class="ecf-color-token-copy" data-ecf-copy-text="' + escapeHtml(token) + '"><i style="background:' + escapeHtml(item.value) + ';"></i><span><code>' + escapeHtml(token) + '</code><small>' + escapeHtml(item.value) + '</small></span><em>' + escapeHtml(i18n.color_generator_copy || '') + '</em></button>';
    }).join('');

    $detail.find('.ecf-color-detail__preview').html(previewHtml);
    $detail.find('.ecf-color-detail__shades').html(chipHtml);
  }

  function normalizeDisplayColorValue(value) {
    var normalized = $.trim(String(value || ''));
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(normalized) ? normalized : '';
  }

  function syncGeneralColorField($field, nextValue, options) {
    if (!$field || !$field.length) return;
    var settings = options || {};
    var fallback = normalizeDisplayColorValue($field.find('.ecf-color-text').attr('data-default-color')) || '#000000';
    var value = normalizeDisplayColorValue(nextValue != null ? nextValue : $field.find('.ecf-color-text').val());
    var $picker = $field.find('.ecf-color-field--general').first();

    if (settings.writeText && value) {
      $field.find('.ecf-color-text').val(value);
    }

    if ($picker.length) {
      var targetColor = value || fallback;
      if (String($picker.val() || '').toUpperCase() !== String(targetColor).toUpperCase()) {
        $picker.val(targetColor);
        if ($picker.hasClass('wp-color-picker')) {
          $picker.wpColorPicker('color', targetColor);
        }
      }
    }
  }

  function initGeneralColorPickers(scope) {
    scope.find('.ecf-color-field--general').each(function() {
      var $input = $(this);
      if ($input.data('ecfColorPickerReady')) {
        return;
      }

      $input.data('ecfColorPickerReady', true);
      $input.wpColorPicker({
        change: function(event, ui) {
          var $field = $(this).closest('[data-ecf-general-field]');
          var hex = ui.color.toString();
          syncGeneralColorField($field, hex, { writeText: true });
          $field.find('.ecf-color-text').trigger('input').trigger('change');
        },
        clear: function() {
          var $field = $(this).closest('[data-ecf-general-field]');
          $field.find('.ecf-color-text').val('');
          scheduleSettingsAutosave({ delay: 250 });
        }
      });

      syncGeneralColorField($input.closest('[data-ecf-general-field]'));
    });
  }

  function applyDisplayValueToRow($row) {
    var $display = $row.find('.ecf-color-value-display');
    var format = $row.find('.ecf-color-format-select').val() || 'hex';
    var rgb = parseDisplayColor($display.val(), format);
    if (!rgb) {
      $display.toggleClass('ecf-input-invalid', $.trim($display.val()) !== '');
      return false;
    }
    var hex = rgbToHex(rgb).toUpperCase();
    var stored = formatColorValue({
      r: rgb.r,
      g: rgb.g,
      b: rgb.b,
      a: rgb.a == null ? 1 : rgb.a
    }, format);
    $display.removeClass('ecf-input-invalid');
    $row.find('.ecf-color-value-input').val(stored);
    $row.find('.ecf-color-field').val(hex).wpColorPicker('color', hex);
    updateColorRowDisplay($row);
    return true;
  }

  function initColorPickers(scope){
    scope.find('.ecf-color-field').wpColorPicker({
      change: function(event, ui) {
        var $row = $(this).closest('.ecf-row--color');
        var hex = ui.color.toString();
        var currentStored = $row.find('.ecf-color-value-input').val();
        var currentParsed = parseHexValue(currentStored) || parseRgbValue(currentStored) || parseDisplayColor(currentStored, 'hsla') || {};
        var alpha = currentParsed.a == null ? 1 : currentParsed.a;
        var storedFormat = detectStoredFormat(currentStored);
        $(this).val(hex);
        $row.find('.ecf-color-value-input').val(formatColorValue({
          r: parseInt(hex.slice(1, 3), 16),
          g: parseInt(hex.slice(3, 5), 16),
          b: parseInt(hex.slice(5, 7), 16),
          a: alpha
        }, storedFormat));
        updateColorRowDisplay($row);
        scheduleSettingsAutosave({ delay: 250 });
      },
      clear: function() {
        var $row = $(this).closest('.ecf-row--color');
        $(this).val('');
        $row.find('.ecf-color-value-input').val('');
        $row.find('.ecf-color-value-display').val('');
        scheduleSettingsAutosave({ delay: 250 });
      }
    });
    scope.find('.ecf-row--color').each(function(){
      updateColorRowDisplay($(this));
    });
  }
  function nextIndex(group){
    return $('.ecf-table[data-group="'+group+'"] .ecf-row').length;
  }
  function inputKey(group){
    return $('.ecf-table[data-group="'+group+'"]').data('input-key') || ('ecf_framework_v50['+group+']');
  }
  function nextLocalFontIndex($table) {
    return ($table && $table.length ? $table : $('[data-local-font-table]').first()).find('.ecf-font-file-row').length;
  }
  initColorPickers($(document));
  initGeneralColorPickers($(document));

  $(document).on('click', '.ecf-add-row', function(){
    var group = $(this).data('group');
    var index = nextIndex(group);
    var key   = inputKey(group);
    var isColor  = group === 'colors';
    var isMinMax = $('.ecf-table[data-group="'+group+'"]').data('minmax') === 1;
    var templateId = isColor ? '#ecf-row-template-color' : (isMinMax ? '#ecf-row-template-minmax' : '#ecf-row-template-default');
    var html = $(templateId).html()
      .replace(/__NAME__/g,  key+'['+index+'][name]')
      .replace(/__NAME_BASE__/g,  key+'['+index+']')
      .replace(/__VALUE__/g, key+'['+index+'][value]')
      .replace(/__FORMAT__/g, key+'['+index+'][format]')
      .replace(/__MIN__/g,   key+'['+index+'][min]')
      .replace(/__MAX__/g,   key+'['+index+'][max]');
    var $row = $(html);
    $('.ecf-table[data-group="'+group+'"]').append($row);
    if (isColor) initColorPickers($row);
    renderTypePreview();
    renderShadowPreview();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('click', '.ecf-remove-row', function(){
    var $row = $(this).closest('.ecf-row, .ecf-font-file-row');
    if ($row.find('.ecf-color-field').length) $row.find('.wp-picker-container').remove();
    $row.remove();
    renderTypePreview();
    renderShadowPreview();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('click', '.ecf-remove-last-row', function(){
    var group = $(this).data('group');
    var $table = $('.ecf-table[data-group="' + group + '"]');
    var $rows = $table.find('.ecf-row');
    if ($rows.length <= 1) return;
    var $last = $rows.last();
    if ($last.find('.ecf-color-field').length) $last.find('.wp-picker-container').remove();
    $last.remove();
    renderTypePreview();
    renderShadowPreview();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('change', '.ecf-color-format-select', function(){
    updateColorRowDisplay($(this).closest('.ecf-row--color'));
  });

  $(document).on('click', '.ecf-color-detail-toggle', function(e) {
    e.preventDefault();
    var $row = $(this).closest('.ecf-row--color');
    var isOpen = !$row.hasClass('is-open');
    $row.toggleClass('is-open', isOpen);
    $(this).attr('aria-expanded', isOpen ? 'true' : 'false');
    $row.find('.ecf-color-detail').first().prop('hidden', !isOpen);
  });

  $(document).on('change', '[data-ecf-color-generate]', function() {
    updateColorRowDisplay($(this).closest('.ecf-row--color'));
  });

  $(document).on('input change', '[data-ecf-color-count]', function() {
    var value = clamp(parseInt($(this).val(), 10) || 6, 4, 10);
    $(this).val(value);
    updateColorRowDisplay($(this).closest('.ecf-row--color'));
  });

  $(document).on('click', '[data-ecf-color-count-minus], [data-ecf-color-count-plus]', function(e) {
    e.preventDefault();
    var attrName = $(this).is('[data-ecf-color-count-minus]') ? 'data-ecf-color-count-minus' : 'data-ecf-color-count-plus';
    var type = $(this).attr(attrName);
    var $input = $(this).closest('.ecf-color-generator-count').find('[data-ecf-color-count="' + type + '"]');
    var delta = attrName === 'data-ecf-color-count-minus' ? -1 : 1;
    var value = clamp((parseInt($input.val(), 10) || 6) + delta, 4, 10);
    $input.val(value);
    updateColorRowDisplay($(this).closest('.ecf-row--color'));
  });

  $(document).on('input change', '.ecf-color-value-display', function(){
    applyDisplayValueToRow($(this).closest('.ecf-row--color'));
  });

  $(document).on('input change', '.ecf-row--color [data-ecf-slug-field="token"]', function(){
    updateColorRowDisplay($(this).closest('.ecf-row--color'));
  });

  $(document).on('blur', '.ecf-color-value-display', function(){
    var $row = $(this).closest('.ecf-row--color');
    if (!applyDisplayValueToRow($row)) {
      updateColorRowDisplay($row);
      $(this).removeClass('ecf-input-invalid');
    }
  });

  $(document).on('input change', '.ecf-color-text', function() {
    syncGeneralColorField($(this).closest('[data-ecf-general-field]'), $(this).val());
  });

  $(document).on('click', '.ecf-add-local-font', function(){
    var $table = $(this).siblings('[data-local-font-table]').first();
    if (!$table.length) {
      $table = $(this).closest('[data-ecf-local-fonts-section], .ecf-card').find('[data-local-font-table]').first();
    }
    if (!$table.length) return;
    var key = $table.data('input-key');
    var index = nextLocalFontIndex($table);
    var styleOptions = [
      { value: 'normal', label: i18n.font_style_normal || '' },
      { value: 'italic', label: i18n.font_style_italic || '' },
      { value: 'oblique', label: i18n.font_style_oblique || '' }
    ].map(function(item) {
      return '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>';
    }).join('');
    var displayOptions = [
      { value: 'swap', label: i18n.font_display_swap || '' },
      { value: 'fallback', label: i18n.font_display_fallback || '' },
      { value: 'optional', label: i18n.font_display_optional || '' },
      { value: 'block', label: i18n.font_display_block || '' },
      { value: 'auto', label: i18n.font_display_auto || '' }
    ].map(function(item) {
      return '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>';
    }).join('');
    var html = '<div class="ecf-font-file-row">'
      + '<input type="text" data-ecf-slug-field="token" name="' + key + '[' + index + '][name]" value="" placeholder="' + escapeHtml(i18n.local_font_name_placeholder || '') + '" />'
      + '<input type="text" name="' + key + '[' + index + '][family]" value="" placeholder="' + escapeHtml(i18n.local_font_family_placeholder || '') + '" />'
      + '<div class="ecf-font-file-picker">'
      + '<input type="text" class="ecf-font-file-url" name="' + key + '[' + index + '][src]" value="" placeholder="' + escapeHtml(i18n.local_font_upload_placeholder || '') + '" readonly />'
      + '<button type="button" class="button ecf-font-file-select">' + ecfAdmin.i18n.select_file + '</button>'
      + '</div>'
      + '<input type="text" name="' + key + '[' + index + '][weight]" value="400" placeholder="400" />'
      + '<select name="' + key + '[' + index + '][style]">' + styleOptions + '</select>'
      + '<select name="' + key + '[' + index + '][display]">' + displayOptions + '</select>'
      + '<button type="button" class="button ecf-remove-row">×</button>'
      + '</div>';
    $table.append($(html));
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('click', '.ecf-font-file-select', function(e){
    e.preventDefault();
    var $button = $(this);
    var frame = wp.media({
      title: ecfAdmin.i18n.choose_font,
      button: { text: ecfAdmin.i18n.use_font },
      library: { type: ['application/font-woff', 'font/woff', 'font/woff2', 'font/ttf', 'font/otf', 'application/octet-stream'] },
      multiple: false
    });

    frame.on('select', function() {
      var attachment = frame.state().get('selection').first().toJSON();
      $button.siblings('.ecf-font-file-url').val(attachment.url).trigger('input').trigger('change');
      scheduleSettingsAutosave({ delay: 250 });
    });

    frame.open();
  });

  function formatPreviewNumber(value) {
    var rounded = Math.round(value * 100) / 100;
    return String(rounded).replace(/\.0+$|(\.\d*[1-9])0+$/, '$1');
  }

  function syncRootFontSizeControls(value, source) {
    var normalized = (String(value) === '100') ? '100' : '62.5';
    $('[data-ecf-root-font-source], [data-ecf-root-font-mirror]').each(function() {
      if (source && this === source) return;
      $(this).val(normalized);
    });
    updateRootFontSizeLabels(normalized);
  }

  function updateRootFontSizeLabels(value) {
    var normalized = (String(value) === '100') ? '100' : '62.5';
    var rootBasePx = normalized === '62.5' ? 10 : 16;
    $('[data-ecf-root-font-inline]').text(rootBasePx + 'px = 1rem');
    $('[data-ecf-root-font-base]').text(rootBasePx + 'px = 1rem');
  }

  function resetFormatTooltip($picker) {
    var $current = $picker.find('.ecf-format-picker__option.is-active').first();
    var tip = $current.data('tip') || '';
    $picker.closest('.ecf-card').find('[data-ecf-format-tooltip]').first().text(tip);
  }

  function setFormatTooltipVisibility($picker, visible) {
    $picker.closest('.ecf-card').find('[data-ecf-format-tooltip]').first().prop('hidden', !visible);
  }

  function escapeHtml(value) {
    return $('<div>').text(value == null ? '' : String(value)).html();
  }

  function getFontFamilyOptionsFromSettings(settings) {
    var fontRows = (((settings || {}).typography || {}).fonts) || [];
    var options = [
      {
        value: 'var(--ecf-font-primary)',
        label: (i18n.font_option_primary || '') + ': ' + (((fontRows[0] || {}).value) || 'Inter, sans-serif')
      },
      {
        value: 'var(--ecf-font-secondary)',
        label: (i18n.font_option_secondary || '') + ': ' + (((fontRows[1] || {}).value) || 'Georgia, serif')
      }
    ];

    ((((settings || {}).typography || {}).local_fonts) || []).forEach(function(row) {
      var family = $.trim((row && row.family) || '');
      if (!family) {
        return;
      }
      options.push({
        value: "'" + family + "'",
        label: (i18n.font_option_uploaded || '') + ': ' + family
      });
    });

    return options;
  }

  function getGroupedFontFamilyOptionsFromSettings(settings) {
    var groups = [];
    var localOptions = [];

    ((((settings || {}).typography || {}).local_fonts) || []).forEach(function(row) {
      var family = $.trim((row && row.family) || '');
      if (!family) {
        return;
      }
      localOptions.push({
        value: "'" + family + "'",
        label: family,
        source: 'local'
      });
    });

    if (localOptions.length) {
      groups.push({
        label: i18n.font_group_local || '',
        options: localOptions
      });
    }

    groups.push({
      label: i18n.font_group_core || '',
      options: getFontFamilyOptionsFromSettings(settings).slice(0, 2).map(function(option) {
        return $.extend({}, option, { source: 'core' });
      })
    });

    var libraryOptions = (((window.ecfAdmin || {}).fontLibrary) || []).map(function(entry) {
      var family = $.trim((entry && entry.family) || '');
      if (!family) {
        return null;
      }
      return {
        value: '__library__|' + family,
        label: family,
        source: 'library'
      };
    }).filter(Boolean);

    if (libraryOptions.length) {
      groups.push({
        label: i18n.font_group_library || '',
        options: libraryOptions
      });
    }

    return groups;
  }

  function renderFontFamilyGroupsIntoSelect($select, groups, current) {
    var options = [];
    $select.empty();

    groups.forEach(function(group) {
      var groupOptions = Array.isArray(group.options) ? group.options : [];
      if (!groupOptions.length) {
        return;
      }
      var $group = $('<optgroup>').attr('label', group.label);
      groupOptions.forEach(function(option) {
        options.push(option);
        $('<option>')
          .attr('value', option.value)
          .attr('data-ecf-font-source', option.source || '')
          .text(option.label)
          .appendTo($group);
      });
      $group.appendTo($select);
    });

    $('<option value="__custom__">').text(i18n.font_option_custom_stack || '').appendTo($select);

    var hasPreset = options.some(function(option) {
      return option.value === current;
    });

    if (hasPreset) {
      $select.val(current);
    } else if ($select.find('option[value="__custom__"]').length) {
      $select.val('__custom__');
    }

    return hasPreset;
  }

  function currentFontFamilyLabel($field) {
    var $select = $field.find('[data-ecf-font-family-preset]').first();
    var $selected = $select.find('option:selected');
    var $custom = $field.find('[data-ecf-font-family-custom]').first();

    if ($selected.length && $selected.val() !== '__custom__') {
      return $.trim($selected.text() || '');
    }

    return $.trim($custom.val() || '') || $.trim($selected.text() || '');
  }

  function syncFontFamilyCurrentLabel($field) {
    if (!$field || !$field.length) return;
    $field.find('[data-ecf-font-current-value]').text(currentFontFamilyLabel($field));
    syncTypographyFontCardSummaries();
  }

  function applyFontFamilyPresetSelection(fieldName, selectedValue) {
    getFontFamilyFields(fieldName).each(function() {
      var $field = $(this);
      var $select = $field.find('[data-ecf-font-family-preset]').first();
      var $presetInput = $field.find('[data-ecf-font-family-preset-input]').first();
      var $custom = $field.find('[data-ecf-font-family-custom]').first();
      var showCustom = selectedValue === '__custom__';

      if ($select.length) {
        $select.val(selectedValue);
        $select.data('ecf-prev-value', selectedValue);
      }
      $presetInput.val(selectedValue);
      if (!showCustom) {
        $custom.val('').prop('hidden', true);
      } else {
        $custom.prop('hidden', false);
      }
      syncFontFamilyCurrentLabel($field);
    });
  }

  function mirrorFontFamilyCustomValue(fieldName, value) {
    getFontFamilyFields(fieldName).each(function() {
      var $field = $(this);
      var $presetInput = $field.find('[data-ecf-font-family-preset-input]').first();
      var $custom = $field.find('[data-ecf-font-family-custom]').first();
      var $select = $field.find('[data-ecf-font-family-preset]').first();

      if ($select.length) {
        $select.val('__custom__');
        $select.data('ecf-prev-value', '__custom__');
      }
      $presetInput.val('__custom__');
      $custom.val(value).prop('hidden', false);
      syncFontFamilyCurrentLabel($field);
    });
  }

  function syncTypographyFontCardSummaries() {
    var currentPrefix = $.trim(String(i18n.current_prefix || ''));
    var fontSizePrefix = $.trim(String(i18n.font_size_prefix || ''));

    var $bodyField = getPrimaryFontFamilyField('base_font_family');
    if ($bodyField.length) {
      $('[data-ecf-typography-body-current]').text(currentPrefix + ' ' + currentFontFamilyLabel($bodyField));
    }

    var $headingField = getPrimaryFontFamilyField('heading_font_family');
    if ($headingField.length) {
      $('[data-ecf-typography-heading-current]').text(currentPrefix + ' ' + currentFontFamilyLabel($headingField));
    }

    var $bodySizeField = $('[data-ecf-body-size-field]').first();
    if ($bodySizeField.length) {
      var sizeValue = $.trim(String($bodySizeField.find('[data-ecf-size-value-input]').val() || ''));
      var sizeFormat = $.trim(String($bodySizeField.find('[data-ecf-size-format-input], [data-ecf-format-input]').first().val() || ''));
      if (sizeValue && sizeFormat) {
        $('[data-ecf-typography-body-size]').text(fontSizePrefix + ' ' + sizeValue + ' ' + sizeFormat);
      }
    }
  }

  function parseCssSizeParts(value) {
    var normalized = $.trim(String(value || ''));
    var match = normalized.match(/^(-?\d+(?:[.,]\d+)?)(px|rem|em|ch|%|vw|vh)$/i);

    if (match) {
      return {
        value: String(match[1]).replace(',', '.'),
        format: String(match[2]).toLowerCase(),
      };
    }

    return {
      value: normalized,
      format: 'custom',
    };
  }

  var lastDerivedBodySize = null;

  function closeFontPicker($field) {
    if (!$field || !$field.length) return;
    $field.removeClass('is-open');
    $field.find('[data-ecf-font-picker-panel]').prop('hidden', true);
  }

  function openFontPicker($field) {
    if (!$field || !$field.length) return;
    $field.addClass('is-open');
    $field.find('[data-ecf-font-picker-panel]').prop('hidden', false);
  }

  function normalizeSizeRange(minPx, maxPx) {
    var normalizedMin = parseFloat(minPx);
    var normalizedMax = parseFloat(maxPx);

    if (!isFinite(normalizedMin) && !isFinite(normalizedMax)) {
      return null;
    }

    if (!isFinite(normalizedMin)) {
      normalizedMin = normalizedMax;
    }

    if (!isFinite(normalizedMax)) {
      normalizedMax = normalizedMin;
    }

    if (normalizedMin > normalizedMax) {
      var swap = normalizedMin;
      normalizedMin = normalizedMax;
      normalizedMax = swap;
    }

    return {
      minPx: normalizedMin,
      maxPx: normalizedMax
    };
  }

  function enhanceShadowPreviewValue(value) {
    var shadowValue = String(value || '');

    if (!shadowValue) {
      return shadowValue;
    }

    return shadowValue.replace(/rgba\(\s*0\s*,\s*0\s*,\s*0\s*,\s*([0-9]*\.?[0-9]+)\s*\)/gi, function(match, alpha) {
      var normalizedAlpha = parseFloat(alpha);
      if (!isFinite(normalizedAlpha)) {
        return match;
      }
      return 'rgba(0,0,0,' + formatPreviewNumber(Math.max(normalizedAlpha, 0.35)) + ')';
    });
  }

  function extractFontFamilyGroupsFromSelect($select) {
    var groups = [];
    $select.find('optgroup').each(function() {
      var $group = $(this);
      var options = [];
      $group.find('option').each(function() {
        options.push({
          value: String($(this).attr('value') || ''),
          label: String($(this).text() || ''),
          source: String($(this).data('ecf-font-source') || ''),
        });
      });
      if (options.length) {
        groups.push({
          label: String($group.attr('label') || ''),
          options: options,
        });
      }
    });
    return groups;
  }

  function normalizeFontFamilyCurrentValue(settings, current) {
    var normalized = String(current || '').trim();
    var localRows = ((((settings || {}).typography || {}).local_fonts) || []);

    localRows.forEach(function(row) {
      var family = $.trim((row && row.family) || '');
      if (!family) {
        return;
      }
      if (normalized === family || normalized === "'" + family + "'") {
        normalized = "'" + family + "'";
      }
    });

    return normalized;
  }

  function buildLocalFontRowHtml(inputKey, index, row) {
    var styleValue = (row && row.style) || 'normal';
    var displayValue = (row && row.display) || 'swap';
    var styleOptions = [
      { value: 'normal', label: i18n.font_style_normal || '' },
      { value: 'italic', label: i18n.font_style_italic || '' },
      { value: 'oblique', label: i18n.font_style_oblique || '' }
    ].map(function(item) {
      return '<option value="' + escapeHtml(item.value) + '"' + (item.value === styleValue ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
    }).join('');
    var displayOptions = [
      { value: 'swap', label: i18n.font_display_swap || '' },
      { value: 'fallback', label: i18n.font_display_fallback || '' },
      { value: 'optional', label: i18n.font_display_optional || '' },
      { value: 'block', label: i18n.font_display_block || '' },
      { value: 'auto', label: i18n.font_display_auto || '' }
    ].map(function(item) {
      return '<option value="' + escapeHtml(item.value) + '"' + (item.value === displayValue ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>';
    }).join('');

    return '<div class="ecf-font-file-row">'
      + '<input type="text" data-ecf-slug-field="token" name="' + inputKey + '[' + index + '][name]" value="' + escapeHtml((row && row.name) || '') + '" placeholder="' + escapeHtml(i18n.local_font_name_placeholder || '') + '" />'
      + '<input type="text" name="' + inputKey + '[' + index + '][family]" value="' + escapeHtml((row && row.family) || '') + '" placeholder="' + escapeHtml(i18n.local_font_family_placeholder || '') + '" />'
      + '<div class="ecf-font-file-picker">'
      + '<input type="text" class="ecf-font-file-url" name="' + inputKey + '[' + index + '][src]" value="' + escapeHtml((row && row.src) || '') + '" placeholder="' + escapeHtml(i18n.local_font_upload_placeholder || '') + '" readonly />'
      + '<button type="button" class="button ecf-font-file-select">' + escapeHtml(i18n.select_file || '') + '</button>'
      + '</div>'
      + '<input type="text" name="' + inputKey + '[' + index + '][weight]" value="' + escapeHtml((row && row.weight) || '400') + '" placeholder="400" />'
      + '<select name="' + inputKey + '[' + index + '][style]">' + styleOptions + '</select>'
      + '<select name="' + inputKey + '[' + index + '][display]">' + displayOptions + '</select>'
      + '<button type="button" class="button ecf-remove-row">×</button>'
      + '</div>';
  }

  function syncLocalFontRowsFromSettings(settings) {
    var rows = ((((settings || {}).typography) || {}).local_fonts) || [];
    $('[data-local-font-table]').each(function() {
      var $table = $(this);
      var inputKey = String($table.data('input-key') || '');
      $table.find('.ecf-font-file-row').remove();
      rows.forEach(function(row, index) {
        $table.append($(buildLocalFontRowHtml(inputKey, index, row)));
      });
    });
  }

  function getFontFamilyFields(fieldName) {
    return $('[data-ecf-general-field="' + fieldName + '"]');
  }

  function getPrimaryFontFamilyField(fieldName) {
    var $fields = getFontFamilyFields(fieldName);
    var $visible = $fields.filter(':visible').first();
    return $visible.length ? $visible : $fields.first();
  }

  function syncFontFamilyFieldFromSettings(fieldName, settings) {
    var $fields = getFontFamilyFields(fieldName);
    if (!$fields.length) {
      return;
    }
    var current = normalizeFontFamilyCurrentValue(settings || {}, settings && settings[fieldName] ? String(settings[fieldName]) : 'var(--ecf-font-primary)');
    var groups = getGroupedFontFamilyOptionsFromSettings(settings || {});
    $fields.each(function() {
      var $field = $(this);
      var $select = $field.find('[data-ecf-font-family-preset]').first();
      var $presetInput = $field.find('[data-ecf-font-family-preset-input]').first();
      var $custom = $field.find('[data-ecf-font-family-custom]').first();
      var hasPreset = renderFontFamilyGroupsIntoSelect($select, groups, current);
      $field.data('ecfFontBaseGroups', groups);

      if (hasPreset) {
        $select.val(current);
        $presetInput.val(current);
        $custom.val('').prop('hidden', true);
      } else {
        $select.val('__custom__');
        $presetInput.val('__custom__');
        $custom.val(current).prop('hidden', false);
      }
      syncFontFamilyCurrentLabel($field);
    });
  }

  function syncInterfaceLanguageFieldFromSettings(settings) {
    if (!settings || typeof settings.interface_language === 'undefined') {
      return;
    }

    $('[data-ecf-general-field="interface_language"]').find('select').val(String(settings.interface_language));
  }

  function getDerivedBodySizeFromTypeScale() {
    var $preview = $('[data-ecf-type-scale-preview]').first();
    if (!$preview.length) {
      return null;
    }

    var config = getTypePreviewConfig($preview);
    var items = buildTypePreviewItems(config);
    var match = null;

    $.each(items, function(_, item) {
      if (item.step === config.baseIndex) {
        match = item;
        return false;
      }
    });

    if (!match && items.length) {
      match = items[0];
    }

    if (!match) {
      return null;
    }

    return {
      value: String(match.maxPx),
      format: 'px',
    };
  }

  function refreshBodySizeLinkedState() {
    var derived = getDerivedBodySizeFromTypeScale();
    if (!derived) {
      return;
    }
    lastDerivedBodySize = {
      value: String(derived.value),
      format: String(derived.format),
    };

    $('[data-ecf-body-size-field]').each(function() {
      var $field = $(this);
      var currentValue = $.trim(String($field.find('[data-ecf-size-value-input]').val() || ''));
      var currentFormat = $.trim(String($field.find('[data-ecf-size-format-input], [data-ecf-format-input]').first().val() || '')).toLowerCase();
      var isLinked = currentFormat === derived.format && currentValue === derived.value;
      $field.attr('data-ecf-body-size-linked', isLinked ? '1' : '0');
    });
  }

  function syncBodySizeWithTypeScale(force) {
    var derived = getDerivedBodySizeFromTypeScale();
    if (!derived) {
      return;
    }
    var previousDerived = lastDerivedBodySize;

    $('[data-ecf-body-size-field]').each(function() {
      var $field = $(this);
      var currentValue = $.trim(String($field.find('[data-ecf-size-value-input]').val() || ''));
      var currentFormat = $.trim(String($field.find('[data-ecf-size-format-input], [data-ecf-format-input]').first().val() || '')).toLowerCase();
      var matchesPreviousDerived = previousDerived
        && currentFormat === previousDerived.format
        && currentValue === previousDerived.value;
      var isLinked = force || $field.attr('data-ecf-body-size-linked') === '1' || matchesPreviousDerived;
      if (!isLinked) {
        return;
      }
      $field.find('[data-ecf-size-value-input]').val(derived.value);
      $field.find('[data-ecf-size-format-input], [data-ecf-format-input]').val(derived.format);
      $field.find('[data-ecf-format-current]').text(derived.format);
      $field.find('[data-ecf-format-option]').removeClass('is-active');
      $field.find('[data-ecf-format-option][data-value="' + derived.format + '"]').addClass('is-active');
    });

    refreshBodySizeLinkedState();
    updateBaseBodyTextSizeWarning();
  }

  function syncBodyTextSizeFieldFromSettings(settings) {
    if (!settings || typeof settings.base_body_text_size === 'undefined') {
      return;
    }

    var parts = parseCssSizeParts(settings.base_body_text_size);
    var format = parts.format === 'custom' ? 'px' : parts.format;

    $('[data-ecf-body-size-field]').each(function() {
      var $field = $(this);
      $field.find('[data-ecf-size-value-input]').val(parts.value);
      $field.find('[data-ecf-size-format-input], [data-ecf-format-input]').val(format);
      $field.find('[data-ecf-format-current]').text(format);
      $field.find('[data-ecf-format-option]').removeClass('is-active');
      $field.find('[data-ecf-format-option][data-value="' + format + '"]').addClass('is-active');
    });

    refreshBodySizeLinkedState();
    updateBaseBodyTextSizeWarning();
    syncTypographyFontCardSummaries();
  }

  function syncGeneralFavoriteTogglesFromSettings(settings) {
    var favorites = settings && settings.general_setting_favorites && typeof settings.general_setting_favorites === 'object'
      ? settings.general_setting_favorites
      : {};

    $('[data-ecf-general-favorite-toggle]').each(function() {
      var $toggle = $(this);
      var key = String($toggle.data('ecf-favorite-key') || '');
      if (!key) {
        return;
      }
      $toggle.prop('checked', favorites[key] === '1');
      syncFavoriteToggleState($toggle.closest('.ecf-favorite-toggle'));
    });

    refreshGeneralFavoritesState();
  }

  function buildFilteredLocalFontGroups(groups, query) {
    var normalized = $.trim(String(query || '')).toLowerCase();
    if (!normalized) {
      return Array.isArray(groups) ? groups : [];
    }

    return (Array.isArray(groups) ? groups : []).map(function(group) {
      var groupOptions = Array.isArray(group.options) ? group.options : [];
      return {
        label: group.label,
        options: groupOptions.filter(function(option) {
          return String(option.label || '').toLowerCase().indexOf(normalized) !== -1;
        })
      };
    }).filter(function(group) {
      return group.options.length > 0;
    });
  }

  function fetchRemoteFontSearchGroups(query) {
    if (!fontSearchRestUrl || !restNonce) {
      return Promise.resolve([]);
    }

    var params = new URLSearchParams();
    params.set('q', String(query || ''));
    params.set('limit', '50');

    return window.fetch(fontSearchRestUrl + '?' + params.toString(), {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': restNonce
      }
    }).then(function(response) {
      return response.json().then(function(data) {
        if (!response.ok || !data || data.success === false) {
          throw new Error((data && data.message) || 'font_search_failed');
        }
        return Array.isArray(data.groups) ? data.groups : [];
      });
    });
  }

  function refreshFontFamilyList($field, query) {
    var fieldName = String($field.data('ecf-general-field') || '');
    if (!fieldName) return;

    var current = String($field.find('[data-ecf-font-family-preset-input]').first().val() || $field.find('[data-ecf-font-family-preset]').first().val() || 'var(--ecf-font-primary)');
    var $select = $field.find('[data-ecf-font-family-preset]').first();
    var baseGroups = $field.data('ecfFontBaseGroups') || [];
    var localGroups = buildFilteredLocalFontGroups(baseGroups, query);

    if (!query) {
      renderFontFamilyGroupsIntoSelect($select, localGroups, current);
      syncFontFamilyCurrentLabel($field);
      return;
    }

    if (fontSearchTimers[fieldName]) {
      window.clearTimeout(fontSearchTimers[fieldName]);
    }

    fontSearchTimers[fieldName] = window.setTimeout(function() {
      if (fontSearchRequests[fieldName] && typeof fontSearchRequests[fieldName].abort === 'function') {
        fontSearchRequests[fieldName].abort();
      }

      var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
      fontSearchRequests[fieldName] = controller;

      var params = new URLSearchParams();
      params.set('q', String(query || ''));
      params.set('limit', '50');

      window.fetch(fontSearchRestUrl + '?' + params.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-WP-Nonce': restNonce
        },
        signal: controller ? controller.signal : undefined
      }).then(function(response) {
        return response.json().then(function(data) {
          if (!response.ok || !data || data.success === false) {
            throw new Error((data && data.message) || 'font_search_failed');
          }
          return Array.isArray(data.groups) ? data.groups : [];
        });
      }).then(function(groups) {
        renderFontFamilyGroupsIntoSelect($select, groups, current);
        syncFontFamilyCurrentLabel($field);
      }).catch(function(error) {
        if (error && error.name === 'AbortError') {
          return;
        }
        renderFontFamilyGroupsIntoSelect($select, localGroups, current);
        syncFontFamilyCurrentLabel($field);
      });
    }, 180);
  }

  function importLibraryFontIntoField(fieldName, target, family, $trigger) {
    if (!fontImportRestUrl || !restNonce) return $.Deferred().reject(new Error('font_import_disabled')).promise();

    var $field = getPrimaryFontFamilyField(fieldName);
    if (!$field.length) return $.Deferred().reject(new Error('font_import_field_missing')).promise();

    if (!family) {
      showAutosaveNotice(i18n.font_import_missing || '', 'error');
      return $.Deferred().reject(new Error('font_import_missing_family')).promise();
    }

    showAutosaveNotice(i18n.font_import_running || '', 'saving');
    if ($trigger && $trigger.length) {
      $trigger.prop('disabled', true);
    }

    return window.fetch(fontImportRestUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({
        family: family,
        target: target
      })
    }).then(function(response) {
      return response.json().then(function(data) {
        if (!response.ok || !data || data.success === false) {
          throw new Error((data && data.message) || 'font_import_failed');
        }
        return data;
      });
    }).then(function(responseData) {
      var settings = responseData && responseData.settings ? responseData.settings : null;
      if (settings) {
        syncLocalFontRowsFromSettings(settings);
        syncFontFamilyFieldFromSettings('base_font_family', settings);
        syncFontFamilyFieldFromSettings('heading_font_family', settings);
      }
      if (settings && settings[fieldName]) {
        var $select = $field.find('[data-ecf-font-family-preset]').first();
        var normalizedValue = normalizeFontFamilyCurrentValue(settings, settings[fieldName]);
        var hasOption = false;

        $select.find('option').each(function() {
          if (String($(this).attr('value') || '') === normalizedValue) {
            hasOption = true;
            return false;
          }
        });

        if (hasOption) {
          $select.val(normalizedValue).trigger('change');
        } else {
          $select.val('__custom__').trigger('change');
          $field.find('[data-ecf-font-family-custom]').first().val(normalizedValue);
        }
      }
      updateSystemInfoCards(responseData && responseData.meta ? responseData.meta : null, settings);
      showAutosaveNotice(i18n.font_import_success || '', 'success');
      return responseData;
    }).catch(function(error) {
      showAutosaveNotice(error && error.message ? error.message : (i18n.font_import_failed || ''), 'error');
      throw error;
    }).finally(function() {
      if ($trigger && $trigger.length) {
        $trigger.prop('disabled', false);
      }
    });
  }

  function getTypePreviewConfig($preview) {
    var maxBase = parseFloat($('[name="ecf_framework_v50[typography][scale][max_base]"]').val());
    if (!maxBase) {
      maxBase = parseFloat($('[name="ecf_framework_v50[typography][scale][base]"]').val()) || 18;
    }
    var minBase = parseFloat($('[name="ecf_framework_v50[typography][scale][min_base]"]').val());
    if (!minBase) {
      var legacyBase = parseFloat($('[name="ecf_framework_v50[typography][scale][base]"]').val()) || 16;
      var legacyScaleFactor = parseFloat($('[name="ecf_framework_v50[typography][scale][scale_factor]"]').val()) || 0.8;
      minBase = legacyBase * legacyScaleFactor || 16;
    }

    return {
      steps: $preview.data('steps') || [],
      rootBasePx: ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16,
      minBase: minBase,
      maxBase: maxBase,
      minRatio: parseFloat($('[name="ecf_framework_v50[typography][scale][min_ratio]"]').val()) || parseFloat($('[name="ecf_framework_v50[typography][scale][ratio]"]').val()) || 1.125,
      maxRatio: parseFloat($('[name="ecf_framework_v50[typography][scale][max_ratio]"]').val()) || parseFloat($('[name="ecf_framework_v50[typography][scale][ratio]"]').val()) || 1.25,
      baseIndex: $('[name="ecf_framework_v50[typography][scale][base_index]"]').val() || 'm',
      fluid: $('[name="ecf_framework_v50[typography][scale][fluid]"]').is(':checked'),
      minVw: parseFloat($('[name="ecf_framework_v50[typography][scale][min_vw]"]').val()) || 375,
      maxVw: parseFloat($('[name="ecf_framework_v50[typography][scale][max_vw]"]').val()) || 1280
    };
  }

  function buildTypePreviewItems(config) {
    var baseIndex = config.steps.indexOf(config.baseIndex);
    if (baseIndex === -1) baseIndex = 2;

    function pxToRem(px) {
      return Math.round((px / config.rootBasePx) * 100) / 100;
    }

    return $.map(config.steps, function(step, i) {
      var exp = i - baseIndex;
      var maxSize = Math.round((config.maxBase * Math.pow(config.maxRatio, exp)) * 1000) / 1000;
      var minSize = Math.round((config.minBase * Math.pow(config.minRatio, exp)) * 1000) / 1000;
      if (minSize > maxSize) { var _s = minSize; minSize = maxSize; maxSize = _s; }
      var cssValue = formatPreviewNumber(pxToRem(maxSize)) + 'rem';

      if (config.fluid && config.maxVw > config.minVw) {
        var slope = (maxSize - minSize) / (config.maxVw - config.minVw);
        var interceptRem = Math.round((((minSize - slope * config.minVw) / config.rootBasePx) * 100)) / 100;
        var slopeVw = Math.round((slope * 100) * 100) / 100;
        cssValue = 'clamp('
          + formatPreviewNumber(pxToRem(minSize)) + 'rem, calc('
          + formatPreviewNumber(slopeVw) + 'vw '
          + (interceptRem >= 0 ? '+ ' : '- ')
          + formatPreviewNumber(Math.abs(interceptRem)) + 'rem), '
          + formatPreviewNumber(pxToRem(maxSize)) + 'rem)';
      } else {
        minSize = maxSize;
      }

      return {
        step: step,
        token: '--ecf-text-' + step,
        min: formatPreviewNumber(pxToRem(minSize)),
        max: formatPreviewNumber(pxToRem(maxSize)),
        minPx: formatPreviewNumber(minSize),
        maxPx: formatPreviewNumber(maxSize),
        cssValue: cssValue,
        previewSize: config.fluid ? cssValue : formatPreviewNumber(pxToRem(maxSize)) + 'rem'
      };
    });
  }

  function getTypePreviewText(stepOrToken, $scope) {
    var normalized = String(stepOrToken || '')
      .toLowerCase()
      .replace(/^--ecf-text-/, '')
      .trim();
    var fallback = (typePreviewTexts && typeof typePreviewTexts.default === 'string') ? typePreviewTexts.default : (($scope && $scope.length) ? ($scope.data('preview-word') || '') : '');

    if (normalized === 'xs' || normalized === 's') {
      return typePreviewTexts.xs || fallback;
    }
    if (normalized === 'm') {
      return typePreviewTexts.m || fallback;
    }
    if (normalized === 'l') {
      return typePreviewTexts.l || fallback;
    }
    if (normalized === 'xl') {
      return typePreviewTexts.xl || fallback;
    }
    if (normalized === '2xl') {
      return typePreviewTexts['2xl'] || fallback;
    }
    if (normalized === '3xl' || normalized === '4xl') {
      return typePreviewTexts.display || fallback;
    }

    return fallback;
  }

  function getPreviewFont() {
    var $preview = $('[data-ecf-type-scale-preview]').first();
    var previewFont = String(($preview.data('preview-font') || '')).trim();
    var $field = getPrimaryFontFamilyField('base_font_family');
    if ($field && $field.length) {
      var presetValue = String($field.find('[data-ecf-font-family-preset-input]').first().val() || '');
      var customValue = String($field.find('[data-ecf-font-family-custom]').first().val() || '').trim();

      if (presetValue === '__custom__') {
        return customValue || previewFont || 'var(--ecf-base-body-font-family, var(--ecf-base-font-family, sans-serif))';
      }

      if (presetValue) {
        return presetValue;
      }
    }

    return previewFont || 'var(--ecf-base-body-font-family, var(--ecf-base-font-family, sans-serif))';
  }

  function getPreviewWeight() {
    var $field = $('[data-ecf-general-field="base_body_font_weight"]').first();
    if ($field.length) {
      var value = String($field.find('select').first().val() || '').trim();
      if (value) {
        return value;
      }
    }
    return '400';
  }

  function renderTypePreview() {
    var $preview = $('[data-ecf-type-scale-preview]');
    if (!$preview.length) return;

    var config = getTypePreviewConfig($preview);
    var items = buildTypePreviewItems(config);
    var html = '';
    var labelMin = $preview.data('preview-label-min') || '';
    var labelMax = $preview.data('preview-label-max') || '';
    var labelFixed = $preview.data('preview-label-fixed') || '';
    var labelFluid = $preview.data('preview-label-fluid') || '';
    var helperText = $preview.data('preview-helper') || '';
    var activeStep = $preview.attr('data-active-step') || config.baseIndex;
    var viewMode = $preview.attr('data-preview-view') || 'fluid';

    if ($.inArray(viewMode, ['min', 'fluid', 'max']) === -1) {
      viewMode = 'fluid';
    }

    if (!items.some(function(item){ return item.step === activeStep; })) {
      activeStep = config.baseIndex;
    }

    function sizeForView(item) {
      if (viewMode === 'min') return item.min + 'rem';
      if (viewMode === 'max') return item.max + 'rem';
      return item.cssValue;
    }

    function modeLabel() {
      if (viewMode === 'min') return '<i class="dashicons dashicons-smartphone"></i>' + labelMin;
      if (viewMode === 'max') return '<i class="dashicons dashicons-desktop"></i>' + labelMax;
      return config.fluid ? labelFluid : labelFixed;
    }

    $.each(items, function(_, item) {
      var selectedClass = item.step === activeStep ? ' is-active' : '';
      var previewText = getTypePreviewText(item.token || item.step, $preview);
      html += '<div class="ecf-type-row' + selectedClass + '" data-ecf-step="' + item.step + '" data-ecf-step-row tabindex="0" role="button" aria-pressed="' + (item.step === activeStep ? 'true' : 'false') + '" style="--ecf-preview-size:' + sizeForView(item) + ';">'
        + '<div class="ecf-type-row__token">'
        + '<div class="ecf-type-row__token-line">'
        + '<span class="ecf-type-row__token-label">' + item.token + '</span>'
        + '<button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button>'
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span>'
        + '</div>'
        + '<button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button>'
        + '</div>'
        + '<div class="ecf-type-row__meta">'
        + '<div><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><div class="ecf-clamp-metric"><strong>' + item.minPx + 'px</strong></div></div>'
        + '<div><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><div class="ecf-clamp-metric"><strong>' + item.maxPx + 'px</strong></div></div>'
        + '</div>'
        + '<div class="ecf-type-row__sample">'
        + '<div class="ecf-type-row__sample-line">'
        + '<strong style="font-size:' + item.minPx + 'px;">' + escapeHtml(previewText) + '</strong>'
        + '<span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span>'
        + '</div>'
        + '<div class="ecf-type-row__sample-line ecf-type-row__sample-line--max">'
        + '<strong style="font-size:' + item.maxPx + 'px;">' + escapeHtml(previewText) + '</strong>'
        + '<span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span>'
        + '</div>'
        + '</div>'
        + '</div>';
    });

    var activeItem = items.find(function(item){ return item.step === activeStep; }) || items[0];
    var activePreviewText = activeItem ? getTypePreviewText(activeItem.token || activeItem.step, $preview) : '';

    $preview.css('--ecf-preview-font', getPreviewFont());
    $preview.css('--ecf-preview-weight', getPreviewWeight());
    $preview.attr('data-active-step', activeStep);
    $preview.attr('data-preview-view', viewMode);
    $preview.find('[data-ecf-type-scale-preview-list]').html(html);
    $preview.find('[data-ecf-preview-mode]').html(modeLabel());
    $preview.find('[data-ecf-focus-token]').text(activeItem ? activeItem.token : '');
    $preview.find('[data-ecf-focus-helper]').text(helperText);
    $preview.find('[data-ecf-focus-word]').text(activePreviewText).css('font-size', activeItem ? sizeForView(activeItem) : '');
    $preview.find('[data-ecf-focus-min]').text(activeItem ? activeItem.minPx + 'px' : '');
    $preview.find('[data-ecf-focus-max]').text(activeItem ? activeItem.maxPx + 'px' : '');
    $preview.find('[data-ecf-focus-copy]').text(activeItem ? activeItem.cssValue : '').attr('data-copy', activeItem ? activeItem.cssValue : '');
    $preview.find('[data-ecf-focus-min-line]').css('font-size', activeItem ? activeItem.minPx + 'px' : '').text(activePreviewText);
    $preview.find('[data-ecf-focus-max-line]').css('font-size', activeItem ? activeItem.maxPx + 'px' : '').text(activePreviewText);
    $preview.find('[data-ecf-preview-view]').removeClass('is-active');
    $preview.find('[data-ecf-preview-view="' + viewMode + '"]').addClass('is-active');
  }

  function buildRadiusCssValue(minPx, maxPx, rootBasePx) {
    minPx = parseFloat(minPx) || 0;
    maxPx = parseFloat(maxPx) || 0;

    if (!minPx && !maxPx) return '';
    if (!maxPx) maxPx = minPx;
    if (!minPx) minPx = maxPx;

    if (Math.abs(minPx - maxPx) < 0.001) {
      return formatPreviewNumber(maxPx / rootBasePx) + 'rem';
    }

    var minVw = 375;
    var maxVw = 1280;
    var slope = (maxPx - minPx) / (maxVw - minVw);
    var interceptRem = Math.round((((minPx - slope * minVw) / rootBasePx) * 100)) / 100;
    var slopeVw = Math.round((slope * 100) * 100) / 100;

    return 'clamp('
      + formatPreviewNumber(minPx / rootBasePx) + 'rem, calc('
      + formatPreviewNumber(slopeVw) + 'vw '
      + (interceptRem >= 0 ? '+ ' : '- ')
      + formatPreviewNumber(Math.abs(interceptRem)) + 'rem), '
      + formatPreviewNumber(maxPx / rootBasePx) + 'rem)';
  }

  function renderRootFontImpact() {
    var $box = $('[data-ecf-root-font-impact]');
    if (!$box.length) return;

    var rootBasePx = ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16;
    var typeConfig = getTypePreviewConfig($('[data-ecf-type-scale-preview]'));
    var typeItems = buildTypePreviewItems(typeConfig);
    var typeStep = $box.attr('data-type-step') || typeConfig.baseIndex || 'm';
    var spacingConfig = getSpacingConfig();
    var spacingItems = buildSpacingItems(getSpacingSteps(), spacingConfig);
    var spacingStep = $box.attr('data-spacing-step') || spacingConfig.baseIndex || 'm';
    var radiusName = $box.attr('data-radius-name') || 'm';
    var spacingPrefix = $('[name="ecf_framework_v50[spacing][prefix]"]').val() || 'space';
    var typeItem = typeItems.find(function(item){ return item.step === typeStep; }) || typeItems[0];
    var spacingItem = spacingItems.find(function(item){ return item.step === spacingStep; }) || spacingItems[0];
    var $radiusRow = $('.ecf-table[data-group="radius"] .ecf-row').filter(function() {
      return $.trim($(this).find('input').eq(0).val()) === radiusName;
    }).first();

    if (!$radiusRow.length) {
      $radiusRow = $('.ecf-table[data-group="radius"] .ecf-row').first();
    }

    var radiusToken = '--ecf-radius-' + (($radiusRow.find('input').eq(0).val() || radiusName).toString().trim() || 'm');
    var radiusMin = parseFloat($radiusRow.find('input').eq(1).val()) || 0;
    var radiusMax = parseFloat($radiusRow.find('input').eq(2).val()) || radiusMin;
    var labelMin = $box.attr('data-label-min') || '';
    var labelMax = $box.attr('data-label-max') || '';
    var typePreviewWord = getTypePreviewText(typeStep, $box);
    var spacingMaxValue = spacingItems.reduce(function(max, item) {
      return Math.max(max, parseFloat(item.maxPx) || 0);
    }, 0);

    updateRootFontSizeLabels($('[name="ecf_framework_v50[root_font_size]"]').val());
    if (typeItem) {
      $box.find('[data-ecf-root-type-token]').text(typeItem.token);
      $box.find('[data-ecf-root-type-copy]').text(typeItem.cssValue).attr('data-copy', typeItem.cssValue);
      $box.find('[data-ecf-root-type-min-label]').text(labelMin);
      $box.find('[data-ecf-root-type-max-label]').text(labelMax);
      $box.find('[data-ecf-root-type-min]').text(typeItem.minPx + 'px');
      $box.find('[data-ecf-root-type-max]').text(typeItem.maxPx + 'px');
      $box.find('[data-ecf-root-type-min-preview]').text(typePreviewWord).css('font-size', typeItem.minPx + 'px');
      $box.find('[data-ecf-root-type-max-preview]').text(typePreviewWord).css('font-size', typeItem.maxPx + 'px');
    }
    if (spacingItem) {
      var spacingMinValue = parseFloat(spacingItem.minPx) || 0;
      var spacingMaxPx = parseFloat(spacingItem.maxPx) || spacingMinValue;
      var minBarWidth = Math.max(0, spacingMinValue);
      var maxBarWidth = Math.max(0, spacingMaxPx);
      var minBarHeight = Math.min(32, Math.max(4, Math.round(spacingMinValue)));
      var maxBarHeight = Math.min(32, Math.max(4, Math.round(spacingMaxPx)));
      $box.find('[data-ecf-root-spacing-token]').text('--ecf-' + spacingPrefix + '-' + spacingItem.step);
      $box.find('[data-ecf-root-spacing-copy]').text(spacingItem.cssValue).attr('data-copy', spacingItem.cssValue);
      $box.find('[data-ecf-root-spacing-min-label]').text(labelMin);
      $box.find('[data-ecf-root-spacing-max-label]').text(labelMax);
      $box.find('[data-ecf-root-spacing-min]').text(spacingItem.minPx + 'px');
      $box.find('[data-ecf-root-spacing-max]').text(spacingItem.maxPx + 'px');
      $box.find('[data-ecf-root-spacing-min-bar]').css({ width: minBarWidth + 'px', height: minBarHeight + 'px' });
      $box.find('[data-ecf-root-spacing-max-bar]').css({ width: maxBarWidth + 'px', height: maxBarHeight + 'px' });
    }
    $box.find('[data-ecf-root-radius-token]').text(radiusToken);
    $box.find('[data-ecf-root-radius-copy]').text(buildRadiusCssValue(radiusMin, radiusMax, rootBasePx)).attr('data-copy', buildRadiusCssValue(radiusMin, radiusMax, rootBasePx));
    $box.find('[data-ecf-root-radius-min-label]').text(labelMin);
    $box.find('[data-ecf-root-radius-max-label]').text(labelMax);
    $box.find('[data-ecf-root-radius-min]').text(formatPreviewNumber(radiusMin) + 'px');
    $box.find('[data-ecf-root-radius-max]').text(formatPreviewNumber(radiusMax) + 'px');
    $box.find('[data-ecf-root-radius-min-preview]').css('border-radius', formatPreviewNumber(radiusMin) + 'px');
    $box.find('[data-ecf-root-radius-max-preview]').css('border-radius', formatPreviewNumber(radiusMax) + 'px');
  }

  function buildShadowPreviewItems() {
    return $('.ecf-table[data-group="shadows"] .ecf-row').map(function(index) {
      var $row = $(this);
      var name = $.trim($row.find('input').eq(0).val()) || ('shadow-' + index);
      var value = $.trim($row.find('input').eq(1).val()) || '0 1px 2px rgba(0,0,0,0.05)';
      var slug = name.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || ('shadow-' + index);

      return {
        index: index,
        name: name,
        slug: slug,
        token: '--ecf-shadow-' + slug,
        className: 'ecf-shadow-' + slug,
        value: value
      };
    }).get();
  }

  function renderShadowPreview() {
    var $preview = $('[data-ecf-shadow-preview]');
    if (!$preview.length) return;

    var items = buildShadowPreviewItems();
    var helperText = $preview.data('preview-helper') || '';
    var previewWord = $preview.data('preview-word') || '';
    var activeShadow = $preview.attr('data-active-shadow') || (items[0] ? items[0].slug : '');
    var html = '';

    if (!items.length) {
      $preview.find('[data-ecf-shadow-preview-list]').html('');
      $preview.find('[data-ecf-shadow-token]').text('');
      $preview.find('[data-ecf-shadow-name]').text('');
      $preview.find('[data-ecf-shadow-css]').text('');
      $preview.find('[data-ecf-shadow-label]').text(previewWord);
      $preview.find('[data-ecf-shadow-helper]').text(helperText);
      $preview.find('[data-ecf-shadow-surface]').css('box-shadow', 'none');
      return;
    }

    if (!items.some(function(item){ return item.slug === activeShadow; })) {
      activeShadow = items[0].slug;
    }

    $.each(items, function(_, item) {
      var selectedClass = item.slug === activeShadow ? ' is-active' : '';
      var previewShadowValue = enhanceShadowPreviewValue(item.value);
      html += '<div class="ecf-shadow-row' + selectedClass + '" data-ecf-shadow-step="' + item.slug + '" data-ecf-shadow-index="' + item.index + '">'
        + '<div class="ecf-shadow-row__class"><code>' + escapeHtml(item.className) + '</code></div>'
        + '<div class="ecf-shadow-row__token">' + escapeHtml(item.token) + '</div>'
        + '<div class="ecf-shadow-row__value"><input type="text" class="ecf-shadow-row__value-input" data-ecf-shadow-inline-value data-ecf-shadow-index="' + item.index + '" value="' + escapeHtml(item.value) + '" spellcheck="false" autocomplete="off"></div>'
        + '<div class="ecf-shadow-row__sample ecf-shadow-preview-bg"><div class="ecf-shadow-row__mini" style="box-shadow:' + escapeHtml(previewShadowValue) + ';"></div></div>'
        + '</div>';
    });

    var activeItem = items.find(function(item){ return item.slug === activeShadow; }) || items[0];

    $preview.attr('data-active-shadow', activeShadow);
    $preview.find('[data-ecf-shadow-preview-list]').html(html);
    $preview.find('[data-ecf-shadow-token]').text(activeItem.token);
    $preview.find('[data-ecf-shadow-class]').text(activeItem.className);
    $preview.find('[data-ecf-shadow-name]').text(activeItem.name);
    $preview.find('[data-ecf-shadow-css]').text(activeItem.value);
    $preview.find('[data-ecf-shadow-label]').text(activeItem.token);
    $preview.find('[data-ecf-shadow-helper]').text(helperText);
    $preview.find('[data-ecf-shadow-surface]').css('box-shadow', enhanceShadowPreviewValue(activeItem.value));
    var $tokenPills = $preview.find('.ecf-field-token-row');
    if ($tokenPills.length) {
      $tokenPills.find('[data-ecf-token-copy]').eq(0).attr('data-ecf-token-copy', activeItem.token).find('code').text(activeItem.token);
      $tokenPills.find('[data-ecf-token-copy]').eq(1).attr('data-ecf-token-copy', activeItem.className).find('code').text(activeItem.className);
    }
  }

  // ── Sidebar navigation ─────────────────────────────────────────
  var $noSavePanel = ['variables', 'sync', 'help', 'changelog']; // panels that don't need the save button
  var panelStorageKey = 'ecfActivePanel';
  var generalTabStorageKey = 'ecfGeneralTab';
  var websiteTabStorageKey = 'ecfWebsiteTab';
  var pageScrollStorageKey = 'ecfPageScrollTop';
  var pageFocusStorageKey = 'ecfPageFocusTarget';
  var whatsNewStorageKey = 'ecfWhatsNewState';
  var whatsNewMaxImpressions = 5;
  var $settingsForm = $('form[action="options.php"]').first();
  var $autosaveNotice = $();
  var autosaveTimer = null;
  var autosaveInFlight = false;
  var autosaveQueued = false;
  var autosaveReloadRequested = false;
  var autosaveSkipValidation = false;
  var autosaveReady = false;
  var autosaveRecoveryNoticePending = false;
  var elementorAutoSyncInFlight = false;
  var elementorAutoSyncQueuedSettings = null;
  var lastElementorAutoSyncPayload = '';
  var pendingFontAutolinkField = '';
  var lastSavedSettingsPayload = '';
  var inFlightSettingsPayload = '';
  var queuedSettingsPayload = '';

  function parseFormFieldPath(name) {
    return String(name || '')
      .replace(/\]/g, '')
      .split('[');
  }

  function shouldUseArrayContainer(nextKey) {
    return nextKey === '' || /^\d+$/.test(nextKey);
  }

  function assignFormValue(target, path, value) {
    var current = target;

    for (var i = 0; i < path.length; i += 1) {
      var key = path[i];
      var isLast = i === path.length - 1;
      var nextKey = path[i + 1];

      if (isLast) {
        if (Array.isArray(current)) {
          if (key === '' || /^\d+$/.test(key)) {
            current[key === '' ? current.length : parseInt(key, 10)] = value;
          } else {
            current[key] = value;
          }
        } else if (key === '') {
          if (!Array.isArray(current)) {
            current = [];
          }
          current.push(value);
        } else {
          current[key] = value;
        }
        return;
      }

      var container;
      if (Array.isArray(current)) {
        if (key === '' || /^\d+$/.test(key)) {
          var arrayIndex = key === '' ? current.length : parseInt(key, 10);
          if (current[arrayIndex] == null || typeof current[arrayIndex] !== 'object') {
            current[arrayIndex] = shouldUseArrayContainer(nextKey) ? [] : {};
          }
          container = current[arrayIndex];
        } else {
          if (current[key] == null || typeof current[key] !== 'object') {
            current[key] = shouldUseArrayContainer(nextKey) ? [] : {};
          }
          container = current[key];
        }
      } else {
        if (current[key] == null || typeof current[key] !== 'object') {
          current[key] = shouldUseArrayContainer(nextKey) ? [] : {};
        }
        container = current[key];
      }

      current = container;
    }
  }

  function buildSettingsPayloadFromForm() {
    var payload = {};
    if (!$settingsForm.length) return payload;

    var fieldMap = {};
    $settingsForm.find(':input[name]').each(function() {
      var $input = $(this);
      var type = String($input.attr('type') || '').toLowerCase();
      var $favoriteCard = $input.closest('[data-ecf-favorite-card]');
      var name = String($input.attr('name') || '');
      var score = 0;

      if (!name || $input.is(':disabled')) {
        return;
      }

      if ((type === 'checkbox' || type === 'radio') && !$input.is(':checked')) {
        return;
      }

      if (type !== 'hidden' && $input.prop('hidden')) {
        return;
      }

      if ($input.is(':visible')) {
        score += 100;
      }

      if (!$favoriteCard.length) {
        score += 10;
      }

      if (type === 'hidden') {
        score -= 5;
      }

      var path = parseFormFieldPath(name);
      var acceptsMultiple = path.indexOf('') !== -1;

      if (acceptsMultiple) {
        if (!fieldMap[name]) {
          fieldMap[name] = {
            score: score,
            entries: [],
          };
        }
        fieldMap[name].entries.push({
          score: score,
          name: name,
          value: $input.val(),
        });
        return;
      }

      if (!fieldMap[name] || score >= fieldMap[name].score) {
        fieldMap[name] = {
          score: score,
          name: name,
          value: $input.val(),
        };
      }
    });

    $.each(fieldMap, function(_, field) {
      var fields = Array.isArray(field.entries) ? field.entries : [field];

      $.each(fields, function(__, entry) {
        var path = parseFormFieldPath(entry.name);
        if (!path.length || path[0] !== 'ecf_framework_v50') {
          return;
        }
        assignFormValue(payload, path.slice(1), entry.value);
      });
    });

    var favoritePayload = {};
    $('[data-ecf-general-favorite-toggle]').each(function() {
      var key = $(this).data('ecf-favorite-key');
      if (!key || favoritePayload[key]) {
        return;
      }
      if ($(this).is(':checked')) {
        favoritePayload[key] = '1';
      }
    });
    payload.general_setting_favorites = favoritePayload;

    return payload;
  }

  function asSettingsRows(value) {
    if ($.isArray(value)) return value;
    if (!value || typeof value !== 'object') return [];
    return Object.keys(value).map(function(key) {
      return value[key];
    });
  }

  function rowHasToken(row) {
    return row && $.trim(String(row.name || '')) !== '';
  }

  function countGeneratedColorVariants(row) {
    var total = 0;
    if (!rowHasToken(row)) return 0;
    if (String(row.generate_shades || '') === '1') {
      total += clamp(parseInt(row.shade_count, 10) || 6, 4, 10);
    }
    if (String(row.generate_tints || '') === '1') {
      total += clamp(parseInt(row.tint_count, 10) || 6, 4, 10);
    }
    return total;
  }

  function countLayrixVariablesFromSettings(settings) {
    var current = settings || {};
    var count = 0;

    asSettingsRows(current.colors).forEach(function(row) {
      if (!rowHasToken(row)) return;
      count += 1 + countGeneratedColorVariants(row);
    });

    count += asSettingsRows(current.radius).filter(rowHasToken).length;
    count += asSettingsRows(current.shadows).filter(rowHasToken).length;

    count += asSettingsRows(current.spacing && current.spacing.steps).filter(function(step) {
      return $.trim(String(step || '')) !== '';
    }).length;

    count += asSettingsRows(current.typography && current.typography.scale && current.typography.scale.steps).filter(function(step) {
      return $.trim(String(step || '')) !== '';
    }).length;

    if ($.trim(String(current.elementor_boxed_width || '')) !== '') {
      count += 1;
    }

    return count;
  }

  function buildLayrixColorVariableItemsFromSettings(settings) {
    var items = [];
    asSettingsRows((settings || {}).colors).forEach(function(row) {
      if (!rowHasToken(row)) return;

      var name = $.trim(String(row.name || ''));
      var value = $.trim(String(row.value || ''));
      var format = $.trim(String(row.format || 'hex')) || 'hex';
      var parsed = parseDisplayColor(value, format) || parseHexValue(value) || parseRgbValue(value);
      var baseValue = parsed ? rgbToHex(parsed).toUpperCase() : value;
      var baseLabel = 'ecf-color-' + name;

      items.push({
        id: 'prepared:' + baseLabel,
        label: baseLabel,
        type: 'global-color-variable',
        value: baseValue,
        pending: true
      });

      if (!parsed) return;
      if (String(row.generate_shades || '') === '1') {
        colorGeneratorItems(parsed, 'shades', row.shade_count).forEach(function(item) {
          items.push({
            id: 'prepared:' + baseLabel + '-' + item.label,
            label: baseLabel + '-' + item.label,
            type: 'global-color-variable',
            value: item.value,
            pending: true
          });
        });
      }
      if (String(row.generate_tints || '') === '1') {
        colorGeneratorItems(parsed, 'tints', row.tint_count).forEach(function(item) {
          items.push({
            id: 'prepared:' + baseLabel + '-' + item.label,
            label: baseLabel + '-' + item.label,
            type: 'global-color-variable',
            value: item.value,
            pending: true
          });
        });
      }
    });
    return items;
  }

  function mergePreparedLayrixVariables(ecfItems, settings) {
    var merged = (ecfItems || []).filter(function(item) {
      return !item.pending;
    });
    var existing = {};
    merged.forEach(function(item) {
      existing[String(item.label || '').toLowerCase()] = true;
    });

    buildLayrixColorVariableItemsFromSettings(settings || buildSettingsPayloadFromForm()).forEach(function(item) {
      if (existing[String(item.label || '').toLowerCase()]) return;
      merged.push(item);
    });

    return merged;
  }

  function refreshPreparedVariablesList(settings) {
    if (!varsLoaded) return;
    var renderedEcfItems = mergePreparedLayrixVariables(varStore.ecf, settings || buildSettingsPayloadFromForm());
    var foreignItems = varStore.foreign || [];
    renderVarList('ecf', renderedEcfItems);
    $('#ecf-total-ecf-variables').text(renderedEcfItems.length);
    $('#ecf-total-foreign-variables').text(foreignItems.length);
    $('#ecf-total-variables, #ecf-total-variables-inline').text(renderedEcfItems.length + foreignItems.length);
  }

  function updateLayrixVariableCount(settings, meta) {
    var count = meta && meta.layrix_variable_count != null
      ? parseInt(meta.layrix_variable_count, 10)
      : countLayrixVariablesFromSettings(settings || buildSettingsPayloadFromForm());
    $('[data-ecf-layrix-variable-count]').text(String(isNaN(count) ? 0 : count));
    refreshPreparedVariablesList(settings);
  }

  function stableStringify(value) {
    if (Array.isArray(value)) {
      return '[' + value.map(stableStringify).join(',') + ']';
    }
    if (value && typeof value === 'object') {
      return '{' + Object.keys(value).sort().map(function(key) {
        return JSON.stringify(key) + ':' + stableStringify(value[key]);
      }).join(',') + '}';
    }
    return JSON.stringify(value);
  }

  function truthySetting(settings, key) {
    var value = settings && typeof settings[key] !== 'undefined' ? settings[key] : '';
    return value === true || value === 1 || value === '1' || value === 'on';
  }

  function isAutosaveEnabled() {
    return $('[name="ecf_framework_v50[autosave_enabled]"]').first().is(':checked');
  }

  function topbarManagedSettingNames() {
    return [
      'elementor_auto_sync_enabled',
      'elementor_auto_sync_variables',
      'elementor_auto_sync_classes'
    ];
  }

  function getSettingsCheckboxByKey(key) {
    return $('[name="ecf_framework_v50[' + key + ']"]').first();
  }

  function syncTopbarAutosaveSettings() {
    $('[data-ecf-topbar-setting]').each(function() {
      var key = String($(this).attr('data-ecf-topbar-setting') || '');
      var $source = getSettingsCheckboxByKey(key);
      if (!$source.length) return;
      $(this).prop('checked', $source.is(':checked'));
    });
  }

  function updateAutosavePill() {
    var $pill = $('[data-ecf-autosave-pill]');
    var $toggle = $('[data-ecf-autosave-toggle]');
    if (!$pill.length) return;
    var active = isAutosaveEnabled();
    $pill.text(active ? (i18n.autosave_active || '') : (i18n.autosave_off || ''));
    $toggle.toggleClass('is-disabled', !active).attr('aria-pressed', active ? 'true' : 'false');
    syncTopbarAutosaveSettings();
  }

  function buildElementorAutoSyncPayload(settings) {
    var source = settings || buildSettingsPayloadFromForm();
    var payload = {
      enabled: truthySetting(source, 'elementor_auto_sync_enabled'),
      variables: truthySetting(source, 'elementor_auto_sync_variables'),
      classes: truthySetting(source, 'elementor_auto_sync_classes'),
      colors: [],
      radius: [],
      shadows: [],
      spacing: {},
      typography: {},
      elementor_boxed_width: '',
      starter_classes: {},
      utility_classes: {}
    };
    if (payload.variables) {
      payload.colors = source.colors || [];
      payload.radius = source.radius || [];
      payload.shadows = source.shadows || [];
      payload.spacing = source.spacing || {};
      payload.typography = source.typography || {};
      payload.elementor_boxed_width = source.elementor_boxed_width || '';
    }
    if (payload.classes) {
      payload.starter_classes = source.starter_classes || {};
      payload.utility_classes = source.utility_classes || {};
      payload.elementor_boxed_width = source.elementor_boxed_width || '';
    }
    return payload;
  }

  function maybeRunElementorAutoSync(settings) {
    var payload = buildElementorAutoSyncPayload(settings);
    if (!syncRestUrl || !restNonce || !payload.enabled || (!payload.variables && !payload.classes)) {
      return;
    }

    var payloadHash = stableStringify(payload);
    if (payloadHash === lastElementorAutoSyncPayload) {
      return;
    }

    if (elementorAutoSyncInFlight) {
      elementorAutoSyncQueuedSettings = settings || buildSettingsPayloadFromForm();
      return;
    }

    elementorAutoSyncInFlight = true;
    showAutosaveNotice(i18n.elementor_syncing || '', 'saving');

    window.fetch(syncRestUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({
        variables: !!payload.variables,
        classes: !!payload.classes
      })
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('elementor_sync_failed');
      }
      return response.json();
    }).then(function(responseData) {
      lastElementorAutoSyncPayload = payloadHash;
      if (responseData && responseData.meta) {
        updateSystemInfoCards(responseData.meta, settings || buildSettingsPayloadFromForm());
      }
      showAutosaveNotice(i18n.elementor_synced || '', 'success');
    }).catch(function() {
      showAutosaveNotice(i18n.elementor_sync_failed || '', 'error');
    }).finally(function() {
      elementorAutoSyncInFlight = false;
      if (elementorAutoSyncQueuedSettings) {
        var queued = elementorAutoSyncQueuedSettings;
        elementorAutoSyncQueuedSettings = null;
        maybeRunElementorAutoSync(queued);
      }
    });
  }

  function updateUnsavedBadge() {
    var currentPayload = stableStringify(buildSettingsPayloadFromForm());
    var hasChanges = !!lastSavedSettingsPayload && currentPayload !== lastSavedSettingsPayload;
    $('[data-ecf-unsaved-badge]').prop('hidden', !hasChanges);
  }

  function markCurrentStateAsSaved() {
    lastSavedSettingsPayload = stableStringify(buildSettingsPayloadFromForm());
    updateUnsavedBadge();
  }

  function getPanelButtonLabel(panel) {
    var $source = $('[data-panel="' + panel + '"]').filter('.ecf-nav-item, .ecf-sidebar-link').first();
    if (!$source.length) {
      return '';
    }
    var $clone = $source.clone();
    $clone.find('.dashicons, .ecf-new-dot, .ecf-unsaved-badge').remove();
    return $.trim($clone.text());
  }

  function getStickyTopbarTitle(panel) {
    var titleMap = {
      tokens: i18n.topbar_colors_radius || '',
      typography: i18n.topbar_typography || '',
      spacing: i18n.topbar_spacing || '',
      shadows: i18n.topbar_shadows || '',
      variables: i18n.topbar_elementor_variables || '',
      utilities: i18n.topbar_elementor_classes || '',
      sync: i18n.topbar_sync_export || '',
      components: i18n.topbar_general_settings || '',
      help: i18n.topbar_help_support || '',
      changelog: i18n.topbar_help_support || ''
    };
    return titleMap[panel] || '';
  }

  function scrollActivePanelToTop(panel) {
    var $panel = $('.ecf-panel[data-panel="' + panel + '"]').first();
    var $main = $('.ecf-main').first();

    if ($panel.length && $panel[0] && typeof $panel[0].scrollTo === 'function') {
      $panel[0].scrollTo(0, 0);
    } else if ($panel.length) {
      $panel.scrollTop(0);
    }

    if ($main.length && $main[0] && typeof $main[0].scrollTo === 'function') {
      $main[0].scrollTo(0, 0);
    } else if ($main.length) {
      $main.scrollTop(0);
    }
  }

  function updateStickyTopbar(panel) {
    var panelTitle = getStickyTopbarTitle(panel);
    var $panel = $('.ecf-panel[data-panel="' + panel + '"]').first();
    if (!panelTitle && $panel.length) {
      panelTitle = $.trim($panel.find('h2').first().text());
    }
    if (!panelTitle) {
      panelTitle = getPanelButtonLabel(panel);
    }
    $('[data-ecf-active-panel-title]').text(panelTitle);
    $('.ecf-sticky-topbar__save').prop('hidden', $noSavePanel.indexOf(panel) !== -1);
  }

  function validateRequiredPositiveSizeField($field) {
    var $input = $field.find('[data-ecf-size-value-input]').first();
    var $format = $field.find('[data-ecf-size-format-input]').first();
    var $warning = $field.siblings('[data-ecf-inline-size-warning]').first();
    var $bodyField = $field.closest('[data-ecf-body-size-field]');
    var $bodyWarning = $bodyField.find('[data-ecf-body-size-warning]').first();
    var rawValue = $.trim($input.val() || '');
    var format = $.trim($format.val() || '').toLowerCase();
    var numeric = parseFloat(String(rawValue).replace(',', '.'));
    var message = '';

    if (!rawValue) {
      message = i18n.size_value_required || '';
    } else if (format === 'custom') {
      var normalized = rawValue.toLowerCase();
      if (/^(?:0|0px|0rem|0em|0ch|0%|0vw|0vh)$/.test(normalized)) {
        message = i18n.size_value_positive || '';
      }
    } else if (isNaN(numeric) || numeric <= 0) {
      message = i18n.size_value_positive || '';
    }

    if (message) {
      $field.addClass('is-invalid');
      $warning.prop('hidden', false).text(message);
      if ($bodyField.length && $bodyWarning.length) {
        $bodyField.addClass('is-warning');
        $bodyWarning.prop('hidden', false).text(message);
      }
      return false;
    }

    $field.removeClass('is-invalid');
    $warning.prop('hidden', true).text('');
    if ($bodyField.length && $bodyWarning.length) {
      $bodyField.removeClass('is-warning');
      $bodyWarning.prop('hidden', true).text('');
    }
    return true;
  }

  function shakeField($field) {
    $field.removeClass('ecf-field-shake');
    if ($field.length && $field[0]) {
      void $field[0].offsetWidth;
    }
    $field.addClass('ecf-field-shake');
  }

  function validateSettingsForSave() {
    updateBaseBodyTextSizeWarning();
    var invalid = [];
    $('[data-ecf-inline-size-field]').each(function() {
      var $field = $(this);
      if (!validateRequiredPositiveSizeField($field)) {
        invalid.push($field);
      }
    });

    if (invalid.length) {
      invalid.forEach(function($field) {
        shakeField($field);
      });
      if (invalid[0] && invalid[0].length) {
        invalid[0].find('[data-ecf-size-value-input]').trigger('focus');
      }
      autosaveRecoveryNoticePending = true;
      showAutosaveNotice(i18n.autosave_invalid || '', 'error');
      return false;
    }

    return true;
  }

  function updateSystemInfoCards(meta, settings) {
    var snapshot = meta && meta.elementor_limit_snapshot ? meta.elementor_limit_snapshot : null;
    var debug = meta && meta.elementor_debug_snapshot ? meta.elementor_debug_snapshot : null;
    var currentSettings = settings || {};

    if (snapshot) {
      $('[data-ecf-classes-total]').text(snapshot.classes_total == null ? '0' : String(snapshot.classes_total));
      $('[data-ecf-classes-limit]').text(snapshot.classes_limit == null ? '0' : String(snapshot.classes_limit));
      $('[data-ecf-variables-total]').text(snapshot.variables_total == null ? '0' : String(snapshot.variables_total));
      $('[data-ecf-variables-limit]').text(snapshot.variables_limit == null ? '0' : String(snapshot.variables_limit));
    }
    updateLayrixVariableCount(currentSettings, meta);

    if (typeof currentSettings.github_update_checks_enabled !== 'undefined') {
      $('[data-ecf-github-status]').text(currentSettings.github_update_checks_enabled ? (i18n.enabled || '') : (i18n.disabled || ''));
    }

    if (debug) {
      $('[data-ecf-debug-core-state]').text(debug.core_recognized ? (i18n.yes || '') : (i18n.no || ''));
      $('[data-ecf-debug-pro-state]').text(debug.pro_recognized ? (i18n.yes || '') : (i18n.no || ''));
      $('[data-ecf-debug-variables-state]').text(debug.variables_active ? (i18n.yes || '') : (i18n.no || ''));
      $('[data-ecf-debug-classes-state]').text(debug.global_classes_active ? (i18n.yes || '') : (i18n.no || ''));
      $('[data-ecf-debug-sync-state]').text(debug.design_system_sync_active ? (i18n.yes || '') : (i18n.no || ''));
      $('[data-ecf-debug-limits]').text(
        String(i18n.limit_summary || '')
          .replace('%1$s', String(debug.classes_limit))
          .replace('%2$s', String(debug.variables_limit))
      );
      $('[data-ecf-debug-limit-sources]').text(
        String(i18n.source_limits || '')
          .replace('%1$s', debug.classes_limit_source || '')
          .replace('%2$s', debug.variables_limit_source || '')
      );

      if ($('[data-ecf-debug-core-version]').length) {
        $('[data-ecf-debug-core-version]').text(debug.core_version ? formatTemplate(i18n.version || '', debug.core_version) : '');
      }
      if ($('[data-ecf-debug-pro-version]').length) {
        $('[data-ecf-debug-pro-version]').text(debug.pro_version ? formatTemplate(i18n.version || '', debug.pro_version) : '');
      }
    }
  }

  function applyAdminDesignSettings(preset, mode) {
    var $wrap = $('.ecf-wrap');
    if (!$wrap.length) return;
    $wrap.attr('data-ecf-admin-design', preset || 'current');
    $wrap.attr('data-ecf-admin-mode', mode || 'dark');
  }

  function getSelectedAdminDesignPreset() {
    return $('[data-ecf-admin-design-preset]').first().val() || adminDesign.preset || 'current';
  }

  function getSelectedAdminDesignMode() {
    return $('[data-ecf-admin-design-mode]').first().val() || adminDesign.mode || 'dark';
  }

  function refreshAdminDesignChooser() {
    var preset = getSelectedAdminDesignPreset();
    var mode = getSelectedAdminDesignMode();

    $('[data-ecf-admin-design-option]').each(function() {
      $(this).toggleClass('is-active', $(this).data('value') === preset);
    });

    $('[data-ecf-admin-design-mode-option]').each(function() {
      $(this).toggleClass('is-active', $(this).data('value') === mode);
    });

    applyAdminDesignSettings(preset, mode);
  }

  function applyAdminContentFontSize(value, syncInputs) {
    var size = parseInt(value || 16, 10);
    if (!size || size < 14) {
      size = 14;
    }
    if (size > 22) {
      size = 22;
    }
    $('.ecf-wrap').css('--ecf-admin-content-font-size', size + 'px');
    if (syncInputs) {
      $('[data-ecf-admin-content-font-size]').val(String(size));
    }
  }

  function applyAdminMenuFontSize(value, syncInputs) {
    var size = parseInt(value || 14, 10);
    if (!size || size < 12) {
      size = 12;
    }
    if (size > 20) {
      size = 20;
    }
    $('.ecf-wrap').css('--ecf-admin-menu-font-size', size + 'px');
    if (syncInputs) {
      $('[data-ecf-admin-menu-font-size]').val(String(size));
    }
  }

  function normalizeLayoutOrders(orders) {
    var normalized = {};

    $.each(orders || {}, function(group, items) {
      if (!Array.isArray(items)) return;
      var cleanItems = [];
      var seen = {};

      $.each(items, function(_, itemId) {
        var itemKey = String(itemId || '').trim().replace(/[^A-Za-z0-9\-_]/g, '');
        if (!itemKey || seen[itemKey]) return;
        seen[itemKey] = true;
        cleanItems.push(itemKey);
      });

      if (cleanItems.length) {
        normalized[String(group)] = cleanItems;
      }
    });

    return normalized;
  }

  function normalizeLayoutColumns(columns) {
    var normalized = {};

    $.each(columns || {}, function(group, count) {
      var groupKey = String(group || '').trim();
      var countInt = parseInt(count, 10);
      if (!groupKey || !countInt || countInt < 1 || countInt > 3) {
        return;
      }
      normalized[groupKey] = countInt;
    });

    return normalized;
  }

  function getLayoutItemIds($group) {
    return $group.children('[data-ecf-layout-item]').map(function() {
      return $(this).data('ecf-layout-item');
    }).get();
  }

  function captureDefaultLayoutState() {
    defaultLayoutOrders = {};
    defaultLayoutColumns = {};

    $('[data-ecf-layout-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-group') || '');
      var itemIds = getLayoutItemIds($group);

      if (groupKey && itemIds.length) {
        defaultLayoutOrders[groupKey] = itemIds.slice();
      }
    });

    $('[data-ecf-layout-columns-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-columns-group') || '');
      var count = parseInt($group.attr('data-ecf-layout-columns') || 2, 10);

      if (groupKey && count >= 1 && count <= 3) {
        defaultLayoutColumns[groupKey] = count;
      }
    });
  }

  function mergedLayoutItemIds(groupKey, domIds) {
    var savedIds = (layoutOrders && layoutOrders[groupKey]) ? layoutOrders[groupKey] : [];
    var domMap = {};
    var merged = [];
    var pinnedFirstByGroup = {
      'utilities-main': ['utilities-library']
    };

    $.each(domIds, function(_, id) {
      domMap[id] = true;
    });

    $.each(savedIds, function(_, id) {
      if (domMap[id]) {
        merged.push(id);
        delete domMap[id];
      }
    });

    $.each(domIds, function(_, id) {
      if (domMap[id]) {
        merged.push(id);
      }
    });

    if (pinnedFirstByGroup[groupKey] && pinnedFirstByGroup[groupKey].length) {
      var pinned = pinnedFirstByGroup[groupKey];
      merged.sort(function(left, right) {
        var leftIndex = pinned.indexOf(left);
        var rightIndex = pinned.indexOf(right);
        var leftPinned = leftIndex !== -1;
        var rightPinned = rightIndex !== -1;

        if (leftPinned && rightPinned) {
          return leftIndex - rightIndex;
        }
        if (leftPinned) {
          return -1;
        }
        if (rightPinned) {
          return 1;
        }

        return 0;
      });
    }

    return merged;
  }

  function applySavedLayoutToGroup($group) {
    var groupKey = $group.data('ecf-layout-group');
    var domIds = getLayoutItemIds($group);
    var orderedIds = mergedLayoutItemIds(groupKey, domIds);

    $.each(orderedIds, function(_, itemId) {
      var $item = $group.children('[data-ecf-layout-item="' + itemId + '"]').first();
      if ($item.length) {
        $group.append($item);
      }
    });

    if (!layoutOrders[groupKey] || layoutOrders[groupKey].join('|') !== orderedIds.join('|')) {
      layoutOrders[groupKey] = orderedIds;
    }
  }

  function applySavedLayoutColumns() {
    $('[data-ecf-layout-columns-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-columns-group') || '');
      var fallbackCount = parseInt($group.attr('data-ecf-layout-columns') || 2, 10);
      var count = parseInt(layoutColumns[groupKey] || fallbackCount, 10);
      if (!count || count < 1 || count > 3) {
        count = fallbackCount && fallbackCount >= 1 && fallbackCount <= 3 ? fallbackCount : 2;
      }

      $group.attr('data-ecf-layout-columns', String(count)).css('--ecf-layout-columns', String(count));

      $('[data-ecf-layout-columns-btn][data-group="' + groupKey + '"]').each(function() {
        var isActive = String($(this).data('ecf-layout-columns')) === String(count);
        $(this)
          .toggleClass('is-active', isActive)
          .attr('aria-pressed', isActive ? 'true' : 'false');
      });
    });

    scheduleMasonryLayouts();
  }

  function restoreDefaultLayoutState() {
    $.each(defaultLayoutOrders, function(groupKey, orderedIds) {
      var $group = $('[data-ecf-layout-group="' + groupKey + '"]').first();
      if (!$group.length) return;

      $.each(orderedIds, function(_, itemId) {
        var $item = $group.children('[data-ecf-layout-item="' + itemId + '"]').first();
        if ($item.length) {
          $group.append($item);
        }
      });
    });

    $('[data-ecf-layout-columns-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-columns-group') || '');
      var fallbackCount = parseInt(defaultLayoutColumns[groupKey] || 2, 10);
      var count = fallbackCount >= 1 && fallbackCount <= 3 ? fallbackCount : 2;

      $group.attr('data-ecf-layout-columns', String(count)).css('--ecf-layout-columns', String(count));
    });
  }

  var masonryLayoutTimer = null;
  var masonryLayoutObserver = null;

  function ensureMasonryLayoutObserver() {
    if (masonryLayoutObserver || typeof window.ResizeObserver === 'undefined') {
      return;
    }

    masonryLayoutObserver = new window.ResizeObserver(function() {
      scheduleMasonryLayouts();
    });
  }

  function watchMasonryLayoutItems($group) {
    ensureMasonryLayoutObserver();
    if (!masonryLayoutObserver || !$group.length) {
      return;
    }

    var groupElement = $group.get(0);
    if (groupElement && !groupElement.__ecfMasonryObserved) {
      masonryLayoutObserver.observe(groupElement);
      groupElement.__ecfMasonryObserved = true;
    }

    $group.children('[data-ecf-layout-item]').each(function() {
      if (!this.__ecfMasonryObserved) {
        masonryLayoutObserver.observe(this);
        this.__ecfMasonryObserved = true;
      }
    });
  }

  function countGridTracks(value) {
    var normalized = String(value || '').trim();
    if (!normalized || normalized === 'none') {
      return 0;
    }

    var parts = [];
    var token = '';
    var depth = 0;

    for (var index = 0; index < normalized.length; index += 1) {
      var character = normalized.charAt(index);
      if (character === '(') {
        depth += 1;
        token += character;
        continue;
      }
      if (character === ')') {
        depth = Math.max(0, depth - 1);
        token += character;
        continue;
      }
      if (character === ' ' && depth === 0) {
        if (token.trim()) {
          parts.push(token.trim());
        }
        token = '';
        continue;
      }
      token += character;
    }

    if (token.trim()) {
      parts.push(token.trim());
    }

    return parts.length;
  }

  function applyMasonryLayoutToGroup($group) {
    if (!$group.length) return;

    if (!$group.is(':visible')) {
      clearMasonryLayoutToGroup($group);
      return;
    }

    watchMasonryLayoutItems($group);

    var groupElement = $group.get(0);
    if (!groupElement) return;

    var computedGroupStyle = window.getComputedStyle(groupElement);
    var declaredColumnsValue = String($group.attr('data-ecf-layout-columns') || $group.css('--ecf-layout-columns') || '').trim();
    var declaredColumns = parseInt(declaredColumnsValue, 10);
    var columns = declaredColumns && declaredColumns > 0
      ? declaredColumns
      : countGridTracks(computedGroupStyle.gridTemplateColumns);
    if (!columns || columns < 1) {
      columns = 1;
    }
    var rowUnit = parseFloat(computedGroupStyle.getPropertyValue('--ecf-masonry-row-unit')) || 10;
    var gap = parseFloat(computedGroupStyle.rowGap || computedGroupStyle.gap || '20') || 20;

    $group.children('[data-ecf-layout-item]').each(function() {
      var $item = $(this);
      $item.css('--ecf-masonry-span', '1');
      $item.css('grid-column', '');
      $item.css('grid-row', '');
      $item.data('ecfMasonrySpan', 1);

      if (columns < 2) {
        return;
      }

      var itemHeight = Math.ceil($item.outerHeight());
      var span = Math.max(1, Math.ceil((itemHeight + gap) / (rowUnit + gap)));
      $item.css('--ecf-masonry-span', String(span));
      $item.data('ecfMasonrySpan', span);
      $item.data('ecfMasonryHeight', itemHeight);
    });

    if (columns < 2) {
      return;
    }

    var columnSpans = [];
    var columnHeights = [];
    for (var index = 0; index < columns; index += 1) {
      columnSpans.push(0);
      columnHeights.push(0);
    }

    $group.children('[data-ecf-layout-item]').each(function() {
      var $item = $(this);
      var span = parseInt($item.data('ecfMasonrySpan') || 1, 10);
      var itemHeight = parseInt($item.data('ecfMasonryHeight') || 0, 10);
      if (!span || span < 1) {
        span = 1;
      }
      if (!itemHeight || itemHeight < 1) {
        itemHeight = Math.ceil($item.outerHeight());
      }

      var targetColumn = 0;
      for (var col = 1; col < columnSpans.length; col += 1) {
        if (columnHeights[col] < columnHeights[targetColumn]) {
          targetColumn = col;
        }
      }

      var startRow = columnSpans[targetColumn] + 1;
      $item.css('grid-column', String(targetColumn + 1));
      $item.css('grid-row', startRow + ' / span ' + span);
      columnSpans[targetColumn] += span;
      columnHeights[targetColumn] += itemHeight + gap;
    });
  }

  function applyMasonryLayouts() {
    $('[data-ecf-masonry-layout]').each(function() {
      applyMasonryLayoutToGroup($(this));
    });
  }

  function clearMasonryLayoutToGroup($group) {
    if (!$group.length) return;

    $group.children('[data-ecf-layout-item]').each(function() {
      var $item = $(this);
      $item.css('--ecf-masonry-span', '1');
      $item.css('grid-column', '');
      $item.css('grid-row', '');
      $item.removeData('ecfMasonrySpan');
      $item.removeData('ecfMasonryHeight');
    });
  }

  function scheduleMasonryLayouts() {
    window.clearTimeout(masonryLayoutTimer);
    masonryLayoutTimer = window.setTimeout(function() {
      window.requestAnimationFrame(function() {
        applyMasonryLayouts();
        window.requestAnimationFrame(function() {
          applyMasonryLayouts();
        });
      });
    }, 20);
  }

  function collectAllLayoutOrders() {
    var orders = {};

    $('[data-ecf-layout-group]').each(function() {
      var $group = $(this);
      var groupKey = $group.data('ecf-layout-group');
      var itemIds = getLayoutItemIds($group);
      if (groupKey && itemIds.length) {
        orders[groupKey] = itemIds;
      }
    });

    return normalizeLayoutOrders(orders);
  }

  function collectAllLayoutColumns() {
    var columns = {};

    $('[data-ecf-layout-columns-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-columns-group') || '');
      var count = parseInt($group.attr('data-ecf-layout-columns') || 1, 10);
      if (groupKey && count >= 1 && count <= 3) {
        columns[groupKey] = count;
      }
    });

    return normalizeLayoutColumns(columns);
  }

  function hasCustomLayoutState() {
    var currentOrders = collectAllLayoutOrders();
    var currentColumns = collectAllLayoutColumns();

    return stableStringify(currentOrders) !== stableStringify(normalizeLayoutOrders(defaultLayoutOrders))
      || stableStringify(currentColumns) !== stableStringify(normalizeLayoutColumns(defaultLayoutColumns));
  }

  function updateResetLayoutButtonState() {
    var $button = $('[data-ecf-reset-layout]');
    if (!$button.length) return;

    var isBusy = $button.data('ecfResetting') === true;
    $button.prop('disabled', isBusy || !hasCustomLayoutState());
  }

  function saveLayoutOrders() {
    if (!layoutRestUrl || !restNonce) return;

    var orders = collectAllLayoutOrders();
    var columns = collectAllLayoutColumns();
    layoutOrders = orders;
    layoutColumns = columns;

    window.fetch(layoutRestUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({ orders: orders, columns: columns })
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('layout_save_failed');
      }
      return response.json();
    }).then(function(responseData) {
      if (responseData && responseData.orders) {
        layoutOrders = normalizeLayoutOrders(responseData.orders);
      }
      if (responseData && responseData.columns) {
        layoutColumns = normalizeLayoutColumns(responseData.columns);
        applySavedLayoutColumns();
      }
      updateResetLayoutButtonState();
      showAutosaveNotice(i18n.layout_saved || '', 'success');
    }).catch(function() {
      updateResetLayoutButtonState();
      showAutosaveNotice(i18n.layout_failed || '', 'error');
    });
  }

  function resetSavedLayout() {
    var $button = $('[data-ecf-reset-layout]').first();
    if (!$button.length || !layoutRestUrl || !restNonce) return;

    if (!hasCustomLayoutState()) {
      updateResetLayoutButtonState();
      return;
    }

    if (i18n.layout_reset_confirm && !window.confirm(i18n.layout_reset_confirm)) {
      return;
    }

    $button.data('ecfResetting', true);
    updateResetLayoutButtonState();

    window.fetch(layoutRestUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({ orders: {}, columns: {} })
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('layout_reset_failed');
      }
      return response.json();
    }).then(function(responseData) {
      layoutOrders = normalizeLayoutOrders((responseData && responseData.orders) ? responseData.orders : {});
      layoutColumns = normalizeLayoutColumns((responseData && responseData.columns) ? responseData.columns : {});
      restoreDefaultLayoutState();
      applySavedLayoutColumns();
      refreshSortableLayoutGroups();
      scheduleMasonryLayouts();
      showAutosaveNotice(i18n.layout_reset || '', 'success');
    }).catch(function() {
      showAutosaveNotice(i18n.layout_reset_failed || i18n.layout_failed || '', 'error');
    }).finally(function() {
      $button.data('ecfResetting', false);
      updateResetLayoutButtonState();
    });
  }

  function ensureLayoutHandle($item, groupKey) {
    var handleSelector = '[data-ecf-layout-handle][data-ecf-layout-handle-for="' + groupKey + '"]';
    var $handle = $item.find(handleSelector).first();
    if ($handle.length) {
      return $handle;
    }

    var selectors = [
      '.ecf-vargroup-header:first',
      '.ecf-settings-group__header:first',
      '.ecf-settings-group__summary:first',
      '.ecf-system-limit-card__header:first',
      '.ecf-system-debug-card__summary:first',
      '.ecf-typography-preview-header:first',
      '.ecf-spacing-preview-header:first',
      '.ecf-shadow-preview-header:first',
      '.ecf-changelog-header:first',
      '> h2:first',
      '> h3:first'
    ];
    var $target = $();

    $.each(selectors, function(_, selector) {
      var $candidate = $item.find(selector).first();
      if (!$candidate.length && selector.indexOf('> ') === 0) {
        $candidate = $item.children(selector.replace(/^>\s*/, '')).first();
      }
      if ($candidate.length) {
        $target = $candidate;
        return false;
      }
    });

    if (!$target.length) {
      $target = $item;
    }

    var useStandaloneHandle = $target.is($item);
    $target.addClass('ecf-layout-handle-zone');

    if (useStandaloneHandle) {
      if (!$target.children('.ecf-layout-handle').length) {
        $target.prepend('<span class="ecf-layout-handle ecf-layout-handle--standalone" data-ecf-layout-handle="1" data-ecf-layout-handle-for="' + groupKey + '" aria-hidden="true"><span class="dashicons dashicons-move" aria-hidden="true"></span></span>');
      }
      return $target.find(handleSelector).first();
    }

    $target.attr('data-ecf-layout-handle', '1');
    $target.attr('data-ecf-layout-handle-for', groupKey);

    if (!$target.find(handleSelector).length && !$target.is('.ecf-settings-group__summary, .ecf-system-debug-card__summary')) {
      $target.prepend('<span class="ecf-layout-handle" data-ecf-layout-handle="1" data-ecf-layout-handle-for="' + groupKey + '" aria-hidden="true"><span class="dashicons dashicons-move"></span></span>');
    }

    return $target.find(handleSelector).first();
  }

  function initSortableLayoutGroups() {
    if (typeof $.fn.sortable !== 'function') return;

    $('[data-ecf-layout-group]').each(function() {
      var $group = $(this);
      var groupKey = String($group.data('ecf-layout-group') || '');
      applySavedLayoutToGroup($group);

      var $items = $group.children('[data-ecf-layout-item]');
      if ($items.length < 2) return;

      $items.each(function() {
        ensureLayoutHandle($(this), groupKey);
      });

      if ($group.data('ui-sortable')) {
        $group.sortable('destroy');
      }

      $group.sortable({
        items: '> [data-ecf-layout-item]',
        handle: '[data-ecf-layout-handle][data-ecf-layout-handle-for="' + groupKey + '"]',
        tolerance: 'pointer',
        placeholder: 'ecf-sortable-placeholder',
        forcePlaceholderSize: true,
        change: function(event, ui) {
          ui.placeholder.height(ui.item.outerHeight());
          ui.placeholder.width(ui.item.outerWidth());
        },
        start: function(event, ui) {
          var isMasonryGroup = $group.is('[data-ecf-masonry-layout]');
          ui.placeholder.height(ui.item.outerHeight());
          ui.placeholder.width(ui.item.outerWidth());
          ui.item.addClass('ecf-sortable-item--dragging');
          if (isMasonryGroup) {
            $group.addClass('ecf-layout-group--sorting');
            clearMasonryLayoutToGroup($group);
            ui.placeholder.height(ui.item.outerHeight());
          }
        },
        stop: function(event, ui) {
          var isMasonryGroup = $group.is('[data-ecf-masonry-layout]');
          ui.item.removeClass('ecf-sortable-item--dragging');
          if (isMasonryGroup) {
            $group.removeClass('ecf-layout-group--sorting');
            scheduleMasonryLayouts();
          }
          saveLayoutOrders();
        }
      });
    });
  }

  function refreshSortableLayoutGroups() {
    $('[data-ecf-layout-group]').each(function() {
      var $group = $(this);
      if ($group.data('ui-sortable')) {
        $group.sortable('refresh');
      }
    });
  }

  $(document).on('click', '.ecf-layout-columns-btn, [data-ecf-layout-columns-btn]', function() {
    var $button = $(this);
    var groupKey = String($button.data('group') || '');
    var count = parseInt($button.data('ecf-layout-columns') || 1, 10);
    if (!groupKey || count < 1 || count > 3) return;

    var $group = $('[data-ecf-layout-columns-group="' + groupKey + '"]').first();
    if (!$group.length) return;

    $group.attr('data-ecf-layout-columns', String(count)).css('--ecf-layout-columns', String(count));
    layoutColumns[groupKey] = count;
    applySavedLayoutColumns();
    saveLayoutOrders();
  });

  $(document).on('click', '[data-ecf-reset-layout]', function() {
    resetSavedLayout();
  });

  function submitSettingsAutosave() {
    if (!$settingsForm.length || !restUrl || !restNonce) return;
    updateAutosavePill();
    if (!autosaveSkipValidation && !validateSettingsForSave()) return;

    var payload = buildSettingsPayloadFromForm();
    var payloadHash = stableStringify(payload);

    if (lastSavedSettingsPayload && payloadHash === lastSavedSettingsPayload) {
      if (autosaveRecoveryNoticePending) {
        autosaveRecoveryNoticePending = false;
        showAutosaveNotice(i18n.autosave_saved || '', 'success');
      }
      autosaveQueued = false;
      queuedSettingsPayload = '';
      return;
    }

    if (autosaveInFlight) {
      if (payloadHash !== inFlightSettingsPayload) {
        autosaveQueued = true;
        queuedSettingsPayload = payloadHash;
      }
      return;
    }

    autosaveInFlight = true;
    inFlightSettingsPayload = payloadHash;
    autosaveQueued = false;
    queuedSettingsPayload = '';
    persistAdminPageState($settingsForm);
    showAutosaveNotice(i18n.autosave_saving || '', 'saving');

    window.fetch(restUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({ settings: payload })
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('rest_save_failed');
      }
      return response.json();
    }).then(function(responseData) {
      autosaveInFlight = false;
      inFlightSettingsPayload = '';
      var responseSettings = responseData && responseData.settings ? responseData.settings : payload;
      updateSystemInfoCards(responseData && responseData.meta ? responseData.meta : null, responseSettings);
      syncLocalFontRowsFromSettings(responseSettings);
      syncFontFamilyFieldFromSettings('base_font_family', responseSettings);
      syncFontFamilyFieldFromSettings('heading_font_family', responseSettings);
      syncInterfaceLanguageFieldFromSettings(responseSettings);
      syncBodyTextSizeFieldFromSettings(responseSettings);
      syncGeneralFavoriteTogglesFromSettings(responseSettings);
      if (responseSettings.admin_design_preset) {
        $('[data-ecf-admin-design-preset]').val(responseSettings.admin_design_preset);
        adminDesign.preset = responseSettings.admin_design_preset;
      }
      if (responseSettings.admin_design_mode) {
        $('[data-ecf-admin-design-mode]').val(responseSettings.admin_design_mode);
        adminDesign.mode = responseSettings.admin_design_mode;
      }
      if (responseSettings.admin_content_font_size) {
        applyAdminContentFontSize(responseSettings.admin_content_font_size, true);
      }
      if (responseSettings.admin_menu_font_size) {
        applyAdminMenuFontSize(responseSettings.admin_menu_font_size, true);
      }
      refreshAdminDesignChooser();
      markCurrentStateAsSaved();
      autosaveRecoveryNoticePending = false;

      if (autosaveQueued) {
        if (queuedSettingsPayload && queuedSettingsPayload === lastSavedSettingsPayload) {
          autosaveQueued = false;
          queuedSettingsPayload = '';
        } else {
          autosaveQueued = false;
          submitSettingsAutosave();
          return;
        }
      }

      if (autosaveReloadRequested) {
        autosaveReloadRequested = false;
        window.location.reload();
        return;
      }

      showAutosaveNotice(i18n.autosave_saved || '', 'success');
      maybeRunElementorAutoSync(responseSettings);
    }).catch(function() {
      autosaveInFlight = false;
      inFlightSettingsPayload = '';

      if (autosaveQueued) {
        autosaveQueued = false;
        queuedSettingsPayload = '';
      }

      showAutosaveNotice(i18n.autosave_failed || '', 'error');
    }).finally(function() {
      autosaveSkipValidation = false;
    });
  }

  function scheduleSettingsAutosave(options) {
    if (!$settingsForm.length || !autosaveReady) return;

    var opts = options || {};
    updateAutosavePill();
    if (!opts.force && !isAutosaveEnabled()) {
      updateLayrixVariableCount();
      updateUnsavedBadge();
      showAutosaveNotice(i18n.autosave_disabled || '', 'error');
      return;
    }
    var delay = typeof opts.delay === 'number' ? opts.delay : 700;
    updateLayrixVariableCount();
    updateUnsavedBadge();
    if (opts.reloadAfterSave) {
      autosaveReloadRequested = true;
    }
    if (opts.skipValidation) {
      autosaveSkipValidation = true;
    }

    window.clearTimeout(autosaveTimer);
    autosaveTimer = window.setTimeout(function() {
      submitSettingsAutosave();
    }, delay);
  }

  function ensureAutosaveNotice() {
    if ($autosaveNotice.length) {
      return $autosaveNotice;
    }

    $autosaveNotice = $('<div class="notice ecf-panel-notice ecf-autosave-notice" aria-live="polite" hidden><p></p></div>');
    $('.ecf-main').prepend($autosaveNotice);
    return $autosaveNotice;
  }

  function showAutosaveNotice(message, state) {
    var $notice = ensureAutosaveNotice();
    var hideTimer = $notice.data('hideTimer');
    if (hideTimer) {
      window.clearTimeout(hideTimer);
    }

    $notice.removeClass('ecf-panel-notice--success ecf-panel-notice--error ecf-panel-notice--saving');
    $notice.find('p').text(message);
    if (state === 'error') {
      $notice.addClass('ecf-panel-notice--error');
    } else if (state === 'saving') {
      $notice.addClass('ecf-panel-notice--saving');
    } else {
      $notice.addClass('ecf-panel-notice--success');
    }
    $notice.prop('hidden', false).addClass('is-visible');

    if (state === 'saving') {
      return;
    }

    hideTimer = window.setTimeout(function() {
      $notice.removeClass('is-visible');
      window.setTimeout(function() {
        $notice.prop('hidden', true);
      }, 180);
    }, 1800);

    $notice.data('hideTimer', hideTimer);
  }

  function formatTemplate(template, value) {
    return String(template || '').replace('%s', value == null ? '' : String(value));
  }

  function formatFileSize(bytes) {
    var size = Number(bytes || 0);
    if (!size) return '0 B';
    if (size < 1024) return size + ' B';
    if (size < 1024 * 1024) return formatNumber(size / 1024, 1) + ' KB';
    return formatNumber(size / (1024 * 1024), 1) + ' MB';
  }

  function safeInitStep(label, callback) {
    try {
      callback();
    } catch (error) {
      if (window.console && typeof window.console.error === 'function') {
        window.console.error('ECF admin init failed:', label, error);
      }
    }
  }

  function updateImportPreview(data) {
    var $preview = $('[data-ecf-import-preview]');
    if (!$preview.length) return;

    var $meta = $preview.find('[data-ecf-import-preview-meta]');
    var $warning = $preview.find('[data-ecf-import-preview-warning]');
    var lines = [];

    if (!data) {
      $preview.prop('hidden', true);
      $meta.empty();
      $warning.prop('hidden', true).text('');
      return;
    }

    lines.push('<div>' + escapeHtml(formatTemplate(i18n.import_preview_file || '', data.name + ' (' + formatFileSize(data.size) + ')')) + '</div>');
    if (data.meta && data.meta.plugin_version) {
      lines.push('<div>' + escapeHtml(formatTemplate(i18n.import_preview_version || '', data.meta.plugin_version)) + '</div>');
    }
    if (data.meta && data.meta.exported_at) {
      lines.push('<div>' + escapeHtml(formatTemplate(i18n.import_preview_date || '', data.meta.exported_at)) + '</div>');
    }
    if (data.meta && data.meta.schema_version) {
      lines.push('<div>' + escapeHtml(formatTemplate(i18n.import_preview_schema || '', data.meta.schema_version)) + '</div>');
    }
    if (data.settingsCount) {
      lines.push('<div>' + escapeHtml(formatTemplate(i18n.import_preview_sections || '', data.settingsCount)) + '</div>');
    }
    if (!data.meta) {
      lines.push('<div>' + escapeHtml(i18n.import_preview_legacy || '') + '</div>');
    }

    $meta.html(lines.join(''));

    if (data.warning) {
      $warning.prop('hidden', false).text(data.warning);
    } else {
      $warning.prop('hidden', true).text('');
    }

    $preview.prop('hidden', false);
  }

  $(document).on('change', '[data-ecf-import-file]', function() {
    var file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
      updateImportPreview(null);
      return;
    }

    var reader = new window.FileReader();
    reader.onload = function(event) {
      try {
        var parsed = JSON.parse(String(event.target.result || ''));
        var meta = parsed && typeof parsed === 'object' && parsed.meta && typeof parsed.meta === 'object' ? parsed.meta : null;
        var warning = '';
        if (!meta && !(parsed && typeof parsed === 'object')) {
          throw new Error('invalid');
        }
        if (meta && meta.plugin_version && ecfAdmin.pluginVersion && String(meta.plugin_version) !== String(ecfAdmin.pluginVersion)) {
          warning = i18n.import_preview_incompatible || '';
        }

        updateImportPreview({
          name: file.name,
          size: file.size,
          meta: meta,
          settingsCount: parsed && parsed.settings && typeof parsed.settings === 'object' ? Object.keys(parsed.settings).length : 0,
          warning: warning
        });
      } catch (err) {
        updateImportPreview({
          name: file.name,
          size: file.size,
          meta: null,
          warning: i18n.import_preview_invalid || ''
        });
      }
    };
    reader.readAsText(file);
  });

  function storePageScrollPosition() {
    try {
      window.sessionStorage.setItem(pageScrollStorageKey, String(window.scrollY || window.pageYOffset || 0));
    } catch (err) {}
  }

  function storePageFocusTarget(targetId) {
    try {
      if (targetId) {
        window.sessionStorage.setItem(pageFocusStorageKey, String(targetId));
      } else {
        window.sessionStorage.removeItem(pageFocusStorageKey);
      }
    } catch (err) {}
  }

  function getPersistedFocusTargetId($origin) {
    if (!$origin || !$origin.length) return '';

    var $focusTarget = $origin.closest('#ecf-elementor-limits');
    if ($focusTarget.length) return $focusTarget.attr('id') || '';

    $focusTarget = $origin.closest('[data-panel]');
    if ($focusTarget.length && $focusTarget.attr('data-panel')) {
      return '';
    }

    $focusTarget = $origin.closest('[id]');
    if ($focusTarget.length) return $focusTarget.attr('id') || '';

    return '';
  }

  function persistAdminPageState($origin) {
    storePageFocusTarget(getPersistedFocusTargetId($origin));
    storePageScrollPosition();
  }

  function restorePageScrollPosition() {
    var storedTop = null;
    var storedFocus = null;
    try {
      storedTop = window.sessionStorage.getItem(pageScrollStorageKey);
      storedFocus = window.sessionStorage.getItem(pageFocusStorageKey);
      window.sessionStorage.removeItem(pageScrollStorageKey);
      window.sessionStorage.removeItem(pageFocusStorageKey);
    } catch (err) {
      storedTop = null;
      storedFocus = null;
    }

    var focusSelector = storedFocus ? ('#' + storedFocus.replace(/[^A-Za-z0-9\-_:.]/g, '')) : '';
    var $focusTarget = focusSelector ? $(focusSelector).first() : $();
    if ($focusTarget.length) {
      window.requestAnimationFrame(function() {
        window.requestAnimationFrame(function() {
          $focusTarget.get(0).scrollIntoView({ block: 'start', behavior: 'auto' });
        });
      });
      window.setTimeout(function() {
        $focusTarget.get(0).scrollIntoView({ block: 'start', behavior: 'auto' });
      }, 160);
    }

    if (storedTop == null) return;

    var top = parseInt(storedTop, 10);
    if (isNaN(top) || top < 0) return;

    window.requestAnimationFrame(function() {
      window.requestAnimationFrame(function() {
        window.scrollTo(0, top);
      });
    });

    window.setTimeout(function() {
      window.scrollTo(0, top);
    }, 160);
  }

  function getWhatsNewState() {
    try {
      var raw = window.localStorage.getItem(whatsNewStorageKey);
      return raw ? JSON.parse(raw) : {};
    } catch (err) {
      return {};
    }
  }

  function saveWhatsNewState(state) {
    try {
      window.localStorage.setItem(whatsNewStorageKey, JSON.stringify(state || {}));
    } catch (err) {}
  }

  function ensureWhatsNewEntry(state, key) {
    if (!key) return null;
    if (!state[key]) {
      state[key] = {
        seen: false,
        impressions: 0
      };
    }
    return state[key];
  }

  function shouldShowWhatsNewBadge(state, key) {
    var entry = ensureWhatsNewEntry(state, key);
    return !!(entry && !entry.seen && entry.impressions < whatsNewMaxImpressions);
  }

  function refreshWhatsNewBadges() {
    var state = getWhatsNewState();
    $('[data-ecf-new-key]').each(function() {
      var key = $(this).data('ecf-new-key');
      var show = shouldShowWhatsNewBadge(state, key);
      $(this).find('[data-ecf-new-badge]').prop('hidden', !show);
    });
  }

  function markWhatsNewSeen(key) {
    if (!key) return;
    var state = getWhatsNewState();
    var entry = ensureWhatsNewEntry(state, key);
    entry.seen = true;
    saveWhatsNewState(state);
    refreshWhatsNewBadges();
  }

  function registerWhatsNewImpressions() {
    var state = getWhatsNewState();
    var changed = false;
    var keys = {};
    $('[data-ecf-new-key]').each(function() {
      var key = $(this).data('ecf-new-key');
      if (key) {
        keys[key] = true;
      }
    });

    Object.keys(keys).forEach(function(key) {
      var entry = ensureWhatsNewEntry(state, key);
      if (!entry.seen && entry.impressions < whatsNewMaxImpressions) {
        entry.impressions += 1;
        changed = true;
      }
    });

    if (changed) {
      saveWhatsNewState(state);
    }
  }

  function switchPanel(panel) {
    $('.ecf-nav-item').removeClass('is-active');
    $('.ecf-sidebar-link[data-panel]').removeClass('is-active');
    $('.ecf-nav-item[data-panel="'+panel+'"]').addClass('is-active');
    $('.ecf-sidebar-link[data-panel="'+panel+'"]').addClass('is-active');
    $('.ecf-panel').removeClass('is-active');
    $('.ecf-panel[data-panel="'+panel+'"]').addClass('is-active');

    try {
      window.sessionStorage.setItem(panelStorageKey, panel);
    } catch (err) {}

    updateStickyTopbar(panel);
    scrollActivePanelToTop(panel);

    if (panel === 'variables') {
      loadVariables();
    }

    refreshSortableLayoutGroups();
    scheduleMasonryLayouts();
  }

  $(document).on('click', '.ecf-nav-item', function(){
    var panel = $(this).data('panel');
    markWhatsNewSeen($(this).data('ecf-new-key'));
    switchPanel(panel);
    updateStickyTopbar(panel);
  });

  $(document).on('click', '.ecf-sidebar-link[data-panel]', function(){
    var panel = $(this).data('panel');
    switchPanel(panel);
    updateStickyTopbar(panel);
  });

  function normalizeGeneralTab(tab) {
    if (tab === 'layout' || tab === 'colors' || tab === 'typography') {
      return 'website';
    }
    if (tab === 'behavior' || tab === 'editor' || tab === 'ui') {
      return 'interface';
    }
    if (tab === 'favorites' || tab === 'website' || tab === 'interface' || tab === 'system') {
      return tab;
    }
    return 'website';
  }

  function normalizeWebsiteTab(tab) {
    if (tab === 'layout' || tab === 'colors' || tab === 'advanced' || tab === 'type') {
      return tab;
    }
    return 'type';
  }

  function switchWebsiteTab(tab) {
    var activeTab = normalizeWebsiteTab(tab);
    $('[data-ecf-website-tab]').removeClass('is-active').attr('aria-pressed', 'false')
      .filter('[data-ecf-website-tab="' + activeTab + '"]').addClass('is-active').attr('aria-pressed', 'true');
    $('[data-ecf-website-section]').removeClass('is-active').prop('hidden', true)
      .filter('[data-ecf-website-section="' + activeTab + '"]').addClass('is-active').prop('hidden', false);

    try {
      window.sessionStorage.setItem(websiteTabStorageKey, activeTab);
    } catch (err) {}

    closeFontPicker($('[data-ecf-website-section]').not('.is-active').find('[data-ecf-font-picker]'));
    scheduleMasonryLayouts();
  }

  function switchGeneralTab(tab) {
    var activeTab = normalizeGeneralTab(tab);
    $('[data-ecf-general-tab]').removeClass('is-active')
      .filter('[data-ecf-general-tab="' + activeTab + '"]').addClass('is-active');
    $('[data-ecf-general-section]').removeClass('is-active').prop('hidden', true)
      .filter('[data-ecf-general-section="' + activeTab + '"]').addClass('is-active').prop('hidden', false);

    try {
      window.sessionStorage.setItem(generalTabStorageKey, activeTab);
    } catch (err) {}

    closeFontPicker($('[data-ecf-general-section="' + activeTab + '"] [data-ecf-font-picker]'));
    scrollActivePanelToTop('components');
    refreshSortableLayoutGroups();
    scheduleMasonryLayouts();
  }

  $(document).on('click', '[data-ecf-general-tab]', function() {
    markWhatsNewSeen($(this).data('ecf-new-key'));
    switchGeneralTab($(this).data('ecf-general-tab'));
  });

  $(document).on('click', '[data-ecf-website-tab]', function() {
    switchWebsiteTab($(this).data('ecf-website-tab'));
  });

  $(document).on('toggle', '.ecf-system-debug-card', function() {
    if (this.open) {
      markWhatsNewSeen($(this).data('ecf-new-key'));
    }
    scheduleMasonryLayouts();
  });

  $(document).on('toggle', '[data-ecf-masonry-layout] details', function() {
    scheduleMasonryLayouts();
  });

  function refreshGeneralFavoritesState() {
    var visibleCards = 0;
    $('[data-ecf-favorite-card]').each(function() {
      var key = $(this).data('ecf-favorite-card');
      var enabled = $('[data-ecf-general-favorite-toggle][data-ecf-favorite-key="' + key + '"]').filter(':checked').length > 0;
      $(this).prop('hidden', !enabled);
      if (enabled) visibleCards += 1;
    });

    $('[data-ecf-general-favorites-group]').each(function() {
      var hasVisible = $(this).find('[data-ecf-favorite-card]:not([hidden])').length > 0;
      $(this).prop('hidden', !hasVisible);
    });

    $('[data-ecf-general-favorites-empty]').prop('hidden', visibleCards > 0);
  }

  function syncFavoriteToggleState($toggle) {
    if (!$toggle || !$toggle.length) return;
    var $input = $toggle.find('[data-ecf-general-favorite-toggle]');
    var $icon = $toggle.find('.ecf-favorite-toggle__icon');
    var enabled = $input.is(':checked');
    var tip = enabled ? ($toggle.attr('data-tip-on') || '') : ($toggle.attr('data-tip-off') || '');
    $toggle.attr('data-tip', tip);
    $toggle.attr('aria-label', tip);
    if ($icon.length) {
      $icon.text(enabled ? '♥' : '♡');
    }
  }

  function syncAllFavoriteToggleStates() {
    $('.ecf-favorite-toggle').each(function() {
      syncFavoriteToggleState($(this));
    });
  }

  function syncFavoriteToggleGroup($source) {
    if (!$source || !$source.length) return;
    var key = $source.data('ecf-favorite-key');
    if (!key) return;
    var checked = $source.is(':checked');
    $('[data-ecf-general-favorite-toggle][data-ecf-favorite-key="' + key + '"]').not($source).each(function() {
      $(this).prop('checked', checked);
      syncFavoriteToggleState($(this).closest('.ecf-favorite-toggle'));
    });
  }

  $(document).on('change', '[data-ecf-general-favorite-toggle]', function() {
    syncFavoriteToggleGroup($(this));
    syncFavoriteToggleState($(this).closest('.ecf-favorite-toggle'));
    refreshGeneralFavoritesState();
  });

  $(document).on('click', '[data-ecf-favorite-remove]', function(e) {
    e.stopPropagation();
    var key = $(this).data('ecf-favorite-remove');
    $('[data-ecf-general-favorite-toggle][data-ecf-favorite-key="' + key + '"]').prop('checked', false).trigger('change');
  });

  syncAllFavoriteToggleStates();

  var $floatingNewTooltip = null;
  var activeNewTooltipEl = null;

  function ensureFloatingNewTooltip() {
    if ($floatingNewTooltip && $floatingNewTooltip.length) {
      return $floatingNewTooltip;
    }

    $floatingNewTooltip = $('<div class="ecf-floating-tooltip" aria-hidden="true"></div>').appendTo(document.body);
    return $floatingNewTooltip;
  }

  function positionFloatingNewTooltip($anchor) {
    if (!$anchor || !$anchor.length || !$floatingNewTooltip || !$floatingNewTooltip.length) {
      return;
    }

    var rect = $anchor[0].getBoundingClientRect();
    var tooltipWidth = $floatingNewTooltip.outerWidth();
    var tooltipHeight = $floatingNewTooltip.outerHeight();
    var spacing = 12;
    var left = rect.left + (rect.width / 2) - (tooltipWidth / 2);
    var top = rect.top - tooltipHeight - spacing;

    left = Math.max(16, Math.min(left, window.innerWidth - tooltipWidth - 16));

    if (top < 16) {
      top = rect.bottom + spacing;
    }

    $floatingNewTooltip.css({
      left: Math.round(left) + 'px',
      top: Math.round(top) + 'px'
    });
  }

  function hideFloatingNewTooltip() {
    if (activeNewTooltipEl) {
      if ($(activeNewTooltipEl).hasClass('ecf-new-dot')) {
        $(activeNewTooltipEl).removeClass('ecf-new-dot--floating-active');
      }
      activeNewTooltipEl = null;
    }

    if ($floatingNewTooltip && $floatingNewTooltip.length) {
      $floatingNewTooltip.removeClass('is-visible').text('');
    }
  }

  function showFloatingNewTooltip(el) {
    var $el = $(el);
    var tip = $.trim($el.attr('data-tip') || '');
    if (!tip) {
      return;
    }

    activeNewTooltipEl = el;
    if ($el.hasClass('ecf-new-dot')) {
      $el.addClass('ecf-new-dot--floating-active');
    }
    ensureFloatingNewTooltip().text(tip);
    positionFloatingNewTooltip($el);
    requestAnimationFrame(function() {
      if ($floatingNewTooltip && $floatingNewTooltip.length) {
        $floatingNewTooltip.addClass('is-visible');
      }
    });
  }

  $(document).on('mouseenter focus', '[data-tip]', function() {
    showFloatingNewTooltip(this);
  });

  $(document).on('mouseleave blur', '[data-tip]', function() {
    hideFloatingNewTooltip();
  });

  $(window).on('resize scroll', function() {
    if (activeNewTooltipEl) {
      positionFloatingNewTooltip($(activeNewTooltipEl));
    }
  });

  var isSyncingGeneralFields = false;

  function syncNamedField($source) {
    if (isSyncingGeneralFields) return;
    var name = $source.attr('name');
    if (!name) return;
    var selector = '[name="' + name.replace(/"/g, '\\"') + '"]';
    var $targets = $(selector).not($source);
    if (!$targets.length) return;

    isSyncingGeneralFields = true;

    if ($source.is(':checkbox')) {
      var checked = $source.is(':checked');
      $targets.each(function() {
        $(this).prop('checked', checked);
      });
      isSyncingGeneralFields = false;
      return;
    }

    var value = $source.val();
    $targets.each(function() {
      var $target = $(this);
      if ($target.val() !== value) {
        $target.val(value);
      }
    });

    isSyncingGeneralFields = false;
  }

  $(document).on('input change', '.ecf-general-favorite-card [name], [data-ecf-general-section] [name]', function() {
    syncNamedField($(this));
  });

  $(document).on('input change', '[data-ecf-admin-content-font-size]', function(event) {
    applyAdminContentFontSize($(this).val(), event.type === 'change');
  });

  $(document).on('input change', '[data-ecf-admin-menu-font-size]', function(event) {
    applyAdminMenuFontSize($(this).val(), event.type === 'change');
  });

  $(document).on('blur change', '[data-ecf-slug-field="token"]', function() {
    var normalized = normalizeTokenName($(this).val());
    if ($(this).val() !== normalized) {
      $(this).val(normalized).trigger('input');
    }
  });

  $(document).on('input', 'form[action="options.php"] :input[name]:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"])', function() {
    var $input = $(this);
    updateUnsavedBadge();
    if ($input.is('[name^="ecf_framework_v50[typography][scale]"]')) {
      return;
    }
    scheduleSettingsAutosave({ delay: 900 });
  });

  $(document).on('change', 'form[action="options.php"] select[name], form[action="options.php"] textarea[name], form[action="options.php"] input[type="checkbox"][name], form[action="options.php"] input[type="radio"][name], form[action="options.php"] input[type="hidden"][name]', function() {
    updateUnsavedBadge();
    var currentValue = $(this).val();
    if (typeof currentValue === 'string' && currentValue.indexOf('__library__|') === 0) {
      return;
    }
    var fieldName = String($(this).attr('name') || '');
    var managedTopbarFields = topbarManagedSettingNames().map(function(key) {
      return 'ecf_framework_v50[' + key + ']';
    });
    var isAutosaveToggle = fieldName === 'ecf_framework_v50[autosave_enabled]';
    var isManagedTopbarField = managedTopbarFields.indexOf(fieldName) !== -1;
    var isLanguageField = fieldName === 'ecf_framework_v50[interface_language]';
    scheduleSettingsAutosave({
      delay: 250,
      force: isAutosaveToggle || isManagedTopbarField,
      reloadAfterSave: isLanguageField,
      skipValidation: isLanguageField
    });
  });

  function closeAutosaveMenu() {
    $('[data-ecf-autosave-control]').removeClass('is-open');
    $('[data-ecf-autosave-menu]').prop('hidden', true);
    $('[data-ecf-autosave-toggle]').attr('aria-expanded', 'false');
  }

  function toggleAutosaveMenu() {
    var $control = $('[data-ecf-autosave-control]').first();
    if (!$control.length) return;
    var isOpen = $control.hasClass('is-open');
    closeAutosaveMenu();
    if (isOpen) return;
    $control.addClass('is-open');
    $control.find('[data-ecf-autosave-menu]').prop('hidden', false);
    $control.find('[data-ecf-autosave-toggle]').attr('aria-expanded', 'true');
  }

  function toggleTopbarSetting(key, checked) {
    var $source = getSettingsCheckboxByKey(key);
    if (!$source.length) return;
    if ($source.is(':checked') !== checked) {
      $source.prop('checked', checked).trigger('change');
    }
    updateAutosavePill();
  }

  $(document).on('click', '[data-ecf-autosave-toggle]', function(event) {
    event.preventDefault();
    event.stopPropagation();
    var rect = this.getBoundingClientRect();
    var arrowZoneWidth = 48;
    var clickedArrowZone = event.clientX >= (rect.right - arrowZoneWidth);

    if (clickedArrowZone) {
      toggleAutosaveMenu();
      return;
    }

    closeAutosaveMenu();
    toggleTopbarSetting('autosave_enabled', !isAutosaveEnabled());
  });

  $(document).on('change', '[data-ecf-topbar-setting]', function() {
    var key = String($(this).attr('data-ecf-topbar-setting') || '');
    toggleTopbarSetting(key, $(this).is(':checked'));
  });

  $(document).on('click', function(event) {
    if ($(event.target).closest('[data-ecf-autosave-control]').length) {
      return;
    }
    closeAutosaveMenu();
  });

  $(document).on('keydown', function(event) {
    if (event.key === 'Escape') {
      closeAutosaveMenu();
    }
  });

  $(document).on('change', '[data-ecf-font-family-preset]', function() {
    var $select = $(this);
    var $field = $select.closest('[data-ecf-general-field]');
    var fieldName = $(this).data('ecf-font-family-field') || 'base_font_family';
    var $presetInput = $field.find('[data-ecf-font-family-preset-input][data-ecf-font-family-field="' + fieldName + '"]').first();
    var $custom = $field.find('[data-ecf-font-family-custom][data-ecf-font-family-field="' + fieldName + '"]').first();
    var selectedValue = String($select.val() || '');
    var previousPresetValue = String($presetInput.val() || '');
    var showCustom = selectedValue === '__custom__';

    if (selectedValue.indexOf('__library__|') === 0) {
      var family = selectedValue.split('|').slice(1).join('|');
      var previousValue = String($select.data('ecf-prev-value') || 'var(--ecf-font-primary)');
      var target = String($select.data('ecf-font-library-target') || 'body');

      window.clearTimeout(autosaveTimer);
      $select.val(previousValue);
      $custom.val('').prop('hidden', true);
      importLibraryFontIntoField(fieldName, target, family, $select);
      return;
    }

    $select.data('ecf-prev-value', selectedValue);
    applyFontFamilyPresetSelection(fieldName, selectedValue);
    if (previousPresetValue !== selectedValue) {
      $presetInput.trigger('input').trigger('change');
    }
    closeFontPicker($field);
    updateUnsavedBadge();
    if (!showCustom && previousPresetValue !== selectedValue) {
      scheduleSettingsAutosave({ delay: 250 });
    }
    if (showCustom) {
      $custom.trigger('focus');
    }
  });

  $(document).on('focus mousedown', '[data-ecf-font-family-preset]', function() {
    $(this).data('ecf-prev-value', String($(this).val() || ''));
  });

  $(document).on('pointerdown', '[data-ecf-font-family-search]', function() {
    $(this).data('ecf-open-intent', 'pointer');
  });

  $(document).on('keydown', '[data-ecf-font-family-search]', function(event) {
    if (event.key === 'Tab' || event.key === 'Enter' || event.key === 'ArrowDown' || event.key === ' ') {
      $(this).data('ecf-open-intent', 'keyboard');
    }
  });

  $('[data-ecf-font-family-preset]').each(function() {
    $(this).data('ecf-prev-value', String($(this).val() || ''));
    var $field = $(this).closest('[data-ecf-general-field]');
    $field.data('ecfFontBaseGroups', extractFontFamilyGroupsFromSelect($(this)));
    syncFontFamilyCurrentLabel($field);
    closeFontPicker($field);
  });

  $(document).on('focusin', '[data-ecf-font-family-search]', function() {
    var $search = $(this);
    if (!$search.data('ecf-open-intent')) {
      return;
    }
    var $field = $search.closest('[data-ecf-general-field]');
    $search.removeData('ecf-open-intent');
    openFontPicker($field);
    refreshFontFamilyList($field, $search.val());
  });

  $(document).on('click', '[data-ecf-font-family-search]', function() {
    var $search = $(this);
    var $field = $search.closest('[data-ecf-general-field]');
    $search.removeData('ecf-open-intent');
    openFontPicker($field);
    refreshFontFamilyList($field, $search.val());
  });

  $(document).on('input', '[data-ecf-font-family-search]', function() {
    var $field = $(this).closest('[data-ecf-general-field]');
    if (!$field.hasClass('is-open')) {
      openFontPicker($field);
    }
    refreshFontFamilyList($field, $(this).val());
  });

  $(document).on('input change', '[data-ecf-font-family-custom]', function() {
    var fieldName = String($(this).data('ecf-font-family-field') || 'base_font_family');
    mirrorFontFamilyCustomValue(fieldName, $(this).val());
  });

  $(document).on('click', function(event) {
    var $target = $(event.target);
    $('[data-ecf-font-picker]').each(function() {
      var $picker = $(this);
      if ($picker.is($target) || $picker.has($target).length) {
        return;
      }
      closeFontPicker($picker);
    });
  });

  function openLocalFontsSection(callback) {
    switchPanel('typography');
    window.setTimeout(function() {
      var $section = $('[data-ecf-local-fonts-section]').first();
      if ($section.length) {
        var node = $section.get(0);
        if (node && typeof node.scrollIntoView === 'function') {
          node.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
      if (typeof callback === 'function') {
        callback($section);
      }
    }, 150);
  }

  $(document).on('click', '[data-ecf-local-font-add]', function(e) {
    e.preventDefault();
    pendingFontAutolinkField = String($(this).data('ecf-font-family-field') || 'base_font_family');
    openLocalFontsSection(function($section) {
      var $addButton = $section.find('.ecf-add-local-font').first();
      if ($addButton.length) {
        $addButton.trigger('click');
        window.setTimeout(function() {
          $section.find('.ecf-font-file-row:last input[name$="[family]"]').trigger('focus');
        }, 40);
      }
    });
  });

  $(document).on('input change', '[data-ecf-local-fonts-section] .ecf-font-file-row input[name$="[family]"]', function() {
    if (!pendingFontAutolinkField) return;

    var family = $.trim($(this).val() || '');
    if (!family) return;

    var $field = getPrimaryFontFamilyField(pendingFontAutolinkField);
    $field.find('[data-ecf-font-family-preset]').first().val('__custom__').trigger('change');
    $field.find('[data-ecf-font-family-custom]').first().val("'" + family + "'").trigger('input').trigger('change');
    pendingFontAutolinkField = '';
  });

  $(document).on('click', '[data-ecf-local-font-remove]', function(e) {
    e.preventDefault();
    var family = $.trim($(this).data('ecf-local-font-remove') || '');
    var fieldName = String($(this).data('ecf-font-family-field') || 'base_font_family');
    if (!family) return;
    getFontFamilyFields(fieldName).each(function() {
      var $field = $(this);
      $field.find('[data-ecf-font-family-preset]').first().val('var(--ecf-font-primary)').trigger('change');
      $field.find('[data-ecf-font-family-custom]').first().val('').prop('hidden', true);
      syncFontFamilyCurrentLabel($field);
      closeFontPicker($field);
    });
    openLocalFontsSection(function($section) {
      var removed = false;
      $section.find('.ecf-font-file-row').each(function() {
        var $row = $(this);
        var rowFamily = $.trim($row.find('input[name$="[family]"]').val() || '');
        if (rowFamily === family) {
          $row.find('.ecf-remove-row').trigger('click');
          removed = true;
          return false;
        }
      });
      if (removed) {
        scheduleSettingsAutosave({ delay: 250 });
      }
    });
  });

  $(document).on('click', '[data-ecf-font-library-import]', function(e) {
    e.preventDefault();
    var $button = $(this);
    var target = String($button.data('ecf-font-library-target') || 'body');
    var fieldName = String($button.data('ecf-font-family-field') || 'base_font_family');
    var $controls = $button.closest('[data-ecf-font-library-controls]');
    var family = $.trim($controls.find('[data-ecf-font-library-search]').first().val() || '');

    importLibraryFontIntoField(fieldName, target, family, $button);
  });

  function openChangelogModal() {
    $('[data-ecf-changelog-modal]').prop('hidden', false).addClass('is-open');
    $('body').addClass('ecf-modal-open');
  }

  function closeChangelogModal() {
    $('[data-ecf-changelog-modal]').removeClass('is-open').prop('hidden', true);
    $('body').removeClass('ecf-modal-open');
  }

  $(document).on('click', '[data-ecf-open-changelog-modal]', function(){
    openChangelogModal();
  });

  $(document).on('click', '[data-ecf-close-changelog-modal]', function(){
    closeChangelogModal();
  });

  $(document).on('keydown', function(e){
    if (e.key === 'Escape') {
      closeChangelogModal();
      closeSearchEditModal();
    }
  });

  // Keep the active panel after saving or reloading
  var initialPanel = 'tokens';
  try {
    var storedPanel = window.sessionStorage.getItem(panelStorageKey);
    if (storedPanel && $('[data-panel="'+storedPanel+'"]').filter('.ecf-nav-item, .ecf-sidebar-link').length) {
      initialPanel = storedPanel;
    }
  } catch (err) {}
  layoutOrders = normalizeLayoutOrders(layoutOrders);
  captureDefaultLayoutState();
  switchPanel(initialPanel);
  var initialGeneralTab = 'website';
  try {
    var storedGeneralTab = window.sessionStorage.getItem(generalTabStorageKey);
    if (storedGeneralTab) {
      initialGeneralTab = normalizeGeneralTab(storedGeneralTab);
    }
  } catch (err) {}
  switchGeneralTab(initialGeneralTab);
  var initialWebsiteTab = 'type';
  try {
    var storedWebsiteTab = window.sessionStorage.getItem(websiteTabStorageKey);
    if (storedWebsiteTab) {
      initialWebsiteTab = normalizeWebsiteTab(storedWebsiteTab);
    }
  } catch (err) {}
  switchWebsiteTab(initialWebsiteTab);
  $('[data-ecf-base-font-preset]').trigger('change');
  updateLayrixVariableCount();
  updateAutosavePill();
  lastElementorAutoSyncPayload = stableStringify(buildElementorAutoSyncPayload());
  markCurrentStateAsSaved();
  autosaveReady = true;
  refreshGeneralFavoritesState();
  safeInitStep('mark active whats-new items', function() {
    markWhatsNewSeen($('.ecf-nav-item.is-active').data('ecf-new-key'));
    markWhatsNewSeen($('[data-ecf-general-tab].is-active').data('ecf-new-key'));
    if ($('.ecf-system-debug-card').prop('open')) {
      markWhatsNewSeen($('.ecf-system-debug-card').data('ecf-new-key'));
    }
  });
  safeInitStep('register whats-new impressions', function() {
    registerWhatsNewImpressions();
  });
  safeInitStep('refresh whats-new badges', function() {
    refreshWhatsNewBadges();
  });
  safeInitStep('restore scroll position', function() {
    restorePageScrollPosition();
  });
  safeInitStep('refresh admin design chooser', function() {
    refreshAdminDesignChooser();
  });
  safeInitStep('apply saved layout columns', function() {
    applySavedLayoutColumns();
  });
  safeInitStep('init sortable layout groups', function() {
    initSortableLayoutGroups();
  });
  safeInitStep('schedule masonry layouts', function() {
    scheduleMasonryLayouts();
  });
  safeInitStep('refresh reset layout button', function() {
    updateResetLayoutButtonState();
  });

  $(window).on('resize', function() {
    scheduleMasonryLayouts();
  });

  $(document).on('submit', '.ecf-wrap form', function() {
    window.clearTimeout(autosaveTimer);
    persistAdminPageState($(this));
  });

  $(document).on('submit', 'form[action="options.php"]', function() {
    if (!validateSettingsForSave()) {
      return false;
    }
    var activePanel = $('.ecf-nav-item.is-active').data('panel') || 'tokens';
    try {
      window.sessionStorage.setItem(panelStorageKey, activePanel);
      var activeGeneralTab = $('[data-ecf-general-tab].is-active').data('ecf-general-tab') || 'system';
      window.sessionStorage.setItem(generalTabStorageKey, activeGeneralTab);
    } catch (err) {}
  });

  updateRootFontSizeLabels($('[data-ecf-root-font-source]').first().val() || $('[data-ecf-root-font-mirror]').first().val());
  $('[data-ecf-format-picker]').each(function() {
    resetFormatTooltip($(this));
  });
  renderTypePreview();
  renderShadowPreview();
  refreshBodySizeLinkedState();
  updateBaseBodyTextSizeWarning();
  syncTypographyFontCardSummaries();

  $(document).on('input change', '[name="ecf_framework_v50[root_font_size]"], [name^="ecf_framework_v50[typography][scale]"], [name^="ecf_framework_v50[typography][fonts]"]', function(){
    renderTypePreview();
    syncBodySizeWithTypeScale(false);
    renderRootFontImpact();
    updateBaseBodyTextSizeWarning();
    scheduleSettingsAutosave({ delay: 900 });
  });

  $(document).on('input change', '[data-ecf-font-family-preset-input][data-ecf-font-family-field="base_font_family"], [data-ecf-font-family-custom][data-ecf-font-family-field="base_font_family"]', function() {
    renderTypePreview();
    syncTypographyFontCardSummaries();
  });

  $(document).on('change', '[name="ecf_framework_v50[base_body_font_weight]"]', function() {
    renderTypePreview();
  });

  $(document).on('input change', '[data-ecf-body-size-field] [data-ecf-size-value-input], [data-ecf-body-size-field] [data-ecf-size-format-input], [data-ecf-body-size-field] [data-ecf-format-input]', function() {
    syncTypographyFontCardSummaries();
  });

  var isSyncingRootFontControls = false;

  $(document).on('change', '[data-ecf-root-font-source], [data-ecf-root-font-mirror]', function() {
    if (isSyncingRootFontControls) return;
    var value = $(this).val();
    isSyncingRootFontControls = true;
    syncRootFontSizeControls(value, this);

    var $canonical = $('[data-ecf-root-font-source]').first();
    if (!$canonical.is(this)) {
      $canonical.val(value);
      syncRootFontSizeControls(value, $canonical.get(0));
      renderTypePreview();
      renderSpacingPreview();
      renderRootFontImpact();
      isSyncingRootFontControls = false;
      return;
    }

    renderTypePreview();
    renderSpacingPreview();
    renderRootFontImpact();
    isSyncingRootFontControls = false;
  });

  $(document).on('click', '[data-ecf-format-trigger]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $picker = $(this).closest('[data-ecf-format-picker]');
    var $menu = $picker.find('[data-ecf-format-menu]');
    var willOpen = $menu.prop('hidden');
    $('[data-ecf-format-menu]').prop('hidden', true);
    $('[data-ecf-format-trigger]').attr('aria-expanded', 'false');
    $('[data-ecf-format-tooltip]').prop('hidden', true);
    if (willOpen) {
      $menu.prop('hidden', false);
      $(this).attr('aria-expanded', 'true');
      resetFormatTooltip($picker);
      setFormatTooltipVisibility($picker, true);
    }
  });

  $(document).on('mouseenter focus', '[data-ecf-format-option]', function() {
    var $option = $(this);
    $option.closest('[data-ecf-format-picker]').find('[data-ecf-format-tooltip]').text($option.data('tip') || '');
  });

  $(document).on('mouseleave', '.ecf-format-picker__options', function() {
    resetFormatTooltip($(this).closest('[data-ecf-format-picker]'));
  });

  $(document).on('click', '[data-ecf-format-option]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $option = $(this);
    var $picker = $option.closest('[data-ecf-format-picker]');
    var $formatInput = $picker.find('[data-ecf-format-input]').first();
    $picker.find('[data-ecf-format-option]').removeClass('is-active');
    $option.addClass('is-active');
    $formatInput.val($option.data('value')).trigger('change');
    $picker.find('[data-ecf-format-current]').text($option.data('label'));
    resetFormatTooltip($picker);
    $picker.find('[data-ecf-format-menu]').prop('hidden', true);
    $picker.find('[data-ecf-format-trigger]').attr('aria-expanded', 'false');
    setFormatTooltipVisibility($picker, false);
    updateBaseBodyTextSizeWarning();
  });

  $(document).on('input change', '[name="ecf_framework_v50[base_body_text_size_value]"], [name="ecf_framework_v50[base_body_text_size_format]"]', function() {
    refreshBodySizeLinkedState();
    updateBaseBodyTextSizeWarning();
  });

  $(document).on('input change', '[data-ecf-size-value-input], [data-ecf-size-format-input]', function() {
    validateRequiredPositiveSizeField($(this).closest('[data-ecf-inline-size-field]'));
  });

  $(document).on('click', function() {
    $('[data-ecf-format-menu]').prop('hidden', true);
    $('[data-ecf-format-trigger]').attr('aria-expanded', 'false');
    $('[data-ecf-format-tooltip]').prop('hidden', true);
  });

  $(document).on('input change', '[name^="ecf_framework_v50[shadows]"]', function(){
    renderShadowPreview();
  });

  $(document).on('click', '[data-ecf-step]', function(){
    var $preview = $('[data-ecf-type-scale-preview]');
    $preview.attr('data-active-step', $(this).data('ecf-step'));
    renderTypePreview();
  });

  $(document).on('keydown', '[data-ecf-step-row]', function(e){
    if (e.key !== 'Enter' && e.key !== ' ') return;
    e.preventDefault();
    $(this).trigger('click');
  });

  $(document).on('click', '[data-ecf-shadow-step]', function(event){
    if ($(event.target).is('[data-ecf-shadow-inline-value]')) {
      return;
    }
    var $preview = $('[data-ecf-shadow-preview]');
    $preview.attr('data-active-shadow', $(this).data('ecf-shadow-step'));
    renderShadowPreview();
  });

  $(document).on('focus click', '[data-ecf-shadow-inline-value]', function(event) {
    event.stopPropagation();
    var $input = $(this);
    var $row = $input.closest('[data-ecf-shadow-step]');
    $('[data-ecf-shadow-preview]').attr('data-active-shadow', $row.data('ecf-shadow-step'));
    $('[data-ecf-shadow-step]').removeClass('is-active');
    $row.addClass('is-active');
    $input.trigger('select');
  });

  $(document).on('input change', '[data-ecf-shadow-inline-value]', function(event) {
    event.stopPropagation();
    var $input = $(this);
    var shadowIndex = $input.data('ecf-shadow-index');
    var value = $input.val() || '';
    var $editorInput = $('[data-ecf-shadow-edit-row][data-ecf-shadow-row-index="' + shadowIndex + '"]').find('[data-ecf-shadow-value-input]').first();
    if ($editorInput.length) {
      $editorInput.val(value);
    }

    var $previewRow = $input.closest('[data-ecf-shadow-step]');
    var shadowSlug = String($previewRow.data('ecf-shadow-step') || '');
    var previewShadowValue = enhanceShadowPreviewValue(value);
    $previewRow.find('.ecf-shadow-row__mini').css('box-shadow', previewShadowValue);
    $('[data-ecf-shadow-preview]').attr('data-active-shadow', shadowSlug);
    $('[data-ecf-shadow-css]').text(value);
    $('[data-ecf-shadow-surface]').css('box-shadow', previewShadowValue);
  });

  $(document).on('blur', '[data-ecf-shadow-inline-value]', function() {
    var $input = $(this);
    var shadowIndex = $input.data('ecf-shadow-index');
    var $editorInput = $('[data-ecf-shadow-edit-row][data-ecf-shadow-row-index="' + shadowIndex + '"]').find('[data-ecf-shadow-value-input]').first();
    if ($editorInput.length) {
      $editorInput.trigger('input').trigger('change');
    } else {
      renderShadowPreview();
    }
  });

  $(document).on('focus', '[data-ecf-shadow-name-input], [data-ecf-shadow-value-input]', function() {
    var $row = $(this).closest('[data-ecf-shadow-edit-row]');
    if (!$row.length) return;
    var name = $.trim($row.find('[data-ecf-shadow-name-input]').val() || '');
    var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'shadow';
    $('[data-ecf-shadow-preview]').attr('data-active-shadow', slug);
    renderShadowPreview();
  });

  $(document).on('click', '[data-ecf-preview-view]', function(){
    var $preview = $('[data-ecf-type-scale-preview]');
    $preview.attr('data-preview-view', $(this).data('ecf-preview-view'));
    renderTypePreview();
  });

  // ── Variables Management ───────────────────────────────────────
  var varsLoaded = false;
  var classesLoaded = false;
  var varTabs = {
    ecf: 'all',
    foreign: 'all',
    'ecf-classes': 'all',
    'foreign-classes': 'all'
  };
  var varStore = {
    ecf: [],
    foreign: [],
    'ecf-classes': [],
    'foreign-classes': []
  };
  var varSearch = {
    ecf: '',
    foreign: '',
    'ecf-classes': '',
    'foreign-classes': ''
  };

  function typeLabel(type) {
    if (type === 'global-color-variable')  return i18n.type_color;
    if (type === 'global-size-variable')   return i18n.type_size;
    if (type === 'global-string-variable') return i18n.type_string;
    if (type === 'spacing') return i18n.type_spacing || '';
    if (type === 'typography') return i18n.type_typography || '';
    if (type === 'layout') return i18n.type_layout || '';
    if (type === 'radius') return i18n.type_radius || '';
    if (type === 'shadow') return i18n.type_shadow || '';
    if (type === 'class') return i18n.type_class || '';
    return type;
  }

  function typeTabKey(type) {
    if (type === 'global-color-variable') return 'color';
    if (type === 'global-size-variable') return 'size';
    if (type === 'global-string-variable') return 'string';
    if (type === 'spacing') return 'spacing';
    if (type === 'typography') return 'typography';
    if (type === 'layout') return 'layout';
    if (type === 'radius') return 'radius';
    if (type === 'shadow') return 'shadow';
    if (type === 'class') return 'class';
    return 'other';
  }

  function typeTabLabel(tabKey) {
    if (tabKey === 'color') return i18n.type_color;
    if (tabKey === 'size') return i18n.type_size;
    if (tabKey === 'string') return i18n.type_string;
    if (tabKey === 'spacing') return i18n.type_spacing || '';
    if (tabKey === 'typography') return i18n.type_typography || '';
    if (tabKey === 'layout') return i18n.type_layout || '';
    if (tabKey === 'radius') return i18n.type_radius || '';
    if (tabKey === 'shadow') return i18n.type_shadow || '';
    if (tabKey === 'class') return i18n.type_class || '';
    if (tabKey === 'other') return i18n.type_other || '';
    return i18n.type_all || '';
  }

  function getVariablePreviewKind(item) {
    var label = String(item.label || '').toLowerCase();
    var tabKey = typeTabKey(item.type);
    if (tabKey === 'spacing') return 'spacing';
    if (tabKey === 'typography' || label.indexOf('ecf-text-') === 0 || label.indexOf('text-') === 0) return 'typography';
    if (tabKey === 'radius' || label.indexOf('ecf-radius-') === 0 || label.indexOf('radius') !== -1) return 'radius';
    if (tabKey === 'color') return 'color';
    if (label.indexOf('space') !== -1 || label.indexOf('gap') !== -1 || label.indexOf('padding') !== -1 || label.indexOf('margin') !== -1) return 'spacing';
    return 'value';
  }

  function getVariableSizePreviewData(value) {
    var clampData = parseClampSizeValue(value);
    if (clampData) {
      return {
        minPx: clampData.minPx,
        maxPx: clampData.maxPx,
        raw: clampData.raw
      };
    }

    var token = parseSizeToken(value);
    if (!token) return null;

    var px = sizeTokenToPx(token, getRootBasePx());
    if (px == null) return null;

    return {
      minPx: round(px, 2),
      maxPx: round(px, 2),
      raw: String(value || '')
    };
  }

  function renderVariableSizeMetrics(minPx, maxPx, visualHtml, modifierClass) {
    var sizeRange = normalizeSizeRange(minPx, maxPx);
    if (!sizeRange) {
      return '';
    }

    minPx = sizeRange.minPx;
    maxPx = sizeRange.maxPx;
    var maxRef = Math.max(minPx, maxPx, 1);
    var minWidth = clamp((minPx / maxRef) * 100, 10, 100);
    var maxWidth = clamp((maxPx / maxRef) * 100, 10, 100);

    return '<span class="ecf-var-value ecf-var-value--size ' + (modifierClass || '') + '">'
      + '<span class="ecf-var-space-metric"><small>' + escapeHtml(i18n.preview_min || '') + '</small><strong>' + escapeHtml(formatNumber(minPx, 2)) + 'px</strong></span>'
      + '<span class="ecf-var-space-visual" aria-hidden="true">'
      + (visualHtml || (
          '<span class="ecf-var-space-bar"><span class="ecf-var-space-fill" style="width:' + minWidth + '%;height:6px;"></span></span>'
          + '<span class="ecf-var-space-bar"><span class="ecf-var-space-fill" style="width:' + maxWidth + '%;height:10px;"></span></span>'
        ))
      + '</span>'
      + '<span class="ecf-var-space-metric"><small>' + escapeHtml(i18n.preview_max || '') + '</small><strong>' + escapeHtml(formatNumber(maxPx, 2)) + 'px</strong></span>'
      + '</span>';
  }

  function renderVariableValue(item) {
    var kind = getVariablePreviewKind(item);
    var value = String(item.value || '');
    var preview = '';

    if (kind === 'color') {
      preview = '<span class="ecf-color-dot" style="background:' + escapeHtml(value) + '"></span>';
      return '<span class="ecf-var-value">' + preview + escapeHtml(value) + '</span>';
    }

    if (kind === 'spacing') {
      var tokenKey = String(item.label || '').toLowerCase();
      var spacingData = spacingPreviewMap[tokenKey] || null;
      var minPx = null;
      var maxPx = null;

      if (spacingData) {
        minPx = parseFloat(spacingData.minPx);
        maxPx = parseFloat(spacingData.maxPx);
      } else {
        var clampData = parseClampSizeValue(value);
        if (clampData) {
          minPx = clampData.minPx;
          maxPx = clampData.maxPx;
        }
      }

      if (minPx != null && maxPx != null) {
        return renderVariableSizeMetrics(minPx, maxPx, '', 'ecf-var-value--spacing');
      }
    }

    if (kind === 'radius' || kind === 'typography') {
      var tokenKey = String(item.label || '').toLowerCase();
      var previewMap = kind === 'radius' ? radiusPreviewMap : typePreviewMap;
      var mappedData = previewMap[tokenKey] || null;
      var sizeData = mappedData ? {
        minPx: parseFloat(mappedData.minPx),
        maxPx: parseFloat(mappedData.maxPx),
        raw: String(mappedData.cssValue || value)
      } : getVariableSizePreviewData(value);
      if (sizeData) {
        var visualHtml = '';
        if (kind === 'radius') {
          visualHtml =
            '<span class="ecf-var-radius-visual-row">'
            + '<span class="ecf-var-radius-box" style="border-radius:' + escapeHtml(formatNumber(sizeData.minPx, 2)) + 'px;"></span>'
            + '<span class="ecf-var-radius-box ecf-var-radius-box--max" style="border-radius:' + escapeHtml(formatNumber(sizeData.maxPx, 2)) + 'px;"></span>'
            + '</span>';
        } else {
          visualHtml =
            '<span class="ecf-var-type-visual-row">'
            + '<span class="ecf-var-type-sample" style="font-size:' + escapeHtml(formatNumber(sizeData.minPx, 2)) + 'px;">' + escapeHtml(i18n.search_preview_text_sample || '') + '</span>'
            + '<span class="ecf-var-type-sample ecf-var-type-sample--max" style="font-size:' + escapeHtml(formatNumber(sizeData.maxPx, 2)) + 'px;">' + escapeHtml(i18n.search_preview_text_sample || '') + '</span>'
            + '</span>';
        }

        return renderVariableSizeMetrics(sizeData.minPx, sizeData.maxPx, visualHtml, kind === 'radius' ? 'ecf-var-value--radius' : 'ecf-var-value--typography');
      }
    }

    return '<span class="ecf-var-value">' + escapeHtml(value) + '</span>';
  }

  function buildVarTable(group, items) {
    var nameColumnLabel = isClassGroup(group)
      ? (i18n.col_name || '')
      : (i18n.col_variable_name || i18n.col_name || '');
    var html = '<div class="ecf-var-table">';
    html += '<div class="ecf-var-head"><span></span><span>'+nameColumnLabel+'</span><span>'+i18n.col_type+'</span><span>'+i18n.col_value+'</span></div>';
    $.each(items, function(i, v) {
      var pendingAttr = v.pending ? ' data-ecf-prepared-variable="1"' : '';
      var checkAttr = v.pending ? ' checked disabled' : '';
      var usageHtml = '';
      if (isClassGroup(group) && v.in_use) {
        var usageLabel = escapeHtml(i18n.class_in_use || '');
        var usageTip = escapeHtml((i18n.class_usage_count || '').replace('%d', String(v.usage_count || 0)));
        usageHtml = '<span class="ecf-var-usage-badge" data-tip="' + usageTip + '">' + usageLabel + '</span>';
      }
      html += '<div class="ecf-var-row' + (v.pending ? ' is-prepared' : '') + '" data-id="'+v.id+'" data-group="'+group+'"' + pendingAttr + '>'
        + '<input type="checkbox" class="ecf-var-check" value="'+v.id+'"' + checkAttr + '>'
        + '<span class="ecf-var-label">'+originBadge(group)+'<span>'+v.label+'</span>' + usageHtml + '</span>'
        + '<span class="ecf-var-type">'+typeLabel(v.type)+'</span>'
        + renderVariableValue(v)
        + '</div>';
    });
    html += '</div>';
    return html;
  }

  function groupLabel(group) {
    if (group === 'ecf') return i18n.group_ecf_variables || '';
    if (group === 'foreign') return i18n.group_foreign_variables || '';
    if (group === 'ecf-classes') return i18n.group_ecf_classes || '';
    if (group === 'foreign-classes') return i18n.group_foreign_classes || '';
    return group;
  }

  function originBadge(group) {
    if (group === 'ecf' || group === 'ecf-classes') {
      return '<span class="ecf-origin-badge ecf-origin-badge--ecf">ECF</span>';
    }
    return '<span class="ecf-origin-badge ecf-origin-badge--ext">EXT</span>';
  }

  function isClassGroup(group) {
    return String(group).indexOf('classes') !== -1;
  }

  function canEditSearchItem(item) {
    return item.group === 'foreign' && !isClassGroup(item.group);
  }

  function getSearchPreviewKind(item) {
    var label = String(item.label || '').toLowerCase();
    if (item.type === 'global-color-variable') return 'color';
    if (label.indexOf('text') !== -1) return 'typography';
    if (label.indexOf('space') !== -1 || label.indexOf('gap') !== -1 || label.indexOf('padding') !== -1 || label.indexOf('margin') !== -1) return 'spacing';
    if (label.indexOf('radius') !== -1 || label.indexOf('round') !== -1) return 'radius';
    if (label.indexOf('shadow') !== -1) return 'shadow';
    if (isClassGroup(item.group)) return 'class';
    return 'value';
  }

  function renderSearchPreview(item) {
    var kind = getSearchPreviewKind(item);
    if (kind === 'color') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--color"><span class="ecf-color-dot" style="background:' + escapeHtml(item.value) + '"></span><span>' + escapeHtml(String(item.value || '')) + '</span></span>';
    }
    if (kind === 'typography') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--type">' + escapeHtml(i18n.search_preview_text || '') + '</span>';
    }
    if (kind === 'spacing') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--spacing"><span></span></span>';
    }
    if (kind === 'radius') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--radius"></span>';
    }
    if (kind === 'shadow') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--shadow"></span>';
    }
    if (kind === 'class') {
      return '<span class="ecf-global-search__preview ecf-global-search__preview--class">' + escapeHtml(i18n.search_preview_class || '') + '</span>';
    }
    return '<span class="ecf-global-search__preview ecf-global-search__preview--value">' + escapeHtml(String(item.value || '')) + '</span>';
  }

  function renderVarList(group, items) {
    varStore[group] = items.slice();
    var $list = $('#ecf-varlist-' + group);
    var $card = $list.closest('.ecf-card');
    var $actions = $card.find('.ecf-vargroup-actions').first();
    $('#ecf-badge-' + group).text(items.length);
    if (!items.length) {
      $list.html('<p style="color:#9ca3af;font-size:13px;">'+i18n.none+'</p>');
      updateSelectAllState(group);
      return;
    }

    var buckets = {
      all: items.slice()
    };

    $.each(items, function(_, item) {
      var key = typeTabKey(item.type);
      if (!buckets[key]) buckets[key] = [];
      buckets[key].push(item);
    });

    var order = ['all', 'color', 'size', 'string', 'spacing', 'typography', 'layout', 'radius', 'shadow', 'class', 'other'];
    if (!varTabs[group] || !buckets[varTabs[group]] || !buckets[varTabs[group]].length) {
      varTabs[group] = 'all';
    }

    var tabs = '<div class="ecf-var-tabs" data-group="' + group + '">';
    $.each(order, function(_, key) {
      if (!buckets[key] || !buckets[key].length) return;
      tabs += '<button type="button" class="ecf-var-tab' + (key === varTabs[group] ? ' is-active' : '') + '" data-group="' + group + '" data-var-tab="' + key + '">'
        + typeTabLabel(key)
        + '<span class="ecf-var-tab__count">' + buckets[key].length + '</span>'
        + '</button>';
    });
    tabs += '</div>';

    $list.html(tabs + '<div class="ecf-var-toolbar"></div>' + buildVarTable(group, buckets[varTabs[group]] || items));
    if ($actions.length) {
      $list.find('.ecf-var-toolbar').append($actions);
    }
    applyVarSearch(group);
  }

  function applyVarSearch(group) {
    var query = String(varSearch[group] || '').trim().toLowerCase();
    var $list = $('#ecf-varlist-' + group);
    var $rows = $list.find('.ecf-var-row');

    if (!$rows.length) {
      updateSelectAllState(group);
      return;
    }

    var visibleCount = 0;
    $rows.each(function() {
      var $row = $(this);
      var matches = !query || $row.text().toLowerCase().indexOf(query) !== -1;
      $row.toggle(matches);
      if (matches) visibleCount += 1;
    });

    $list.find('.ecf-var-empty-search').remove();
    if (!visibleCount) {
      $list.append('<p class="ecf-var-empty-search">'+(i18n.none || '')+'</p>');
    }

    updateSelectAllState(group);
  }

  function getVisibleChecks(group) {
    return $('#ecf-varlist-' + group).find('.ecf-var-row:visible .ecf-var-check:not(:disabled)');
  }

  function updateSelectAllState(group) {
    var $btn = $('.ecf-select-all[data-group="' + group + '"]');
    var $deleteBtn = $('.ecf-delete-selected[data-group="' + group + '"]');
    if (!$btn.length) return;

    var $checks = getVisibleChecks(group);
    if (!$checks.length) {
      $btn.prop('disabled', true).removeClass('is-active').find('span:last-child').text(i18n.select_all);
      $deleteBtn.prop('disabled', true).removeClass('is-active');
      return;
    }

    var checkedCount = $checks.filter(':checked').length;
    var allChecked = $checks.length === $checks.filter(':checked').length;
    $btn
      .prop('disabled', false)
      .toggleClass('is-active', allChecked)
      .find('span:last-child')
      .text(allChecked ? i18n.deselect_all : i18n.select_all);

    $deleteBtn
      .prop('disabled', checkedCount === 0)
      .toggleClass('is-active', checkedCount > 0);
  }

  function updateVariableSummary(ecfItems, foreignItems) {
    var ecfCount = (ecfItems || []).length;
    var foreignCount = (foreignItems || []).length;
    var total = ecfCount + foreignCount;
    $('#ecf-total-ecf-variables').text(ecfCount);
    $('#ecf-total-foreign-variables').text(foreignCount);
    $('#ecf-total-variables, #ecf-total-variables-inline').text(total);
    updateLayrixVariableCount();
  }

  function updateClassSummary(ecfItems, foreignItems) {
    var total = (ecfItems || []).length + (foreignItems || []).length;
    $('#ecf-total-global-classes').text(total);
    $('.ecf-total-global-classes-compact').text(total);
    $('[data-ecf-starter-classes]').attr('data-ecf-class-current', total);
    $('[data-ecf-starter-current]').text(total);
    var labels = []
      .concat((ecfItems || []).map(function(item) { return String(item.label || '').toLowerCase(); }))
      .concat((foreignItems || []).map(function(item) { return String(item.label || '').toLowerCase(); }));
    $('[data-ecf-starter-classes]').attr('data-ecf-existing-labels', JSON.stringify(Array.from(new Set(labels))));
    applyClassUsageStatus(total);
    updateStarterClassesState();
  }

  function getClassUsageStatus(total, limit) {
    if (!limit || limit <= 0) return 'neutral';
    if (total >= 90) return 'danger';
    if (total >= 70) return 'warning';
    return 'success';
  }

  function applyClassUsageStatus(total) {
    $('[data-ecf-class-usage-card]').each(function() {
      var $card = $(this);
      var limit = parseInt($card.data('ecf-class-limit'), 10) || parseInt($('#ecf-limit-global-classes').first().text(), 10) || 100;
      var status = getClassUsageStatus(total, limit);
      $card
        .removeClass('ecf-class-limit-card--neutral ecf-class-limit-card--success ecf-class-limit-card--warning ecf-class-limit-card--danger')
        .addClass('ecf-class-limit-card--' + status);
    });
  }

  function getStarterExistingLabels() {
    var raw = $('[data-ecf-starter-classes]').attr('data-ecf-existing-labels') || '[]';
    try {
      return JSON.parse(raw).map(function(label) { return String(label || '').toLowerCase(); });
    } catch (err) {
      return [];
    }
  }

  function getSelectedStarterNames() {
    var names = [];
    $('.ecf-starter-class-toggle:checked').each(function() {
      var name = String($(this).closest('[data-class-name]').data('class-name') || '').toLowerCase();
      if (name) names.push(name);
    });
    $('.ecf-starter-custom-row').each(function() {
      var $row = $(this);
      if (!$row.find('.ecf-custom-starter-enabled').is(':checked')) return;
      var name = String($row.find('.ecf-custom-starter-name').val() || '').trim().toLowerCase();
      if (!name) return;
      if (name.indexOf('ecf-') !== 0) {
        name = 'ecf-' + name.replace(/^-+/, '');
      }
      names.push(name);
    });
    return Array.from(new Set(names));
  }

  function getSelectedUtilityNames() {
    var names = [];
    $('.ecf-utility-class-toggle:checked').each(function() {
      var name = String($(this).closest('[data-class-name]').data('class-name') || '').toLowerCase();
      if (name) names.push(name);
    });
    return Array.from(new Set(names));
  }

  function getBoxedHelperName() {
    var value = $.trim($('[name="ecf_framework_v50[elementor_boxed_width]"]').val() || '');
    return value ? 'ecf-container-boxed' : '';
  }

  function getSelectedSyncPayloadNames() {
    var names = getSelectedStarterNames().concat(getSelectedUtilityNames());
    var helperName = getBoxedHelperName();
    if (helperName) names.push(helperName);
    return Array.from(new Set(names));
  }

  function renderActiveClassGroup(type, names) {
    var $group = $('[data-ecf-active-class-group="' + type + '"]');
    var $list = $('[data-ecf-active-class-list="' + type + '"]');
    if (!$group.length || !$list.length) return;
    $group.find('.ecf-badge').first().text(names.length);

    if (!names.length) {
      if (type === 'existing-foreign') {
        var expectedForeignCount = parseInt($('[data-ecf-existing-foreign-summary-count]').first().text(), 10) || 0;
        if (expectedForeignCount > 0 && !classesLoaded) {
          $group.prop('hidden', false);
          $group.prop('open', true).attr('open', 'open');
          $list.html('<p class="ecf-active-class-empty">' + escapeHtml(i18n.loading_elementor_classes || i18n.loading || '') + '</p>');
          return;
        }
      }
      $group.prop('hidden', true);
      $list.empty();
      return;
    }

    if (type === 'existing-foreign') {
      var legacyNames = [];
      var manualNames = [];

      $.each(names, function(_, name) {
        var normalized = String(name || '').toLowerCase();
        if (normalized.indexOf('ecf-') === 0 || normalized.indexOf('cf-') === 0) {
          legacyNames.push(normalized);
        } else {
          manualNames.push(normalized);
        }
      });

      var html = '';
      if (legacyNames.length) {
        html += '<div class="ecf-active-class-subgroup">'
          + '<h4 class="ecf-active-class-subgroup__title">' + escapeHtml(i18n.existing_foreign_legacy_heading || '') + '</h4>'
          + '<p class="ecf-active-class-subgroup__hint">' + escapeHtml(i18n.existing_foreign_legacy_hint || '') + '</p>'
          + '<ul class="ecf-active-class-plain-list">'
          + legacyNames.map(function(name) {
              return '<li class="ecf-active-class-item ecf-active-class-item--legacy">' + escapeHtml(name) + '</li>';
            }).join('')
          + '</ul>'
          + '</div>';
      }
      if (manualNames.length) {
        html += '<div class="ecf-active-class-subgroup">'
          + '<h4 class="ecf-active-class-subgroup__title">' + escapeHtml(i18n.existing_foreign_manual_heading || '') + '</h4>'
          + '<p class="ecf-active-class-subgroup__hint">' + escapeHtml(i18n.existing_foreign_manual_hint || '') + '</p>'
          + '<ul class="ecf-active-class-plain-list">'
          + manualNames.map(function(name) {
              return '<li class="ecf-active-class-item">' + escapeHtml(name) + '</li>';
            }).join('')
          + '</ul>'
          + '</div>';
      }

      $group.prop('hidden', false);
      $group.prop('open', true).attr('open', 'open');
      $list.html(html);
      return;
    }

    var itemClass = type === 'helper' ? 'ecf-active-class-item ecf-active-class-item--helper' : 'ecf-active-class-item';
    $group.prop('hidden', false);
    $group.prop('open', true).attr('open', 'open');
    $list.html(names.map(function(name) {
      return '<li class="' + itemClass + '">' + escapeHtml(name) + '</li>';
    }).join(''));
  }

  function updateActiveClassTierVisibility(activeTier) {
    var tier = activeTier || 'all';
    var focusExistingForeign = tier === 'existing-foreign';
    $('[data-ecf-active-class-summary__grid], .ecf-active-class-summary__grid').prop('hidden', focusExistingForeign);
    $('[data-ecf-active-class-hint]').prop('hidden', focusExistingForeign);

    $('[data-ecf-active-class-group]').each(function() {
      var $group = $(this);
      var groupTier = String($group.data('ecf-active-class-group') || '');
      var $list = $group.find('[data-ecf-active-class-list]').first();
      var hasItems = $group.find('.ecf-active-class-item').length > 0
        || $list.find('.ecf-active-class-empty').length > 0
        || $.trim($list.text()).length > 0;
      var show = tier === 'all' ? hasItems : (groupTier === tier && hasItems);
      $group.prop('hidden', !show);
      if (show && tier !== 'all') {
        $group.prop('open', true).attr('open', 'open');
      }
    });
  }

  function updateActiveClassSummary(starterNames, utilityNames, syncPayloadNames) {
    var basicNames = [];
    var extraNames = [];
    var customNames = [];
    var helperNames = [];
    var existingForeignNames = [];
    var knownStarter = {};

    $('.ecf-starter-class-item').each(function() {
      var $item = $(this);
      var name = String($item.data('class-name') || '').toLowerCase();
      var tier = String($item.data('tier') || '');
      if (!name) return;
      knownStarter[name] = true;
      if (starterNames.indexOf(name) === -1) return;
      if (tier === 'basic') {
        basicNames.push(name);
      } else {
        extraNames.push(name);
      }
    });

    $.each(starterNames, function(_, name) {
      if (knownStarter[name]) return;
      customNames.push(name);
    });

    $.each(syncPayloadNames, function(_, name) {
      if (starterNames.indexOf(name) !== -1 || utilityNames.indexOf(name) !== -1) return;
      helperNames.push(name);
    });

    $.each(getStarterExistingLabels(), function(_, name) {
      if (!name) return;
      if (syncPayloadNames.indexOf(name) !== -1) return;
      existingForeignNames.push(name);
    });

    $('[data-ecf-active-basic-count]').text(basicNames.length);
    $('[data-ecf-active-extras-count]').text(extraNames.length);
    $('[data-ecf-active-utility-count]').text(utilityNames.length);
    $('[data-ecf-active-custom-count]').text(customNames.length);
    $('[data-ecf-active-helper-count]').text(helperNames.length);
    $('[data-ecf-active-existing-foreign-count]').text(existingForeignNames.length);
    $('[data-ecf-existing-foreign-summary-count]').text(existingForeignNames.length);
    $('[data-ecf-active-total-count]').text(syncPayloadNames.length);
    var summaryCounts = {
      basic: basicNames.length,
      extras: extraNames.length,
      utility: utilityNames.length,
      custom: customNames.length,
      helper: helperNames.length,
      total: syncPayloadNames.length,
      'existing-foreign': existingForeignNames.length
    };
    $.each(summaryCounts, function(key, count) {
      $('[data-ecf-active-summary-item="' + key + '"]').prop('hidden', key !== 'total' && !count);
    });
    var parts = [];
    if (basicNames.length) parts.push(basicNames.length + ' basic');
    if (extraNames.length) parts.push(extraNames.length + ' extras');
    if (utilityNames.length) parts.push(utilityNames.length + ' utility');
    if (customNames.length) parts.push(customNames.length + ' custom');
    if (helperNames.length) parts.push(helperNames.length + ' helper');
    $('[data-ecf-class-breakdown]').text(syncPayloadNames.length + ' selected = ' + parts.join(' + '));

    $('[data-ecf-active-class-hint]').text(
      helperNames.length
        ? (i18n.active_class_hint_helper || '')
        : (i18n.active_class_hint_default || '')
    );

    renderActiveClassGroup('basic', basicNames);
    renderActiveClassGroup('extras', extraNames);
    renderActiveClassGroup('utility', utilityNames);
    renderActiveClassGroup('custom', customNames);
    renderActiveClassGroup('helper', helperNames);
    renderActiveClassGroup('existing-foreign', existingForeignNames);
    updateClassTierTabVisibility({
      basic: basicNames.length,
      extras: extraNames.length,
      utility: utilityNames.length,
      custom: customNames.length,
      'existing-foreign': existingForeignNames.length
    });
    updateActiveClassTierVisibility($('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all');
  }

  function updateClassTierTabVisibility(counts) {
    var visibility = counts || {};
    var currentTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    $.each(visibility, function(tier, count) {
      $('[data-ecf-class-tier="' + tier + '"]').prop('hidden', !count);
    });
    if (currentTier !== 'all' && $('[data-ecf-class-tier="' + currentTier + '"]').prop('hidden')) {
      applyClassTierFilter('all');
    }
  }

  function updateStarterClassesState() {
    var $root = $('[data-ecf-starter-classes]');
    if (!$root.length) return;

    var currentTotal = parseInt($root.data('ecf-class-current'), 10) || 0;
    var limit = parseInt($root.data('ecf-class-limit'), 10) || 100;
    var existing = getStarterExistingLabels();
    var starterNames = getSelectedStarterNames();
    var utilityNames = getSelectedUtilityNames();
    var selectedNames = getSelectedSyncPayloadNames();
    var pendingNew = selectedNames.filter(function(name) { return existing.indexOf(name) === -1; }).length;
    var projected = currentTotal + pendingNew;
    var basicCount = $('.ecf-starter-class-item[data-tier="basic"]').length;
    var advancedCount = $('.ecf-starter-class-item[data-tier="advanced"]').length;
    var activeBasicCount = $('.ecf-starter-class-item[data-tier="basic"]').find('.ecf-starter-class-toggle:checked').length;
    var activeAdvancedCount = $('.ecf-starter-class-item[data-tier="advanced"]').find('.ecf-starter-class-toggle:checked').length;
    var customCount = $('.ecf-starter-custom-row').filter(function() {
      return $.trim($(this).find('.ecf-custom-starter-name').val() || '') !== '';
    }).length;
    var activeCustomCount = $('.ecf-starter-custom-row').filter(function() {
      return $(this).find('.ecf-custom-starter-enabled').is(':checked') && $.trim($(this).find('.ecf-custom-starter-name').val() || '') !== '';
    }).length;
    var utilityCount = $('.ecf-utility-class-item').length;
    var activeUtilityCount = $('.ecf-utility-class-item').find('.ecf-utility-class-toggle:checked').length;
    var status = getClassUsageStatus(projected, limit);
    var percent = limit > 0 ? Math.round((projected / limit) * 100) : 0;
    percent = Math.max(0, Math.min(100, percent));

    $('[data-ecf-starter-selected]').text(selectedNames.length);
    $('[data-ecf-starter-projected]').text(projected);
    $('[data-ecf-starter-projected-inline]').text(projected);
    $('[data-ecf-starter-basic-count]').text(activeBasicCount + '/' + basicCount);
    $('[data-ecf-starter-extras-count]').text(activeAdvancedCount + '/' + advancedCount);
    $('[data-ecf-starter-custom-count]').text(activeCustomCount + '/' + customCount);
    $('[data-ecf-utility-summary-count]').text(activeUtilityCount + '/' + utilityCount);
    updateUtilityTabCounts();
    $('[data-ecf-starter-percent]').text(percent);
    $('[data-ecf-starter-progress]').css('width', percent + '%');
    $('[data-ecf-starter-status]')
      .removeClass('ecf-class-limit-card--neutral ecf-class-limit-card--success ecf-class-limit-card--warning ecf-class-limit-card--danger')
      .addClass('ecf-class-limit-card--' + status);

    updateActiveClassSummary(starterNames, utilityNames, selectedNames);
    updateClassLibrarySelectAllState();
  }

  function applyClassTierFilter(tier) {
    var activeTier = tier || 'all';
    $('[data-ecf-class-tier]').removeClass('is-active')
      .filter('[data-ecf-class-tier="' + activeTier + '"]').addClass('is-active');

    $('[data-ecf-starter-classes]').attr('data-ecf-active-class-tier', activeTier);

    if (activeTier === 'extras') {
      showClassLibrarySection('starter');
      applyStarterClassFilter('all');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }

    if (activeTier === 'utility') {
      showClassLibrarySection('utility');
      applyUtilityClassFilter('all');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }

    if (activeTier === 'existing-foreign') {
      var $existingForeignGroup = $('[data-ecf-active-class-group="existing-foreign"]');
      var $existingForeignList = $('[data-ecf-active-class-list="existing-foreign"]');
      var expectedExistingForeign = parseInt($('[data-ecf-existing-foreign-summary-count]').first().text(), 10) || 0;
      loadClasses();
      showClassLibrarySection('active');
      $existingForeignGroup.prop('hidden', false).prop('open', true).attr('open', 'open');
      if (expectedExistingForeign > 0 && !$.trim($existingForeignList.text()).length) {
        $existingForeignList.html('<p class="ecf-active-class-empty">' + escapeHtml(i18n.loading_elementor_classes || i18n.loading || '') + '</p>');
      }
      updateActiveClassTierVisibility('existing-foreign');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }

    if (activeTier === 'basic') {
      showClassLibrarySection('starter');
    }

    if (activeTier === 'custom') {
      showClassLibrarySection('starter');
      applyStarterClassFilter('custom');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }
    if (activeTier === 'all') {
      showClassLibrarySection('active');
      updateActiveClassTierVisibility('all');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }
    showClassLibrarySection('starter');
    refreshStarterClassVisibility();
    updateClassLibraryContext();
    updateClassLibrarySelectAllState();
  }

  function getVisibleClassLibraryChecks() {
    var activeTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    var activeLibrary = getClassLibrarySectionForTier(activeTier);
    var $section = $('[data-ecf-library-section="' + activeLibrary + '"]');
    if (!$section.length) return $();

    if (activeLibrary === 'active') {
      return $();
    }

    if (activeLibrary === 'utility') {
      return $section.find('.ecf-utility-class-item:visible .ecf-utility-class-toggle');
    }

    var $starterChecks = $section.find('.ecf-starter-class-item:visible .ecf-starter-class-toggle');
    var activeStarterCategory = $('[data-ecf-starter-select]').val() || 'all';
    var $customChecks = $();

    if (activeStarterCategory === 'all' || activeStarterCategory === 'custom') {
      $customChecks = $section.find('.ecf-custom-starter-enabled');
    }

    return $starterChecks.add($customChecks);
  }

  function updateClassLibrarySelectAllState() {
    var $button = $('[data-ecf-class-select-all]');
    if (!$button.length) return;

    var $checks = getVisibleClassLibraryChecks();
    var allChecked = $checks.length > 0 && $checks.filter(':checked').length === $checks.length;
    var hasSelection = $checks.filter(':checked').length > 0;

    $button
      .prop('disabled', !$checks.length)
      .toggleClass('is-active', allChecked)
      .find('[data-ecf-class-select-all-label]')
      .text(allChecked ? (i18n.deselect_all || '') : (i18n.select_all || ''));

    $('[data-ecf-class-select-all-icon]').toggleClass('is-active', allChecked || hasSelection);
  }

  function normalizeBemSegment(value) {
    return String(value || '')
      .toLowerCase()
      .trim()
      .replace(/^ecf-/, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .replace(/-{2,}/g, '-');
  }

  function parseBemCsv(value) {
    return String(value || '')
      .split(',')
      .map(function(part) { return normalizeBemSegment(part); })
      .filter(Boolean);
  }

  function getBemGeneratorRoot() {
    return $('[data-ecf-bem-generator]').first();
  }

  function getBemGeneratorPresets() {
    var $root = getBemGeneratorRoot();
    if (!$root.length) return {};
    var raw = $root.attr('data-ecf-bem-presets') || '{}';
    try {
      return JSON.parse(raw);
    } catch (err) {
      return {};
    }
  }

  function getBemGeneratorPresetKey() {
    return String(getBemGeneratorRoot().find('[data-ecf-bem-preset]').val() || 'header');
  }

  function getBemGeneratorPreset() {
    var presets = getBemGeneratorPresets();
    return presets[getBemGeneratorPresetKey()] || null;
  }

  function isBemCustomPreset() {
    return getBemGeneratorPresetKey() === 'custom';
  }

  function updateBemBlockFieldState() {
    var $root = getBemGeneratorRoot();
    if (!$root.length) return;

    var $field = $root.find('[data-ecf-bem-block-field]');
    var $input = $root.find('[data-ecf-bem-block]');
    var customMode = isBemCustomPreset();

    $field.prop('hidden', !customMode);
    $field.toggleClass('is-hidden', !customMode);
    $input.prop('disabled', !customMode);

    if (!customMode) {
      $input.val('');
    }
  }

  function renderBemGeneratorOptions(type, values) {
    return (values || []).map(function(value) {
      var label = String(value || '');
      return '<label class="ecf-bem-generator__option">'
        + '<input type="checkbox" data-ecf-bem-' + type + '-check value="' + escapeHtml(label) + '">'
        + '<span>' + escapeHtml(label) + '</span>'
        + '</label>';
    }).join('');
  }

  function refreshBemGeneratorOptions() {
    var $root = getBemGeneratorRoot();
    var preset = getBemGeneratorPreset();
    if (!$root.length || !preset) return;
    $root.find('[data-ecf-bem-elements]').html(renderBemGeneratorOptions('element', preset.elements));
    $root.find('[data-ecf-bem-modifiers]').html(renderBemGeneratorOptions('modifier', preset.modifiers));
    $root.find('[data-ecf-bem-help]').text(String(preset.help || ''));
    updateBemBlockFieldState();
  }

  function getBemGeneratedClasses() {
    var $root = getBemGeneratorRoot();
    var presetKey = getBemGeneratorPresetKey();
    var preset = getBemGeneratorPreset();
    if (!$root.length || !preset) return [];

    var baseName = normalizeBemSegment($root.find('[data-ecf-bem-block]').val());
    if (!baseName) {
      baseName = presetKey === 'custom' ? '' : normalizeBemSegment(presetKey);
    }
    if (!baseName) return [];

    var block = 'ecf-' + baseName;
    var generated = [block];
    var elements = []
      .concat($root.find('[data-ecf-bem-element-check]:checked').map(function() { return $(this).val(); }).get())
      .concat(parseBemCsv($root.find('[data-ecf-bem-extra-elements]').val()));
    var modifiers = []
      .concat($root.find('[data-ecf-bem-modifier-check]:checked').map(function() { return $(this).val(); }).get())
      .concat(parseBemCsv($root.find('[data-ecf-bem-extra-modifiers]').val()));

    $.each(Array.from(new Set(elements.map(normalizeBemSegment).filter(Boolean))), function(_, element) {
      generated.push(block + '__' + element);
    });
    $.each(Array.from(new Set(modifiers.map(normalizeBemSegment).filter(Boolean))), function(_, modifier) {
      generated.push(block + '--' + modifier);
    });

    return Array.from(new Set(generated));
  }

  function renderBemGeneratorPreview() {
    var $root = getBemGeneratorRoot();
    if (!$root.length) return;
    var classes = getBemGeneratedClasses();
    var emptyText = isBemCustomPreset()
      ? (i18n.bem_preview_empty_custom || '')
      : (i18n.bem_preview_empty_preset || '');
    var html = classes.length
      ? classes.map(function(className) { return '<code>' + escapeHtml(className) + '</code>'; }).join('')
      : '<span class="ecf-muted-copy">' + escapeHtml(emptyText) + '</span>';
    $root.find('[data-ecf-bem-preview]').html(html);
  }

  function appendCustomStarterRow(config) {
    var $rows = $('[data-ecf-starter-custom-rows]');
    var template = $('#ecf-starter-custom-row-template').html();
    if (!$rows.length || !template) return null;
    var index = $rows.find('.ecf-starter-custom-row').length;
    template = template
      .replace('__ENABLED__', 'ecf_framework_v50[starter_classes][custom][' + index + '][enabled]')
      .replace('__NAME__', 'ecf_framework_v50[starter_classes][custom][' + index + '][name]')
      .replace('__CATEGORY__', 'ecf_framework_v50[starter_classes][custom][' + index + '][category]');
    var $row = $(template);
    $row.find('.ecf-custom-starter-enabled').prop('checked', config.enabled !== false);
    $row.find('.ecf-custom-starter-name').val(config.name || '');
    $row.find('.ecf-custom-starter-category').val(config.category || 'custom');
    $rows.append($row);
    return $row;
  }

  function pulseCustomStarterRow($row) {
    if (!$row || !$row.length) return;
    $row.addClass('is-new');
    window.setTimeout(function() {
      $row.removeClass('is-new');
    }, 1800);
  }

  function addBemGeneratedClasses() {
    var $root = getBemGeneratorRoot();
    var preset = getBemGeneratorPreset();
    if (!$root.length || !preset) return;

    var existing = {};
    $('[data-class-name]').each(function() {
      var name = String($(this).data('class-name') || '').toLowerCase();
      if (name) existing[name] = true;
    });
    $('.ecf-custom-starter-name').each(function() {
      var name = String($(this).val() || '').trim().toLowerCase();
      if (!name) return;
      if (name.indexOf('ecf-') !== 0) name = 'ecf-' + name.replace(/^-+/, '');
      existing[name] = true;
    });

    var category = String((preset.category || 'custom'));
    var classes = getBemGeneratedClasses();
    var added = 0;
    var addedRows = [];

    $.each(classes, function(_, className) {
      var normalized = String(className || '').toLowerCase();
      if (!normalized || existing[normalized]) return;
      var $row = appendCustomStarterRow({ name: normalized, category: category, enabled: true });
      if ($row && $row.length) {
        $row.addClass('is-new');
        addedRows.push($row);
      }
      existing[normalized] = true;
      added += 1;
    });

    $root.find('[data-ecf-bem-feedback]').text(
      added
        ? formatTemplate(added === 1 ? (i18n.bem_feedback_added_one || '') : (i18n.bem_feedback_added_many || ''), added)
        : (i18n.bem_feedback_exists || '')
    );

    updateStarterClassesState();

    if (!added) {
      refreshStarterClassVisibility();
      return;
    }

    scheduleSettingsAutosave({ delay: 250 });

    $('[data-ecf-library-section="starter"] [data-ecf-class-search]').val('');
    applyClassTierFilter('custom');
    applyStarterClassFilter('custom');
    updateClassLibraryContext();
    updateClassLibrarySelectAllState();

    if (addedRows[0] && addedRows[0].length && addedRows[0].get(0).scrollIntoView) {
      addedRows[0].get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    window.setTimeout(function() {
      $.each(addedRows, function(_, $row) {
        $row.removeClass('is-new');
      });
    }, 2200);
  }

  function getClassLibrarySectionForTier(activeTier) {
    if (activeTier === 'utility') return 'utility';
    if (activeTier === 'all' || activeTier === 'existing-foreign') return 'active';
    return 'starter';
  }

  function showClassLibrarySection(section) {
    var activeSection = section || 'active';
    $('[data-ecf-library-section]').attr('hidden', true)
      .filter('[data-ecf-library-section="' + activeSection + '"]').removeAttr('hidden');
  }

  function updateClassLibraryContext() {
    var activeTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    var activeLibrary = getClassLibrarySectionForTier(activeTier);
    var showStarterFilter = activeLibrary === 'starter' && activeTier !== 'custom';
    var showBemGenerator = activeLibrary === 'starter' && activeTier === 'custom';
    var showActiveSummary = activeTier === 'all';
    var tierLabel = $.trim($('[data-ecf-class-tier="' + activeTier + '"]').clone().children().remove().end().text());
    var starterCopy = $.trim($('.ecf-class-library-intro').first().text() || '');

    showClassLibrarySection(activeLibrary);
    $('[data-ecf-active-class-summary__grid]').prop('hidden', !showActiveSummary);
    $('[data-ecf-active-class-hint]').prop('hidden', !showActiveSummary);
    $('[data-ecf-category-help="utility"]').prop('hidden', activeLibrary !== 'utility');
    $('[data-ecf-starter-filterbar]').prop('hidden', !showStarterFilter);
    $('[data-ecf-bem-generator]').prop('hidden', !showBemGenerator);

    if (activeLibrary === 'starter') {
      $('[data-ecf-class-workspace-title]').text(tierLabel || '');
      $('[data-ecf-class-workspace-copy]').text(starterCopy);
    }
  }

  function getActiveClassSearchQuery() {
    var activeTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    var activeLibrary = getClassLibrarySectionForTier(activeTier);
    return $.trim($('[data-ecf-library-section="' + activeLibrary + '"] [data-ecf-class-search]').val() || '').toLowerCase();
  }

  function matchesClassSearch(parts, query) {
    if (!query) return true;
    return (parts || []).some(function(part) {
      return String(part || '').toLowerCase().indexOf(query) !== -1;
    });
  }

  function sortVisibleStarterItems() {
    var $list = $('.ecf-library-section[data-ecf-library-section="starter"] .ecf-starter-class-list').first();
    if (!$list.length) return;

    var $items = $list.children('[data-ecf-starter-item]');
    if (!$items.length) return;

    $items.sort(function(left, right) {
      var $left = $(left);
      var $right = $(right);
      var leftChecked = $left.find('.ecf-starter-class-toggle').is(':checked') ? 1 : 0;
      var rightChecked = $right.find('.ecf-starter-class-toggle').is(':checked') ? 1 : 0;
      if (leftChecked !== rightChecked) {
        return rightChecked - leftChecked;
      }

      var leftName = String($left.data('class-name') || '').toLowerCase();
      var rightName = String($right.data('class-name') || '').toLowerCase();
      return leftName.localeCompare(rightName);
    });

    $list.append($items);
  }

  function sortVisibleUtilityItems() {
    var $list = $('.ecf-library-section[data-ecf-library-section="utility"] .ecf-starter-class-list').first();
    if (!$list.length) return;

    var $items = $list.children('[data-ecf-utility-item]');
    if (!$items.length) return;

    $items.sort(function(left, right) {
      var $left = $(left);
      var $right = $(right);
      var leftChecked = $left.find('.ecf-utility-class-toggle').is(':checked') ? 1 : 0;
      var rightChecked = $right.find('.ecf-utility-class-toggle').is(':checked') ? 1 : 0;
      if (leftChecked !== rightChecked) {
        return rightChecked - leftChecked;
      }

      var leftName = String($left.data('class-name') || '').toLowerCase();
      var rightName = String($right.data('class-name') || '').toLowerCase();
      return leftName.localeCompare(rightName);
    });

    $list.append($items);
  }

  function updateUtilityTabCounts() {
    var groups = {
      all: { active: 0, total: 0 },
      typography: { active: 0, total: 0 },
      text: { active: 0, total: 0 },
      layout: { active: 0, total: 0 },
      shadows: { active: 0, total: 0 },
      accessibility: { active: 0, total: 0 }
    };

    $('.ecf-utility-class-item').each(function() {
      var $item = $(this);
      var category = String($item.data('category') || 'all');
      var isChecked = $item.find('.ecf-utility-class-toggle').is(':checked');

      if (!groups[category]) {
        groups[category] = { active: 0, total: 0 };
      }

      groups[category].total += 1;
      groups.all.total += 1;

      if (isChecked) {
        groups[category].active += 1;
        groups.all.active += 1;
      }
    });

    Object.keys(groups).forEach(function(groupKey) {
      $('[data-ecf-utility-tab-count="' + groupKey + '"]').text(groups[groupKey].active + '/' + groups[groupKey].total);
    });
  }

  function refreshStarterClassVisibility() {
    var activeCategory = $('[data-ecf-starter-select]').val() || 'all';
    var activeTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    var query = getActiveClassSearchQuery();

    $('[data-ecf-starter-item]').each(function() {
      var $item = $(this);
      var itemGroup = String($item.data('tabgroup') || 'all');
      var itemTier = String($item.data('tier') || '');
      var itemCategory = String($item.data('category') || '');
      var className = String($item.data('class-name') || '');
      var matchesGroup = activeCategory === 'all' || activeCategory === itemGroup;
      var matchesTier = activeTier === 'all' || activeTier === 'basic' || activeTier === 'extras'
        ? (activeTier === 'all' ? true : (activeTier === 'extras' ? itemTier === 'advanced' : itemTier === activeTier))
        : activeTier === 'custom'
          ? false
          : true;
      var matchesQuery = matchesClassSearch([className, itemCategory, itemGroup, itemTier], query);
      $item.toggle(matchesGroup && matchesTier && matchesQuery);
    });

    var showCustomSection = (activeCategory === 'all' || activeCategory === 'custom') && (activeTier === 'all' || activeTier === 'custom');
    $('[data-ecf-starter-custom-section]').toggle(showCustomSection);

    if (showCustomSection) {
      $('[data-ecf-starter-custom-rows] .ecf-starter-custom-row').each(function() {
        var $row = $(this);
        var name = $row.find('.ecf-custom-starter-name').val() || '';
        var category = $row.find('.ecf-custom-starter-category').val() || '';
        $row.toggle(matchesClassSearch([name, category, 'custom'], query));
      });
    }

    sortVisibleStarterItems();
  }

  function refreshUtilityClassVisibility() {
    var activeCategory = $('[data-ecf-utility-tab].is-active').data('ecf-utility-tab') || 'all';
    var query = getActiveClassSearchQuery();

    $('[data-ecf-utility-item]').each(function() {
      var $item = $(this);
      var itemCategory = String($item.data('category') || 'all');
      var className = String($item.data('class-name') || '');
      var matchesCategory = activeCategory === 'all' || activeCategory === itemCategory;
      var matchesQuery = matchesClassSearch([className, itemCategory, 'utility'], query);
      $item.toggle(matchesCategory && matchesQuery);
    });

    sortVisibleUtilityItems();
  }

  function applyStarterClassFilter(category) {
    var activeCategory = category || 'all';
    $('[data-ecf-starter-select]').val(activeCategory);
    $('[data-ecf-starter-classes]').attr('data-ecf-active-starter-category', activeCategory);
    refreshStarterClassVisibility();
  }

  function applyUtilityClassFilter(category) {
    var activeCategory = category || 'all';
    var $activeTab = $('[data-ecf-utility-tab]').removeClass('is-active')
      .filter('[data-ecf-utility-tab="' + activeCategory + '"]').addClass('is-active');

    $('[data-ecf-starter-classes]').attr('data-ecf-active-utility-category', activeCategory);
    $('[data-ecf-category-help="utility"]').text($activeTab.attr('data-ecf-help') || '');
    refreshUtilityClassVisibility();
  }

  function loadVariables() {
    if (varsLoaded) return;
    $.post(ecfAdmin.ajaxurl, {
      action: 'ecf_get_variables',
      nonce:  ecfAdmin.nonce
    }, function(res) {
      if (!res.success) {
        $('#ecf-varlist-ecf, #ecf-varlist-foreign').html('<p style="color:#ef4444;">'+res.data+'</p>');
        return;
      }
      varStore.ecf = res.data.ecf || [];
      varStore.foreign = res.data.foreign || [];
      renderVarList('ecf',     mergePreparedLayrixVariables(res.data.ecf || []));
      renderVarList('foreign', res.data.foreign);
      updateVariableSummary(mergePreparedLayrixVariables(res.data.ecf || []), res.data.foreign);
      renderGlobalSearchResults($('#ecf-global-search-input').val() || '');
      varsLoaded = true;
    });
  }

  function loadClasses() {
    if (classesLoaded) return;
    $.post(ecfAdmin.ajaxurl, {
      action: 'ecf_get_classes',
      nonce:  ecfAdmin.nonce
    }, function(res) {
      if (!res.success) {
        $('#ecf-varlist-ecf-classes, #ecf-varlist-foreign-classes').html('<p style="color:#ef4444;">'+res.data+'</p>');
        return;
      }
      varStore['ecf-classes'] = res.data.ecf || [];
      varStore['foreign-classes'] = res.data.foreign || [];
      renderVarList('ecf-classes', res.data.ecf);
      renderVarList('foreign-classes', res.data.foreign);
      updateClassSummary(res.data.ecf, res.data.foreign);
      renderGlobalSearchResults($('#ecf-global-search-input').val() || '');
      classesLoaded = true;
    });
  }

  $(document).on('change', '[data-ecf-starter-select]', function() {
    applyStarterClassFilter($(this).val());
    updateClassLibrarySelectAllState();
  });

  $(document).on('click', '[data-ecf-utility-tab]', function() {
    applyUtilityClassFilter($(this).data('ecf-utility-tab'));
    updateClassLibrarySelectAllState();
  });

  $(document).on('click', '[data-ecf-class-tier]', function() {
    applyClassTierFilter($(this).data('ecf-class-tier'));
  });

  $(document).on('change input', '.ecf-starter-class-toggle, .ecf-custom-starter-enabled, .ecf-custom-starter-name, .ecf-custom-starter-category, .ecf-utility-class-toggle', function() {
    updateStarterClassesState();
  });

  $(document).on('click', '[data-ecf-class-select-all]', function() {
    var $checks = getVisibleClassLibraryChecks();
    if (!$checks.length) return;

    var allChecked = $checks.filter(':checked').length === $checks.length;
    $checks.prop('checked', !allChecked).trigger('change');
    updateClassLibrarySelectAllState();
  });

  function submitClassLibrarySync($button) {
    var $sourceForm = $('form[action="options.php"]').first();
    if (!$sourceForm.length) return;

    var actionUrl = '';
    if (typeof window.ajaxurl === 'string' && window.ajaxurl.indexOf('admin-ajax.php') !== -1) {
      actionUrl = window.ajaxurl.replace('admin-ajax.php', 'admin-post.php');
    } else {
      actionUrl = String($button.attr('data-ecf-class-sync-url') || '');
    }
    if (!actionUrl) return;

    try {
      window.sessionStorage.setItem(panelStorageKey, 'utilities');
    } catch (err) {}

    var $tempForm = $('<form>', {
      method: 'post',
      action: actionUrl,
      style: 'display:none'
    });

    $.each($sourceForm.serializeArray(), function(_, field) {
      $('<input>', {
        type: 'hidden',
        name: field.name,
        value: field.value
      }).appendTo($tempForm);
    });

    $('<input>', {
      type: 'hidden',
      name: 'action',
      value: 'ecf_class_library_sync'
    }).appendTo($tempForm);

    $('body').append($tempForm);
    persistAdminPageState($button);
    $tempForm.trigger('submit');
  }

  $(document).on('click', '[data-ecf-class-sync-button]', function(e) {
    e.preventDefault();
    submitClassLibrarySync($(this));
  });

  $(document).on('click', '[data-ecf-starter-custom-add]', function() {
    appendCustomStarterRow({ enabled: true, category: 'custom' });
    updateStarterClassesState();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('click', '[data-ecf-starter-custom-remove]', function() {
    var $rows = $('[data-ecf-starter-custom-rows]');
    $rows.find('.ecf-starter-custom-row').last().remove();
    updateStarterClassesState();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('click', '[data-ecf-custom-suggestion]', function() {
    var suggestion = String($(this).data('ecf-custom-suggestion') || '').trim();
    var $rows = $('[data-ecf-starter-custom-rows]');
    if (!suggestion || !$rows.length) return;

    var normalized = suggestion.toLowerCase();
    var $existing = $rows.find('.ecf-starter-custom-row').filter(function() {
      return String($(this).find('.ecf-custom-starter-name').val() || '').trim().toLowerCase() === normalized;
    }).first();

    if ($existing.length) {
      $existing.find('.ecf-custom-starter-enabled').prop('checked', true);
      pulseCustomStarterRow($existing);
      $existing.find('.ecf-custom-starter-name').focus();
      updateStarterClassesState();
      scheduleSettingsAutosave({ delay: 250 });
      return;
    }

    var $target = $rows.find('.ecf-starter-custom-row').filter(function() {
      return !$.trim($(this).find('.ecf-custom-starter-name').val() || '');
    }).first();

    if (!$target.length) {
      $target = appendCustomStarterRow({ enabled: true, category: 'custom' });
    }

    if (!$target || !$target.length) return;

    $target.find('.ecf-custom-starter-enabled').prop('checked', true);
    $target.find('.ecf-custom-starter-name').val(suggestion).trigger('input');
    $target.find('.ecf-custom-starter-category').val('custom').trigger('change');
    pulseCustomStarterRow($target);
    $target.find('.ecf-custom-starter-name').focus();
    updateStarterClassesState();
    scheduleSettingsAutosave({ delay: 250 });
  });

  $(document).on('input', '[data-ecf-class-search]', function() {
    if ($(this).closest('[data-ecf-library-section]').data('ecf-library-section') === 'utility') {
      refreshUtilityClassVisibility();
    } else {
      refreshStarterClassVisibility();
    }
    updateClassLibrarySelectAllState();
  });

  $(document).on('change', '[data-ecf-bem-preset]', function() {
    refreshBemGeneratorOptions();
    renderBemGeneratorPreview();
    getBemGeneratorRoot().find('[data-ecf-bem-feedback]').text('');
  });

  $(document).on('input change', '[data-ecf-bem-generator] input', function() {
    renderBemGeneratorPreview();
  });

  $(document).on('click', '[data-ecf-bem-reset]', function() {
    var $root = getBemGeneratorRoot();
    if (!$root.length) return;
    $root.find('[data-ecf-bem-block], [data-ecf-bem-extra-elements], [data-ecf-bem-extra-modifiers]').val('');
    refreshBemGeneratorOptions();
    renderBemGeneratorPreview();
    $root.find('[data-ecf-bem-feedback]').text('');
  });

  $(document).on('click', '[data-ecf-bem-add]', function() {
    addBemGeneratedClasses();
  });

  function buildGlobalSearchMatches(query) {
    var normalized = String(query || '').trim().toLowerCase();
    var matches = [];
    if (!normalized) return matches;

    $.each(varStore, function(group, items) {
      if (isClassGroup(group)) return;
      $.each(items || [], function(_, item) {
        var label = String(item.label || '').toLowerCase();
        var value = String(item.value || '').toLowerCase();
        var starts = label.indexOf(normalized) === 0;
        var contains = starts || label.indexOf(normalized) !== -1 || value.indexOf(normalized) !== -1;
        if (!contains) return;

        matches.push({
          group: group,
          id: item.id,
          label: item.label,
          value: item.value,
          type: item.type,
          tabKey: typeTabKey(item.type),
          rank: starts ? 0 : 1
        });
      });
    });

    matches.sort(function(a, b) {
      if (a.rank !== b.rank) return a.rank - b.rank;
      return String(a.label || '').localeCompare(String(b.label || ''), 'de', { sensitivity: 'base' });
    });

    return matches;
  }

  function renderGlobalSearchResults(query) {
    var $results = $('#ecf-global-search-results');
    if (!$results.length) return;

    var normalized = String(query || '').trim();
    if (!normalized) {
      $results.prop('hidden', true).empty();
      return;
    }

    var matches = buildGlobalSearchMatches(normalized);
    if (!matches.length) {
      $results.html('<div class="ecf-global-search__empty">'+(i18n.none || '')+'</div>').prop('hidden', false);
      return;
    }

    var html = '<div class="ecf-global-search__list">';
    $.each(matches.slice(0, 24), function(_, item) {
      var actionsHtml = '';
      if (canEditSearchItem(item)) {
        actionsHtml += '<button type="button" class="ecf-icon-btn ecf-icon-btn--secondary ecf-search-edit" data-ecf-search-edit data-group="' + item.group + '" data-id="' + item.id + '" data-tip="' + escapeHtml(i18n.edit || '') + '" aria-label="' + escapeHtml(i18n.edit || '') + '"><span class="dashicons dashicons-edit"></span></button>';
      }
      actionsHtml += '<button type="button" class="ecf-icon-btn ecf-icon-btn--danger ecf-search-delete" data-ecf-search-delete data-group="' + item.group + '" data-id="' + item.id + '" data-label="' + escapeHtml(item.label) + '" data-tip="' + escapeHtml(i18n.delete) + '" aria-label="' + escapeHtml(i18n.delete) + '"><span class="dashicons dashicons-trash"></span></button>';

      html += '<div class="ecf-global-search__item">'
        + '<button type="button" class="ecf-global-search__main" data-ecf-search-result data-group="' + item.group + '" data-id="' + item.id + '" data-tab-key="' + item.tabKey + '">'
        + '<div class="ecf-global-search__identity">'
        + '<span class="ecf-global-search__meta">' + escapeHtml(groupLabel(item.group)) + '</span>'
        + '<strong class="ecf-global-search__label">' + originBadge(item.group) + '<span>' + escapeHtml(item.label) + '</span></strong>'
        + '</div>'
        + '<span class="ecf-global-search__type">' + escapeHtml(typeLabel(item.type)) + '</span>'
        + renderSearchPreview(item)
        + '</button>'
        + '<div class="ecf-global-search__actions">'
        + actionsHtml
        + '</div>'
        + '</div>';
    });
    html += '</div>';
    $results.html(html).prop('hidden', false);
  }

  function focusSearchResult(group, id, tabKey) {
    if (!varStore[group] || !varStore[group].length) return;
    varTabs[group] = tabKey || 'all';
    renderVarList(group, varStore[group]);

    var $card = $('#ecf-varlist-' + group).closest('.ecf-card');
    var $row = $('#ecf-varlist-' + group).find('.ecf-var-row[data-id="' + id + '"]');

    if ($card.length) {
      $card.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    if ($row.length) {
      $row.addClass('is-flash');
      setTimeout(function(){ $row.removeClass('is-flash'); }, 1600);
    }
  }

  function findSearchItem(group, id) {
    var items = varStore[group] || [];
    return items.find(function(item) { return String(item.id) === String(id); }) || null;
  }

  function closeSearchEditModal() {
    $('[data-ecf-search-edit-modal]').prop('hidden', true).removeClass('is-open');
    $('body').removeClass('ecf-modal-open');
  }

  function parseSearchEditSizeValue(rawValue) {
    var value = String(rawValue || '').trim();
    var match = value.match(/^(-?\d+(?:\.\d+)?)(px|rem|em|ch|%|vw|vh)$/i);
    if (match) {
      return {
        value: match[1],
        format: String(match[2]).toLowerCase()
      };
    }

    return {
      value: value,
      format: 'fx'
    };
  }

  function normalizeSearchEditLabel(rawValue) {
    return String(rawValue || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9\-_ ]+/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function normalizeTokenName(rawValue) {
    return String(rawValue || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9\-_ ]+/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function getRootBasePx() {
    return ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16;
  }

  function splitTopLevelArgs(value) {
    var parts = [];
    var current = '';
    var depth = 0;
    String(value || '').split('').forEach(function(char) {
      if (char === '(') depth += 1;
      if (char === ')') depth = Math.max(0, depth - 1);
      if (char === ',' && depth === 0) {
        parts.push(current.trim());
        current = '';
        return;
      }
      current += char;
    });
    if (current.trim() !== '') {
      parts.push(current.trim());
    }
    return parts;
  }

  function parseSizeToken(value) {
    var match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)(px|rem|em|ch|%|vw|vh)$/i);
    if (!match) return null;
    return {
      amount: parseFloat(match[1]),
      unit: String(match[2]).toLowerCase()
    };
  }

  function sizeTokenToPx(token, rootBasePx) {
    if (!token) return null;
    if (token.unit === 'px') return token.amount;
    if (token.unit === 'rem' || token.unit === 'em') return token.amount * rootBasePx;
    return null;
  }

  function pxToSizeToken(px, unit, rootBasePx) {
    var amount = parseFloat(px);
    if (!isFinite(amount)) return '';
    if (unit === 'rem' || unit === 'em') {
      return formatNumber(amount / rootBasePx, 4) + unit;
    }
    return formatNumber(amount, 4) + 'px';
  }

  function parseClampSizeValue(rawValue) {
    var value = String(rawValue || '').trim();
    if (!/^clamp\(/i.test(value)) return null;

    var inner = value.replace(/^clamp\(/i, '').replace(/\)$/, '');
    var parts = splitTopLevelArgs(inner);
    if (parts.length !== 3) return null;

    var minToken = parseSizeToken(parts[0]);
    var maxToken = parseSizeToken(parts[2]);
    if (!minToken || !maxToken) return null;

    var rootBasePx = getRootBasePx();
    var minPx = sizeTokenToPx(minToken, rootBasePx);
    var maxPx = sizeTokenToPx(maxToken, rootBasePx);
    if (minPx == null || maxPx == null) return null;

    return {
      raw: value,
      middle: parts[1],
      minToken: minToken,
      maxToken: maxToken,
      minPx: round(minPx, 2),
      maxPx: round(maxPx, 2)
    };
  }

  function updateSearchEditClampInfo(clampData) {
    var $tech = $('[data-ecf-search-edit-tech]');
    var $valueFields = $('.ecf-search-edit-value-fields');
    var $clampFields = $('[data-ecf-search-edit-clamp-fields]');
    var type = $('[data-ecf-search-edit-type]').val();

    if (type === 'global-size-variable' && clampData) {
      $tech
        .html('<strong>' + escapeHtml(i18n.clamp_label || '') + '</strong><br><code>' + escapeHtml(clampData.raw) + '</code>')
        .prop('hidden', false);
      $('[data-ecf-search-edit-clamp-min]').val(formatNumber(clampData.minPx, 2));
      $('[data-ecf-search-edit-clamp-max]').val(formatNumber(clampData.maxPx, 2));
      $clampFields.prop('hidden', false);
      $valueFields.prop('hidden', true);
      return;
    }

    $tech.prop('hidden', true).empty();
    $clampFields.prop('hidden', true);
    $valueFields.prop('hidden', false);
  }

  function inferSearchEditType(item) {
    var explicitType = String(item.type || '');
    if (explicitType === 'global-color-variable' || explicitType === 'global-size-variable') {
      return explicitType;
    }

    var label = String(item.label || '').toLowerCase();
    var value = String(item.value || '').trim().toLowerCase();
    var looksLikeSize =
      /^-?\d+(?:\.\d+)?(?:px|rem|em|ch|%|vw|vh)$/i.test(value) ||
      /^(?:clamp|min|max|calc)\(/i.test(value);
    var isTypographyLike =
      label.indexOf('text-') === 0 ||
      label.indexOf('font-') === 0 ||
      label.indexOf('size') !== -1 ||
      label.indexOf('typography') !== -1;

    if (looksLikeSize || isTypographyLike) {
      return 'global-size-variable';
    }

    return explicitType || 'global-string-variable';
  }

  function syncSearchEditFieldVisibility() {
    var type = $('[data-ecf-search-edit-type]').val();
    $('[data-ecf-search-edit-color-row]').prop('hidden', type !== 'global-color-variable');
    $('[data-ecf-search-edit-format]').prop('hidden', type !== 'global-size-variable');
    if (type !== 'global-size-variable') {
      updateSearchEditClampInfo(null);
    }
    updateSearchEditTypeHelp();
  }

  function updateSearchEditTypeHelp() {
    var type = $('[data-ecf-search-edit-type]').val();
    var text = '';
    if (type === 'global-color-variable') {
      text = i18n.search_type_help_color || '';
    } else if (type === 'global-size-variable') {
      text = i18n.search_type_help_size || '';
    } else {
      text = i18n.search_type_help_string || '';
    }
    $('[data-ecf-search-edit-type-help]').text(text);
  }

  function openSearchEditModal(item) {
    var inferredType = inferSearchEditType(item);
    var clampData = inferredType === 'global-size-variable' ? parseClampSizeValue(item.value) : null;
    $('[data-ecf-search-edit-id]').val(item.id);
    $('[data-ecf-search-edit-label]').val(item.label || '');
    $('[data-ecf-search-edit-type]').val(inferredType);
    $('[data-ecf-search-edit-note]').prop('hidden', true).text('');

    if (inferredType === 'global-size-variable') {
      var sizeParts = clampData ? { value: '', format: 'fx' } : parseSearchEditSizeValue(item.value);
      $('[data-ecf-search-edit-value]').val(sizeParts.value || '');
      $('[data-ecf-search-edit-format]').val(sizeParts.format || 'fx');
    } else {
      $('[data-ecf-search-edit-value]').val(item.value || '');
      $('[data-ecf-search-edit-format]').val('px');
    }

    if (inferredType === 'global-color-variable') {
      var parsed = parseDisplayColor(item.value, 'hexa') || parseDisplayColor(item.value, 'rgba') || parseDisplayColor(item.value, 'hsla');
      $('[data-ecf-search-edit-color]').val(parsed ? rgbToHex(parsed) : '#3b82f6');
    }

    syncSearchEditFieldVisibility();
    updateSearchEditClampInfo(clampData);
    $('[data-ecf-search-edit-modal]').data('ecfClampData', clampData || null);
    $('[data-ecf-search-edit-modal]').prop('hidden', false).addClass('is-open');
    $('body').addClass('ecf-modal-open');
  }

  function closeClassDeleteModal() {
    $('[data-ecf-class-delete-modal]').removeData('ecfDeletePayload').prop('hidden', true).removeClass('is-open');
    $('body').removeClass('ecf-modal-open');
  }

  function executeClassDelete(group, ids, forceDelete, $button) {
    var request = {
      action: 'ecf_delete_classes',
      nonce: ecfAdmin.nonce,
      ids: ids,
      force_delete: forceDelete ? 1 : 0
    };
    if ($button && $button.length) {
      $button.prop('disabled', true).addClass('is-busy');
    }
    $.post(ecfAdmin.ajaxurl, request, function(res) {
      if ($button && $button.length) {
        $button.prop('disabled', false).removeClass('is-busy');
      }
      if (!res.success) {
        var message = res.data && res.data.message ? res.data.message : res.data;
        alert((i18n.error || '') + message);
        return;
      }
      classesLoaded = false;
      loadClasses();
    });
  }

  function openClassDeleteModal(payload) {
    var $modal = $('[data-ecf-class-delete-modal]');
    var usedItems = payload.usedItems || [];
    var unusedItems = payload.unusedItems || [];
    var listLines = usedItems.map(function(item) {
      return '• ' + item.label;
    });

    $('[data-ecf-class-delete-title]').text(i18n.class_delete_modal_title || '');
    $('[data-ecf-class-delete-subtitle]').text(i18n.class_delete_modal_subtitle || '');
    $('[data-ecf-class-delete-message]').text(i18n.class_delete_modal_message || '');
    $('[data-ecf-class-delete-used-count]').text(String(usedItems.length));
    $('[data-ecf-class-delete-unused-count]').text(String(unusedItems.length));
    $('[data-ecf-class-delete-list]').text(listLines.length ? listLines.join('\n') : (i18n.class_delete_none_unused || ''));
    $('[data-ecf-class-delete-unused] span:last-child').text(i18n.class_delete_unused_only || '');
    $('[data-ecf-class-delete-all] span:last-child').text(i18n.class_delete_all_anyway || '');
    $('[data-ecf-class-delete-unused]').prop('disabled', unusedItems.length === 0);

    $modal.data('ecfDeletePayload', payload).prop('hidden', false).addClass('is-open');
    $('body').addClass('ecf-modal-open');
  }

  function deleteSearchItem(group, id, label) {
    var isClass = isClassGroup(group);
    var targetItem = isClass ? findSearchItem(group, id) : null;
    if (isClass && targetItem && targetItem.in_use) {
      openClassDeleteModal({
        group: group,
        usedItems: [targetItem],
        unusedItems: [],
        allIds: [id],
        unusedIds: [],
        triggerButton: null
      });
      return;
    } else if (!confirm((i18n.search_delete_confirm || '').replace('%s', label))) {
      return;
    }
    $.post(ecfAdmin.ajaxurl, {
      action: isClass ? 'ecf_delete_classes' : 'ecf_delete_variables',
      nonce: ecfAdmin.nonce,
      ids: [id]
    }, function(res) {
      if (!res.success) {
        var message = res.data && res.data.message ? res.data.message : res.data;
        alert((i18n.error || '') + message);
        return;
      }

      if (isClass) {
        classesLoaded = false;
        loadClasses();
      } else {
        varsLoaded = false;
        loadVariables();
      }
    });
  }

  // Select all toggle
  $(document).on('click', '.ecf-select-all', function(){
    var group = $(this).data('group');
    var $checks = getVisibleChecks(group);
    var allChecked = $checks.length === $checks.filter(':checked').length;
    $checks.prop('checked', !allChecked);
    updateSelectAllState(group);
  });

  // Delete selected
  $(document).on('click', '.ecf-delete-selected', function(){
    var group = $(this).data('group');
    var isClassGroup = String(group).indexOf('classes') !== -1;
    var ids = [];
    var selectedItems = [];
    getVisibleChecks(group).filter(':checked').each(function(){
      ids.push($(this).val());
    });
    if (!ids.length) { alert(i18n.none_selected); return; }
    if (isClassGroup) {
      selectedItems = (varStore[group] || []).filter(function(item) {
        return ids.indexOf(String(item.id)) !== -1;
      });
      var usedItems = selectedItems.filter(function(item) { return !!item.in_use; });
      var unusedItems = selectedItems.filter(function(item) { return !item.in_use; });
      if (usedItems.length) {
        openClassDeleteModal({
          group: group,
          usedItems: usedItems,
          unusedItems: unusedItems,
          allIds: ids.slice(),
          unusedIds: unusedItems.map(function(item) { return String(item.id); }),
          triggerButton: $(this)
        });
        return;
      } else if (!confirm(ids.length + i18n.confirm_delete)) {
        return;
      }
    } else if (!confirm(ids.length + i18n.confirm_delete)) {
      return;
    }

    var $btn = $(this).prop('disabled', true).addClass('is-busy');
    $.post(ecfAdmin.ajaxurl, {
      action: isClassGroup ? 'ecf_delete_classes' : 'ecf_delete_variables',
      nonce:  ecfAdmin.nonce,
      ids:    ids
    }, function(res) {
      $btn.prop('disabled', false).removeClass('is-busy');
      if (!res.success) {
        var message = res.data && res.data.message ? res.data.message : res.data;
        alert(i18n.error + message);
        return;
      }
      if (isClassGroup) {
        classesLoaded = false;
        loadClasses();
      } else {
        varsLoaded = false;
        loadVariables();
      }
    });
  });

  $(document).on('click', '[data-ecf-class-delete-close]', function(){
    closeClassDeleteModal();
  });

  $(document).on('click', '[data-ecf-class-delete-unused]', function(){
    var payload = $('[data-ecf-class-delete-modal]').data('ecfDeletePayload') || null;
    if (!payload || !payload.unusedIds || !payload.unusedIds.length) {
      alert(i18n.class_delete_none_unused || '');
      return;
    }
    closeClassDeleteModal();
    executeClassDelete(payload.group, payload.unusedIds, false, payload.triggerButton || null);
  });

  $(document).on('click', '[data-ecf-class-delete-all]', function(){
    var payload = $('[data-ecf-class-delete-modal]').data('ecfDeletePayload') || null;
    if (!payload || !payload.allIds || !payload.allIds.length) return;
    closeClassDeleteModal();
    executeClassDelete(payload.group, payload.allIds, true, payload.triggerButton || null);
  });

  // Row click toggles checkbox
  $(document).on('click', '.ecf-var-row', function(e){
    if ($(e.target).is('input')) return;
    $(this).find('.ecf-var-check').trigger('click');
  });

  $(document).on('change', '.ecf-var-check', function(){
    updateSelectAllState($(this).closest('.ecf-var-row').data('group'));
  });

  $(document).on('click', '.ecf-var-tab', function(){
    var group = $(this).data('group') || 'foreign';
    varTabs[group] = $(this).data('var-tab') || 'all';
    if (String(group).indexOf('classes') !== -1) {
      classesLoaded = false;
      loadClasses();
      return;
    }
    varsLoaded = false;
    loadVariables();
  });

  $(document).on('input', '#ecf-global-search-input', function(){
    renderGlobalSearchResults($(this).val() || '');
  });

  $(document).on('click', '[data-ecf-search-result]', function(){
    var $item = $(this);
    focusSearchResult($item.data('group'), String($item.data('id')), $item.data('tab-key'));
  });

  $(document).on('click', '[data-ecf-search-edit]', function(e){
    e.preventDefault();
    e.stopPropagation();
    var $button = $(this);
    var group = $button.data('group');
    var id = String($button.data('id'));
    var item = findSearchItem(group, id);
    if (!item) return;

    if (!canEditSearchItem($.extend({}, item, { group: group }))) {
      alert(isClassGroup(group) ? i18n.search_edit_class : i18n.search_edit_generated);
      return;
    }

    openSearchEditModal($.extend({}, item, { group: group }));
  });

  $(document).on('click', '[data-ecf-search-delete]', function(e){
    e.preventDefault();
    e.stopPropagation();
    var $button = $(this);
    deleteSearchItem($button.data('group'), String($button.data('id')), String($button.data('label') || ''));
  });

  $(document).on('click', '[data-ecf-search-edit-close]', function(){
    closeSearchEditModal();
  });

  $(document).on('change', '[data-ecf-search-edit-type]', function(){
    if ($(this).val() === 'global-size-variable') {
      var sizeParts = parseSearchEditSizeValue($('[data-ecf-search-edit-value]').val());
      $('[data-ecf-search-edit-value]').val(sizeParts.value || '');
      $('[data-ecf-search-edit-format]').val(sizeParts.format || 'fx');
    }
    $('[data-ecf-search-edit-modal]').data('ecfClampData', null);
    syncSearchEditFieldVisibility();
  });

  $(document).on('input', '[data-ecf-search-edit-color]', function(){
    if ($('[data-ecf-search-edit-type]').val() === 'global-color-variable') {
      $('[data-ecf-search-edit-value]').val($(this).val());
    }
  });

  $(document).on('input', '[data-ecf-search-edit-value]', function(){
    if ($('[data-ecf-search-edit-type]').val() === 'global-color-variable') {
      var parsed = parseDisplayColor($(this).val(), 'hexa') || parseDisplayColor($(this).val(), 'rgba') || parseDisplayColor($(this).val(), 'hsla');
      if (parsed) {
        $('[data-ecf-search-edit-color]').val(rgbToHex(parsed));
      }
    }
  });

  $(document).on('click', '[data-ecf-search-edit-save]', function(){
    var id = $('[data-ecf-search-edit-id]').val();
    var label = normalizeSearchEditLabel($('[data-ecf-search-edit-label]').val());
    var type = $('[data-ecf-search-edit-type]').val();
    var value = $('[data-ecf-search-edit-value]').val();
    var format = $('[data-ecf-search-edit-format]').val();
    var clampData = $('[data-ecf-search-edit-modal]').data('ecfClampData');

    $('[data-ecf-search-edit-label]').val(label);

    if (!label) {
      $('[data-ecf-search-edit-note]').prop('hidden', false).text((i18n.error || '') + (i18n.search_label_invalid || ''));
      return;
    }

    if (type === 'global-color-variable' && $('[data-ecf-search-edit-color]').val()) {
      value = $('[data-ecf-search-edit-color]').val();
    } else if (type === 'global-size-variable') {
      if (clampData) {
        var minPx = parseFloat($('[data-ecf-search-edit-clamp-min]').val());
        var maxPx = parseFloat($('[data-ecf-search-edit-clamp-max]').val());
        var rootBasePx = getRootBasePx();
        if (!isFinite(minPx) || !isFinite(maxPx)) {
          $('[data-ecf-search-edit-note]').prop('hidden', false).text((i18n.error || '') + (i18n.search_clamp_number_error || ''));
          return;
        }
        value = 'clamp('
          + pxToSizeToken(minPx, clampData.minToken.unit, rootBasePx)
          + ', '
          + clampData.middle
          + ', '
          + pxToSizeToken(maxPx, clampData.maxToken.unit, rootBasePx)
          + ')';
      } else {
        value = String(value || '').trim();
        if (format && format !== 'fx' && value !== '') {
          value = value + format;
        }
      }
    }

    $.ajax({
      url: ecfAdmin.ajaxurl,
      method: 'POST',
      dataType: 'json',
      data: {
      action: 'ecf_update_variable',
      nonce: ecfAdmin.nonce,
      id: id,
      label: label,
      type: type,
      value: value
      }
    }).done(function(res) {
      if (!res.success) {
        var message = res && res.data && res.data.message ? res.data.message : res.data;
        $('[data-ecf-search-edit-note]').prop('hidden', false).text((i18n.error || '') + message);
        return;
      }

      closeSearchEditModal();
      varsLoaded = false;
      loadVariables();
    }).fail(function(xhr) {
      var message = '';
      if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
        message = xhr.responseJSON.data.message || xhr.responseJSON.data;
      }
      if (!message && xhr && xhr.responseText) {
        message = xhr.responseText;
      }
      $('[data-ecf-search-edit-note]').prop('hidden', false).text((i18n.error || '') + (message || (i18n.autosave_failed || '')));
    });
  });

  // ── Spacing Preview ────────────────────────────────────────────
  var ALL_SPACE_STEPS = ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl'];

  function getSpacingConfig() {
    return {
      rootBasePx: ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16,
      minBase:   parseFloat($('[name="ecf_framework_v50[spacing][min_base]"]').val()) || 16,
      maxBase:   parseFloat($('[name="ecf_framework_v50[spacing][max_base]"]').val()) || 28,
      minRatio:  parseFloat($('[name="ecf_framework_v50[spacing][min_ratio]"]').val()) || 1.25,
      maxRatio:  parseFloat($('[name="ecf_framework_v50[spacing][max_ratio]"]').val()) || 1.414,
      baseIndex: $('[name="ecf_framework_v50[spacing][base_index]"]').val() || 'm',
      fluid:     $('[name="ecf_framework_v50[spacing][fluid]"]').is(':checked'),
      minVw:     parseFloat($('[name="ecf_framework_v50[spacing][min_vw]"]').val()) || 375,
      maxVw:     parseFloat($('[name="ecf_framework_v50[spacing][max_vw]"]').val()) || 1280,
      prefix:    $('[name="ecf_framework_v50[spacing][prefix]"]').val() || 'space'
    };
  }

  function getSpacingSteps() {
    var steps = [];
    $('#ecf-spacing-steps-container .ecf-spacing-step-input').each(function() {
      var v = $(this).val();
      if (v) steps.push(v);
    });
    if (steps.length >= 2) return steps;
    var $preview = $('[data-ecf-spacing-preview]');
    try { steps = JSON.parse($preview.attr('data-steps')); } catch(e) {}
    return Array.isArray(steps) && steps.length >= 2 ? steps : ['3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl'];
  }

  function buildSpacingItems(steps, cfg) {
    var baseIdx = steps.indexOf(cfg.baseIndex);
    if (baseIdx === -1) baseIdx = Math.floor(steps.length / 2);

    function pxToRem(px) {
      return Math.round((px / cfg.rootBasePx) * 100) / 100;
    }

    return $.map(steps, function(step, i) {
      var exp = i - baseIdx;
      var maxSize, minSize;
      if (exp === 0) { maxSize = cfg.maxBase; minSize = cfg.minBase; }
      else if (exp > 0) { maxSize = cfg.maxBase * Math.pow(cfg.maxRatio, exp); minSize = cfg.minBase * Math.pow(cfg.minRatio, exp); }
      else { maxSize = cfg.maxBase / Math.pow(cfg.maxRatio, Math.abs(exp)); minSize = cfg.minBase / Math.pow(cfg.minRatio, Math.abs(exp)); }
      maxSize = Math.round(maxSize * 1000) / 1000;
      minSize = Math.round(minSize * 1000) / 1000;
      if (minSize > maxSize) {
        var swap = minSize;
        minSize = maxSize;
        maxSize = swap;
      }
      var cssValue;
      if (cfg.fluid && cfg.maxVw > cfg.minVw) {
        var slope = (maxSize - minSize) / (cfg.maxVw - cfg.minVw);
        var interceptRem = Math.round((((minSize - slope * cfg.minVw) / cfg.rootBasePx) * 100)) / 100;
        var slopeVw = Math.round((slope * 100) * 100) / 100;
        cssValue = 'clamp('
          + formatPreviewNumber(pxToRem(minSize)) + 'rem, calc('
          + formatPreviewNumber(slopeVw) + 'vw '
          + (interceptRem >= 0 ? '+ ' : '- ')
          + formatPreviewNumber(Math.abs(interceptRem)) + 'rem), '
          + formatPreviewNumber(pxToRem(maxSize)) + 'rem)';
      } else {
        cssValue = formatPreviewNumber(pxToRem(maxSize)) + 'rem';
        minSize = maxSize;
      }
      return { step: step, token: '--ecf-' + cfg.prefix + '-' + step, min: formatPreviewNumber(pxToRem(minSize)), max: formatPreviewNumber(pxToRem(maxSize)), minPx: formatPreviewNumber(minSize), maxPx: formatPreviewNumber(maxSize), cssValue: cssValue, isBase: (i === baseIdx) };
    });
  }

  function renderSpacingPreview() {
    var $preview = $('[data-ecf-spacing-preview]');
    if (!$preview.length) return;
    var steps = getSpacingSteps();
    var cfg = getSpacingConfig();
    var items = buildSpacingItems(steps, cfg);
    var labelMin = $preview.data('preview-label-min') || '';
    var labelMax = $preview.data('preview-label-max') || '';
    var html = '';
    $.each(items, function(_, item) {
      var sizeRange = normalizeSizeRange(item.minPx, item.maxPx);
      var minValue = sizeRange ? sizeRange.minPx : 0;
      var maxValue = sizeRange ? sizeRange.maxPx : 0;
      var minBarWidth = Math.max(0, minValue);
      var maxBarWidth = Math.max(0, maxValue);
      var minBarH = Math.min(40, Math.max(4, Math.round(minValue)));
      var maxBarH = Math.min(40, Math.max(4, Math.round(maxValue)));
      html += '<div class="ecf-space-row' + (item.isBase ? ' is-base' : '') + '" data-ecf-space-step="' + item.step + '">'
        + '<div class="ecf-space-row__token"><span class="ecf-space-row__token-text ecf-spacing-token-name">' + item.token + '</span>'
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span></div>'
        + '<div class="ecf-space-row__meta">'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><div class="ecf-clamp-metric"><strong>' + formatPreviewNumber(minValue) + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + formatPreviewNumber(minBarWidth) + 'px;height:' + minBarH + 'px;"></div></div>'
        + '</div>'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><div class="ecf-clamp-metric"><strong>' + formatPreviewNumber(maxValue) + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + formatPreviewNumber(maxBarWidth) + 'px;height:' + maxBarH + 'px;"></div></div>'
        + '</div>'
        + '</div>'
        + '</div>';
    });
    $preview.find('[data-ecf-spacing-preview-list]').html(html);
  }

  $(document).on('input change', '[name="ecf_framework_v50[root_font_size]"], [name^="ecf_framework_v50[spacing]"]', function(){
    renderSpacingPreview();
    renderRootFontImpact();
  });

  renderSpacingPreview();
  renderRootFontImpact();
  syncRootFontSizeControls($('[data-ecf-root-font-source]').first().val());

  $(document).on('input change', '.ecf-table[data-group="radius"] input, [name="ecf_framework_v50[root_font_size]"]', function(){
    renderRootFontImpact();
  });

  function applySpacingSteps(steps) {
    if (!Array.isArray(steps) || steps.length < 2) return;
    var $preview = $('[data-ecf-spacing-preview]');
    $preview.attr('data-steps', JSON.stringify(steps));
    var $container = $('#ecf-spacing-steps-container');
    $container.empty();
    $.each(steps, function(_, step) {
      $container.append('<input type="hidden" class="ecf-spacing-step-input" name="ecf_framework_v50[spacing][steps][]" value="' + step + '">');
    });
    renderSpacingPreview();
    scheduleSettingsAutosave({ delay: 250 });
  }

  $('[data-ecf-spacing-add="smaller"]').on('click', function(e) {
    e.preventDefault();
    var steps = getSpacingSteps();
    var idx = ALL_SPACE_STEPS.indexOf(steps[0]);
    if (idx > 0) applySpacingSteps([ALL_SPACE_STEPS[idx - 1]].concat(steps));
  });
  $('[data-ecf-spacing-remove="smaller"]').on('click', function(e) {
    e.preventDefault();
    var steps = getSpacingSteps();
    if (steps.length > 2) applySpacingSteps(steps.slice(1));
  });
  $('[data-ecf-spacing-add="larger"]').on('click', function(e) {
    e.preventDefault();
    var steps = getSpacingSteps();
    var idx = ALL_SPACE_STEPS.indexOf(steps[steps.length - 1]);
    if (idx < ALL_SPACE_STEPS.length - 1) applySpacingSteps(steps.concat([ALL_SPACE_STEPS[idx + 1]]));
  });
  $('[data-ecf-spacing-remove="larger"]').on('click', function(e) {
    e.preventDefault();
    var steps = getSpacingSteps();
    if (steps.length > 2) applySpacingSteps(steps.slice(0, -1));
  });

  // ── Type Scale step management ─────────────────────────────────
  var ALL_STEPS = ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl','7xl','8xl','9xl'];

  function getScaleSteps() {
    // Read from hidden inputs (always up-to-date)
    var steps = [];
    $('#ecf-scale-steps-container .ecf-scale-step-input').each(function() {
      var v = $(this).val();
      if (v) steps.push(v);
    });
    if (steps.length >= 2) return steps;
    // Fallback: read from data attribute
    var $preview = $('[data-ecf-type-scale-preview]');
    var raw = $preview.attr('data-steps');
    try { steps = JSON.parse(raw); } catch(e) {}
    return Array.isArray(steps) && steps.length >= 2 ? steps : ['xs','s','m','l','xl','2xl','3xl','4xl'];
  }

  function applySteps(steps) {
    if (!Array.isArray(steps) || steps.length < 2) return;
    var $preview = $('[data-ecf-type-scale-preview]');
    if (!$preview.length) { console.warn('ECF: preview element not found'); return; }
    $preview.data('steps', steps);
    $preview.attr('data-steps', JSON.stringify(steps));
    // Rebuild hidden inputs
    var $container = $('#ecf-scale-steps-container');
    $container.empty();
    $.each(steps, function(_, step) {
      $container.append('<input type="hidden" class="ecf-scale-step-input" name="ecf_framework_v50[typography][scale][steps][]" value="' + step + '">');
    });
    try { renderTypePreview(); } catch(err) { console.error('ECF renderTypePreview error:', err); }
    scheduleSettingsAutosave({ delay: 250 });
  }

  // bind step buttons directly (they are static HTML, not dynamic)
  function bindStepButtons() {
    $('[data-ecf-add-step="smaller"]').off('click.ecfstep').on('click.ecfstep', function(e) {
      e.preventDefault();
      var steps = getScaleSteps();
      var idx = ALL_STEPS.indexOf(steps[0]);
      if (idx > 0) applySteps([ALL_STEPS[idx - 1]].concat(steps));
    });
    $('[data-ecf-remove-step="smaller"]').off('click.ecfstep').on('click.ecfstep', function(e) {
      e.preventDefault();
      var steps = getScaleSteps();
      if (steps.length > 2) applySteps(steps.slice(1));
    });
    $('[data-ecf-add-step="larger"]').off('click.ecfstep').on('click.ecfstep', function(e) {
      e.preventDefault();
      var steps = getScaleSteps();
      var idx = ALL_STEPS.indexOf(steps[steps.length - 1]);
      if (idx < ALL_STEPS.length - 1) applySteps(steps.concat([ALL_STEPS[idx + 1]]));
    });
    $('[data-ecf-remove-step="larger"]').off('click.ecfstep').on('click.ecfstep', function(e) {
      e.preventDefault();
      var steps = getScaleSteps();
      if (steps.length > 2) applySteps(steps.slice(0, -1));
    });
  }
  bindStepButtons();

  // ── Copy token to clipboard ────────────────────────────────────
  $(document).on('click', '.ecf-copy-pill', function(e) {
    e.stopPropagation();
    var $pill = $(this);
    var text = $pill.data('copy');
    if (!navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      $pill.text(i18n.copied).addClass('is-copied');
      setTimeout(function() {
        $pill.text(i18n.copy).removeClass('is-copied');
      }, 1500);
    });
  });

  $(document).on('click', '[data-ecf-token-copy]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $pill = $(this);
    var text = String($pill.attr('data-ecf-token-copy') || '').trim();
    if (!text || !navigator.clipboard) return;
    var originalHtml = $pill.html();
    navigator.clipboard.writeText(text).then(function() {
      $pill.addClass('is-copied').text(i18n.copied || '');
      setTimeout(function() {
        $pill.removeClass('is-copied').html(originalHtml);
      }, 1200);
    });
  });

  $(document).on('click', '.ecf-debug-copy', function(e) {
    e.preventDefault();
    var $button = $(this);
    var text = $button.attr('data-ecf-copy-text') || '';
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      var original = $button.text();
      $button.text(i18n.copied).addClass('is-copied');
      setTimeout(function() {
        $button.text(original).removeClass('is-copied');
      }, 1200);
    });
  });

  $(document).on('click', '.ecf-clamp-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $toggle = $(this);
    var $pop = $toggle.closest('.ecf-clamp-group, .ecf-type-row__token').find('.ecf-clamp-popover').first();
    $('.ecf-clamp-popover').not($pop).removeClass('is-open');
    $pop.toggleClass('is-open');
  });

  $(document).on('click', '[data-ecf-copy-target]', function(e) {
    e.preventDefault();
    var $button = $(this);
    var targetId = String($button.attr('data-ecf-copy-target') || '');
    var $target = targetId ? $('#' + targetId) : $();
    var text = $target.length ? String($target.val() || '') : '';
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      var original = $button.text();
      $button.text(i18n.copied || '');
      setTimeout(function() {
        $button.text(original);
      }, 1200);
    });
  });

  $(document).on('click', '.ecf-color-token-copy', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $button = $(this);
    var text = $button.attr('data-ecf-copy-text') || '';
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      var original = $button.text();
      $button.text(i18n.color_generator_copied || '').addClass('is-copied');
      setTimeout(function() {
        $button.text(original).removeClass('is-copied');
      }, 1200);
    });
  });

  $(document).on('click', '.ecf-clamp-popover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $pop = $(this);
    var text = $pop.attr('data-copy') || $pop.text();
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      var original = $pop.attr('data-copy') || text;
      $pop.addClass('is-copied').text(i18n.copied);
      setTimeout(function() {
        $pop.removeClass('is-copied').text(original);
      }, 1200);
    });
  });

  $(document).on('click', '[data-ecf-root-copy-toggle]', function(e) {
    e.preventDefault();
    var $toggle = $(this);
    var $item = $toggle.closest('.ecf-root-font-impact__item');
    var $pop = $item.find('.ecf-root-font-impact__copy-pop');
    $('.ecf-root-font-impact__copy-pop').not($pop).removeClass('is-open');
    $pop.toggleClass('is-open');
  });

  $(document).on('click', '.ecf-root-font-impact__copy-pop', function(e) {
    e.preventDefault();
    var $pop = $(this);
    var text = $pop.attr('data-copy') || $pop.text();
    if (!text || !navigator.clipboard) return;
    navigator.clipboard.writeText(text).then(function() {
      var original = $pop.text();
      $pop.addClass('is-copied').text(i18n.copied);
      setTimeout(function() {
        $pop.removeClass('is-copied').text(text);
      }, 1200);
    });
  });

  $(document).on('click', '[data-ecf-reload-page]', function(e) {
    e.preventDefault();
    persistAdminPageState($(this));
    window.location.reload();
  });

  $(document).on('click', '[data-ecf-refresh-system-info]', function(e) {
    e.preventDefault();
    if (!restUrl || !restNonce) return;

    var $button = $(this);
    if ($button.prop('disabled')) return;
    $button.prop('disabled', true).addClass('is-loading');

    window.fetch(restUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': restNonce
      }
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('rest_refresh_failed');
      }
      return response.json();
    }).then(function(responseData) {
      updateSystemInfoCards(responseData && responseData.meta ? responseData.meta : null, responseData && responseData.settings ? responseData.settings : null);
      showAutosaveNotice(i18n.system_refreshed || '', 'success');
    }).catch(function() {
      showAutosaveNotice(i18n.autosave_failed || '', 'error');
    }).finally(function() {
      $button.prop('disabled', false).removeClass('is-loading');
    });
  });

  $(document).on('change', '[data-ecf-admin-design-preset], [data-ecf-admin-design-mode]', function() {
    refreshAdminDesignChooser();
  });

  $(document).on('click', '[data-ecf-admin-design-option]', function() {
    var value = $(this).data('value') || 'current';
    $('[data-ecf-admin-design-preset]').val(value);
    adminDesign.preset = value;
    refreshAdminDesignChooser();
    $('[data-ecf-admin-design-preset]').first().trigger('change');
  });

  $(document).on('click', '[data-ecf-admin-design-mode-option]', function() {
    var value = $(this).data('value') || 'dark';
    $('[data-ecf-admin-design-mode]').val(value);
    adminDesign.mode = value;
    refreshAdminDesignChooser();
    $('[data-ecf-admin-design-mode]').first().trigger('change');
  });

  $(document).on('click', function(e) {
    if ($(e.target).closest('.ecf-root-font-impact__token-row, .ecf-root-font-impact__copy-pop').length) return;
    $('.ecf-root-font-impact__copy-pop').removeClass('is-open');
  });

  function updateBaseBodyTextSizeWarning() {
    var rootBasePx = ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16;

    $('[data-ecf-body-size-field]').each(function() {
      var $field = $(this);
      var value = $.trim($field.find('[name="ecf_framework_v50[base_body_text_size_value]"]').val() || '');
      var format = $.trim($field.find('[name="ecf_framework_v50[base_body_text_size_format]"]').val() || '').toLowerCase();
      var $warning = $field.find('[data-ecf-body-size-warning]');
      var numeric = parseFloat(String(value).replace(',', '.'));
      var message = '';
      var pxEquivalent = null;

      if (!value) {
        message = i18n.size_value_required || '';
      } else if (format === 'custom') {
        var normalizedCustom = value.toLowerCase();
        if (/^(?:0|0px|0rem|0em|0ch|0%|0vw|0vh)$/.test(normalizedCustom)) {
          message = i18n.size_value_positive || '';
        }
      } else if (isNaN(numeric) || numeric <= 0) {
        message = i18n.size_value_positive || '';
      }

      if (message) {
        $field.addClass('is-warning');
        $warning.prop('hidden', false).text(message);
        return;
      }

      if (format === 'custom') {
        $field.removeClass('is-warning');
        $warning.prop('hidden', true).text('');
        return;
      }

      if (format === 'px') {
        pxEquivalent = numeric;
      } else if (format === 'rem' || format === 'em') {
        pxEquivalent = numeric * rootBasePx;
      }

      if ((format === 'rem' || format === 'em') && numeric >= 8) {
        message = i18n.body_size_warn_large_unit || '';
      } else if (pxEquivalent !== null && (pxEquivalent < 10 || pxEquivalent > 32)) {
        message = i18n.body_size_warn_unusual || '';
      }

      if (message) {
        $field.addClass('is-warning');
        $warning.prop('hidden', false).text(message);
      } else {
        $field.removeClass('is-warning');
        $warning.prop('hidden', true).text('');
      }
    });
  }

  applyStarterClassFilter('all');
  applyUtilityClassFilter('all');
  applyClassTierFilter('all');
  refreshBemGeneratorOptions();
  renderBemGeneratorPreview();
  updateClassLibraryContext();
  updateStarterClassesState();
  updateBaseBodyTextSizeWarning();
});
