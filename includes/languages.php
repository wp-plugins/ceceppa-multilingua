<?php

global $wpCeceppaML;
if(!is_object($wpCeceppaML)) die("Access denied");

require_once("donate.php");

class CeceppaMLLanguages {
  public function __construct() {
    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_script('ceceppa-tipsy');

    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-lang');
    wp_enqueue_style('ceceppaml-dd');
    wp_enqueue_style('ceceppa-tipsy');

    if(isset($_POST['form']) && wp_verify_nonce($_POST['cml_nonce_edit_language'], 'cml_edit_language')) 
      $this->update_languages();
      
    require_once('form_languages.php');
  }

  function update_languages() {
    global $wpdb;

    $form = $_POST['form'];

    require_once("mo-downloader.php");
    $d = new CMLMoDownloader();

    for($i = 0; $i < count($_POST['id']); $i++) :
	$id = $_POST['id'][$i];
	@list( $lang, $lang_slug ) = explode( "@", $_POST['flags'][$i] );
	$default = (array_key_exists('default', $_POST) && $_POST['default'] == $id) ? 1 : 0;

	$flag_path = "";

	//Upload?
	if( ! empty( $_FILES[ 'flag_file' ][ 'name' ][ $i ] ) ) :
	  if ( $_FILES["flag_file"]["error"][ $i ] > 0 ) :	//Errore
	    echo '<div class="error">';
	    echo "Error: " . $_FILES["flag_file"]["name"][ $i ] . "<br />";
	    echo '</div>';
	  else:
	    $imageData = @getimagesize( $_FILES["flag_file"]["tmp_name"][ $i ] );

	    if( $imageData === FALSE || !( $imageData[2] == IMAGETYPE_GIF || $imageData[2] == IMAGETYPE_JPEG || $imageData[2] == IMAGETYPE_PNG ) ) {
	      echo '<div class="error">';
	      echo __( "Invalid image: ", 'ceceppaml' ) . $_FILES["flag_file"]["name"][ $i ] . "<br />";
	      echo '</div>';
	    } else {
	      $upload_dir = wp_upload_dir();
	      $temp = $_FILES["flag_file"]["tmp_name"][ $i ];
	      $outname = $_POST[ 'locale' ][ $i ] . ".png";

	      //Ridimensiono
	      list( $width, $height ) = getimagesize( $temp );
	      $src = imagecreatefromstring( file_get_contents( $temp ) );
	      
	      //Creo le cartelle
	      if( ! is_dir( $upload_dir[ 'basedir' ] . "/ceceppaml/tiny/" ) ) mkdir( $upload_dir[ 'basedir' ] . "/ceceppaml/tiny/" );
	      if( ! is_dir( $upload_dir[ 'basedir' ] . "/ceceppaml/small/" ) ) mkdir( $upload_dir[ 'basedir' ] . "/ceceppaml/small/" );

	      //Tiny
	      $out = $upload_dir[ 'basedir' ] . "/ceceppaml/tiny/" . $outname;
	      $tiny = imagecreatetruecolor( 16, 11 );
	      imagecopyresized( $tiny, $src, 0, 0, 0, 0, 16, 11, $width, $height );
	      imagepng( $tiny, $out );

	      //Small
	      $out = $upload_dir[ 'basedir' ] . "/ceceppaml/small/" . $outname;
	      $small = imagecreatetruecolor( 32, 23 );
	      imagecopyresized( $small, $src, 0, 0, 0, 0, 32, 23, $width, $height );
	      imagepng( $small, $out );

	      $lang = $_POST[ 'locale' ][ $i ];
	      $flag_path = $upload_dir[ 'basedir' ] . "/ceceppaml/";
	    }
	  endif;
	endif;

	//Se è vuoto, è una "nuova lingua"
	if(empty($id)) {
	  if(!empty($_POST['language'][$i])) :
	    $wpdb->insert(CECEPPA_ML_TABLE,
			  array('cml_default' => $default,
				'cml_flag' => $lang,
				'cml_language_slug' => $_POST['language_slug'][$i],
				'cml_language' => $_POST['language'][$i],
				'cml_locale' => $_POST['locale'][$i],
				'cml_notice_post' => bin2hex(htmlentities($_POST['notice_post'][$i], ENT_COMPAT, "UTF-8")),
				'cml_notice_page' => bin2hex(htmlentities($_POST['notice_page'][$i], ENT_COMPAT, "UTF-8")),
				'cml_notice_category' => '',
				'cml_enabled' => 1,
				'cml_sort_id' => $_POST['sort-id'][$i],
				'cml_rtl' => @$_POST[ 'rtl' ][ $id ],
				'cml_date_format' => @$_POST[ 'dformat' ][ $i ],
				'cml_flag_path' => $flag_path ),
			    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s'));

	    if( substr( $lang, 0, 2 ) != "en" )
	      $d->download_language($wpdb->insert_id, $_POST['language'][$i]);
          
        //Aggiorno una nuova colonna alla tabella
        $sql = sprintf( "ALTER TABLE %s ADD lang_%d bigint(20) NOT NULL DEFAULT 0", CECEPPA_ML_RELATIONS, $wpdb->insert_id );
        $wpdb->query( $sql );

        cml_table_language_columns();
	  endif;
	} else {
	    $wpdb->update(CECEPPA_ML_TABLE,
			  array('cml_default' => $default,
				'cml_flag' => $lang,
				'cml_language_slug' => $_POST['language_slug'][$i],
				'cml_language' => $_POST['language'][$i],
				'cml_locale' => $_POST['locale'][$i],
				'cml_notice_post' => bin2hex(htmlentities($_POST['notice_post'][$i], ENT_COMPAT, "UTF-8")),
				'cml_notice_page' => bin2hex(htmlentities($_POST['notice_page'][$i], ENT_COMPAT, "UTF-8")),
				'cml_notice_category' => '',
				'cml_enabled' => $_POST['lang-enabled'][$i],
				'cml_sort_id' => $_POST['sort-id'][$i],
				'cml_rtl' => @$_POST[ 'rtl' ][ $id ],
				'cml_date_format' => @$_POST[ 'dformat' ][ $i ],
				'cml_flag_path' => $flag_path ),
			    array('id' => $id),
			    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s'),
			    array('%d'));
	}
	
	//Se ho fatto un'operazione sulle lingue "forzo" il controllo sull'esistenza dei file .mo
	update_option("cml_check_language_file_exists", 1);
    endfor;

    //Delete
    if( ! empty( $_POST['delete'] ) && intval( $_POST[ "delete" ] ) > 0 ) :
      $delete = intval($_POST['delete']);

      $wpdb->query("DELETE FROM " . CECEPPA_ML_TABLE . " WHERE id = " . $delete);
      $wpdb->query(sprintf("DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_lang_1 = %d OR cml_post_lang_2 = %d", $delete, $delete));
      
      //Remove the column from CECEPPA_ML_RELATIONS table
      $wpdb->query( "ALTER TABLE " . CECEPPA_ML_RELATIONS . " DROP lang_" . $delete );
    endif;
  }
}
?>