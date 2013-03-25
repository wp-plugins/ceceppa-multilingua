<?php
	/**
	 * LANGUAGE SETTINGS
	 * Gestione delle lingue
	 */
    require_once "locales_codes.php";
		
		$row = 0;
?>

    <div class="wrap">
<!-- Lingue -->
    <h2><?php _e('Languages settings:', 'ceceppaml') ?></h2>
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-language-page">
    <input type="hidden" name="form" value="languages" />
    <input type="hidden" name="action" value="add" />
	<input type="hidden" name="delete" id="delete" value="" />
	<div class="CSSTableGenerator">
    <table id="ceceppaml-table" class="ceceppaml">
    <tbody>
    <tr>
      <td style="width: 1%;"><?php _e('Default', 'ceceppaml') ?></td>
      <td style="width: 5%;"><?php _e('Flag', 'ceceppaml') ?></td>
      <td style="width:15%"><?php _e('Name of the language ', 'ceceppaml') ?></td>
      <td style="width:12%"><?php _e('Main page', 'ceceppaml') ?></td>
      <td style="width:10%"><?php _e('Main category', 'ceceppaml') ?></td>
      <td style="width:5%"><?php _e('Enabled', 'ceceppaml') ?></td>
      <td style="width: 5%">
	  	<a href="#" onclick="javascript: toggleDetails(-1)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Show/Hide advanced options for languages.') ?>"></a>
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
      <option data-image="<?php echo WP_PLUGIN_URL . "/ceceppa-multilingua/images/no.png"; ?>"><?php _e('No related page', 'ceceppamp'); ?></option>
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
      <td><?php ceceppa_show_category($result->cml_category_id, $row) ?></td>
<!-- Attiva lingua-->
			<td>
					<input type="checkbox" id="lang-enabled" name="lang-enabled[<?php echo $row++ ?>]" value="1" <?php echo ($result->cml_enabled == 1) ? "checked" : "" ?>/>
			</td>
      <th>
<!-- Dettagli e cancella -->
		<a href="#" onclick="javascript: toggleDetails(<?php echo $result->id ?>)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Show/Hide advanced options for languages', 'ceceppaml') ?>"></a>
		<a href="javascript: void(0)" class="_delete" id="_delete-<?php echo $result->id ?>"><img class="delete" src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/delete.png" width="32" title="<?php _e('Delete the selected language', 'ceceppaml') ?>"></a>
	  </th>
    </tr>
	<tr>
		<td colspan="5">
			<table>
				<tr>
					<td style="background: none; font-size: 1.1em;"><?php _e('Url slug', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Post notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Page notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Category notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Locale Wordpress', 'ceceppaml') ?></td>
				</tr>
				<tr>
					<td><input name="language_slug[]" class="_tipsy" id="slug-<?php echo $result->id ?>" value="<?php echo utf8_decode($result->cml_language_slug) ?>" type="text" style="margin-left:2%;width:98%" title="<?php _e('Allows you to specify an abbreviation to be used in the URL of the page. <br /> Ex: <br /> www.example.com / en <br /> www.example.com / uk', 'ceceppaml') ?>" /></td>
					<td><input name="notice_post[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_post); ?>" style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the post is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="notice_page[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_page); ?>" style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the page is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="notice_category[]" class="_tipsy" type="text" value="<?php echo utf8_decode($result->cml_notice_category); ?>"  style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the category is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="locale[]" class="_tipsy" id="locale-<?php echo $result->id ?>" type="text" value="<?php echo $result->cml_locale ?>" title="<?php _e('Helps to link correctly the defined language by the user\'s browser. ') ?>" /></td>
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
		<option value=""><?php _e('Related page', 'ceceppaml') ?></option>
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
		<option value=""><?php _e('Related category', 'ceceppaml') ?></option>
      <?php
	$categories = get_categories(array('hide_empty' => 0));

	foreach($categories as $category) { 
	  if($category->parent == 0) {
	    echo "\t\t<option value=\"$category->cat_ID\">$category->name</option>\n";
	  }
	}
      ?>
      </select></td>
		<td></td>
	  <td style="text-align: center">
	  	<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/addlang.png" width="32" />
	  </td>
    </tr>
	<tr>
		<td colspan="5">
			<table>
				<tr>
					<td style="background: none; font-size: 1.1em;"><?php _e('Url slug', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Post notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Page notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Category notice', 'ceceppaml'); ?></td>
					<td style="background: none; font-size: 1.1em;"><?php _e('Locale Wordpress', 'ceceppaml') ?></td>
				</tr>
				<tr>
					<td><input name="language_slug[]" class="_tipsy" id="slug-x" type="text" style="margin-left:2%;width:98%" title="<?php _e('Allows you to specify an abbreviation to be used in the URL of the page. <br /> Ex: <br /> www.example.com / en <br /> www.example.com / uk', 'ceceppaml') ?>" /></td>
					<td><input name="notice_post[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the post is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="notice_page[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the page is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="notice_category[]" class="_tipsy" type="text" style="margin-left:2%;width:98%" title="<?php _e('Define the text of the notice to be displayed when the category is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
					<td><input name="locale[]" class="_tipsy" id="locale-x" type="text" title="<?php _e('Helps to link correctly the defined language by the user\'s browser. ') ?>" /></td>
				</tr>
			</table>
		</td>
	</tr>
    </tbody>
    </table>
	<br />
    <div style="text-align:right;padding-right: 10px;">
        <input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Update', 'ceceppaml') ?>" />
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
    echo "<option>" . __('Choose the language', 'ceceppaml') . "</option>\n";

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
   function ceceppa_show_category($id, $row) {
      echo "\n\t<select name=\"category[$row]\" id=\"category$id-$row\">\n";
      echo "\t\t<option data-image=\"" . WP_PLUGIN_URL . "/ceceppa-multilingua/images/no.png\">" . __('No related category', 'ceceppamp') . "</option>\n";

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