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
global $_cml_settings;

if( ! get_option( "cml_migration_done", 0 ) ) {
  add_action( 'plugins_loaded', 'cml_migrate_database' );
}

function cml_migrate_database() {
  global $wpdb, $wpCeceppaML, $_cml_language_columns;
  
  cml_table_language_columns();

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

    if( $lang == 0 ) $lang = $wpCeceppaML->get_language_id_by_post_id( $pid );
//     echo "$pid: $lang<br />";
    if( ! in_array( $lang, $avlangs ) ) $lang = 0;

    /*
     * For migrate I need to retrive info about linked posts with cml_get_linked_post
     */
    $linked = cml_get_linked_posts( $pid );
    foreach( $langs as $l ) {
      $_linked = ( ! empty( $linked ) && isset( $linked->indexes[ $l->id ] ) ) ? $linked->indexes[ $l->id ] : 0;
      $_llang = ( $_linked > 0 ) ? $l->id : 0;

      if( $lang > 0 ) {
        if( $_linked > 0 ) {
          cml_migrate_database_add_item( $_llang, $_linked, $lang, $pid );
        } else {
          cml_migrate_database_add_item( $lang, $pid, 0, 0 );
        }
      } else {
        /*
         * Post withouth language exists in all languages
         */
        cml_migrate_database_add_item( $l->id, $pid, $_linked, $_llang );
      }
    }
  }
  
  update_option( "cml_migration_done", 1 );
  
  cml_fix_rebuild_posts_info();
}

function cml_migrate_database_add_item( $lang, $pid, $lid, $linked ) {
  global $wpdb;

  //Values
  $values = array( "lang_$lang" => $pid );
  $data = array( "%d", "%d", "%d", "%d" );

  //Linked?
  if( $linked > 0 ) {
    $values = array_merge( $values, array( "lang_$lid" => $linked ) );
  }

  //Record Exists?
  $record = $wpdb->get_var( sprintf( "SELECT id FROM %s WHERE lang_%d = %d ", CECEPPA_ML_RELATIONS, $lang, $pid ) );

  if( empty( $record ) || $record === NULL ) {
    $wpdb->insert( CECEPPA_ML_RELATIONS,
		    $values, $data );
  } else {
    $wpdb->update( CECEPPA_ML_RELATIONS,
		    $values,
		    array( "id" => $record ),
		    $data,
		    array( "%d" ) );
  }
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
?>
