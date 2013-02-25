<?php
	/**
	 * LANGUAGE SETTINGS
	 * Gestione delle lingue
	 */
    require_once "locales_codes.php";
?>

    <div class="wrap">
<!-- Lingue -->
    <h2><?php _e('Configurazione Lingue:', 'ceceppaml') ?></h2>
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-language-page">
    <input type="hidden" name="form" value="languages" />
    <input type="hidden" name="action" value="add" />
	<input type="hidden" name="delete" id="delete" value="" />
	<div class="CSSTableGenerator">
    <table id="ceceppaml-table" class="ceceppaml">
    <tbody>
    <tr>
      <td style="width: 5%;"><?php _e('Predefinita', 'ceceppaml') ?></td>
      <td style="width: 5%;"><?php _e('Bandiera', 'ceceppaml') ?></td>
      <td style="width:15%"><?php _e('Nome della lingua', 'ceceppaml') ?></td>
      <td style="width:12%"><?php _e('Pagina base', 'ceceppaml') ?></td>
      <td style="width:10%"><?php _e('Categoria base', 'ceceppaml') ?></td>
      <td style="width: 5%">
	  	<a href="#" onclick="javascript: toggleDetails(-1)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Visualizza/Nascondi le opzioni avanzate delle lingue.') ?>"></a>
	  </td>
    </tr>
<?php
    global $wpdb;
    
    $query = "SELECT * FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_language DESC";
    $results = $wpdb->get_results($query);
    
    foreach($results as $result) :
?>
    <tr id="lang-<?php echo $result->id ?>" class="lang-row">
      <td rowspan="2">
	  <input type="hidden" name="id[]" value="<?php echo $result->id ?>" />
<!-- Default (radio) -->
	  <input type="radio" name="default" value="<?php echo $result->id ?>" <?php echo ($result->cml_default == 1) ? "checked" : "" ?> />
      </td>
<!-- Combobox lingue -->
      <td rowspan="2">
		<?php ceceppa_show_flags($_langs, $result->id, $result->cml_flag) ?>
	  </td>
<!-- nome della lingua -->
	  <td>
	    <input name="language[]" type="text" id="language-<?php echo $result->id ?>" value="<?php echo $result->cml_language; ?>">
	  </td>
<!-- combobox pagine disponibili -->
      <td><select name="page[]" id="page<?php echo $result->id ?>">
      <option data-image="<?php echo WP_PLUGIN_URL . "/ceceppa-multilingua/images/no.png"; ?>"><?php _e('Nessuna pagina associata', 'ceceppamp'); ?></option>
      <?php
	$pages = get_pages(array('hide_empty' => 0));
	$s = $result->cml_page_id;

	foreach($pages as $page) { 
	  if($page->post_parent == 0) {
	    $selected = ($s == $page->ID) ? "selected" : "";

	    echo "<option value=\"$page->ID\" $selected>$page->post_title</option>";
	  }
	}
      ?>
      </select></td>
<!-- combobox categorie -->
      <td><?php ceceppa_show_category($result->cml_category_id) ?></td>
      <th>
<!-- Dettagli e cancella -->
		<a href="#" onclick="javascript: toggleDetails(<?php echo $result->id ?>)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Visualizza/Nascondi opzioni avanzate della lingua.', 'ceceppaml') ?>"></a>
		<a href="javascript: void(0)" class="_delete" id="_delete-<?php echo $result->id ?>"><img class="delete" src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/delete.png" width="32" title="<?php _e('Cancella la lingua selezionata', 'ceceppaml') ?>"></a>
	  </th>
    </tr>
	<tr>
		<td colspan="5">
			<table>
				<tr>
					<td style="background: none; font-size: 1.1em;"><?php _e('Abbreviazione url', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso articolo', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso pagina', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso categoria', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Locale Wordpress', 'ceceppaml') ?></td>
				</tr>
				<tr>
					<td><input name="language_slug[]" class="_tipsy" id="slug-<?php echo $result->id ?>" value="<?php echo utf8_decode($result->cml_language_slug) ?>" type="text" style="margin-left:2%;width:98%" title="<?php _e('Permette di specificare l\'abbreviazione da utilizzare all\'interno dell\'Url della pagina.<br />Es: <br />www.example.com/it<br />www.example.com/uk', 'ceceppaml') ?>" /></td>
					<td><input name="notice_post[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_post); ?>" style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando l\'articolo &egrave; disponibile nella lingua del visitatore') ?>" /></td>
					<td><input name="notice_page[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_page); ?>" style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando la pagina &egrave; disponibile nella lingua del visitatore') ?>" /></td>
					<td><input name="notice_category[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_category); ?>"  style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando la categoria &egrave; disponibile nella lingua del visitatore') ?>" /></td>
					<td><input name="locale[]" class="_tipsy" id="locale-<?php echo $result->id ?>" type="text" value="<?php echo $result->cml_locale ?>" title="<?php _e('Serve ad abbinare correttamente la lingua definita, con quella del browser dell\'utente.') ?>" /></td>
				</tr>
			</table>
		</td>
	</tr>
