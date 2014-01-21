<?php
/*
 * Ho spostato in questo file vari fix ovvero funzioni che mi servono solo per alcuni aggiornamenti
 */
if ( ! defined( 'ABSPATH' ) ) {
  die();
}
 
require_once(CECEPPA_PLUGIN_PATH . "functions.php");

function cml_fix_database() {
  global $wpdb;

  $dbVersion = get_option( "cml_db_version", CECEPPA_DB_VERSION );

  if( $dbVersion < 23 ) {
    add_action( 'plugins_loaded', 'cml_fix_insert_post_info' );
  }

  if( $dbVersion < 22 ) {
    if( get_option( 'cml_option_flags_on_pos', 'top' ) == "top" )
      update_option( "cml_option_flags_on_pos", "after" );
  }

  if( $dbVersion < 21 ) {
      $wpdb->query(  "ALTER TABLE  " . CECEPPA_ML_TABLE . " ADD  `cml_date_format` TEXT NOT NULL ;" );
  }

  if( $dbVersion < 20 ) {
      $wpdb->query(  "ALTER TABLE  " . CECEPPA_ML_TABLE . " ADD  `cml_rtl` INT NOT NULL ;" );
  }
  
    //Rimuovo le colonne non più necessarie
    if( $dbVersion <= 9 ) :
      $wpdb->query("ALTER table " . CECEPPA_ML_TABLE . " DROP cml_category_name, DROP cml_category_id, DROP cml_category_slug, DROP cml_page_id, DROP cml_page_slug");
    endif;

    //modifico il charset della tabella
    if( $dbVersion <= 9 ) :
      $alter = "ALTER TABLE  " . CECEPPA_ML_TABLE . " CHANGE  `cml_language`  `cml_language` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL,"
		. "CHANGE  `cml_notice_post`  `cml_notice_post` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_notice_page`  `cml_notice_page` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_notice_category`  `cml_notice_category` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_locale`  `cml_locale` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL";
      $wpdb->query($alter);

      $alter = "ALTER TABLE  `wp_ceceppa_ml_trans` CHANGE  `cml_text`  `cml_text` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NOT NULL ,"
		. "CHANGE  `cml_translation`  `cml_translation` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL";
      $wpdb->query($alter);
    endif;

    //Fix dovuto alla 0.9.1, tutte le lingue venivano impostate come default se l'utente face click su update :(
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) < 9) :
      $wpdb->query("UPDATE " . CECEPPA_ML_TABLE . " SET cml_default = 0");
      $wpdb->query("UPDATE " . CECEPPA_ML_TABLE . " SET cml_default = 1 WHERE id = 1");
    endif;

    //Ricreo tutti gli indici "cml_post_lang_##" in "cml_page_lang"
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 10) :
      $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_POSTS);
      foreach($results as $result) :
	  if($result->cml_post_lang_1 > 0) update_option("cml_page_lang_" . $result->cml_post_id_1, $result->cml_post_lang_1);
	  if($result->cml_post_lang_2 > 0) update_option("cml_page_lang_" . $result->cml_post_id_2, $result->cml_post_lang_2);

	  delete_option("cml_post_lang_" . $result->cml_post_id_1);
	  delete_option("cml_post_lang_" . $result->cml_post_id_2);
      endforeach;
    endif;

    //Cancello i post che sono stati cancellati, ma esistono ancora nella mia tabella
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 12) :
      $sql = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_id_1 NOT IN (select ID from $wpdb->posts)";
      $wpdb->query($sql);

      $sql = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_id_2 NOT IN (select ID from $wpdb->posts)";
      $wpdb->query($sql);
    endif;

    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 14) :
      $args = array('hide_empty' => 0);
      $cats = get_categories($args);
      
      $langs = cml_get_languages(0, 0);
      foreach($cats as $cat) :
	foreach($langs as $lang) :
	  $name = get_option("cml_category_" . $cat->term_id . "_lang_" . $lang->id, $cat->name);

	  $wpdb->insert(CECEPPA_ML_CATS,
			array("cml_cat_name" => bin2hex(strtolower($cat->name)),
			      "cml_cat_lang_id" => $lang->id,
			      "cml_cat_translation" => bin2hex(strtolower($name)),
			      "cml_cat_id" => $cat->term_id),
			array('%s', '%d', '%s', '%d'));
	endforeach;
      endforeach;
    endif;
 
    if($dbVersion <= 17) :
      $sql = "ALTER TABLE  " . CECEPPA_ML_TABLE . " ADD  `cml_sort_id` INT NOT NULL ;";
      $wpdb->query($sql);

      $sql = "UPDATE " . CECEPPA_ML_TABLE . " SET cml_sort_id = id";
      $wpdb->query($sql);
    endif;

    if($dbVersion <= 15) :
      cml_fix_widget_titles();
    endif;
    
    //Controllo se esiste una pagina con lo slug "/##/", perché nelle versioni < 1.2.6
    //per avere la pagina iniziale in stile www.example.com/it dovevo modificare lo slug della
    //pagina in "it", dalla 1.2.6 basta mettere una pagina statica come iniziale, il plugin
    //si occuperà del resto...
    if($dbVersion <= 16) :
      $id = cml_get_default_language_id();
      $info = cml_get_language_info( $id );
      
      $slug = $info->cml_language_slug;
      $the_id = cml_get_page_id_by_path ( $slug, array('page') );
      
      if( $the_id ) update_option( 'cml_need_use_static_page', 1 );
    endif;
    
    if( $dbVersion <= 17 ) :
	add_action( 'plugins_loaded', 'cml_fix_rebuild_posts_info' );
    endif;
    
    if( $dbVersion <= 18 ) :
      $wpdb->query( "ALTER TABLE  " . CECEPPA_ML_TABLE . " ADD  `cml_flag_path` TEXT" );
    endif;

    update_option("cml_db_version", CECEPPA_DB_VERSION);
}

