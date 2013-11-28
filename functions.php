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

  return $wpdb->get_results(sprintf("SELECT * FROM %s WHERE cml_enabled >= %d AND cml_default <= %d ORDER BY cml_sort_id ",
    					CECEPPA_ML_TABLE, $enabled, $default));
}

/**
 * Elenco delle lingue disponibili
 */
function cml_get_languages_list() {
  global $wpdb;

  $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_sort_id");
  
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
  if( empty( $flag ) ) return "";

  if( file_exists( CECEPPA_UPLOAD_DIR . "/$size/$flag.png" ) )
    $url = CECEPPA_UPLOAD_URL . "/$size/$flag.png";
  else
    $url = CECEPPA_PLUGIN_URL . "flags/$size/$flag.png";
    
  return esc_url( $url );
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

  return cml_get_flag( $flag, $size );
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
  //Restituisco il nome del menù da utilizzare a seconda della lingua
  $lang = cml_get_current_language();

  return "cml_menu_" . $lang->cml_language_slug;
}

function cml_get_menu_name($name) {
  $menus = wp_get_nav_menus();
  $locations = get_nav_menu_locations();
  $menu = cml_get_menu();

  if(is_array($locations)) :
    $loc = $locations[$menu];

    foreach($menus as $menu) :
      if($menu->term_id == $loc) return $menu->slug;
    endforeach;
  endif;
  
  return $name;
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
function cml_show_flags( $show = "flag", $size = "tiny", $class_name = "cml_flags", $image_class = "", $echo = true, $linked = true, $only_existings = false, $sort = false ) {
  global $wpdb, $wpCeceppaML;

  $redirect = get_option( 'cml_option_redirect' );
  
  $results = cml_get_languages();
  $width = ( $size == "tiny" ) ? 16 : 32;

  $r = "<ul class=\"$class_name\">";
  
  //Post language...
  $lang_id = ( ! $sort ) ? -1 : $wpCeceppaML->get_language_id_by_post_id( get_the_ID() );
  $items = array();
  foreach($results as $result) :
    $lang = ($show == "flag") ? "" : $result->cml_language;

    $link = cml_get_the_link( $result, $linked, $only_existings );
    if( empty( $link) ) continue;

    $img = "<img class=\"$size $image_class\" src='" . cml_get_flag_by_lang_id( $result->id, $size ) . "' title='$result->cml_language' width=\"$width\"/>";
    if($show == "text") $img = "";

    $li = "<li><a href=\"$link\">$img$lang</a></li>";
    if( $sort && is_array( $items ) && $result->id == $lang_id )
      array_unshift( $items, $li );
    else
      $items[] = $li;

  endforeach;

  $r .= join( "\n", $items );
  $r .= "</ul>";

  if( $echo ) 
    echo $r;
  else
    return $r;
}

/**
 * cerco nel database la traduzione per la frase
 *
 *  @param string - stringa da cercare
 *  @param id - id della lingua in cui tradurre la frase
 *  @param - wpgettext - utilizza la funzione __ per cercare la traduzione della parola
 *  @param - gettext - indica se utilizzare la funzione "gettext" di "Danilo"
 *  @return - la frase tradotta se esiste la traduzione, altrimeni la stringa passata
 */
function cml_translate($string, $id, $type = "", $wpgettext = false, $gettext = false) {
  global $wpdb, $wpCeceppaML;

  $s = ($type == "W") ? strtolower( $string ) : $string;
  $query = sprintf("SELECT UNHEX(cml_translation) FROM %s WHERE cml_text = '%s' AND cml_lang_id = %d AND cml_type LIKE '%s'",
			  CECEPPA_ML_TRANS, bin2hex($s), $id, "%" . $type . "%");
  $ret = $wpdb->get_var($query);

  if( $wpgettext && empty( $ret ) ) :
    $ret = __( $string );
  endif;

  if( empty( $ret ) && $gettext) :
    //Recupero la traduzione dalle frasi di wordpress ;)
    require_once("gettext/gettext.inc");
    
    $lang = cml_get_language_info($id);
    $locale = $lang->cml_locale;

    // gettext setup
    T_setlocale( LC_MESSAGES, $locale );
    // Set the text domain as 'messages'

    $domain = $locale;
    T_bindtextdomain($domain, LOCALE_DIR);
    T_bind_textdomain_codeset($domain, 'UTF-8');
    T_textdomain($domain);

    $ret = T_gettext($string);
  endif;

  return ( empty( $ret ) ) ?  $string : html_entity_decode( stripslashes( $ret ) );
}

/**
 * restituisco la descrizione della lingua
 *
 * @param id - id della lingua
 *
 * @return - il titolo della lingua
 */
function cml_get_language_title($id = null) {
  global $wpdb;

  if($id == null) $id = cml_get_current_language_id();

  return $wpdb->get_var(sprintf("SELECT cml_language FROM %s WHERE id = %d",
				CECEPPA_ML_TABLE , $id));
}

function cml_get_language_info($id = null) {
  global $wpdb;
  
  if($id == null) $id = cml_get_current_language_id();

  return $wpdb->get_row(sprintf("SELECT * FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $id));
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
		      window.location.href = jQuery( (".<?php echo $name ?> option:selected") ).val();
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

      $value = ( ! $link ) ? $lang->id : cml_get_the_link( $lang );
      $dataimage = 'data-image="' . cml_get_flag_by_lang_id( $lang->id ) . '"';

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
  
  return is_object( $wpCeceppaML ) ? $wpCeceppaML->get_current_language() : null;
}

function cml_get_current_language_id() {
  $lang = cml_get_current_language();

  return is_object( $lang ) ? $lang->id : -1;
}

/* Controllo se sto nella homepage */
function cml_is_homepage() {
  if( is_category() || is_archive() ) return false;

  //Controllo se è stata impostata una pagina "statica" se l'id di questa è = a quello della statica
  if( cml_use_static_page() ) :
    $static_id = get_option( "page_for_posts" ) + get_option( "page_on_front" );

    $lang_id = cml_get_current_language_id();
    $the_id = get_queried_object_id();
    if( ! empty( $the_id ) ) :
      if( $the_id == $static_id ) return true;	//E' proprio lei...
      
      //Mica è una traduzione?
      $linked = cml_get_linked_post( $lang_id, null, $the_id , cml_get_default_language_id() );
      if( !empty($linked) ) return $linked == $static_id;
    endif;
  endif;

  //Non posso utilizzare la funzione is_home, quindi controllo "manualmente"
  $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $home = home_url() . "/";

  $url_parts = parse_url($url);
  $constructed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . ( isset($url_parts['path'] ) ? $url_parts['path'] : '' );

  return $constructed_url  == $home;
}

function cml_get_page_id_by_path($url, $types = null) {
  $plinks = explode( "/", $url );

  //Se l'ultimo elemento è vuoto, lo cancello ;)
  if( substr($url, -1) == "/" ) array_pop( $plinks );
  $title = array_pop( $plinks );

  if( $types == null ) $types = array_keys( get_post_types() );
  $p = cml_get_page_by_path( $url, OBJECT, $types );
  $the_id = is_object( $p ) ? $p->ID : 0;
  
  return $the_id;
}

function cml_debug_print($string) {
  if(!is_user_logged_in()) return;

  if(is_string($string))
    echo $string;
  else
    print_r($string);
}

function cml_use_static_page() {
  return (get_option("page_for_posts") > 0) ||
	  (get_option("page_on_front") > 0);
}

/* Mi serve ad individuare i custom posts, e a visualizzare le bandiere su di essi :) */
function cml_is_custom_post_type() {
  $types = get_post_types( array ( '_builtin' => FALSE ), 'names' );
  
  if( empty( $types) ) return FALSE;

  $name = get_post_type();
  return in_array( $name, $types );
}

function cml_other_langs_available( $id ) {
  echo cml_shortcode_other_langs_available( array( "id" => $id ) );
}

function cml_get_language_by_post_id( $id ) {
  global $wpCeceppaML;
  
  $lang_id = $wpCeceppaML->get_language_id_by_post_id( $id );
  
  return cml_get_language_info( $id );
}

function cml_set_language_of_post( $id, $lang_id ) {
  global $wpCeceppaML;
  
  $wpCeceppaML->set_language_of_post( $id, $lang_id, 0, 0 );
}

function cml_get_posts_by_language( $lang_id = null ) {
  global $wpCeceppaML;
  
  return $wpCeceppaML->get_posts_by_language();
}

/*
 * Ritorno il link formattato in base alla pagina corrente
 *
 * @param $result - language information ( i.e. cml_get_language() )
 * @param $linked - must return linked post ( = true ), or homepage ( = false )?
 * @param $only_existings - return linked post only if it exists, otherwise return blank link
 */
function cml_get_the_link( $result, $linked = true, $only_existings = false ) {
  global $wpCeceppaML, $_cml_settings;

  if( cml_is_homepage() && ! in_the_loop() ) {
    //Se stò nella home vuol dire che ho scelto come metodo di reindirizzamento &lang
    $link = $wpCeceppaML->get_home_url( $result->cml_language_slug );
  } else {
    /* Collego la categoria della lingua attuale con quella della linga della bandierina */
    $link = "";

    if( ! in_the_loop() )
      $lang_id = $wpCeceppaML->get_current_lang_id();
    else
      $lang_id = $wpCeceppaML->get_language_id_by_post_id( get_the_ID() );

    /*
     * I must check that is_category is false, because
     * if wp display 404, is_single is true also for category and in this case
     * the plugin will return wrong link
     */
    if( ( ( is_single() || is_page() ) ||  $linked ) && ! is_category() ):
      $link = cml_get_linked_post( $lang_id, $result, get_the_ID(), $result->id );

      if( !empty( $link ) ) $link = get_permalink( $link );
    endif;

    if( is_archive() && !is_category() ) :
      global $wp;

      $link = home_url( $wp->request ) . "/";
      $link = add_query_arg( array( "lang" => $result->cml_language_slug ), $link );
    endif;

    //Collego le categorie delle varie lingue
    if( is_category() ) :
      $cat = get_the_category();

      if( is_array( $cat ) ) :
        $cat_id = $cat[count($cat) - 1]->term_id;

        //Mi serve a "forzare" lo slug corretto nel link
        $wpCeceppaML->force_category_lang( $result->id );

        //Mi recupererà il link tradotto dal mio plugin ;)
        $link = get_category_link( $cat_id );
      endif;

      $wpCeceppaML->unset_category_lang();
    endif;
    
    if( is_paged() ) :
      $link = add_query_arg( array( "lang" => $result->cml_language_slug ) );
    endif;

    /* Controllo se è stata impostata una pagina statica,
	perché così invece di restituire il link dell'articolo collegato
	aggiungo il più "bello" ?lang=## alla fine della home.

	Se non ho trovato nesuna traduzione per l'articolo, la bandiera punterà alla homepage
    */
    if( empty( $link ) && ! $only_existings ) {
      //If post doesn't exists in current language I'll return the link to default language, if exists :)
      if( $_cml_settings[ 'cml_force_languge' ] == 1 ) {
	if( is_single() || is_page() ) {
	  $l = cml_get_linked_post( $lang_id, null, get_the_ID(), cml_get_default_language_id() );
	  if( ! empty( $l ) ) return get_permalink( $l );
	}

	$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	
	$wpCeceppaML->force_category_lang( $result->id );

	$link = $wpCeceppaML->convert_url( cml_get_current_language()->cml_language_slug, $url, false, true );

	$wpCeceppaML->unset_category_lang();
      } else
	$link = $wpCeceppaML->get_home_url( $result->cml_language_slug );
    }
  }

  return $link;
}
?>