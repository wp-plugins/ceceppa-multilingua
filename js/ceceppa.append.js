/* Modifico la voce action della classe formsearch */
function isEmpty( inputStr ) { if ( null == inputStr || "" == inputStr ) { return true; } return false; }

jQuery(document).ready(function() {
  $flags = jQuery('.cml_append_flags');
  $flags.show();
  jQuery(cml_append_to.element).append($flags);
});
