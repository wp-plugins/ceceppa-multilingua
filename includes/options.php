<?php

global $wpCeceppaML;
if(!is_object($wpCeceppaML)) die("Access denied");

require_once("donate.php");

class CeceppaMLOptions {
  public function __construct() {
    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');
    
    if(array_key_exists("options", $_POST) && wp_verify_nonce($_POST['cml_nonce_edit_settings'], 'cml_edit_settings')) :
      $this->update_options();
    endif;

    $this->add_meta_box();

    require_once('form_options.php');
  }

  function add_meta_box() {
    /* Tab 0: Flags */
    $input = '<label><input type="checkbox" id="to-menu" name="float-div" value="1" ' . checked(get_option('cml_add_float_div', false), true, false) . ' />&nbsp;';
    add_meta_box("cml_options_float_flags", $input .__('Add float div to website:', 'ceceppaml') . '</input></label>', "cml_option_flags_float", "cml_options_page_flags");
    
    $input = '<label><input type="checkbox" id="to-menu" name="append-flags" value="1" ' . checked(get_option('cml_append_flags', false), true, false) . ' />&nbsp;';
    add_meta_box("cml_options_append_flags", $input . __('Append flag to html element:', 'ceceppaml') . '</input></label>', "cml_options_flags_to_element", "cml_options_page_flags");

    $input = '<label><input type="checkbox" id="to-menu" name="to-menu" value="1" ' . checked(get_option('cml_add_flags_to_menu', false), true, false) . ' />&nbsp;';
    add_meta_box("cml_options_menu_flags", $input .__('Add flags to menu:', 'ceceppaml') . '</input></label>', "cml_options_flags_to_menu", "cml_options_page_flags");
  }

  function update_options() {
    global $wpdb;

    $tab = intval( $_POST['tab'] );

    switch($tab) {
      case 0:
	$this->update_flags_info();
	break;
      case 1:
	$this->update_actions();
	break;
      case 2:
	$this->update_filters();
	break;
    }
  }
  
  function update_flags_info() {
    //Float
    @update_option("cml_add_float_div", intval($_POST['float-div']));
      $css = addslashes($_POST['custom-css']);
      file_put_contents(CECEPPA_PLUGIN_PATH . "/css/float.css", $css);

      //Show as...
      @update_option("cml_show_float_items_as", intval($_POST['float-as']));

      //Flag size...
      @update_option("cml_show_float_items_size", $_POST['float-size']);

    //Append
    @update_option("cml_append_flags", intval($_POST['append-flags']));
    update_option("cml_append_flags_to", $_POST['id-class']);

      //Show as...
      @update_option("cml_show_items_as", intval($_POST['show-items-as']));
    
      //Flag size...
      @update_option("cml_show_items_size", $_POST['item-as-size']);

    //Menu
    @update_option("cml_add_flags_to_menu", intval($_POST['to-menu']));
    
      //Add items as...
      @update_option("cml_add_items_as", intval($_POST['add-as']));
      
      //Show as...
      @update_option("cml_show_in_menu_as", intval($_POST['show-as']));

      //Flag size...
      @update_option("cml_show_in_menu_size", $_POST['submenu-size']);
  }

  function update_actions() {
    //Add slug & url mode
    update_option("cml_add_slug_to_link", $_POST['add-slug']);
    update_option("cml_modification_mode", $_POST['url-mode']);
    
    //Traduzione categorie
    @update_option('cml_option_translate_categories', $_POST['categories']);

    //Redirect
    update_option("cml_option_redirect", $_POST['redirect']);
    update_option("cml_option_redirect_type", $_POST['redirect-type']);

    if(array_key_exists("posts", $_POST))
      update_option("cml_option_post_redirect", $_POST['posts']);

    //Flags
    @update_option("cml_option_flags_on_post", intval($_POST['flags-on-posts']));
    @update_option("cml_option_flags_on_page", intval($_POST['flags-on-pages']));
    @update_option("cml_option_flags_on_custom_type", intval($_POST['flags-on-custom']));
    @update_option("cml_option_flags_on_pos", $_POST['flags_on_pos']);

      //Size
      @update_option("cml_option_flags_on_size", $_POST['flag-size']);

    //Avviso
    @update_option("cml_option_notice", $_POST['notice']);
    @update_option("cml_option_notice_pos", $_POST['notice_pos']);
    @update_option("cml_option_notice_after", $_POST['notice_after']);
    @update_option("cml_option_notice_before", $_POST['notice_before']);
    @update_option("cml_option_notice_post", intval($_POST['notice-post']));
    @update_option("cml_option_notice_page", intval($_POST['notice-page']));

    //Commenti
    @update_option('cml_option_comments', $_POST['comments']);

    //Translate menu items?
    @update_option( "cml_option_action_menu", intval( $_POST['action-menu'] ) );
  }

  function update_filters() {
    //Change locale
    update_option("cml_option_change_locale", intval($_POST['change-locale']));

    //Filter posts
    @update_option("cml_option_filter_posts", intval($_POST['filter-posts']));

    //Filter translations
    @update_option("cml_option_filter_translations", intval($_POST['filter-translations']));

    //Filter query
    @update_option("cml_option_filter_query", intval($_POST['filter-query']));
	  
    //Filter search
    @update_option("cml_option_filter_search", intval($_POST['filter-search']));
    @update_option("cml_option_filter_form_class", $_POST['filter-form']);
  }    
}
?>