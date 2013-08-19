<?php
global $wpCeceppaML;

if(!is_object($wpCeceppaML)) die("Access denied");

// require_once "mo-convert.php";

class CeceppaMLTranslations {
  public function __construct() {

    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_style('ceceppaml-style');

    $langs = cml_get_languages(0);
    for($i = 0; $i < count($langs); $i++) :
      if($langs[$i]->cml_default == 1) :
	unset($langs[$i]);
	break;
      endif;
    endfor;

    if( array_key_exists("form", $_POST) ) :
      if( $_POST['form'] == 1 && array_key_exists("id", $_POST) ) :
	$this->update_translations($langs);
      endif;

      if ( $_POST['form'] == 2 ) :
	$this->update_site_title( $langs );
      endif;

    endif;

//     phpmo_convert ( CECEPPA_PLUGIN_PATH . 'includes/ceceppaml-it_IT.po', '', '',  CECEPPA_PLUGIN_PATH . 'includes/ceceppaml-it_IT.mo' );

    require_once('form_translations.php');
  }

  function update_translations($langs) {
    global $wpdb;

    $query = "DELETE FROM " . CECEPPA_ML_TRANS . " WHERE cml_type = 'S'";
    $wpdb->query($query);

    $ids = $_POST['id'];
    $delete = (array_key_exists('remove', $_POST)) ? $_POST['remove'] : array();

    for($i = 0; $i < count($_POST['string']); $i++) :
      $string = $_POST['string'][$i];

      $id = $ids[$i];
      if(!empty($delete) && (@isset($delete[$id]) || @$delete[$id] == 1)) continue;

      for($j = 0; $j < count($_POST['value'][$i]); $j++) :
	$value = $_POST['value'][$i][$j];
	$lang_id = $_POST['lang_id'][$i][$j];

	if(!empty($string)) :
	  $wpdb->insert(CECEPPA_ML_TRANS, 
			array('cml_text' => bin2hex($string),
			      'cml_lang_id' => $lang_id,
			      'cml_translation' => bin2hex($value),
			      'cml_type' => 'S' ),
			array('%s', '%d', '%s', '%s'));
	endif;
      endfor;

    endfor;
  }
  
  /*
   *
   * Se il tema utilizza bloginfo( 'name' ) al posto di wp_title, devo cercare la traduzione nella mia tabella
   * perché il filtro mi passa solo il "titolo" e non il parametro della richiesta ('name')..
   *
   */
  function update_site_title( $langs ) {
    global $wpdb;

    $query = "DELETE FROM " . CECEPPA_ML_TRANS . " WHERE cml_type = 'M'";
    $wpdb->query($query);

    $blog_title = get_bloginfo( 'name' );
    $blog_tagline = get_bloginfo( 'description' );

    for( $i = 0; $i < count( $_POST['title'] ); $i++ ) :
      $id = intval( $_POST['id'][$i] );
      $title = $_POST['title'][$i];
      $tag = $_POST['tagline'][$i];

      //Titolo
      $wpdb->insert(CECEPPA_ML_TRANS, 
		    array('cml_text' => bin2hex($blog_title),
			  'cml_lang_id' => $id,
			  'cml_translation' => bin2hex($title),
			  'cml_type' => 'M' ),
		    array('%s', '%d', '%s', '%s'));

      //Motto
      $wpdb->insert(CECEPPA_ML_TRANS, 
		    array('cml_text' => bin2hex($blog_tagline),
			  'cml_lang_id' => $id,
			  'cml_translation' => bin2hex($tag),
			  'cml_type' => 'M' ),
		    array('%s', '%d', '%s', '%s'));

      //Memorizzo il titolo in un opzione, in modo da recuperarlo facilmente con l'hook wp_title
      update_option( "cml_site_title_$id", $title );
      update_option( "cml_site_tagline_$id", $tag );
    endfor;
  }
}
?>