<?php
    endforeach;
?>
    <tr>
<!-- Nuovo record -->
      <td rowspan="2">
		<input type="hidden" name="id[]" value="" />
		<input type="radio" name="default[]" />
      </td>
      <td><?php ceceppa_show_flags($_langs, "x", null) ?></td>
      <td><input name="language[]" id="language-x" type="text"></td>
      <td>
		<select name="page[]" id="page-x">
		<option value=""><?php _e('Pagina da associare', 'ceceppaml') ?></option>
      <?php
	$pages = get_pages(array('hide_empty' => 0));

	foreach($pages as $page) { 
	  if($page->post_parent == 0) {
	    echo "<option value=\"$page->ID\">$page->post_title</option>";
	  }
	}
      ?>
      </select></td>
      <td>
		<select name="category[]" id="category-x">
		<option value=""><?php _e('Categoria da associare', 'ceceppaml') ?></option>
      <?php
	$categories = get_categories(array('hide_empty' => 0));

	foreach($categories as $category) { 
	  if($category->parent == 0) {
	    echo "\t\t<option value=\"$category->cat_ID\">$category->name</option>\n";
	  }
	}
      ?>
      </select></td>
	  <td style="text-align: center">
	  	<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/addlang.png" width="32" />
	  </td>
    </tr>
	<tr>
		<td colspan="5">
			<table>
				<tr>
					<td style="background: none; font-size: 1.1em;"><?php _e('Abbreviazione url', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso articolo', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso pagina', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Avviso categoria', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Locale Wordpress', 'ceceppaml') ?></td>
				</tr>
				<tr>
					<td><input name="language_slug[]" class="_tipsy" id="slug-x" type="text" style="margin-left:2%;width:98%" title="<?php _e('Permette di specificare l\'abbreviazione da utilizzare all\'interno dell\'Url della pagina.<br />Es: <br />www.example.com/it<br />www.example.com/uk', 'ceceppaml') ?>" /></td>
					<td><input name="notice_post[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando l\'articolo &egrave; disponibile nella lingua del visitatore', 'ceceppaml') ?>" /></td>
					<td><input name="notice_page[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando la pagina &egrave; disponibile nella lingua del visitatore', 'ceceppaml') ?>" /></td>
					<td><input name="notice_category[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Definisci il testo dell\'avviso da visualizzare quando la categoria &egrave; disponibile nella lingua del visitatore', 'ceceppaml') ?>" /></td>
					<td><input name="locale[]" class="_tipsy" id="locale-x" type="text" title="<?php _e('Serve ad abbinare correttamente la lingua definita, con quella del browser dell\'utente.', 'ceceppaml') ?>" /></td>
				</tr>
			</table>
		</td>
	</tr>
    </tbody>
    </table>
	<br />
    <div style="text-align:right;padding-right: 10px;">
        <input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Aggiorna', 'ceceppaml') ?>" />
    </div>
	<br />
	</div>
    </form>

    <br />
</div>
<?php

  /**
   * Bandierine :)
   */
  function ceceppa_show_flags($_langs, $id, $item) {
    $path = WP_PLUGIN_URL . "/ceceppa-multilingua";

    echo "<select id=\"flags-$id\" name=\"flags[]\" class=\"ceceppa-ml-flags\">\n";
    echo "<option>" . __('Scegli la linga', 'ceceppaml') . "</option>\n";

    $keys = array_keys($_langs);
    foreach($keys as $key) :
      $selected = ($item == $_langs[$key]) ? "selected" : "";

	  //L'identificativo della lingua, it, en, uk.. sono le ultime 2 lettere del locale
      $slug = strtolower(substr($_langs[$key], -2));
      $img = "$path/flags/small/$_langs[$key].png";
      echo "<option $selected data-image='$img' value='$_langs[$key]@$slug'>$key</option>\n";
    endforeach;

    echo "</select>\n";
  }
  
  /** 
   * Categorie
   */
   function ceceppa_show_category($id) {
      echo "\n\t<select name=\"category[]\" id=\"category$id\">\n";
      echo "\t\t<option data-image=\"" . WP_PLUGIN_URL . "/ceceppa-multilingua/images/no.png\">" . __('Nessuna categoria associata', 'ceceppamp') . "</option>\n";

      $categories=get_categories(array('hide_empty' => 0));

      foreach($categories as $category) :
	$selected = ($category->cat_ID == $id) ? "selected" : "";

	if($category->parent == 0) {
	  echo "\t\t<option $selected value=\"$category->cat_ID\">$category->name</option>\n";
	}
      endforeach;

      echo "\t</select>\n";
   }
?>