//Imposto in automatico la lingua in tutti i post
function cml_update_all_posts_language() {
  global $wpdb, $wpCeceppaML;

  $posts = get_posts(array('order' => 'ASC',
    'orderby' => 'title',
    'numberposts' => -1,
    'status' => 'publish, draft'));

  foreach($posts as $post) :
    update_option("cml_page_lang_" . $post->ID, $wpCeceppaML->get_default_lang_id());
    $wpdb->insert(CECEPPA_ML_POSTS, 
		  array("cml_post_lang_1" => $wpCeceppaML->get_default_lang_id(),
			"cml_post_id_1" => $post->ID, 
			"cml_post_lang_2" => 0,
			"cml_post_id_2" => 0),
		  array('%d', '%d', '%d', '%d'));
  endforeach;

  $posts = get_pages(array('order' => 'ASC',
    'orderby' => 'title',
    'numberposts' => -1,
    'status' => 'publish, draft, private'));

  foreach($posts as $post) :
    update_option("cml_page_lang_" . $post->ID, $wpCeceppaML->get_default_lang_id());

    $wpdb->insert(CECEPPA_ML_POSTS, 
	    array("cml_post_lang_1" => $wpCeceppaML->get_default_lang_id(),
		  "cml_post_id_1" => $post->ID, 
		  "cml_post_lang_2" => 0,
		  "cml_post_id_2" => 0),
	    array('%d', '%d', '%d', '%d'));
  endforeach;

  update_option("cml_need_update_posts", false);
}

/*
 * Fino alla versione 0.9.22 la funzione hide_translation stabiliva quali articoli nascondere al momento,
 * il che richiedeva una serie di elaborazioni che potrebbero ripercuotersi sulla velocità di caricamento della pagina.
 *
 * Dato che il plugin non presenta bug evidenti, con la 1.0 voglio ottimiazzare anche un po' il codice, evitando
 * "cicli" superflui memorizzando le informazioni necessarie nel momento in cui l'utente pubblica un articolo. 
 *
 * Memorizzo per ogni lingua gli id dei rispettivi post.
 *
 */
