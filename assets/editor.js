jQuery(window).on('elementor:init', function() {
  var $doc = jQuery(document);
  var activeVariableContext = null;

  function inferVariableTypes(settingName) {
    var key = String(settingName || '').toLowerCase();
    if (!key) return null;

    var colorHints = ['color', 'background', 'overlay', 'fill', 'stroke'];
    var textSizeHints = ['font_size', 'font-size', 'typography_typography'];
    var spacingHints = ['padding', 'margin', 'gap', 'space_between', 'space-between', 'column_gap', 'row_gap'];
    var radiusHints = ['radius', 'border_radius', 'border-radius'];
    var genericSizeHints = ['size', 'width', 'height', 'top', 'right', 'bottom', 'left'];
    var stringHints = ['shadow', 'font_family', 'font-family', 'font_weight', 'font-weight'];

    if (colorHints.some(function(hint) { return key.indexOf(hint) !== -1; })) return ['color'];
    if (textSizeHints.some(function(hint) { return key.indexOf(hint) !== -1; })) return ['text'];
    if (spacingHints.some(function(hint) { return key.indexOf(hint) !== -1; })) return ['space'];
    if (radiusHints.some(function(hint) { return key.indexOf(hint) !== -1; })) return ['radius'];

    if (genericSizeHints.some(function(hint) { return key.indexOf(hint) !== -1; })) {
      return ['space', 'radius', 'text', 'size'];
    }

    if (stringHints.some(function(hint) { return key.indexOf(hint) !== -1; })) {
      return key.indexOf('shadow') !== -1 ? ['shadow', 'string'] : ['string', 'text'];
    }

    return null;
  }

  function extractSettingName($control) {
    if (!$control.length) return '';

    var ownSetting = $control.attr('data-setting') || $control.data('setting');
    if (ownSetting) return ownSetting;

    var $field = $control.find('[data-setting]').first();
    return $field.attr('data-setting') || $field.data('setting') || '';
  }

  function updateActiveVariableContext(target) {
    var $control = jQuery(target).closest('.elementor-control');
    if (!$control.length) return;

    var settingName = extractSettingName($control);
    var allowedTypes = inferVariableTypes(settingName);
    if (!allowedTypes) return;

    activeVariableContext = {
      setting: settingName,
      allowedTypes: allowedTypes
    };
  }

  function classifyVariableText(text) {
    var value = String(text || '').toLowerCase();
    if (!value) return null;

    if (value.indexOf('ecf-text-') !== -1 || value.indexOf('--ecf-text-') !== -1 || value.indexOf('cf-text-') !== -1) return 'text';
    if (value.indexOf('ecf-space-') !== -1 || value.indexOf('--ecf-space-') !== -1 || value.indexOf('cf-space-') !== -1) return 'space';
    if (value.indexOf('ecf-radius-') !== -1 || value.indexOf('--ecf-radius-') !== -1 || value.indexOf('cf-radius-') !== -1) return 'radius';
    if (value.indexOf('ecf-color-') !== -1 || value.indexOf('--ecf-color-') !== -1 || value.indexOf('cf-color-') !== -1 || value.indexOf('global-color-variable') !== -1 || value.indexOf(' farbe') !== -1 || value.indexOf('color') !== -1) return 'color';
    if (value.indexOf('global-size-variable') !== -1 || value.indexOf('größe') !== -1 || value.indexOf('groesse') !== -1 || value.indexOf('size') !== -1) return 'size';
    if (value.indexOf('ecf-shadow-') !== -1 || value.indexOf('--ecf-shadow-') !== -1 || value.indexOf('cf-shadow-') !== -1 || value.indexOf('global-string-variable') !== -1 || value.indexOf('shadow') !== -1 || value.indexOf('string') !== -1) return 'string';

    return null;
  }

  function filterVisibleVariablePickers() {
    if (!window.ecfEditor || !window.ecfEditor.variableTypeFilterEnabled || !activeVariableContext || !activeVariableContext.allowedTypes) {
      return;
    }

    var scopes = window.ecfEditor.variableTypeFilterScopes || {};
    var enabledTypes = activeVariableContext.allowedTypes.filter(function(type) {
      return scopes[type] !== false;
    });

    if (!enabledTypes.length) return;

    var selector = [
      '[class*="variable"][class*="picker"]:visible',
      '[class*="Variable"][class*="Picker"]:visible',
      '.dialog-widget:visible:has([data-variable-id], [data-variable-name])',
      '.ui-dialog:visible:has([data-variable-id], [data-variable-name])',
      '.MuiPopover-root:visible:has([data-variable-id], [data-variable-name])',
      '.MuiModal-root:visible:has([data-variable-id], [data-variable-name])'
    ].join(', ');

    jQuery(selector).each(function() {
      var $picker = jQuery(this);
      var $items = $picker.find('[data-variable-id], [data-variable-name]');

      $items.each(function() {
        var $item = jQuery(this);
        var detectedType = classifyVariableText($item.text());
        if (!detectedType) return;

        $item.toggle(enabledTypes.indexOf(detectedType) !== -1);
      });
    });
  }

  function scheduleVariablePickerFilter() {
    window.setTimeout(filterVisibleVariablePickers, 0);
    window.setTimeout(filterVisibleVariablePickers, 120);
    window.setTimeout(filterVisibleVariablePickers, 320);
  }

  $doc.on('focusin click mousedown', '.elementor-control [data-setting], .elementor-control input, .elementor-control textarea, .elementor-control select, .elementor-control button', function() {
    updateActiveVariableContext(this);
    scheduleVariablePickerFilter();
  });
});
