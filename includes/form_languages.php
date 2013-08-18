<?php
/*  Copyright 2013  Alessandro Senese (email : senesealessandro@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
global $wpCeceppaML;

//Non posso richiamare lo script direttamente dal browser :)
if(!is_object($wpCeceppaML)) die("Access denied");

/**
* LANGUAGE SETTINGS
* Gestione delle lingue
*/
require_once "locales_codes.php";

$row = 0;
$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 0;
?>

    <div class="wrap">
<!-- Lingue -->
  <div class="icon32">
    <img src="<?php echo CECEPPA_PLUGIN_URL ?>images/logo.png" height="32"/>
  </div>
  <h2 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo $tab == 0 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-language-page&tab=0"><?php _e('Languages', 'ceceppaml') ?></a>
    <a class="nav-tab <?php echo $tab == 1 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-language-page&tab=1"><?php _e('Language files', 'ceceppaml') ?></a>
  </h2>
  <div id="poststuff">
  <div id="post-body" class="metabox-holder columns-2">
  <?php if($tab == 0) : ?>
    <div id="post-body-content">
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-language-page">
    <input type="hidden" name="form" value="languages" />
    <input type="hidden" name="action" value="add" />
    <?php wp_nonce_field('cml_edit_language','cml_nonce_edit_language'); ?>
    <input type="hidden" name="delete" id="delete" value="" />
    <table id="ceceppaml-table" class="wp-ceceppaml widefat">
    <tbody>
    <thead>
    <tr>
      <th style="width: 1%;padding: 0 5px 0 5px;"><img src="<?php echo CECEPPA_PLUGIN_URL ?>images/default.png" title="<?php _e('Default language', 'ceceppaml') ?>" class="tipsy-me"></th>
      <th style="width: 1%;padding: 0 5px 0 5px;"><img src="<?php echo CECEPPA_PLUGIN_URL ?>images/sort.png" title="<?php _e('Choose language order', 'ceceppaml') ?>" class="tipsy-me"></th>
      <th style="width: 230px;"><?php _e('Flag', 'ceceppaml') ?></th>
      <th style="width:73%"><?php _e('Name of the language ', 'ceceppaml') ?></th>
      <th style="width:5%"><img src="<?php echo CECEPPA_PLUGIN_URL ?>images/enabled.png" title="<?php _e('Enabled', 'ceceppaml') ?>?" class="tipsy-me" height="24"></th>
      <th style="width: 5%">
	<a href="#" onclick="javascript: toggleDetails(-1)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Show/Hide advanced options for languages.') ?>"></a>
      </th>
    </tr>
    </thead>
<?php
    global $wpdb;

    /*
     * La funzione hex2bin è disponibile solo da PHP >= 5.4, a quanto sembra non tutti i servizi di hosting hanno php aggiornato -.-".
     * Quindi utilizzo la funzione HEX di MySQL
     */
    $query = "SELECT id, cml_default, cml_flag, cml_language, cml_language_slug, UNHEX(cml_notice_post) as cml_notice_post, UNHEX(cml_notice_page) as cml_notice_page, cml_locale, cml_enabled, cml_sort_id FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_sort_id";
    $results = $wpdb->get_results($query);
    
    foreach($results as $result) :
?>
    <?php $alternate = @empty($alternate) ? "alternate" : ""; ?>
    <tr id="lang-<?php echo $result->id ?>" class="lang-row <?php echo $alternate ?>">
      <td rowspan="2" align="center">
	  <input type="hidden" name="id[]" value="<?php echo $result->id ?>" />
<!-- Default (radio) -->
	  <input type="radio" name="default" value="<?php echo $result->id ?>" <?php echo ($result->cml_default == 1) ? "checked" : "" ?> />
      </td>
<!--  Order  -->
      <td rowspan="2">
	  <input type="text" name="sort-id[]" value="<?php echo $result->cml_sort_id ?>" size="3" style="width: 30px" />
      </td>
<!-- Combobox lingue -->
      <td rowspan="2">
		<?php ceceppa_show_flags($_langs, $result->id, $result->cml_flag) ?>
	  </td>
<!-- nome della lingua -->
	  <td>
	    <input name="language[]" type="text" id="language-<?php echo $result->id ?>" value="<?php echo $result->cml_language; ?>" style="width: 100%">
	  </td>
<!-- Attiva lingua-->
			<td>
					<input type="checkbox" id="lang-enabled" name="lang-enabled[<?php echo $row++ ?>]" value="1" <?php echo ($result->cml_enabled == 1) ? "checked" : "" ?>/>
			</td>
      <th>