function cml_fix_rebuild_posts_info() {
  global $wpCeceppaML, $wpdb, $_cml_language_columns;

  $pids = array();
  $apids = array(); //All pids
  $i = 0;
  $results = $wpdb->get_results( "SELECT * FROM " . CECEPPA_ML_RELATIONS );
  foreach( $results as $result ) {
    $r = ( Array ) $result;

    foreach( $_cml_language_columns as $key => $l ) {
      if( $r[ $l ] > 0 ) {
	$pids[ $key ][] = $r[ $l ];
	$apids[ $i ][ $key ] = $r[ $l ];
      }
    }

    $i++;
  }

  foreach( $_cml_language_columns as $key => $l ) {
    @update_option( "cml_posts_of_lang_" . $key, array_unique( $pids[ $key ] ) );
  }

  /*
   * hide translations of current post..
   */
  $hide = array();
  $translations = array();
  foreach( $_cml_language_columns as $key => $l ) {
    $langs = $_cml_language_columns;
    unset( $langs[ $key ] );

    foreach( $results as $result ) {
      $r = ( Array ) $result;

      foreach( $langs as $k => $lang ) {
	if( $r[ $lang ] > 0 && $r[ $l ] != $r[ $lang ] ) {
	  $hide[ $key ] = $r[ $lang ];
	}
      }
    }
  }
  
  //Indexes to hide
  foreach( $_cml_language_columns as $key => $l ) {
    @update_option( "cml_hide_posts_for_lang_" . $key,  $hide[ $key ] );    
  }
}

function cml_fix_widget_titles() {
  global $wpdb;

  $sql = "SELECT id, UNHEX(cml_text) as text FROM " . CECEPPA_ML_TRANS . " WHERE cml_type = 'W'";
  $results = $wpdb->get_results($sql);
  
  foreach($results as $result) :
    $sql = sprintf("UPDATE %s SET cml_text = '%s' WHERE id = %d", CECEPPA_ML_TRANS, bin2hex(strtolower($resul->text)), $resul->id);
    $wpdb->query($sql);
  endforeach;
}

function cml_update_float_css() {
  $filename = CECEPPA_PLUGIN_PATH . "/css/float.css";
  $css = get_option( 'cml_float_css', "" );

  if( ! empty( $css ) ) :
    file_put_contents( $filename, $css );
  endif;

  update_option( "cml_version", CECEPPA_ML_VERSION );
}

function cml_update_settings() {
  //Genero il file "settings.php"
  $filename = CECEPPA_UPLOAD_DIR . "/settings.php";
  $fp = fopen( $filename, 'w' );
  
  if( ! $fp ) return;

  //Scrivo la riga di accesso negato
  fwrite( $fp, '<?php' . PHP_EOL );
  fwrite( $fp, "if ( ! defined( 'ABSPATH' ) ) die();" . PHP_EOL . PHP_EOL );

  //Apro il file delle opzioni
  require 'settings_fallback.php';
  
  $keys = array_keys( $_cml_settings );
  foreach( $keys as $key ) :
    $valore = $_cml_settings[ $key ];
    if( !is_numeric( $valore ) ) $valore = "'" . addslashes($valore) . "'";
    $string = "$" . "_cml_settings[ '$key' ] = $valore;";
    fwrite( $fp, $string . PHP_EOL );
  endforeach;
  
  fwrite( $fp, '?>' );
  fclose( $fp );
}

function cml_fix_insert_post_info() {
  global $wpCeceppaML, $wpdb;
  
  //Tipi di post + custon_posts
  $types = array_merge( array( 'post' => 'post', 'page' => 'page' ), 
				get_post_types( array( '_builtin' => false ), 'names' ) );

  //Recupero tutti gli articoli
  $args = array('numberposts' => -1, 'posts_per_page' => 999999,
		  'post_type' => $types,
		  'status' => 'publish,draft,private,future' );

  $p = new WP_Query( $args );
  $langs = cml_get_languages( 0 );
  while( $p->have_posts() ) :
    $p->next_post();

    $id = $p->post->ID;
    $exists = $wpdb->get_row( sprintf( "SELECT * FROM %s WHERE cml_post_id_1 = %d OR cml_post_id_2 = %d", 
			CECEPPA_ML_POSTS, 
			$id, $id ) );
			
    if( empty( $exists ) ) {
      $lang = $wpCeceppaML->get_language_id_by_post_id( $id );
      
      $wpdb->insert( CECEPPA_ML_POSTS,
		      array( "cml_post_id_1" => $id, "cml_post_lang_1" => $lang,
		      "cml_post_id_2" => 0, "cml_post_lang_2" => 0 ),
		      array( "%d", "%d" ) );
    }

  endwhile;
}
?>