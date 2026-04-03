jQuery(function($){
  function initColorPickers(scope){
    scope.find('.ecf-color-field').wpColorPicker();
  }
  function nextIndex(group){
    return $('.ecf-table[data-group="'+group+'"] .ecf-row').length;
  }
  function inputKey(group){
    return $('.ecf-table[data-group="'+group+'"]').data('input-key') || ('ecf_framework_v50['+group+']');
  }
  initColorPickers($(document));

  $(document).on('click', '.ecf-add-row', function(){
    var group = $(this).data('group');
    var index = nextIndex(group);
    var key   = inputKey(group);
    var isColor = group === 'colors';
    var templateId = isColor ? '#ecf-row-template-color' : '#ecf-row-template-default';
    var html = $(templateId).html()
      .replace(/__NAME__/g,  key+'['+index+'][name]')
      .replace(/__VALUE__/g, key+'['+index+'][value]');
    var $row = $(html);
    $('.ecf-table[data-group="'+group+'"]').append($row);
    if (isColor) initColorPickers($row);
  });

  $(document).on('click', '.ecf-remove-row', function(){
    var $row = $(this).closest('.ecf-row');
    if ($row.find('.ecf-color-field').length) $row.find('.wp-picker-container').remove();
    $row.remove();
  });

  // ── Tab switching ──────────────────────────────────────────────
  $(document).on('click', '.ecf-tab', function(){
    var tab = $(this).data('tab');
    $('.ecf-tab').removeClass('is-active');
    $(this).addClass('is-active');
    $('.ecf-panel').removeClass('is-active');
    $('.ecf-panel[data-panel="'+tab+'"]').addClass('is-active');
    if (tab === 'variables') loadVariables();
  });

  // ── Variables Management ───────────────────────────────────────
  var varsLoaded = false;

  function typeLabel(type) {
    if (type === 'global-color-variable') return 'Color';
    if (type === 'global-size-variable')  return 'Size';
    if (type === 'global-string-variable') return 'String';
    return type;
  }

  function renderVarList(group, items) {
    var $list = $('#ecf-varlist-' + group);
    $('#ecf-badge-' + group).text(items.length);
    if (!items.length) {
      $list.html('<p style="color:#9ca3af;font-size:13px;">Keine Variablen vorhanden.</p>');
      return;
    }
    var html = '<div class="ecf-var-table">';
    html += '<div class="ecf-var-head"><span></span><span>Name</span><span>Typ</span><span>Wert</span></div>';
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
    $(this).text(allChecked ? 'Alle wählen' : 'Auswahl aufheben');
  });

  // Delete selected
  $(document).on('click', '.ecf-delete-selected', function(){
    var group = $(this).data('group');
    var ids = [];
    $('#ecf-varlist-' + group).find('.ecf-var-check:checked').each(function(){
      ids.push($(this).val());
    });
    if (!ids.length) { alert('Keine Variablen ausgewählt.'); return; }
    if (!confirm(ids.length + ' Variable(n) löschen?')) return;

    var $btn = $(this).prop('disabled', true).text('Lösche…');
    $.post(ecfAdmin.ajaxurl, {
      action: 'ecf_delete_variables',
      nonce:  ecfAdmin.nonce,
      ids:    ids
    }, function(res) {
      $btn.prop('disabled', false).text('Auswahl löschen');
      if (!res.success) { alert('Fehler: ' + res.data); return; }
      varsLoaded = false;
      loadVariables();
    });
  });

  // Row click toggles checkbox
  $(document).on('click', '.ecf-var-row', function(e){
    if ($(e.target).is('input')) return;
    $(this).find('.ecf-var-check').trigger('click');
  });
});
