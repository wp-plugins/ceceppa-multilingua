<?php
global $wpCeceppaML;

if(!is_object($wpCeceppaML)) die("Access denied");


//Richiede php > 5.3.0 :(
if( PHP_VERSION_ID >= 50300 ) include_once( 'mo-generator.php' );

class CeceppaMLTranslations {
  public function __construct() {
    wp_enqueue_script('ceceppaml-js');

    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppa-tipsy');
    wp_enqueue_style('ceceppaml-dd');

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

      if( $_POST['form'] == 3 ) :
	$this->generate_mo();
      endif;

    endif;

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
  
  function generate_mo() {
    global $cml_theme_locale_path;
    
    //Ho cliccato il pulsante per gestire la lingua di default del tema?
    if( isset( $_POST[ 'theme-lang' ] ) && intval( $_POST[ 'theme-lang' ] ) ) :
      update_option( "cml_theme_language", intval( $_POST[ 'theme-lang' ] ) );
    endif;

    //Se qualcosa è andato storto lo dico
    if( empty( $cml_theme_locale_path ) ) :
      echo '<div class="error"><p>';
      echo __( 'Something goes wrong :\'(. I don\'t know where to store .mo file', 'ceceppaml' );
      echo '</div>';
    endif;
    
    //Se il percorso non esiste lo creo :)
    if( ! file_exists( $cml_theme_locale_path ) ) :
      if ( ! mkdir( $cml_theme_locale_path ) ) :
	echo '<div class="error"><p>';
	echo __( 'Failed to create folder: ', '' ), $cml_theme_locale_path;
	echo '</p></div>';
	
	return;
      endif;
    endif;

    $domain = trim( $_POST[ 'textdomain' ] );
    
    //Recupero le stringhe originali da un file "temporaneo", così evito la conversione degli elementi html ( &rsquo;, etc... )
    $originals = explode( "\n", file_get_contents( $cml_theme_locale_path . "/tmp.pot" ) );

    //Intestazione file .po
    $header = file_get_contents( CECEPPA_PLUGIN_PATH . "/includes/header.po" );

    //Escludo la lingua principale del tema 
    $langs = exclude_theme_language();

    $done = array(); //File completati
    foreach( $langs as $lang ) :
      $filename = "$cml_theme_locale_path/$lang->cml_locale.po";
      $fp = fopen( $filename, 'w' );
      if( !$fp ) :
?>
	<div class="error">
	  <p><?php echo __( 'Error writing the file: ', 'ceceppaml' ) . $filename ?></p>
	</div>
<?php
	continue;
      endif;

      //Intestazione
      $h = $header;
      $user = wp_get_current_user();

      $theme = wp_get_theme();
      $h = str_replace( '%PROJECT%', $theme->get( 'Name' ), $h );
      $h = str_replace( '%AUTHOR%', $user->user_firstname . " " . $user->user_lastname, $h );
      $h = str_replace( '%EMAIL%', $user->user_email, $h );
      $h = str_replace( '%LOCALE%', $lang->cml_locale, $h );
      fwrite( $fp, $h . PHP_EOL );

      $strings = $_POST[ 'string' ][ $lang->id ];
      for( $i = 0; $i <= count( $originals ); $i++ ) :
	if( ! isset( $originals[ $i ] ) ) continue;

	$o = 'msgid "' . addslashes( stripslashes( $originals[$i] ) ) . '"' . PHP_EOL;
	$s = 'msgstr "' . addslashes( stripslashes( $strings[$i] ) ) . '"' . PHP_EOL . PHP_EOL;

	fwrite( $fp, $o );
	fwrite( $fp, $s );
      endfor;

      fclose( $fp );
      
      if( function_exists( 'cml_generate_mo' ) ) :
	$output = cml_generate_mo( $filename );
	if( ! empty( $output ) ) :
	  echo '<div class="error"><p>';
	  echo "<b>" . $output . "</b>: " . $filename;
	  echo "</p></div>";
	else:
	  $done[] = $filename;
	endif;
      endif;

    endforeach;
    
    //File generati
    if( ! empty( $done ) ) :
      echo '<div class="updated"><p>';
      echo __( 'Created files:', 'ceceppaml' ) . "<br /><blockquote>";
      echo join( "<br />", $done );
      echo '</blockquote></div>';
    endif;
  }
}

function exclude_theme_language() {
  $langs = cml_get_languages();

  //Rimuovo tra le lingue da tradurre quella del tema
  $theme_lang = get_option( "cml_theme_language", -1 );
  if( $theme_lang >= 0 ) :
    for( $i = 0; $i < count( $langs ); $i++ ) :
      if( $langs[ $i ]->id == $theme_lang ) {
	unset( $langs[ $i ] );
	break;
      }
    endfor;
  endif;
  
  return $langs;
}
?>