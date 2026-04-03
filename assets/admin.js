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

  $(document).on('click', '.ecf-tab', function(){
    var tab = $(this).data('tab');
    $('.ecf-tab').removeClass('is-active');
    $(this).addClass('is-active');
    $('.ecf-panel').removeClass('is-active');
    $('.ecf-panel[data-panel="'+tab+'"]').addClass('is-active');
  });
});
