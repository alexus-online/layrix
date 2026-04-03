jQuery(window).on('elementor:init', function() {
  function getTextareaFromChip($chip) {
    var $section = $chip.closest('.elementor-control-raw-html, .elementor-control');
    var $panel = $section.closest('.elementor-panel');
    return $panel.find('textarea[data-setting="ecf_classes"]');
  }
  jQuery(document).on('click', '.ecf-chip', function(e){
    e.preventDefault();
    var $chip = jQuery(this);
    var cls = $chip.data('ecf-class');
    var $textarea = getTextareaFromChip($chip);
    if (!$textarea.length) return;
    var current = ($textarea.val() || '').trim();
    var list = current ? current.split(/\s+/) : [];
    if (list.indexOf(cls) === -1) list.push(cls);
    $textarea.val(list.join(' ')).trigger('input').trigger('change');
  });
});