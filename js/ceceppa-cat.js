/*
 * Un modo abbastanza "grezzo" per aggiornare l'elenco delle categorie create
 * senza dover per forza ricaricare la pagina...
 */
jQuery("form#addtag #submit").click(function() {
  jQuery(".cat_lang").val(0);   //Resetto "lingua categoria"
  
  //Aggiorno l'elenco "Collega alla categoria"
  $e = jQuery('select#parent option').length;
  setTimeout(function() { refreshCat($e) }, 100);
});

function refreshCat($elements) {
  if(jQuery("div").hasClass('form-invalid')) return;
//  console.log($elements, jQuery('select#parent option').length);
  if(jQuery('select#parent option').length != $elements) {
    jQuery('select#linked_cat').remove();
    $select = jQuery('select#parent').clone().attr('id', 'linked_cat').css('name', 'linked_cat');
    console.log($select);
    $select.appendTo('div.form-field-link');
  } else
    setTimeout(function() { refreshCat($elements) }, 100);
}