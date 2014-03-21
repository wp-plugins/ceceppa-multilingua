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
 * It's more easy to find linked id.
 *
 * The structure of CECEPPA_ML_TRANSLATIONS isn't fixed but it depends on managed languages.
 * For each languages the plugin create a column in the table, example:
 *
 *  | id | lang_1 | lang_2 | .... | lang_n |
 *
 * the "id" column will exists ever :).
 *
 * Why that?
 * Becase is most simple, for me, get relations between posts, because they are all on same row :)
 * 
 */
global $_cml_settings, $pagenow;

if ( ! defined( 'ABSPATH' ) ) die( "Access denied" );

define( "CECEPPA_ML_MIGRATED", get_option( "cml_migration_done", 0 ) );

if( isset( $_GET[ "cml-migrate" ] ) ) {
  add_action( 'admin_init', 'cml_migrate_database', 99 );
}

add_action( 'admin_notices', 'cml_migrate_notice' );

function cml_migrate_database() {
  global $wpdb, $wpCeceppaML;
  
  //Create table?
  $table_name = CECEPPA_ML_RELATIONS;
  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    /*
     * create the table where to store relations between posts
     */
    cml_migrate_create_table();
  }

  /*
   * in CECEPPA_ML_RELATIONS each column is "lang_{SLUG}", so
   * I generate array with those column for further use :)
   */
  if( empty( $GLOBALS[ '_cml_language_columns' ] ) ) cml_generate_lang_columns();

  $_cml_language_columns = & $GLOBALS[ '_cml_language_columns' ];

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
  
  update_option( "cml_migration_done", 3 );
  
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
  global $wpdb;

  if( $pid == 0 ) return;
  if( empty( $GLOBALS[ '_cml_language_columns' ] ) ) cml_generate_lang_columns();

  $_cml_language_columns = & $GLOBALS[ '_cml_language_columns' ];
  $lpid = intval( $lpid );

  if( $lang == 0 ) {
    $record = _cml_migrate_get_record_by_pid( $pid );
    if( ! empty( $record ) ) {
      _cml_migrate_set_record_to_zero( $pid );
    }

    _cml_migrate_add_record( $lang, $pid, $pid );
  } else {
    $old_lang = CMLPost::get_language_id_by_id( $pid, true );

    if( $old_lang != $lang ) {
      if( $old_lang > 0 ) {
        _cml_migrate_set_pid_to_zero( $old_lang, $pid );
      } else {
        //Set all fields to 0
        $record = _cml_migrate_get_record( $lang, $pid );
        foreach( $_cml_language_columns as $k => $col ) {
          $wpdb->update( CECEPPA_ML_RELATIONS,
              array( "lang_$k" => 0 ),
              array( "id" => $record[ 'id' ] ),
              array( '%d' ), array( '%d' ) );
        }
      }
    }

    $record = _cml_migrate_get_record( $lang, $pid );
    if( ! empty( $record ) ) {
      if( $record[ "lang_$llang" ] != $lpid ) {
        /*
         * I changed linked post, but I need to took other relations.
         * Example:
         *  = old connections =
         *  IT     EN      EO
         *   1      2       3
         *
         *  = new connections =
         *  IT     EN      E0
         *   1      0       3
         *
         *  I cannot set EN = 0 or I'll lost information about post_id "2", so I 
         *  add new record for post_id = 1 :)
         */
        if( $record[ "lang_$llang" ] > 0 && $record[ "lang_$llang" ] != $pid ) {
          $wpdb->update( CECEPPA_ML_RELATIONS,
                          array( "lang_$lang" => 0 ),
                          array( "id" => $record[ 'id' ] ),
                          array( "%d" ), array( "%d" ) 
                        );

          $record = _cml_migrate_get_record( $lang, $pid );
        }
      }

      if( $lpid > 0 ) {
        $r_linked = _cml_migrate_get_record_by_pid( $lpid );

        //Not on same record? Remove old info
        if( ! empty( $r_linked ) && $r_linked[ 'id' ] != $record[ 'id' ] ) {
          $id = $r_linked[ 'id' ];

          unset( $r_linked[ 'id' ] );
          foreach( $r_linked as $_rec => $_lpid ) {
            if( $_lpid == $lpid ) {
              _cml_migrate_set_pid_to_zero( $_rec, $_lpid );
            }
          }
        }
      }

      if( $lang > 0 && $llang > 0 ) {
          $wpdb->update( CECEPPA_ML_RELATIONS,
                          array( "lang_$llang" => $lpid ),
                          array( "id" => $record[ 'id' ] ),
                          array( "%d" ), array( "%d" ) 
                        );
      }
    } else {
      _cml_migrate_add_record( $lang, $pid );
    }
  }

  //Remove rows with all 0
  foreach( $_cml_language_columns as $l ) {
    $where[] = "$l = 0";
  }

  $query = sprintf( "DELETE FROM %s WHERE %s", CECEPPA_ML_RELATIONS, join( " AND ", $where ) );
  $wpdb->query( $query );

}

