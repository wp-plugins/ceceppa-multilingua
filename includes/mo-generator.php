<?php
include_once ( CECEPPA_PLUGIN_PATH . '/Pgettext/Pgettext.php' );

use Pgettext\Pgettext as Pgettext;

function cml_generate_mo( $filename ) {
  //Tadaaaaa, file generato... genero il .mo
  Pgettext::msgfmt( $filename );
}

?>