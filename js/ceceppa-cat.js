/*
 * Un modo abbastanza "grezzo" per aggiornare l'elenco delle categorie create
 * senza dover per forza ricaricare la pagina...
 */
jQuery("form#addtag #submit").click(function() {
  //jQuery(".cat_lang").val(0);   //Resetto "lingua categoria"

  //Aggiorno l'elenco "Collega alla categoria"
  $e = jQuery('select#parent option').length;
  setTimeout(function() { refreshCat($e) }, 100);
});

function refreshCat($elements) {
  if(jQuery("div").hasClass('form-invalid')) return;
//  console.log($elements, jQuery('select#parent option').length);
  if(jQuery('select#parent option').length != $elements) {
    jQuery('select#linked_cat option').remove();
//    $select = jQuery('select#parent').clone().attr('id', 'linked_cat').css('name', 'linked_cat');
    $select = jQuery('select#parent option').clone().appendTo('select#linked_cat');
//    $select.appendTo('div.form-field-link');
  } else
    setTimeout(function() { refreshCat($elements) }, 100);
}

jQuery(document).ready(function() {
  var $tag = jQuery("#addtag").find("div")[0];
  var isEdit = jQuery(jQuery("#edittag").find("tr")[0]).length > 0;
  var $titles = jQuery(".cml-form-field");
  
  if(!isEdit) 
    jQuery($tag).append($titles);
  else {
    $titles.each(function(index) {
      $tag = jQuery("#edittag").find("table tbody tr")[index];
      jQuery(this).insertAfter($tag);
    });
  }
});
