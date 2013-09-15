<?php
  global $cml_theme_locale_path;

  wp_enqueue_script("ceceppa-tipsy");

  //Warning: required php >= 5.3.0
  if( PHP_VERSION_ID < 50300 ) :
?>
    <div class="error">
      <p>
	<b><?php _e( 'Feature not available', 'ceceppaml' ) ?> :(</b><br /><br />
	<?php _e( 'This feature require Php >= 5.3.0, your current version is: ', 'ceceppaml' ); echo phpversion() ?>
      </p>
    </div>
<?php
  endif;

  $path = get_template_directory();

  //Cerco eventuali file *.po/*.mo
  $loc = getAllFilesFrom( $path, 'po' );
  $loc = array_merge( $loc, getAllFilesFrom( $path, 'mo' ) );

  $files = getAllFilesFrom( $path, "php" );
  foreach( $files as $filename ) :
    $content = file_get_contents( $filename );
    
    preg_match_all ( '/(_e|__|esc_html_e|esc_attr__|esc_html__)\((.*?)\)/', $content, $matches );

    //'valore', 'textdomain'
    $m = end( $matches );
    foreach( $m as $line ) :
      preg_match_all( '/\'(.+?)\'/', $line, $string );
      list( $text, $domain ) = end( $string );

      //Rimuovo gli apici iniziali e finali :)
      if( ! empty( $text ) ) :
	$domains[ $domain ][] = $text;
      endif;
    endforeach; //$m as $line
  endforeach;

  //Percorso vuoto?
  if( empty( $cml_theme_locale_path ) ) :
?>
    <div class="error">
      <p>
	<?php printf( __( 'Current theme <i>"%s"</i> doesn\'t support localization, <b>cannot be translated!</b>', 'ceceppaml' ), wp_get_theme()->name ); ?>
      </p>
    </div>
<?php
    return;
  endif;

  echo "<h2 class='nav-tab-wrapper'>";
  echo wp_get_theme()->name;

  $keys = array_filter( array_keys( $domains ) );

  $theme_lang = get_option( "cml_theme_language", -1 );
  $langs = exclude_theme_language();
?>
    <span class="textdomain">
      <?php _e( 'Available languages: ', 'ceceppaml' ); ?>
<?php
  //Elenco delle lingue disponibili
  if( !empty( $loc ) ) :
     //Controllo se esiste il file della lingua
     foreach( $langs as $lang ) :
	//Controllo se esiste il file .mo o .po
	$mofile = $lang->cml_locale . ".mo";
	$exists = file_exists( "$cml_theme_locale_path/$mofile" );

	preg_match( '/themes\/' . addslashes( get_template() ) . '\/(.*)/', $cml_theme_locale_path, $matches );
	$not = $exists ? "" : "not-available";
	$title = empty( $not ) ? __( 'Download', 'ceceppaml' ) : __( 'Not available', 'ceceppaml' );

	$link = get_template_directory_uri() . "/" . end ( $matches ) . "/$mofile";
	echo '<a href="' . $link . '"><img src="' . cml_get_flag($lang->cml_flag) . '" class="available-lang ' . $not . ' _tipsy" title="' . $title . '" /></a>';
     endforeach;
  endif;
?>
      &nbsp;&nbsp;&nbsp;<span class="light">|</span>&nbsp;&nbsp;&nbsp;
      <?php _e( 'Main language of the theme:', 'ceceppaml' ) ?>
      <?php cml_dropdown_langs( "theme-lang", $theme_lang, false, true, __( 'None of the following', 'ceceppaml' ) ); ?>
    </span>
<?php
  echo "</h2>";
?>
<h2 class="nav-tab-wrapper tab-strings">
  &nbsp;
  <a class="nav-tab  nav-tab-active" href="javascript:showStrings( 0 )"><?php _e( 'All strings', 'ceceppaml' ) ?><span></span></a>
  <a class="nav-tab" href="javascript:showStrings( 1, 'to-translate' )"><?php _e( 'To translate', 'ceceppaml' ) ?><span></span></a>
  <a class="nav-tab" href="javascript:showStrings( 2, 'incomplete' )"><?php _e( 'Incomplete', 'ceceppaml' ) ?><span></span></a>
  <a class="nav-tab" href="javascript:showStrings( 3, 'translated' )"><?php _e( 'Translated', 'ceceppaml' ) ?><span></span></a>
  <?php if( PHP_VERSION_ID >= 50300 ) submit_button(); ?>
</h2>
  <input type="hidden" name="textdomain" value="<?php echo $keys[0] ?>" />

  <table class="widefat ceceppaml-theme-translations">
    <thead>
      <tr>
	<th>String</th>
	<?php
	  foreach( $langs as $lang ) :
	      echo "<th class=\"flag\"><img src='" . cml_get_flag( $lang->cml_flag ) . "'/></th>";
	  endforeach;
	?>
      </tr>
    </thead>
    <tbody>
<?php
  $alternate = "";

  //Recupero la traduzione dalle frasi di wordpress ;)
  require_once( CECEPPA_PLUGIN_PATH . "gettext/gettext.inc" );

  //Cerco la traduzione per ogni stringa
  foreach( $keys as $d ) :
    $strings = array_unique( $domains[ $d ] );
    $domains[ $d ] = $strings;

    //Ciclo per ogni lingua per evitare caricamenti continui
    foreach( $langs as $lang ) :
      // gettext setup
      T_setlocale( LC_MESSAGES, $lang->cml_locale );
      // Set the text domain as 'messages'

      $domain = $lang->cml_locale;
      T_bindtextdomain( $domain, $cml_theme_locale_path );
      T_bind_textdomain_codeset( $domain, 'UTF-8' );
      T_textdomain( $domain );

      //Cerco le traduzioni delle stringhe per ogni lingua
      foreach( $strings as $string ) :
	$ret = T_gettext( $string );
	if( strcasecmp( $ret, $string ) == 0 ) $ret = __( $string );  //Cerco anche tra le traduzioni di wordpress
	$done = !( strcasecmp( $ret, $string ) == 0 );

	$trans[ $lang->id ][] = array( "string" => stripslashes( $ret ), "done" => $done );
      endforeach;

    endforeach;
  endforeach;
  
  $i = 0;
  $total = count( $langs );
  foreach( $keys as $d ) :

    foreach( $domains[ $d ] as $s ) :
      $originals[] = $s;

      $alternate = ( empty( $alternate ) ) ? "alternate" : "";

      $td = "<td>$s</td>";
      
      $translated = 0;
      foreach( $langs as $lang ) :
	$done = $trans[ $lang->id ][ $i ][ 'done' ] == 1;
	$translated += intval( $done );
	$td .= "<td>";
	$not = ( $done ) ? "" : "not-available";
	$msg = !empty( $not ) ? __( 'Translate', 'ceceppaml' ) : __( 'Translated', 'ceceppaml' );
	$td .= '<img src="' . cml_get_flag( $lang->cml_flag ) . '" class="available-lang ' . $not . ' tipsy-e" title="' . $msg . '" />';
	$td .= "</td>";
      endforeach;

      if( $translated == 0 )
	$class = "to-translate";
      else if( $translated == $total )
	$class = "translated";
      else
	$class = "to-translate string-incomplete";

      echo "<tr class=\"row-domain-" . trim( $d ) . " $alternate row-domain string-$class\">";
	echo $td;
      echo "</tr>";

      echo "<tr class=\"row-domain-" . trim( $d ) ." $alternate row-details row-hidden \">";
      echo "<td colspan=\"" . ( count( $langs ) + 1 ) ."\">";
      
      foreach( $langs as $lang ) :
	$done = $trans[ $lang->id ][ $i ][ 'done' ] == 1;

	echo "<div class=\"ceceppaml-trans-fields\">";
	echo '<img src="' . cml_get_flag( $lang->cml_flag ) . '" class="available-lang" />';
	echo "&nbsp;<textarea name=\"string[" . $lang->id . "][]\">" . esc_html( $trans[ $lang->id ][ $i ][ 'string' ] ) . "</textarea>";
	
	$done = ( $done )  ? __( 'Translation complete', 'ceceppaml' ) : __( 'Translation not complete', 'ceceppaml' );
	echo "</div>";
      endforeach;
      echo "</td>";

      echo "</tr>";

      $i++;
    endforeach;

  endforeach;
  
  //Memorizzo le stringhe originali in un file "temporaneo", così evito la conversione degli elementi html ( &rsquo;, etc... )
  file_put_contents( $cml_theme_locale_path . "/tmp.pot", implode( "\n", $originals ) );
?>

  </tbody>
  </table>
      <?php if( PHP_VERSION_ID >= 50300 ) submit_button(); ?>
