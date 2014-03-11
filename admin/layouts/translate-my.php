<?php
//Non posso richiamare lo script direttamente dal browser :)
if ( ! defined( 'ABSPATH' ) ) die( "Access denied" );

global $wpdb;

require_once( CML_PLUGIN_LAYOUTS_PATH . "class-mytranslations.php" );

if( isset( $_POST[ 'add' ] ) && wp_verify_nonce( $_POST[ "ceceppaml-nonce" ], "security" ) ) {
  cml_admin_update_my_translations();
}

$types = array(
               "S" => __( "My translations", 'ceceppaml' ),
               "N" => __( "Notice", 'ceceppaml' ),
              );

//3rd part
$others = apply_filters( 'cml_my_translations', array() );
?>

<div class="updated">
    <p>
      <?php _e('You can use this translation with shortcode "cml_translate".', 'ceceppaml') ?>
      <a href="?page=ceceppaml-shortcode-page&tab=0#strings"><?php _e( 'Click here to see the shortcode page', 'ceceppaml' ); ?></a>
      <br />
    </p>
</div>

<h2 class="nav-tab-wrapper cml-tab-wrapper tab-strings">
  &nbsp;
  <a class="nav-tab nav-tab-active" href="javascript:showStrings(0)"><?php _e( 'All strings', 'ceceppaml' ) ?></a>
  <a class="nav-tab" href="javascript:showStrings( 1, 'S' )"><?php _e( 'My translations', 'ceceppaml' ) ?></a>
  <a class="nav-tab" href="javascript:showStrings( 2, '_cml_' )"><?php _e( 'Plugin strings', 'ceceppaml' ) ?></a>
  <?php
  $i = 3;
  foreach( $others as $key => $type ) {
echo <<< EOT
  <a class="nav-tab" href="javascript:showStrings( $i, '$key' )">$type</a>
EOT;

    $i++;
  }
  
  $types = array_merge( $types, $others );
  ?>
</h2>
    <form class="ceceppa-form-translations" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $_GET['page'] ?>">
      <input type="hidden" name="add" value="1" />
      <?php wp_nonce_field( "security", "ceceppaml-nonce" ) ?>
      <input type="hidden" name="form" value="1" />

      <?php
        $table = new MyTranslations_Table( $types );
        $table->prepare_items();
      
        $table->display();
        
        $lkeys = array_keys( CMLLanguage::get_all() );
      ?>
      <div style="text-align:right">
        <p class="submit" style="float: right">
        <?php if( count( CMLLanguage::get_all() ) > 1 ) : ?>
        <input type="button" class="button button-secondaty" name="add" value="<?php _e('Add', 'ceceppaml') ?>" onclick="addRow(<?php echo count( $lkeys ) . ", '" . join(",", $lkeys ) ?>', <?php echo CMLLanguage::get_default_id() ?>)" />
        <?php endif; ?>
        <?php submit_button( __( 'Update', 'ceceppaml' ), "button-primary", "action", false, 'class="button button-primary"' ); ?>
        </p>
      </div>
    </form>

<?php
  function cml_admin_update_my_translations() {
    global $wpdb;

    CMLTranslations::delete( "N" );
    CMLTranslations::delete( "S" );

    $langs = CMLLanguage::get_all();

    $max = count( $_POST[ 'id' ] );
    for( $i = 0; $i < $max; $i++ ) {
      //record id
      $id = intval( $_POST[ 'id' ][ $i ] );
      $text = esc_attr( $_POST[ 'string' ][ $i ] );
      $group = esc_attr( $_POST[ 'group' ][ $i ] );

      foreach( $langs as $lang ) {
        $value = esc_attr( $_POST[ 'values' ][ $lang->id ][ $i ] );

        if( $group == "S" &&
           $lang->id == CMLLanguage::get_default_id() ) {
          continue;
        }

        CMLTranslations::set( $lang->id,
                            $text,
                            $value,
                            $group );
      }
    }

    if( isset( $_POST[ 'delete' ] ) ) {
      $max = count( @$_POST[ 'delete' ] );
      for( $i = 0; $i < $max; $i++ ) {
        $wpdb->delete( CECEPPA_ML_TRANSLATIONS,
                      array(
                        'cml_text' => bin2hex( $_POST[ 'delete' ][ $i ] ),
                      ),
                      array(
                        '%s'
                      )
                     );
      }
    }

    //generate .po
    cml_generate_mo_from_translations( "S", true );
  }
?>