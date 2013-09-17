<?php
include_once ( CECEPPA_PLUGIN_PATH . '/Pgettext/Pgettext.php' );

use Pgettext\Pgettext as Pgettext;

function cml_generate_mo( $filename ) {
  try {
    //Tadaaaaa, file generato... genero il .mo
    Pgettext::msgfmt( $filename );
  } catch (Exception $e) {
    return $e->getMessage();
  }
  
  return "";
}

?>