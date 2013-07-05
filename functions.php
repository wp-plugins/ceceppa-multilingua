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

/**
 * Restituisco l'id della lingua impostata come predefinita
 */
function cml_get_default_language_id() {
  global $wpdb;

  return $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
}

/**
 * Restituisco un array contenente le informazioni sulle lingue configurate
 *
 * La struttura dell'array è la seguente:
 *	id - id della lingua
 *	cml_default - 1 o 0 a seconda se la lingua è quella predefinita o meno
 *	cml_flag    - nome della bandiera
 *	cml_notice_post - avviso "articolo"
 *	cml_notice_page - avviso "pagina"
 *	cml_notice_category - avviso "categoria"
 *	cml_category_name - nome della categoria a cui è collegata la lingua
 *	cml_category_id - id della categoria collegata alla lingua
 *	cml_category_slug - abbreviazione della categoria collegata alla lingua
 *	cml_locale - locale wordpress della lingua
 *	cml_page_id - id della pagina padre collegata alla lingua
 *	cml_page_slug - abbreviazione della pagina collegata alla lingua
 */
function cml_get_languages($enabled = 1, $default = 1) {
  global $wpdb;

  return $wpdb->get_results(sprintf("SELECT * FROM %s WHERE cml_enabled >= %d AND cml_default <= %d",
    					CECEPPA_ML_TABLE, $enabled, $default));
}

/**
 * Elenco delle lingue disponibili
 */
function cml_get_languages_list() {
  global $wpdb;

  $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE);
  
  $array = array();
  foreach($results as $result) {
    $array[$result->cml_category_slug] = $result>cml_language;
  }
  
  return $array;
  
}

/**
 * Restituisco il percorso della bandiera
 */
function cml_get_flag($flag, $size = "tiny") {
  if(empty($flag)) return "";

  return CECEPPA_PLUGIN_URL . "flags/$size/$flag.png";
}

/**
 * Restituisco il percorso della bandiera in base all'id della lingua
 *
 * @param id - id della lingua
 * @param size - dimensione della bandiera:
 * 						tiny
 * 						small
 */
function cml_get_flag_by_lang_id($id, $size = "tiny") {
  global $wpdb;

  $flag = $wpdb->get_var("SELECT cml_flag FROM " . CECEPPA_ML_TABLE . " WHERE id = " . intval($id));

  return cml_get_flag($flag, $size);
}

/**
 * Restituisco il percorso della bandiera in base allo "slug" della lingua
 */
function cml_get_flag_by_lang_slug($slug, $size = "tiny") {
  global $wpdb;

  $flag = $wpdb->get_var(sprintf("SELECT cml_flag FROM %s WHERE cml_category_slug = '%s'",
				  CECEPPA_ML_TABLE, $slug));

  return cml_get_flag($flag, $size);
}

/**
 * Restituisco il numero di lingue disponibili
 */
function cml_get_flags_count() {
  global $wpdb;
  
  return $wpdb->get_var("SELECT COUNT(*) FROM " . CECEPPA_ML_TABLE);
}

/**
 * Restituisco il menù da utilizzare in base alla lingua "attuale"
 */
function cml_get_menu() {
  global $wpCeceppaML;

  //Restituisco il nome del menù da utilizzare a seconda della lingua
  $lang = $wpCeceppaML->get_current_lang();

  if(cml_is_default_lang())
    return "primary-menu";
  else
    return "cml_menu_$lang";
}

/**
 * Controllo se la lingua attuale è quella di default
 *
 * @return True/False a seconda se la lingua corrente è quella impostata come predefinita
 */
function cml_is_default_lang($lang = null) {
  global $wpCeceppaML;

  $default = $wpCeceppaML->get_default_lang_id();
  if(empty($lang)) $lang = $wpCeceppaML->get_current_lang_id();

  return $lang == $default;
}

/**
 * Restituisco l'elenco delle bandierine in formato <ul><li>....</li></ul>
 *
 * @param $show - indica se visualizzare anche il nome della lingua. I valori possibili sono:
 *			"flag" - visualizza solo la bandiera
*      			"text" - visualizza solo il nome della lingua
 *			"both" - visualizza sia la bandiera che il nome della lingua
 * @param $size - dimensione della bandierina da visualizzare. I valori possibili sono:
 *			"tiny" - 20x12
 *			"small" - 80x55
 * @param "class_nam" - nome della classe da assegnare alla lista <ul></ul>
 * @param "echo" - indica se false non esegue il costrutto echo ma ritornerò la lista <ul> che ho creato
 * @param "linked" - se true: la bandiera deve restituire il link all'articolo nelle varie lingue (se presente),
* 				false: la bandiera punterà alla home aggiungendo il suffisso "?lang=##"
 */