<!-- Dettagli e cancella -->
		<a href="#" onclick="javascript: toggleDetails(<?php echo $result->id ?>)"><img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/details.png" width="32" title="<?php _e('Show/Hide advanced options for languages', 'ceceppaml') ?>"></a><br />
		<a href="javascript: void(0)" class="_delete" id="_delete-<?php echo $result->id ?>" style="padding-left: 8px"><img class="delete" src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/delete.png" width="16" title="<?php _e('Delete current language', 'ceceppaml') ?>"></a>
	  </th>
    </tr>
	<tr>
	  <td colspan="5">
	    <table class="wp-list-table widefat">
	      <thead>
	      <tr>
		<th style="width:100px"><?php _e('Url slug', 'ceceppaml'); ?></th>
		<th><?php _e('Post notice', 'ceceppaml'); ?></th>
		<th><?php _e('Page notice', 'ceceppaml'); ?></th>
		<th style="width:100px"><?php _e('Locale Wordpress', 'ceceppaml') ?></th>
	      </tr>
	      </thead>
	      <tr>
		<td><input name="language_slug[]" class="_tipsy" id="slug-<?php echo $result->id ?>" value="<?php echo $result->cml_language_slug ?>" type="text" style="margin-left:2%;" title="<?php _e('Allows you to specify an abbreviation to be used in the URL of the page. <br /> Ex: <br /> www.example.com / en <br /> www.example.com / uk', 'ceceppaml') ?>" /></td>
		<td><input name="notice_post[]" class="_tipsy" type="text" value="<?php echo stripslashes($result->cml_notice_post); ?>" style="margin-left:2%; width: 100%" title="<?php _e('Define the text of the notice to be displayed when the post is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
		<td><input name="notice_page[]" class="_tipsy" type="text" value="<?php echo stripslashes($result->cml_notice_page); ?>" style="margin-left:2%; width: 100%" title="<?php _e('Define the text of the notice to be displayed when the page is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
		<td><input name="locale[]" class="_tipsy" id="locale-<?php echo $result->id ?>" type="text" value="<?php echo $result->cml_locale ?>" title="<?php _e('Helps to link correctly the defined language by the user\'s browser. ', 'ceceppaml') ?>" /></td>
	      </tr>
	    </table>
	  </td>
	</tr>
<?php
    endforeach;
?>
    <tr class="<?php echo @empty($alternate) ? "alternate" : ""; ?>">
<!-- Nuovo record -->
      <td rowspan="2">
		<input type="hidden" name="id[]" value="" />
		<input type="radio" name="default[]" />
      </td>
<!--  Order  -->
      <td>
	  <?php $max = $wpdb->get_var("SELECT max(cml_sort_id) + 1 FROM " . CECEPPA_ML_TABLE); ?>
	  <input type="text" name="sort-id[]" value="<?php echo $max ?>" size="3" style="width: 30px" />
      </td>
      <td><?php ceceppa_show_flags($_langs, "x", null) ?></td>
      <td><input name="language[]" id="language-x" type="text" style="width: 100%"></td>
      <td></td>
      <td style="text-align: center">
	<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/addlang.png" width="32" />
      </td>
    </tr>
    <tr class="<?php echo @empty($alternate) ? "alternate" : ""; ?>">
	<td colspan="5">
	    <table class="wp-list-table widefat">
	      <thead>
	      <tr>
		<th style="width:100px"><?php _e('Url slug', 'ceceppaml'); ?></th>
		<th><?php _e('Post notice', 'ceceppaml'); ?></th>
		<th><?php _e('Page notice', 'ceceppaml'); ?></th>
		<th style="width:100px"><?php _e('Locale Wordpress', 'ceceppaml') ?></th>
	      </tr>
	      </thead>
	      <tr>
		<td><input name="language_slug[]" class="_tipsy" id="slug-x" type="text" style="margin-left:2%" title="<?php _e('Allows you to specify an abbreviation to be used in the URL of the page. <br /> Ex: <br /> www.example.com / en <br /> www.example.com / uk', 'ceceppaml') ?>" /></td>
		<td><input name="notice_post[]" class="_tipsy" type="text" style="margin-left:2%; width: 100%" title="<?php _e('Define the text of the notice to be displayed when the post is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
		<td><input name="notice_page[]" class="_tipsy" type="text" style="margin-left:2%; width: 100%" title="<?php _e('Define the text of the notice to be displayed when the page is available in the visitor\'s language', 'ceceppaml'	) ?>" /></td>
		<td><input name="locale[]" class="_tipsy" id="locale-x" type="text" title="<?php _e('Helps to link correctly the defined language by the user\'s browser. ', 'ceceppaml') ?>" /></td>
	      </tr>
	    </table>
	  </td>
	</tr>
    </tbody>
    </table>
      <br />
    <div style="text-align:right;padding-right: 10px;">
      <?php submit_button(); ?>
    </div>
    <br />
    </form>
    </div>
    <?php else:
      require_once("mo-downloader.php");

      echo '<div class="ceceppa-form">';
      $d = new CMLMoDownloader();
      $d->init();
      echo '</div>';

      endif; ?>

    <!-- DONATE   -->
    <div id="postbox-container-1" class="postbox-container cml-donate">
      <?php do_meta_boxes('cml_donate_box','advanced',null); ?>
    </div>

    </div>
    </div>
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
