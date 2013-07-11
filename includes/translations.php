<?php
global $wpCeceppaML;

if(!is_object($wpCeceppaML)) die("Access denied");

class CeceppaMLTranslations {
  public function __construct() {

    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_style('ceceppaml-style');

    $langs = cml_get_languages(0);
    for($i = 0; $i < count($langs); $i++) :
      if($langs[$i]->cml_default == 1) :
	unset($langs[$i]);
	break;
      endif;
    endfor;

    if(array_key_exists("form", $_POST) && array_key_exists("id", $_POST)) :
      $this->update_translations($langs);
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
			      'cml_type' => 'S'),
			array('%s', '%d', '%s', '%s'));
	endif;
      endfor;

    endfor;
  }
}
?>