function cml_show_flags($show = "flag", $size = "tiny", $class_name = "cml_flags", $image_class = "", $echo = true, $linked = true) {
  global $wpdb, $wpCeceppaML;

  $redirect = get_option('cml_option_redirect');
  $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " WHERE cml_enabled = 1 ORDER BY cml_language");  
  $width = ($size == "tiny") ? 16 : 32;

  $r = "<ul class='$class_name'>";
  foreach($results as $result) :
    $lang = ($show == "flag") ? "" : $result->cml_language;

    if(is_home()) {
        //Se stò nella home vuol dire che ho scelto come metodo di reindirizzamento &lang
    	$link = "?lang=$result->cml_language_slug";
    } else {
      /* Collego la categoria della lingua attuale con quella della linga della bandierina */
      $link = "";

      $lang_id = $wpCeceppaML->get_current_lang_id();

      if($linked) {
	//Collego gli articoli delle varie pagine
	if(is_single() || is_page()) {
	  $link = cml_get_linked_post($lang_id, $result, get_the_ID());

	  if(!empty($link)) $link = get_permalink($link);
	}
	
	if(is_archive() && !is_category()) {
	  global $wp;

	  $link = home_url($wp->request) . "/";
	  $link = add_query_arg(array("lang" => $result->cml_language_slug), $link);
	}

	//Collego le categorie delle varie lingue
	if(is_category()) {
	  $cat = get_the_category();

	  if(is_array($cat)) :
	    $cat_id = $cat[count($cat) - 1]->term_id;
	    
	    $wpCeceppaML->force_category_lang($result->id);
	    $link = get_category_link($cat_id);
	  endif;
	  
	  $wpCeceppaML->unset_category_lang();
	}
      }
      
      /* Controllo se è stata impostata una pagina statica,
         perché così invece di restituire il link dell'articolo collegato
         aggiungo il più "bello" ?lang=## alla fine della home.
         
         Se non ho trovato nesuna traduzione per l'articolo, la bandiera punterà alla homepage
      */
      if(cml_is_homepage() || empty($link)) :
	$sp = "";
	$use_static= (get_option("page_for_posts") > 0) ||
		      (get_option("page_on_front") > 0);

	if($use_static && $result->id != cml_get_default_language_id()) $sp = "&sp=1";

	$link = home_url() . "/?lang=$result->cml_language_slug" . $sp;
      endif;
    }

    $img = "<img class=\"$size $image_class\" src='" . cml_get_flag_by_lang_id($result->id, $size) . "' title='$result->cml_language' width=\"$width\"/>";
    if($show == "text") $img = "";

    $r .= "<li><a href='$link'>$img$lang</a></li>";
  endforeach;

  $r .= "</ul>";

  if($echo) 
    echo $r;
  else
    return $r;
}

/**
 * cerco nel database la traduzione per la frase
 *
 *  @param string - stringa da cercare
 *  @param id - id della lingua in cui tradurre la frase
 *
 *  @return - la frase tradotta se esiste la traduzione, altrimeni la stringa passata
 */
function cml_translate($string, $id, $type = "") {
  global $wpdb;

  $query = sprintf("SELECT UNHEX(cml_translation) FROM %s WHERE cml_text = '%s' AND cml_lang_id = %d AND cml_type LIKE '%s'",
			  CECEPPA_ML_TRANS, bin2hex($string), $id, "%" . $type . "%");

  $ret = $wpdb->get_var($query);

  return (empty($ret)) ? $string : html_entity_decode(stripslashes($ret));
}

/**
 * restituisco la descrizione della lingua
 *
 * @param id - id della lingua
 *
 * @return - il titolo della lingua
 */
function cml_get_language_title($id) {
  global $wpdb;

  return $wpdb->get_var(sprintf("SELECT cml_language FROM %s WHERE id = %d",
				CECEPPA_ML_TABLE , $id));
}

/**
 * restiutisco l'avviso che l'articolo è disponibile nella lingua specificata
 *
 * @param $lang_slug - slug della lingua
 *
 * @return - l'avviso in base al tipo di pagina
 */
