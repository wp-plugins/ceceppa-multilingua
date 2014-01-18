<?php
/*
 * Migration from 1.3.x to 1.4
 *
 * Due a problem with linked posts I integrated this code already in 1.3.47
 *
 * I changed the structure of _ceceppa_ml_posts that contains 4 ( +1 ) columns...
 *
 * From 1.4 
 * 
 * id, lang_xx
 * 
 * xx - are the id of language
 *
 * relations are stored in _ceceppa_ml_relations, and all language has their own column...
 * So in the same row are stored the indexes of translations.
 * It's more easy to find linked id
 */
global $_cml_settings, $pagenow;

if( isset( $_GET[ "cml-migrate" ] ) ) {
  add_action( 'admin_init', 'cml_migrate_database', 99 );
}

add_action( 'admin_notices', 'cml_migrate_notice' );

function cml_migrate_database() {
  global $wpdb, $wpCeceppaML, $_cml_language_columns;
  
  //Create table?
  $table_name = CECEPPA_ML_RELATIONS;
  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    cml_migrate_create_table();
  }

  /*
   * create the table where store indexes of each post
   */
  cml_migrate_create_table();

  /*
   * Parse the _ml_posts
   */
  $types = array_merge( array( 'post' => 'post', 'page' => 'page' ), 
				get_post_types( array( '_builtin' => false ), 'names' ) );
				
  $args = array('numberposts' => -1, 'posts_per_page' => 999999,
		  'post_type' => $types,
		  'status' => 'publish,draft,private,future' );

  $avlangs = array_keys( $_cml_language_columns );

  $p = new WP_Query( $args );
  $langs = cml_get_languages( 0 );
  while( $p->have_posts() ) {
    $p->next_post();

    $pid = $p->post->ID;
    $lang = get_option( "cml_page_lang_" . $pid, 0 );

//     if( $lang == 0 ) $lang = $wpCeceppaML->get_language_id_by_post_id( $pid );
//     echo "$pid: $lang<br />";
    if( ! in_array( $lang, $avlangs ) ) $lang = 0;

    /*
     * For migrate I need to retrive info about linked posts with cml_get_linked_post
     */
    $query = sprintf( "SELECT * FROM %s WHERE ( cml_post_id_1 = %d OR cml_post_id_2 = %d ) AND ( cml_post_lang_1 > 0 AND cml_post_lang_2 > 0 )",
		      CECEPPA_ML_POSTS, $pid, $pid );
    $linked = $wpdb->get_results( $query );
    if( empty ( $linked ) ) {
	cml_migrate_database_add_item( $lang, $pid, 0, 0 );
    } else {
      foreach( $linked as $result ) {
	$lpid = ( $result->cml_post_id_1 == $pid ) ? $result->cml_post_id_2 : $result->cml_post_id_1;
	$llang = ( $result->cml_post_id_1 == $pid ) ? $result->cml_post_lang_2 : $result->cml_post_lang_1;
	
	cml_migrate_database_add_item( $lang, $pid, $llang, $lpid );
      }
    }
  }
  
  update_option( "cml_migration_done", 2 );
  
  cml_fix_rebuild_posts_info();
}

/*
 * Add relation in CECEPPA_ML_RELATIONS table
 *
 * @aram $lang - of the post
 * @param $pid  - id of the post
 * @param $llang - lang of linked post
 * @param $lpid - id of linked post
 */
function cml_migrate_database_add_item( $lang, $pid, $llang, $lpid ) {
  global $wpdb, $_cml_language_columns;

  if( empty( $_cml_language_columns ) ) cml_table_language_columns();

  //Remove current post from all columns
  foreach( $_cml_language_columns as $col ) {
    $wpdb->update( CECEPPA_ML_RELATIONS,
		    array( $col => 0 ),
		    array( $col => $pid ),
		    array( "%d" ),
		    array( "%d" ) );
  }

  //Has linked post?
  if( $lpid > 0 ) {
    //Linked post has no language?
    if( $llang == 0 ) {
      $cols = $_cml_language_columns;
      unset( $cols[ $lang ] );
      $llang = end( array_keys( $cols ) );
    }
 
    $record = $wpdb->get_var( sprintf( "SELECT id FROM %s WHERE lang_%d = %d", 
					  CECEPPA_ML_RELATIONS,
					  $llang,
					  $lpid ) );

    if( ! empty( $record ) ) {
      $wpdb->update( CECEPPA_ML_RELATIONS,
		      array( "lang_$lang" => $pid ),
		      array( "id" => $record ),
		      array( "%d" ),
		      array( "%d" ) );
    } else {
      //Set all fields to 0
      foreach( $_cml_language_columns as $col ) {
	$values[ $col ] = 0;
      }
      
      $values[ "lang_$lang" ] = $pid;
      $values[ "lang_$llang" ] = $lpid;

      $wpdb->insert( CECEPPA_ML_RELATIONS,
		      $values,
		      array_fill( 0, count( $values ), "%d" ) );
    }
  } else { // if
    //Insert new record

    //Set all fields to 0
    foreach( $_cml_language_columns as $col ) {
      $values[ $col ] = ( $lang > 0 ) ? 0 : $pid;
    }

    if( $lang > 0 ) $values[ "lang_$lang" ] = $pid;

    $wpdb->insert( CECEPPA_ML_RELATIONS,
		    $values,
		    array_fill( 0, count( $values ), "%d" ) );
  }

  foreach( $_cml_language_columns as $l ) {
    $where[] = "$l = 0";
  }

  $query = sprintf( "DELETE FROM %s WHERE %s", CECEPPA_ML_RELATIONS, join( " AND ", $where ) );
  $wpdb->query( $query );
}

function cml_migrate_create_table() {
  global $wpdb;

  $wpdb->query( "DROP TABLE " . CECEPPA_ML_RELATIONS );

  $langs = cml_get_languages( 0 );

  $query = "CREATE TABLE " . CECEPPA_ML_RELATIONS . " ( id bigint(20) PRIMARY KEY NOT NULL AUTO_INCREMENT ";
  foreach( $langs as $lang ) {
    $query .= ", lang_" . $lang->id . " bigint(20) NOT NULL ";
  }
  $query .= ")";

  $wpdb->query( $query );
}

function cml_migrate_notice( $force = false ) {
  global $wpdb;

  $query = "SELECT COUNT(*) FROM " . CECEPPA_ML_RELATIONS;
  $results = $wpdb->get_results( $query );

  if( CECEPPA_ML_MIGRATED < 3 ) {
    cml_table_language_columns();

    update_option( "cml_migration_done", 3 );
  }
  
  if( CECEPPA_ML_MIGRATED < 2 ||  $wpdb->num_rows == 0 || $force ) {
?>
    <div class="updated">
      <strong>
	Ceceppa Multilingua
      </strong>
      <br /><br />
      <a href="<?php echo add_query_arg( array( 'cml-migrate' => 1 ) ) ?>">
	<?php _e('Update required, click here for update posts relations', 'ceceppaml') ?>
      </a>
    </div>
<?php
  }
}

?>
