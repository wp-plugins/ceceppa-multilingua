/* Modifico la voce action della classe formsearch */
function isEmpty( inputStr ) { if ( null == inputStr || "" == inputStr ) { return true; } return false; }

jQuery(document).ready(function() {
  var input = jQuery('<input type="hidden" name="lang" value="' + cml_object.lang + '" />');

  $form = jQuery( cml_object.form_class );
  $form.append(input);
});