function cml_get_notice($lang_slug) {
  global $wpdb, $wpCeceppaML;

  $row = $wpdb->get_row(sprintf("SELECT cml_language, UNHEX(cml_notice_category) as cml_notice_category, UNHEX(cml_notice_page) as cml_notice_page, UNHEX(cml_notice_post) as cml_notice_post FROM %s WHERE cml_language_slug = '%s' OR id = %d",
			 	  CECEPPA_ML_TABLE , $lang_slug, intval($lang_slug)));

  if(is_category()) $r = stripslashes($row->cml_notice_category);
  if(is_page()) $r = stripslashes($row->cml_notice_page);
  if(is_single()) $r = stripslashes($row->cml_notice_post);

  if(!empty($r))
    return $r;
  else
    return $row->cml_language;
}

function cml_get_notice_by_lang_id($lang_id) {
  global $wpdb;

  $slug = $wpdb->get_var(sprintf("SELECT cml_language_slug FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $lang_id));

  return cml_get_notice($slug);
}

/**
 * Restituisco una combo con le lingue configurate
 *
 * @name - nome della classe e dell'oggetto <select>
 * @default - id della lingua predefinita
 * @link - indica se devo cambiare pagina al cambio della voce
 * @none - indica se mostrare l'elemento "Scegli una lingua dall'elenco"
 */
function cml_dropdown_langs($name, $default, $link = false, $none = false, $none_text = null, $none_id = "", $only_enabled = 1) {
  global $wpCeceppaML;

	$none_text = ($none_text == null) ? __('No language selected', 'ceceppaml') : $none_text;
	$none_text = ($none) ? $none_text : "";
	if($link) :
	?>
	  <script type="text/javascript">
	    jQuery(document).ready(function(){
		    jQuery('.<?php echo $name ?>').change(function() {
			    window.location.href = jQuery((".<?php echo $name ?> option:selected")).val();
		    });
	    });
	  </script>
	<?php endif; ?>

  <select class="<?php echo $name ?>" name="<?php echo $name ?>">
	<option value="<?php echo $none_id ?>"><?php echo $none_text ?></option>
	
	<?php
	$id = $wpCeceppaML->get_current_lang_id();
	$langs = cml_get_languages($only_enabled);
	foreach($langs as $lang) :
	  $selected = ($lang->id == $default) ? "selected" : "";

	  $value = (!$link) ? $lang->id : get_permalink(cml_get_linked_post($id, null, get_the_ID(), $lang->id));
	  $dataimage = 'data-image="' . cml_get_flag_by_lang_id($lang->id) . '"';

	  echo "<option $dataimage value=\"$value\" $selected>$lang->cml_language</option>";
	endforeach;

  echo "</select>";
}

function cml_add_translation($text, $lang_id, $translation, $type) {
  global $wpdb;

  $wpdb->insert(CECEPPA_ML_TRANS,
		array("cml_text" => bin2hex($text),
		      "cml_lang_id" => $lang_id,
		      "cml_translation" => bin2hex($translation),
		      "cml_type" => $type),
		array('%s', '%d', '%s', '%s'));
}

function cml_add_category_translation($id, $name, $lang_id, $translation) {
  global $wpdb;

  $query = sprintf("SELECT * FROM %s WHERE cml_cat_id = %d AND cml_cat_lang_id = %d", CECEPPA_ML_CATS, $id, $lang_id);
  $q = $wpdb->get_row($query);
  
  $name = strtolower($name);
  $translation = strtolower($translation);
  if(count($q) > 0) :
    $r_id = $q->id;

    $wpdb->update(CECEPPA_ML_CATS,
		  array("cml_cat_name" => bin2hex($name),
			"cml_cat_lang_id" => $lang_id,
			"cml_cat_translation" => bin2hex($translation)),
		  array("id" => $r_id),
		  array('%s', '%d', '%s'),
		  array("%d"));
  else :
    $wpdb->insert(CECEPPA_ML_CATS,
		  array("cml_cat_name" => bin2hex($name),
			"cml_cat_lang_id" => $lang_id,
			"cml_cat_translation" => bin2hex($translation),
			"cml_cat_id" => $id),
		  array('%s', '%d', '%s', '%d'));
  endif;
}

function cml_get_current_language() {
  global $wpCeceppaML;
  
  return $wpCeceppaML->get_current_language();
}

/* Controllo se sto nella homepage */
function cml_is_homepage() {
  //Non posso utilizzare la funzione is_home, quindi controllo "manualmente"
  $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $home = home_url() . "/";

  $url_parts = parse_url($url);
  $constructed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . (isset($url_parts['path'])?$url_parts['path']:'');

  return $constructed_url  == $home;
}

function cml_debug_print($string) {
  if(!is_user_logged_in()) return;

  if(is_string($string))
    echo $string;
  else
    print_r($string);
}
?>