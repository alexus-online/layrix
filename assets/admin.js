jQuery(function($){
  var i18n = (typeof ecfAdmin !== 'undefined' && ecfAdmin.i18n) ? ecfAdmin.i18n : {};
  var spacingPreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.spacingPreview) ? ecfAdmin.spacingPreview : {};
  var typePreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.typePreview) ? ecfAdmin.typePreview : {};
  var radiusPreviewMap = (typeof ecfAdmin !== 'undefined' && ecfAdmin.radiusPreview) ? ecfAdmin.radiusPreview : {};
  var restUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.restUrl) ? ecfAdmin.restUrl : '';
  var layoutRestUrl = (typeof ecfAdmin !== 'undefined' && ecfAdmin.layoutRestUrl) ? ecfAdmin.layoutRestUrl : '';
  var restNonce = (typeof ecfAdmin !== 'undefined' && ecfAdmin.restNonce) ? ecfAdmin.restNonce : '';
  var adminDesign = (typeof ecfAdmin !== 'undefined' && ecfAdmin.adminDesign) ? ecfAdmin.adminDesign : {};
  var layoutOrders = (typeof ecfAdmin !== 'undefined' && ecfAdmin.layoutOrders) ? ecfAdmin.layoutOrders : {};
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

  function updateColorRowDisplay($row) {
    var hex = $row.find('.ecf-color-value-input').val() || $row.find('.ecf-color-field').val();
    var format = $row.find('.ecf-color-format-select').val() || 'hex';
    $row.find('.ecf-color-value-display').val(formatColorValue(hex, format));
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

  $(document).on('click', '.ecf-add-row', function(){
    var group = $(this).data('group');
    var index = nextIndex(group);
    var key   = inputKey(group);
    var isColor  = group === 'colors';
    var isMinMax = $('.ecf-table[data-group="'+group+'"]').data('minmax') === 1;
    var templateId = isColor ? '#ecf-row-template-color' : (isMinMax ? '#ecf-row-template-minmax' : '#ecf-row-template-default');
    var html = $(templateId).html()
      .replace(/__NAME__/g,  key+'['+index+'][name]')
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

  $(document).on('input change', '.ecf-color-value-display', function(){
    applyDisplayValueToRow($(this).closest('.ecf-row--color'));
  });

  $(document).on('blur', '.ecf-color-value-display', function(){
    var $row = $(this).closest('.ecf-row--color');
    if (!applyDisplayValueToRow($row)) {
      updateColorRowDisplay($row);
      $(this).removeClass('ecf-input-invalid');
    }
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

  function getPreviewFont() {
    var fontValue = $('[name^="ecf_framework_v50[typography][fonts]"][name$="[value]"]').first().val();
    return fontValue || 'Inter, sans-serif';
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
    var previewWord = $preview.data('preview-word') || '';
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
      html += '<div class="ecf-type-row' + selectedClass + '" data-ecf-step="' + item.step + '" data-ecf-step-row tabindex="0" role="button" aria-pressed="' + (item.step === activeStep ? 'true' : 'false') + '" style="--ecf-preview-size:' + sizeForView(item) + ';">'
        + '<div class="ecf-type-row__token">' + item.token
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span>'
        + '</div>'
        + '<div class="ecf-type-row__meta">'
        + '<div><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><div class="ecf-clamp-metric"><strong>' + item.minPx + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '<div><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><div class="ecf-clamp-metric"><strong>' + item.maxPx + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '</div>'
        + '<div class="ecf-type-row__sample">'
        + '<div class="ecf-type-row__sample-line">'
        + '<strong style="font-size:' + item.minPx + 'px;">' + labelMin + '</strong>'
        + '<span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span>'
        + '</div>'
        + '<div class="ecf-type-row__sample-line ecf-type-row__sample-line--max">'
        + '<strong style="font-size:' + item.maxPx + 'px;">' + labelMax + '</strong>'
        + '<span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span>'
        + '</div>'
        + '</div>'
        + '</div>';
    });

    var activeItem = items.find(function(item){ return item.step === activeStep; }) || items[0];

    $preview.css('--ecf-preview-font', getPreviewFont());
    $preview.attr('data-active-step', activeStep);
    $preview.attr('data-preview-view', viewMode);
    $preview.find('[data-ecf-type-scale-preview-list]').html(html);
    $preview.find('[data-ecf-preview-mode]').html(modeLabel());
    $preview.find('[data-ecf-focus-token]').text(activeItem ? activeItem.token : '');
    $preview.find('[data-ecf-focus-helper]').text(helperText);
    $preview.find('[data-ecf-focus-word]').text(previewWord).css('font-size', activeItem ? sizeForView(activeItem) : '');
    $preview.find('[data-ecf-focus-min]').text(activeItem ? activeItem.minPx + 'px' : '');
    $preview.find('[data-ecf-focus-max]').text(activeItem ? activeItem.maxPx + 'px' : '');
    $preview.find('[data-ecf-focus-min-copy]').text(activeItem ? activeItem.cssValue : '').attr('data-copy', activeItem ? activeItem.cssValue : '');
    $preview.find('[data-ecf-focus-max-copy]').text(activeItem ? activeItem.cssValue : '').attr('data-copy', activeItem ? activeItem.cssValue : '');
    $preview.find('[data-ecf-focus-min-line]').css('font-size', activeItem ? activeItem.minPx + 'px' : '').text(labelMin);
    $preview.find('[data-ecf-focus-max-line]').css('font-size', activeItem ? activeItem.maxPx + 'px' : '').text(labelMax);
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
    var typePreviewWord = $box.attr('data-preview-type-word') || '';
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
      var minBarWidth = spacingMaxValue > 0 ? Math.max(4, Math.round((spacingMinValue / spacingMaxValue) * 1000) / 10) : 0;
      var maxBarWidth = spacingMaxValue > 0 ? Math.max(4, Math.round((spacingMaxPx / spacingMaxValue) * 1000) / 10) : 0;
      var minBarHeight = Math.min(32, Math.max(4, Math.round(spacingMinValue)));
      var maxBarHeight = Math.min(32, Math.max(4, Math.round(spacingMaxPx)));
      $box.find('[data-ecf-root-spacing-token]').text('--ecf-' + spacingPrefix + '-' + spacingItem.step);
      $box.find('[data-ecf-root-spacing-copy]').text(spacingItem.cssValue).attr('data-copy', spacingItem.cssValue);
      $box.find('[data-ecf-root-spacing-min-label]').text(labelMin);
      $box.find('[data-ecf-root-spacing-max-label]').text(labelMax);
      $box.find('[data-ecf-root-spacing-min]').text(spacingItem.minPx + 'px');
      $box.find('[data-ecf-root-spacing-max]').text(spacingItem.maxPx + 'px');
      $box.find('[data-ecf-root-spacing-min-bar]').css({ width: minBarWidth + '%', height: minBarHeight + 'px' });
      $box.find('[data-ecf-root-spacing-max-bar]').css({ width: maxBarWidth + '%', height: maxBarHeight + 'px' });
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
        name: name,
        slug: slug,
      token: '--ecf-shadow-' + slug,
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
      html += '<button type="button" class="ecf-shadow-row' + selectedClass + '" data-ecf-shadow-step="' + item.slug + '">'
        + '<div class="ecf-shadow-row__token">' + escapeHtml(item.token) + '</div>'
        + '<div class="ecf-shadow-row__value"><code>' + escapeHtml(item.value) + '</code></div>'
        + '<div class="ecf-shadow-row__sample"><div class="ecf-shadow-row__mini" style="box-shadow:' + escapeHtml(item.value) + ';"></div></div>'
        + '</button>';
    });

    var activeItem = items.find(function(item){ return item.slug === activeShadow; }) || items[0];

    $preview.attr('data-active-shadow', activeShadow);
    $preview.find('[data-ecf-shadow-preview-list]').html(html);
    $preview.find('[data-ecf-shadow-token]').text(activeItem.token);
    $preview.find('[data-ecf-shadow-name]').text(activeItem.name);
    $preview.find('[data-ecf-shadow-css]').text(activeItem.value);
    $preview.find('[data-ecf-shadow-label]').text(activeItem.token);
    $preview.find('[data-ecf-shadow-helper]').text(helperText);
    $preview.find('[data-ecf-shadow-surface]').css('box-shadow', activeItem.value);
  }

  // ── Sidebar navigation ─────────────────────────────────────────
  var $noSavePanel = ['variables', 'sync', 'help', 'changelog']; // panels that don't need the save button
  var panelStorageKey = 'ecfActivePanel';
  var generalTabStorageKey = 'ecfGeneralTab';
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

    $.each($settingsForm.serializeArray(), function(_, field) {
      var path = parseFormFieldPath(field.name);
      if (!path.length || path[0] !== 'ecf_framework_v50') {
        return;
      }
      assignFormValue(payload, path.slice(1), field.value);
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

  function validateRequiredPositiveSizeField($field) {
    var $input = $field.find('[data-ecf-size-value-input]').first();
    var $format = $field.find('[data-ecf-size-format-input]').first();
    var $warning = $field.siblings('[data-ecf-inline-size-warning]').first();
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
      return false;
    }

    $field.removeClass('is-invalid');
    $warning.prop('hidden', true).text('');
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

  function getLayoutItemIds($group) {
    return $group.children('[data-ecf-layout-item]').map(function() {
      return $(this).data('ecf-layout-item');
    }).get();
  }

  function mergedLayoutItemIds(groupKey, domIds) {
    var savedIds = (layoutOrders && layoutOrders[groupKey]) ? layoutOrders[groupKey] : [];
    var domMap = {};
    var merged = [];

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

  function saveLayoutOrders() {
    if (!layoutRestUrl || !restNonce) return;

    var orders = collectAllLayoutOrders();
    layoutOrders = orders;

    window.fetch(layoutRestUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': restNonce
      },
      body: JSON.stringify({ orders: orders })
    }).then(function(response) {
      if (!response.ok) {
        throw new Error('layout_save_failed');
      }
      return response.json();
    }).then(function(responseData) {
      if (responseData && responseData.orders) {
        layoutOrders = normalizeLayoutOrders(responseData.orders);
      }
      showAutosaveNotice(i18n.layout_saved || '', 'success');
    }).catch(function() {
      showAutosaveNotice(i18n.layout_failed || '', 'error');
    });
  }

  function ensureLayoutHandle($item) {
    var $handle = $item.find('[data-ecf-layout-handle]').first();
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

    $target.attr('data-ecf-layout-handle', '1').addClass('ecf-layout-handle-zone');

    if (!$target.find('.ecf-layout-handle').length && !$target.is('.ecf-settings-group__summary, .ecf-system-debug-card__summary')) {
      $target.prepend('<span class="ecf-layout-handle" aria-hidden="true"><span class="dashicons dashicons-move"></span></span>');
    }

    return $target;
  }

  function initSortableLayoutGroups() {
    if (typeof $.fn.sortable !== 'function') return;

    $('[data-ecf-layout-group]').each(function() {
      var $group = $(this);
      applySavedLayoutToGroup($group);

      var $items = $group.children('[data-ecf-layout-item]');
      if ($items.length < 2) return;

      $items.each(function() {
        ensureLayoutHandle($(this));
      });

      if ($group.data('ui-sortable')) {
        $group.sortable('destroy');
      }

      $group.sortable({
        items: '> [data-ecf-layout-item]',
        handle: '[data-ecf-layout-handle]',
        tolerance: 'pointer',
        placeholder: 'ecf-sortable-placeholder',
        forcePlaceholderSize: true,
        start: function(event, ui) {
          ui.placeholder.height(ui.item.outerHeight());
          ui.placeholder.width(ui.item.outerWidth());
          ui.item.addClass('ecf-sortable-item--dragging');
        },
        stop: function(event, ui) {
          ui.item.removeClass('ecf-sortable-item--dragging');
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

  function submitSettingsAutosave() {
    if (!$settingsForm.length || !restUrl || !restNonce) return;
    if (!autosaveSkipValidation && !validateSettingsForSave()) return;

    if (autosaveInFlight) {
      autosaveQueued = true;
      return;
    }

    autosaveInFlight = true;
    autosaveQueued = false;
    persistAdminPageState($settingsForm);
    showAutosaveNotice(i18n.autosave_saving || '', 'saving');
    var payload = buildSettingsPayloadFromForm();

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
      var responseSettings = responseData && responseData.settings ? responseData.settings : payload;
      updateSystemInfoCards(responseData && responseData.meta ? responseData.meta : null, responseSettings);
      if (responseSettings.admin_design_preset) {
        $('[data-ecf-admin-design-preset]').val(responseSettings.admin_design_preset);
        adminDesign.preset = responseSettings.admin_design_preset;
      }
      if (responseSettings.admin_design_mode) {
        $('[data-ecf-admin-design-mode]').val(responseSettings.admin_design_mode);
        adminDesign.mode = responseSettings.admin_design_mode;
      }
      refreshAdminDesignChooser();

      if (autosaveQueued) {
        autosaveQueued = false;
        submitSettingsAutosave();
        return;
      }

      if (autosaveReloadRequested) {
        autosaveReloadRequested = false;
        window.location.reload();
        return;
      }

      showAutosaveNotice(i18n.autosave_saved || '', 'success');
    }).catch(function() {
      autosaveInFlight = false;

      if (autosaveQueued) {
        autosaveQueued = false;
      }

      showAutosaveNotice(i18n.autosave_failed || '', 'error');
    }).finally(function() {
      autosaveSkipValidation = false;
    });
  }

  function scheduleSettingsAutosave(options) {
    if (!$settingsForm.length || !autosaveReady) return;

    var opts = options || {};
    var delay = typeof opts.delay === 'number' ? opts.delay : 700;
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
    $('.ecf-nav-item[data-panel="'+panel+'"]').addClass('is-active');
    $('.ecf-panel').removeClass('is-active');
    $('.ecf-panel[data-panel="'+panel+'"]').addClass('is-active');

    try {
      window.sessionStorage.setItem(panelStorageKey, panel);
    } catch (err) {}

    // show/hide save footer
    if ($noSavePanel.indexOf(panel) !== -1) {
      $('#ecf-save-footer').hide();
    } else {
      $('#ecf-save-footer').show();
    }

    if (panel === 'variables') {
      loadVariables();
    }

    refreshSortableLayoutGroups();
  }

  $(document).on('click', '.ecf-nav-item', function(){
    var panel = $(this).data('panel');
    markWhatsNewSeen($(this).data('ecf-new-key'));
    switchPanel(panel);
  });

  function normalizeGeneralTab(tab) {
    if (tab === 'layout' || tab === 'colors' || tab === 'typography') {
      return 'website';
    }
    if (tab === 'behavior') {
      return 'editor';
    }
    if (tab === 'favorites' || tab === 'website' || tab === 'editor' || tab === 'ui' || tab === 'system') {
      return tab;
    }
    return 'website';
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

    refreshSortableLayoutGroups();
  }

  $(document).on('click', '[data-ecf-general-tab]', function() {
    markWhatsNewSeen($(this).data('ecf-new-key'));
    switchGeneralTab($(this).data('ecf-general-tab'));
  });

  $(document).on('toggle', '.ecf-system-debug-card', function() {
    if (this.open) {
      markWhatsNewSeen($(this).data('ecf-new-key'));
    }
  });

  function refreshGeneralFavoritesState() {
    var visibleCards = 0;
    $('[data-ecf-favorite-card]').each(function() {
      var key = $(this).data('ecf-favorite-card');
      var enabled = $('[data-ecf-general-favorite-toggle][data-ecf-favorite-key="' + key + '"]').first().is(':checked');
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
      $(activeNewTooltipEl).removeClass('ecf-new-dot--floating-active');
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
    $el.addClass('ecf-new-dot--floating-active');
    ensureFloatingNewTooltip().text(tip);
    positionFloatingNewTooltip($el);
    requestAnimationFrame(function() {
      if ($floatingNewTooltip && $floatingNewTooltip.length) {
        $floatingNewTooltip.addClass('is-visible');
      }
    });
  }

  $(document).on('mouseenter focus', '.ecf-new-dot[data-tip]', function() {
    showFloatingNewTooltip(this);
  });

  $(document).on('mouseleave blur', '.ecf-new-dot[data-tip]', function() {
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

  $(document).on('blur change', '[data-ecf-slug-field="token"]', function() {
    var normalized = normalizeTokenName($(this).val());
    if ($(this).val() !== normalized) {
      $(this).val(normalized).trigger('input');
    }
  });

  $(document).on('input', 'form[action="options.php"] :input[name]:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"])', function() {
    scheduleSettingsAutosave({ delay: 900 });
  });

  $(document).on('change', 'form[action="options.php"] select[name], form[action="options.php"] textarea[name], form[action="options.php"] input[type="checkbox"][name], form[action="options.php"] input[type="radio"][name], form[action="options.php"] input[type="hidden"][name]', function() {
    var isLanguageField = $(this).attr('name') === 'ecf_framework_v50[interface_language]';
    scheduleSettingsAutosave({
      delay: 250,
      reloadAfterSave: isLanguageField,
      skipValidation: isLanguageField
    });
  });

  $(document).on('change', '[data-ecf-base-font-preset]', function() {
    var $custom = $('[data-ecf-base-font-custom]');
    var showCustom = $(this).val() === '__custom__';
    $custom.prop('hidden', !showCustom);
    if (showCustom) {
      $custom.trigger('focus');
    }
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

  $(document).on('click', '[data-ecf-local-font-remove]', function(e) {
    e.preventDefault();
    var family = $.trim($(this).data('ecf-local-font-remove') || '');
    if (!family) return;
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
        $('[data-ecf-base-font-preset]').val('var(--ecf-font-primary)').trigger('change');
        $('[data-ecf-base-font-custom]').val('');
      }
    });
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
    if (storedPanel && $('.ecf-nav-item[data-panel="'+storedPanel+'"]').length) {
      initialPanel = storedPanel;
    }
  } catch (err) {}
  layoutOrders = normalizeLayoutOrders(layoutOrders);
  switchPanel(initialPanel);
  var initialGeneralTab = 'website';
  try {
    var storedGeneralTab = window.sessionStorage.getItem(generalTabStorageKey);
    if (storedGeneralTab) {
      initialGeneralTab = normalizeGeneralTab(storedGeneralTab);
    }
  } catch (err) {}
  switchGeneralTab(initialGeneralTab);
  $('[data-ecf-base-font-preset]').trigger('change');
  refreshGeneralFavoritesState();
  markWhatsNewSeen($('.ecf-nav-item.is-active').data('ecf-new-key'));
  markWhatsNewSeen($('[data-ecf-general-tab].is-active').data('ecf-new-key'));
  if ($('.ecf-system-debug-card').prop('open')) {
    markWhatsNewSeen($('.ecf-system-debug-card').data('ecf-new-key'));
  }
  registerWhatsNewImpressions();
  refreshWhatsNewBadges();
  restorePageScrollPosition();
  refreshAdminDesignChooser();
  initSortableLayoutGroups();
  autosaveReady = true;

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
  updateBaseBodyTextSizeWarning();

  $(document).on('input change', '[name="ecf_framework_v50[root_font_size]"], [name^="ecf_framework_v50[typography][scale]"], [name^="ecf_framework_v50[typography][fonts]"]', function(){
    renderTypePreview();
    renderRootFontImpact();
    updateBaseBodyTextSizeWarning();
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
    $picker.find('[data-ecf-format-option]').removeClass('is-active');
    $option.addClass('is-active');
    $picker.find('[data-ecf-format-input]').val($option.data('value'));
    $picker.find('[data-ecf-format-current]').text($option.data('label'));
    resetFormatTooltip($picker);
    $picker.find('[data-ecf-format-menu]').prop('hidden', true);
    $picker.find('[data-ecf-format-trigger]').attr('aria-expanded', 'false');
    setFormatTooltipVisibility($picker, false);
    updateBaseBodyTextSizeWarning();
  });

  $(document).on('input change', '[name="ecf_framework_v50[base_body_text_size_value]"], [name="ecf_framework_v50[base_body_text_size_format]"]', function() {
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

  $(document).on('click', '[data-ecf-shadow-step]', function(){
    var $preview = $('[data-ecf-shadow-preview]');
    $preview.attr('data-active-shadow', $(this).data('ecf-shadow-step'));
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
    var html = '<div class="ecf-var-table">';
    html += '<div class="ecf-var-head"><span></span><span>'+i18n.col_name+'</span><span>'+i18n.col_type+'</span><span>'+i18n.col_value+'</span></div>';
    $.each(items, function(i, v) {
      html += '<div class="ecf-var-row" data-id="'+v.id+'" data-group="'+group+'">'
        + '<input type="checkbox" class="ecf-var-check" value="'+v.id+'">'
        + '<span class="ecf-var-label">'+originBadge(group)+'<span>'+v.label+'</span></span>'
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
    return $('#ecf-varlist-' + group).find('.ecf-var-row:visible .ecf-var-check');
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

  function updateStarterClassesState() {
    var $root = $('[data-ecf-starter-classes]');
    if (!$root.length) return;

    var currentTotal = parseInt($root.data('ecf-class-current'), 10) || 0;
    var limit = parseInt($root.data('ecf-class-limit'), 10) || 100;
    var existing = getStarterExistingLabels();
    var starterNames = getSelectedStarterNames();
    var utilityNames = getSelectedUtilityNames();
    var selectedNames = Array.from(new Set(starterNames.concat(utilityNames)));
    var pendingNew = selectedNames.filter(function(name) { return existing.indexOf(name) === -1; }).length;
    var projected = currentTotal + pendingNew;
    var basicCount = $('.ecf-starter-class-item[data-tier="basic"]').length;
    var advancedCount = $('.ecf-starter-class-item[data-tier="advanced"]').length;
    var customCount = $('.ecf-starter-custom-row').filter(function() {
      return $.trim($(this).find('.ecf-custom-starter-name').val() || '') !== '';
    }).length;
    var utilityCount = $('.ecf-utility-class-item').length;
    var status = getClassUsageStatus(projected, limit);
    var percent = limit > 0 ? Math.round((projected / limit) * 100) : 0;
    percent = Math.max(0, Math.min(100, percent));

    $('[data-ecf-starter-selected]').text(selectedNames.length);
    $('[data-ecf-starter-projected]').text(projected);
    $('[data-ecf-starter-projected-inline]').text(projected);
    $('[data-ecf-starter-basic-count]').text(basicCount);
    $('[data-ecf-starter-extras-count]').text(advancedCount + utilityCount);
    $('[data-ecf-starter-custom-count]').text(customCount);
    $('[data-ecf-starter-percent]').text(percent);
    $('[data-ecf-starter-progress]').css('width', percent + '%');
    $('[data-ecf-starter-status]')
      .removeClass('ecf-class-limit-card--neutral ecf-class-limit-card--success ecf-class-limit-card--warning ecf-class-limit-card--danger')
      .addClass('ecf-class-limit-card--' + status);

    updateClassLibrarySelectAllState();
  }

  function applyClassTierFilter(tier) {
    var activeTier = tier || 'all';
    $('[data-ecf-class-tier]').removeClass('is-active')
      .filter('[data-ecf-class-tier="' + activeTier + '"]').addClass('is-active');

    $('[data-ecf-starter-classes]').attr('data-ecf-active-class-tier', activeTier);

    if (activeTier === 'extras') {
      switchClassLibrary('starter');
      applyStarterClassFilter('all');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }

    switchClassLibrary('starter');

    if (activeTier === 'custom') {
      applyStarterClassFilter('custom');
      updateClassLibraryContext();
      updateClassLibrarySelectAllState();
      return;
    }
    refreshStarterClassVisibility();
    updateClassLibraryContext();
    updateClassLibrarySelectAllState();
  }

  function getVisibleClassLibraryChecks() {
    var activeLibrary = $('[data-ecf-library-tab].is-active').data('ecf-library-tab') || 'starter';
    var $section = $('[data-ecf-library-section="' + activeLibrary + '"]');
    if (!$section.length) return $();

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

  function updateClassLibraryContext() {
    var activeTier = $('[data-ecf-class-tier].is-active').data('ecf-class-tier') || 'all';
    var activeLibrary = $('[data-ecf-library-tab].is-active').data('ecf-library-tab') || 'starter';
    var showExtrasControls = activeTier === 'extras';
    var showStarterFilter = activeLibrary === 'starter' && activeTier !== 'custom';
    var showBemGenerator = activeLibrary === 'starter' && activeTier === 'custom';

    $('[data-ecf-library-tabs]').prop('hidden', !showExtrasControls);
    $('[data-ecf-library-help], [data-ecf-category-help="utility"]').prop('hidden', !showExtrasControls);
    $('[data-ecf-starter-filterbar]').prop('hidden', !showStarterFilter);
    $('[data-ecf-bem-generator]').prop('hidden', !showBemGenerator);
  }

  function getActiveClassSearchQuery() {
    var activeLibrary = $('[data-ecf-library-tab].is-active').data('ecf-library-tab') || 'starter';
    return $.trim($('[data-ecf-library-section="' + activeLibrary + '"] [data-ecf-class-search]').val() || '').toLowerCase();
  }

  function matchesClassSearch(parts, query) {
    if (!query) return true;
    return (parts || []).some(function(part) {
      return String(part || '').toLowerCase().indexOf(query) !== -1;
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

  function switchClassLibrary(tab) {
    var activeTab = tab || 'starter';
    var $activeTab = $('[data-ecf-library-tab]').removeClass('is-active')
      .filter('[data-ecf-library-tab="' + activeTab + '"]').addClass('is-active');

    $('[data-ecf-library-help]').text($activeTab.attr('data-ecf-help') || '');
    $('[data-ecf-library-section]').attr('hidden', true)
      .filter('[data-ecf-library-section="' + activeTab + '"]').removeAttr('hidden');
    updateClassLibraryContext();
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
      renderVarList('ecf',     res.data.ecf);
      renderVarList('foreign', res.data.foreign);
      updateVariableSummary(res.data.ecf, res.data.foreign);
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

  $(document).on('click', '[data-ecf-library-tab]', function() {
    switchClassLibrary($(this).data('ecf-library-tab'));
    if ($(this).data('ecf-library-tab') === 'utility') {
      refreshUtilityClassVisibility();
    } else {
      refreshStarterClassVisibility();
    }
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
        actionsHtml += '<button type="button" class="ecf-icon-btn ecf-icon-btn--secondary ecf-search-edit" data-ecf-search-edit data-group="' + item.group + '" data-id="' + item.id + '" title="' + escapeHtml(i18n.edit || '') + '"><span class="dashicons dashicons-edit"></span></button>';
      }
      actionsHtml += '<button type="button" class="ecf-icon-btn ecf-icon-btn--danger ecf-search-delete" data-ecf-search-delete data-group="' + item.group + '" data-id="' + item.id + '" data-label="' + escapeHtml(item.label) + '" title="' + escapeHtml(i18n.delete) + '"><span class="dashicons dashicons-trash"></span></button>';

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

  function deleteSearchItem(group, id, label) {
    var isClass = isClassGroup(group);
    if (!confirm((i18n.search_delete_confirm || '').replace('%s', label))) return;

    $.post(ecfAdmin.ajaxurl, {
      action: isClass ? 'ecf_delete_classes' : 'ecf_delete_variables',
      nonce: ecfAdmin.nonce,
      ids: [id]
    }, function(res) {
      if (!res.success) {
        alert((i18n.error || '') + res.data);
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
    getVisibleChecks(group).filter(':checked').each(function(){
      ids.push($(this).val());
    });
    if (!ids.length) { alert(i18n.none_selected); return; }
    if (!confirm(ids.length + i18n.confirm_delete)) return;

    var $btn = $(this).prop('disabled', true).addClass('is-busy');
    $.post(ecfAdmin.ajaxurl, {
      action: isClassGroup ? 'ecf_delete_classes' : 'ecf_delete_variables',
      nonce:  ecfAdmin.nonce,
      ids:    ids
    }, function(res) {
      $btn.prop('disabled', false).removeClass('is-busy');
      if (!res.success) { alert(i18n.error + res.data); return; }
      if (isClassGroup) {
        classesLoaded = false;
        loadClasses();
      } else {
        varsLoaded = false;
        loadVariables();
      }
    });
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
      minBase:   parseFloat($('[name="ecf_framework_v50[spacing][min_base]"]').val()) || 14,
      maxBase:   parseFloat($('[name="ecf_framework_v50[spacing][max_base]"]').val()) || 16,
      minRatio:  parseFloat($('[name="ecf_framework_v50[spacing][min_ratio]"]').val()) || 1.2,
      maxRatio:  parseFloat($('[name="ecf_framework_v50[spacing][max_ratio]"]').val()) || 1.25,
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
    var maxVal = 0;
    $.each(items, function(_, it) { if (parseFloat(it.max) > maxVal) maxVal = parseFloat(it.max); });
    var html = '';
    $.each(items, function(_, item) {
      var minValue = parseFloat(item.minPx);
      var maxValue = parseFloat(item.maxPx);
      var minBarPct = maxVal > 0 ? Math.round((minValue / maxVal) * 100 * 10) / 10 : 0;
      var maxBarPct = maxVal > 0 ? Math.round((maxValue / maxVal) * 100 * 10) / 10 : 0;
      var minBarH = Math.min(40, Math.max(4, Math.round(minValue)));
      var maxBarH = Math.min(40, Math.max(4, Math.round(maxValue)));
      html += '<div class="ecf-space-row' + (item.isBase ? ' is-base' : '') + '" data-ecf-space-step="' + item.step + '">'
        + '<div class="ecf-space-row__token">' + item.token
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span></div>'
        + '<div class="ecf-space-row__meta">'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><div class="ecf-clamp-metric"><strong>' + item.minPx + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + minBarPct + '%;height:' + minBarH + 'px;"></div></div>'
        + '</div>'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><div class="ecf-clamp-metric"><strong>' + item.maxPx + 'px</strong><button type="button" class="ecf-clamp-toggle" data-ecf-clamp-toggle="' + escapeHtml(i18n.copy) + '"><span class="dashicons dashicons-editor-code"></span></button></div><button type="button" class="ecf-clamp-popover" data-copy="' + escapeHtml(item.cssValue) + '">' + escapeHtml(item.cssValue) + '</button></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + maxBarPct + '%;height:' + maxBarH + 'px;"></div></div>'
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
    var $pop = $toggle.closest('div').siblings('.ecf-clamp-popover').first();
    $('.ecf-clamp-popover').not($pop).removeClass('is-open');
    $pop.toggleClass('is-open');
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
    var $field = $('[data-ecf-body-size-field]');
    if (!$field.length) return;

    var value = $.trim($field.find('[name="ecf_framework_v50[base_body_text_size_value]"]').val() || '');
    var format = $.trim($field.find('[name="ecf_framework_v50[base_body_text_size_format]"]').val() || '').toLowerCase();
    var $warning = $field.find('[data-ecf-body-size-warning]');
    var numeric = parseFloat(String(value).replace(',', '.'));
    var rootBasePx = ($('[name="ecf_framework_v50[root_font_size]"]').val() === '62.5') ? 10 : 16;
    var message = '';
    var pxEquivalent = null;

    if (!value || isNaN(numeric) || numeric <= 0 || format === 'custom') {
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
  }

  applyStarterClassFilter('all');
  applyUtilityClassFilter('all');
  switchClassLibrary('starter');
  applyClassTierFilter('all');
  refreshBemGeneratorOptions();
  renderBemGeneratorPreview();
  updateClassLibraryContext();
  updateStarterClassesState();
  updateBaseBodyTextSizeWarning();
});