/*
 * get record of 
 */
function _cml_migrate_get_record( $lang, $pid ) {
  global $wpdb;

  if( $lang == 0 ) $lang = CMLLanguage::get_current_id();
  $query = sprintf( "SELECT * FROM %s WHERE lang_%d = %d", 
          CECEPPA_ML_RELATIONS,
          $lang,
          $pid );
  return $wpdb->get_row( $query, ARRAY_A );
}

function _cml_migrate_get_record_by_pid( $pid ) {
  global $wpdb;

  $_cml_language_columns = & $GLOBALS[ '_cml_language_columns' ];

  $query = "SELECT * FROM " . CECEPPA_ML_RELATIONS . " WHERE ";
  foreach( $_cml_language_columns as $col ) {
    $where[] = "$col = $pid";
  }
  $query .= join( " OR ", $where );

  return $wpdb->get_row( $query, ARRAY_A );
}

function _cml_migrate_set_pid_to_zero( $lang, $pid ) {
  global $wpdb;

  $lang_key = is_numeric( $lang ) ? "lang_{$lang}" : $lang;
  $wpdb->update( CECEPPA_ML_RELATIONS,
                  array( $lang_key => 0 ),
                  array( $lang_key => $pid ),
                  array( "%d" ), array( "%d" ) 
                );
}

function _cml_migrate_set_record_to_zero( $id ) {
  global $wpdb;

  $_cml_language_columns = & $GLOBALS[ '_cml_language_columns' ];

  foreach( $_cml_language_columns as $col ) {
    $wpdb->update( CECEPPA_ML_RELATIONS,
                   array( $col => 0 ),
                   array( $col => $id ),
                   array( "%d" ), array( "%d" ) );
  }
}

/*
 * add new record and fill all other fields to 0
 */
function _cml_migrate_add_record( $lang, $pid, $linked = 0 ) {
  global $wpdb;

  $_cml_language_columns = & $GLOBALS[ '_cml_language_columns' ];

  //Set all fields to 0
  foreach( $_cml_language_columns as $col ) {
    $values[ $col ] = $linked;
  }
  
  if( $lang > 0 ) {
    $values[ "lang_$lang" ] = $pid;
  }

  $wpdb->insert( CECEPPA_ML_RELATIONS,
      $values,
      array_fill( 0, count( $values ), "%d" ) );
}

/*
 * create table CECEPPA_ML_RELATIONS
 */
function cml_migrate_create_table() {
  global $wpdb;

  $wpdb->query( "DROP TABLE " . CECEPPA_ML_RELATIONS );

  $langs = cml_get_languages( false );

  $query = "CREATE TABLE " . CECEPPA_ML_RELATIONS . " ( id bigint(20) PRIMARY KEY NOT NULL AUTO_INCREMENT ";
  foreach( $langs as $lang ) {
    $query .= ", lang_" . $lang->id . " bigint(20) NOT NULL ";
  }
  $query .= ")";

  $wpdb->query( $query );
}

/*
 * For CeceppaML < 1.3.47 I need to migrate rilations from
 *
 * CECEPPA_ML_POSTS to CECEPPA_ML_RELATIONS
 */
function cml_migrate_notice( $force = false ) {
  global $wpdb;

  $query = "SELECT COUNT(*) FROM " . CECEPPA_ML_RELATIONS;
  $results = $wpdb->get_results( $query );

  if( CECEPPA_ML_MIGRATED == 2 ) {
    cml_generate_lang_columns();

    update_option( "cml_migration_done", 3 );
  }
  
  if( CECEPPA_ML_MIGRATED < 2 || $force ) {
    if( ! $force ) {
      echo '<div class="updated">';
    }
?>
      <strong>
        Ceceppa Multilingua
      </strong>
      <br /><br />
      <a href="<?php echo add_query_arg( array( 'cml-migrate' => 1 ) ) ?>">
        <?php _e('Update required, click here for update posts relations', 'ceceppaml') ?>
      </a>
<?php
    if( ! $force ) {
      echo '</div>';
    }
  }
}

?>
