jQuery(function($){
  var i18n = (typeof ecfAdmin !== 'undefined' && ecfAdmin.i18n) ? ecfAdmin.i18n : {};
  i18n.copy    = i18n.copy    || 'Copy';
  i18n.copied  = i18n.copied  || 'Copied!';

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
      },
      clear: function() {
        var $row = $(this).closest('.ecf-row--color');
        $(this).val('');
        $row.find('.ecf-color-value-input').val('');
        $row.find('.ecf-color-value-display').val('');
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
  function nextLocalFontIndex() {
    return $('[data-local-font-table] .ecf-font-file-row').length;
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
  });

  $(document).on('click', '.ecf-remove-row', function(){
    var $row = $(this).closest('.ecf-row, .ecf-font-file-row');
    if ($row.find('.ecf-color-field').length) $row.find('.wp-picker-container').remove();
    $row.remove();
    renderTypePreview();
    renderShadowPreview();
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
    var $table = $('[data-local-font-table]');
    var key = $table.data('input-key');
    var index = nextLocalFontIndex();
    var html = '<div class="ecf-font-file-row">'
      + '<input type="text" name="' + key + '[' + index + '][name]" value="" placeholder="primary-regular" />'
      + '<input type="text" name="' + key + '[' + index + '][family]" value="" placeholder="Primary" />'
      + '<div class="ecf-font-file-picker">'
      + '<input type="text" class="ecf-font-file-url" name="' + key + '[' + index + '][src]" value="" placeholder="Local upload" readonly />'
      + '<button type="button" class="button ecf-font-file-select">' + ecfAdmin.i18n.select_file + '</button>'
      + '</div>'
      + '<input type="text" name="' + key + '[' + index + '][weight]" value="400" placeholder="400" />'
      + '<select name="' + key + '[' + index + '][style]"><option value="normal">normal</option><option value="italic">italic</option><option value="oblique">oblique</option></select>'
      + '<select name="' + key + '[' + index + '][display]"><option value="swap">swap</option><option value="fallback">fallback</option><option value="optional">optional</option><option value="block">block</option><option value="auto">auto</option></select>'
      + '<button type="button" class="button ecf-remove-row">×</button>'
      + '</div>';
    $table.append($(html));
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
    });

    frame.open();
  });

  function formatPreviewNumber(value) {
    var rounded = Math.round(value * 100) / 100;
    return String(rounded).replace(/\.0+$|(\.\d*[1-9])0+$/, '$1');
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

    return $.map(config.steps, function(step, i) {
      var exp = i - baseIndex;
      var maxSize = Math.round((config.maxBase * Math.pow(config.maxRatio, exp)) * 1000) / 1000;
      var minSize = Math.round((config.minBase * Math.pow(config.minRatio, exp)) * 1000) / 1000;
      var cssValue = maxSize + 'px';

      if (config.fluid && config.maxVw > config.minVw) {
        var slope = (maxSize - minSize) / (config.maxVw - config.minVw);
        var intercept = Math.round((minSize - slope * config.minVw) * 10000) / 10000;
        var slopeVw = Math.round((slope * 100) * 10000) / 10000;
        cssValue = 'clamp(' + minSize + 'px,' + slopeVw + 'vw' + (intercept >= 0 ? '+' : '') + intercept + 'px,' + maxSize + 'px)';
      } else {
        minSize = maxSize;
      }

      return {
        step: step,
        token: '--cf-text-' + step,
        min: formatPreviewNumber(minSize),
        max: formatPreviewNumber(maxSize),
        cssValue: cssValue,
        previewSize: config.fluid ? cssValue : maxSize + 'px'
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
    var labelMin = $preview.data('preview-label-min') || 'Minimum';
    var labelMax = $preview.data('preview-label-max') || 'Maximum';
    var labelFixed = $preview.data('preview-label-fixed') || 'Static';
    var labelFluid = $preview.data('preview-label-fluid') || 'Fluid';
    var previewWord = $preview.data('preview-word') || 'Typography';
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
      if (viewMode === 'min') return item.min + 'px';
      if (viewMode === 'max') return item.max + 'px';
      return item.cssValue;
    }

    function modeLabel() {
      if (viewMode === 'min') return '<i class="dashicons dashicons-smartphone"></i>' + labelMin;
      if (viewMode === 'max') return '<i class="dashicons dashicons-desktop"></i>' + labelMax;
      return config.fluid ? labelFluid : labelFixed;
    }

    $.each(items, function(_, item) {
      var selectedClass = item.step === activeStep ? ' is-active' : '';
      html += '<button type="button" class="ecf-type-row' + selectedClass + '" data-ecf-step="' + item.step + '" style="--ecf-preview-size:' + sizeForView(item) + ';">'
        + '<div class="ecf-type-row__token">' + item.token
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span>'
        + '</div>'
        + '<div class="ecf-type-row__meta">'
        + '<div><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><strong>' + item.min + 'px</strong></div>'
        + '<div><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><strong>' + item.max + 'px</strong></div>'
        + '</div>'
        + '<div class="ecf-type-row__sample">'
        + '<div class="ecf-type-row__word">' + previewWord + '</div>'
        + '<code>' + item.cssValue + '</code>'
        + '</div>'
        + '</button>';
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
    $preview.find('[data-ecf-focus-min]').text(activeItem ? activeItem.min + 'px' : '');
    $preview.find('[data-ecf-focus-max]').text(activeItem ? activeItem.max + 'px' : '');
    $preview.find('[data-ecf-focus-css]').text(activeItem ? activeItem.cssValue : '');
    $preview.find('[data-ecf-preview-view]').removeClass('is-active');
    $preview.find('[data-ecf-preview-view="' + viewMode + '"]').addClass('is-active');
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
        token: 'cf-shadow-' + slug,
        value: value
      };
    }).get();
  }

  function renderShadowPreview() {
    var $preview = $('[data-ecf-shadow-preview]');
    if (!$preview.length) return;

    var items = buildShadowPreviewItems();
    var helperText = $preview.data('preview-helper') || '';
    var previewWord = $preview.data('preview-word') || 'Shadow';
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
  var $noSavePanel = ['variables', 'sync']; // panels that don't need the save button

  function switchPanel(panel) {
    $('.ecf-nav-item').removeClass('is-active');
    $('.ecf-nav-item[data-panel="'+panel+'"]').addClass('is-active');
    $('.ecf-panel').removeClass('is-active');
    $('.ecf-panel[data-panel="'+panel+'"]').addClass('is-active');

    // show/hide save footer
    if ($noSavePanel.indexOf(panel) !== -1) {
      $('#ecf-save-footer').hide();
    } else {
      $('#ecf-save-footer').show();
    }

    if (panel === 'variables') loadVariables();
  }

  $(document).on('click', '.ecf-nav-item', function(){
    var panel = $(this).data('panel');
    switchPanel(panel);
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
    }
  });

  // Activate first panel on load
  switchPanel('tokens');
  renderTypePreview();
  renderShadowPreview();

  $(document).on('input change', '[name^="ecf_framework_v50[typography][scale]"], [name^="ecf_framework_v50[typography][fonts]"]', function(){
    renderTypePreview();
  });

  $(document).on('input change', '[name^="ecf_framework_v50[shadows]"]', function(){
    renderShadowPreview();
  });

  $(document).on('click', '[data-ecf-step]', function(){
    var $preview = $('[data-ecf-type-scale-preview]');
    $preview.attr('data-active-step', $(this).data('ecf-step'));
    renderTypePreview();
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

  function typeLabel(type) {
    if (type === 'global-color-variable')  return i18n.type_color;
    if (type === 'global-size-variable')   return i18n.type_size;
    if (type === 'global-string-variable') return i18n.type_string;
    return type;
  }

  function renderVarList(group, items) {
    var $list = $('#ecf-varlist-' + group);
    $('#ecf-badge-' + group).text(items.length);
    if (!items.length) {
      $list.html('<p style="color:#9ca3af;font-size:13px;">'+i18n.none+'</p>');
      return;
    }
    var html = '<div class="ecf-var-table">';
    html += '<div class="ecf-var-head"><span></span><span>'+i18n.col_name+'</span><span>'+i18n.col_type+'</span><span>'+i18n.col_value+'</span></div>';
    $.each(items, function(i, v) {
      var preview = '';
      if (v.type === 'global-color-variable') {
        preview = '<span class="ecf-color-dot" style="background:'+v.value+'"></span>';
      }
      html += '<div class="ecf-var-row" data-id="'+v.id+'" data-group="'+group+'">'
        + '<input type="checkbox" class="ecf-var-check" value="'+v.id+'">'
        + '<span class="ecf-var-label">'+v.label+'</span>'
        + '<span class="ecf-var-type">'+typeLabel(v.type)+'</span>'
        + '<span class="ecf-var-value">'+preview+v.value+'</span>'
        + '</div>';
    });
    html += '</div>';
    $list.html(html);
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
      renderVarList('ecf',     res.data.ecf);
      renderVarList('foreign', res.data.foreign);
      varsLoaded = true;
    });
  }

  // Select all toggle
  $(document).on('click', '.ecf-select-all', function(){
    var group = $(this).data('group');
    var $checks = $('#ecf-varlist-' + group).find('.ecf-var-check');
    var allChecked = $checks.length === $checks.filter(':checked').length;
    $checks.prop('checked', !allChecked);
    $(this).text(allChecked ? i18n.select_all : i18n.deselect_all);
  });

  // Delete selected
  $(document).on('click', '.ecf-delete-selected', function(){
    var group = $(this).data('group');
    var ids = [];
    $('#ecf-varlist-' + group).find('.ecf-var-check:checked').each(function(){
      ids.push($(this).val());
    });
    if (!ids.length) { alert(i18n.none_selected); return; }
    if (!confirm(ids.length + i18n.confirm_delete)) return;

    var $btn = $(this).prop('disabled', true).text(i18n.deleting);
    $.post(ecfAdmin.ajaxurl, {
      action: 'ecf_delete_variables',
      nonce:  ecfAdmin.nonce,
      ids:    ids
    }, function(res) {
      $btn.prop('disabled', false).text(i18n.delete_sel);
      if (!res.success) { alert(i18n.error + res.data); return; }
      varsLoaded = false;
      loadVariables();
    });
  });

  // Row click toggles checkbox
  $(document).on('click', '.ecf-var-row', function(e){
    if ($(e.target).is('input')) return;
    $(this).find('.ecf-var-check').trigger('click');
  });

  // ── Spacing Preview ────────────────────────────────────────────
  var ALL_SPACE_STEPS = ['6xs','5xs','4xs','3xs','2xs','xs','s','m','l','xl','2xl','3xl','4xl','5xl','6xl'];

  function getSpacingConfig() {
    return {
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
        var intercept = Math.round((minSize - slope * cfg.minVw) * 10000) / 10000;
        var slopeVw = Math.round(slope * 100 * 10000) / 10000;
        cssValue = 'clamp(' + minSize + 'px,' + slopeVw + 'vw' + (intercept >= 0 ? '+' : '') + intercept + 'px,' + maxSize + 'px)';
      } else {
        cssValue = maxSize + 'px';
        minSize = maxSize;
      }
      return { step: step, token: '--cf-' + cfg.prefix + '-' + step, min: formatPreviewNumber(minSize), max: formatPreviewNumber(maxSize), cssValue: cssValue, isBase: (i === baseIdx) };
    });
  }

  function renderSpacingPreview() {
    var $preview = $('[data-ecf-spacing-preview]');
    if (!$preview.length) return;
    var steps = getSpacingSteps();
    var cfg = getSpacingConfig();
    var items = buildSpacingItems(steps, cfg);
    var labelMin = $preview.data('preview-label-min') || 'Minimum';
    var labelMax = $preview.data('preview-label-max') || 'Maximum';
    var maxVal = 0;
    $.each(items, function(_, it) { if (parseFloat(it.max) > maxVal) maxVal = parseFloat(it.max); });
    var html = '';
    $.each(items, function(_, item) {
      var minValue = parseFloat(item.min);
      var maxValue = parseFloat(item.max);
      var minBarPct = maxVal > 0 ? Math.round((minValue / maxVal) * 100 * 10) / 10 : 0;
      var maxBarPct = maxVal > 0 ? Math.round((maxValue / maxVal) * 100 * 10) / 10 : 0;
      var minBarH = Math.min(40, Math.max(4, Math.round(minValue)));
      var maxBarH = Math.min(40, Math.max(4, Math.round(maxValue)));
      html += '<div class="ecf-space-row' + (item.isBase ? ' is-base' : '') + '" data-ecf-space-step="' + item.step + '">'
        + '<div class="ecf-space-row__token">' + item.token
        + '<span class="ecf-copy-pill" data-copy="' + item.token + '">' + i18n.copy + '</span></div>'
        + '<div class="ecf-space-row__meta">'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-smartphone"></i>' + labelMin + '</span><strong>' + item.min + 'px</strong></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + minBarPct + '%;height:' + minBarH + 'px;"></div></div>'
        + '</div>'
        + '<div class="ecf-space-row__metric">'
        + '<div class="ecf-space-row__metric-meta"><span><i class="dashicons dashicons-desktop"></i>' + labelMax + '</span><strong>' + item.max + 'px</strong></div>'
        + '<div class="ecf-space-row__bar"><div class="ecf-space-row__bar-fill" style="width:' + maxBarPct + '%;height:' + maxBarH + 'px;"></div></div>'
        + '</div>'
        + '</div>'
        + '</div>';
    });
    $preview.find('[data-ecf-spacing-preview-list]').html(html);
  }

  $(document).on('input change', '[name^="ecf_framework_v50[spacing]"]', function(){
    renderSpacingPreview();
  });

  renderSpacingPreview();

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
});
