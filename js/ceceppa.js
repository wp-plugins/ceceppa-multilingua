function toggleDetails(index) {
  jQuery('#ceceppaml-table').find('.lang-row').each(function() {
    $this = jQuery(this);
    var id = $this.attr('id');
    var row = id.split('-');

    //Se index == -1, controllo che l'utente abbia fornito le descrizioni degli avvisi :)
    if(index < 0 || row[1] == index) {
      //Trovo la riga successiva
      var $next = $this.next('tr');
      $next.toggle();

      var ok = ($next.find('input:text[value=""]').length == 0);
	//Rimuovo/aggiungo rowspan senno succede un casino :)
	if($next.is(":visible")) {
	    $this.find('td:first').attr('rowspan', '2')
	} else {
	    $this.find('td[rowspan="2"]').removeAttr('rowspan');
	}
    }
  });
}

function addRow(count, lid) {
  $table = jQuery("table.ceceppaml");
  $tr = jQuery("<tr>");
  
  //Stringa
  $td = jQuery("<td>");
    $hidden = jQuery("<input>").attr('type', 'hidden').attr('name', 'id[]');
    $td.append($hidden);

    $input = jQuery("<input>").attr('type', 'text').attr('name', 'string[]');
    $td.append($input);
    $tr.append($td);

  id = lid.split(',');
  row = $table.find("tr").length - 1;
  for(var i = 0; i < count; i++) {
    $td = jQuery("<td>");

    $hidden = jQuery("<input>").attr('type', 'hidden').attr('name', 'lang_id[][]').attr('value', id[i]);
    $td.append($hidden);

    $input = jQuery("<input>").attr('type', 'text').attr('name', 'value[][]');
    $td.append($input);
    $tr.append($td);
  }

  $table.append($tr);
}

jQuery(document).ready(function(e) {
	//DropDown
	jQuery(".linked_post").msDropDown();
	jQuery(".link-category").msDropDown();
	jQuery(".page_lang").msDropDown();
	jQuery(".post_lang").msDropDown();
	jQuery('.linked_page').msDropDown();
	jQuery(".ceceppa-form select").msDropDown();
	jQuery(".cml-widget-flags").msDropDown();

	//Tooltip
	jQuery('img').tipsy({gravity: 'e'});
	jQuery('._tipsy').tipsy({gravity: 'n', html: true});

	//Delete language
	jQuery('a._delete').click(function() {
		if(!confirm('Cancella la lingua selezionata?'))
			return false;
		else {
			var id = jQuery(this).attr('id');
			var ids = id.split("-");

			jQuery('input#delete').attr('value', ids[1]);
			jQuery('form.ceceppa-form').submit();
		}
	});

	//Get language name and locale from dropdown list
	jQuery('.ceceppa-ml-flags').change(function() {
		var $this = jQuery(this);
		var val = $this.val();
		var ids = $this.attr('id');
		
		//Locale and Id
		var locale = val.split('@');
		var id = ids.split('-');

		//Testo dell'elemento selezionato 
		var text = $this.children("option:selected").text();

		//Set language name
		jQuery('#language-' + id[1]).val(text);

		//Url slug, come predefinito considero le prime 2 cifre del "locale"
		var loc = locale[0];
		var l = loc.split("_");
		jQuery('#slug-' + id[1]).val(l[0].toLowerCase());

		//Set locale
		jQuery('#locale-' + id[1]).val(locale[0]);
	});

	//Toggle all details
	toggleDetails(-1);
	
	//Remove button
	//jQuery('img.delete').
});