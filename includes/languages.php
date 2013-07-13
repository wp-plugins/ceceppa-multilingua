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

    for($i = 0; $i < count($_POST['id']); $i++) :
	$id = $_POST['id'][$i];
	@list($lang, $lang_slug) = explode("@", $_POST['flags'][$i]);
	$default = (array_key_exists('default', $_POST) && $_POST['default'] == $id) ? 1 : 0;

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
				'cml_enabled' => 1),
			    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'));
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
				'cml_sort_id' => $_POST['sort-id'][$i]),
			    array('id' => $id),
			    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
			    array('%d'));
	}
    endfor;

    //Delete
    if(!empty($_POST['delete'])) :
      $delete = intval($_POST['delete']);

      $wpdb->query("DELETE FROM " . CECEPPA_ML_TABLE . " WHERE id = " . $delete);
      $wpdb->query(sprintf("DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_lang_1 = %d OR cml_post_lang_2 = %d", $delete, $delete));
    endif;
  }
}
?>