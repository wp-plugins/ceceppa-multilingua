<?php
/*
 * Questo è un file di ripiego, che viene utilizzato se ci sono problemi nel generare il file
 * settings.php
 *
 * In questo file le opzioni vengono recuperate dal database...
 */
  global $wpdb;

  //Db
  $_cml_settings[ 'cml_db_version' ] = get_option( 'cml_db_version', CECEPPA_DB_VERSION );
 
 //Lingue
  $_cml_settings[ 'default_language' ] = $wpdb->get_var("SELECT cml_language FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
  $_cml_settings[ 'default_language_id' ] = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
  $_cml_settings[ 'default_language_slug' ] = $wpdb->get_var("SELECT cml_language_slug FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
  $_cml_settings[ 'default_language_locale' ] = $wpdb->get_var("SELECT cml_locale FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
  $_cml_settings[ 'url_mode' ] = get_option( "cml_modification_mode", 1 );

  
  $_cml_settings[ 'cml_show_admin_notice' ] = get_option( 'cml_show_admin_notice', 1 );
  $_cml_settings[ 'cml_code_optimization' ] = get_option( 'cml_code_optimization', 1 );
  $_cml_settings[ 'cml_option_filter_posts' ] = get_option( 'cml_option_filter_posts', false );
  $_cml_settings[ 'cml_option_filter_translations' ] = get_option( 'cml_option_filter_translations', true );
  $_cml_settings[ 'cml_option_filter_search' ] = get_option('cml_option_filter_search', false);
  $_cml_settings[ 'cml_option_filter_form_class' ] = get_option('cml_option_filter_form_class', false );
  $_cml_settings[ 'cml_option_filter_query' ] = get_option('cml_option_filter_query');
  $_cml_settings[ 'cml_option_redirect' ] = get_option('cml_option_redirect', 'auto');
  $_cml_settings[ 'cml_option_flags_on_post' ] = get_option('cml_option_flags_on_post', true);
  $_cml_settings[ 'cml_option_flags_on_page' ] = get_option('cml_option_flags_on_page', true);
  $_cml_settings[ 'cml_option_flags_on_custom_type' ] = get_option('cml_option_flags_on_custom_type', 0);
  $_cml_settings[ 'cml_option_flags_on_pos' ] = get_option('cml_option_flags_on_pos', 'top');
  $_cml_settings[ 'cml_option_flags_on_the_loop' ] = get_option( 'cml_option_flags_on_the_loop' );

  $_cml_settings[ 'cml_option_notice' ] = get_option('cml_option_notice', 'notice');
  $_cml_settings[ 'cml_option_notice_pos' ] = get_option('cml_option_notice_pos', 'top');
  $_cml_settings[ 'cml_option_comments' ] = get_option('cml_option_comments', 'group');
  $_cml_settings[ 'cml_option_action_menu' ] = get_option( "cml_option_action_menu", true);
  $_cml_settings[ 'cml_add_flags_to_menu' ] = get_option("cml_add_flags_to_menu", 0);
  $_cml_settings[ 'cml_append_flags' ] = get_option("cml_append_flags", false);
  $_cml_settings[ 'cml_add_float_div' ] = get_option("cml_add_float_div", false);
  $_cml_settings[ 'cml_option_translate_categories' ] = get_option("cml_option_translate_categories", 0);
  $_cml_settings[ 'cml_option_filter_translations' ] = get_option( "cml_option_filter_translations", true );
  $_cml_settings[ 'cml_option_change_locale' ] = get_option( "cml_option_change_locale", 1);
  $_cml_settings[ 'cml_option_change_locale_admin' ] = get_option( 'cml_option_change_locale_admin', 1 );
  $_cml_settings[ 'cml_option_flags_on_size' ] = get_option('cml_option_flags_on_size', "small");
  $_cml_settings[ 'cml_options_flags_on_translations' ] = get_option( 'cml_options_flags_on_translations', 1 );
  $_cml_settings[ 'cml_option_notice_page' ] = get_option("cml_option_notice_page");
  $_cml_settings[ 'cml_option_notice_post' ] = get_option("cml_option_notice_post");
  $_cml_settings[ 'cml_option_notice_before' ] = stripslashes( get_option('cml_option_notice_before', '<h5 class="cml-notice">') );
  $_cml_settings[ 'cml_option_notice_after' ] = get_option('cml_option_notice_after', '</h5>');
  $_cml_settings[ 'cml_add_items_as' ] = get_option("cml_add_items_as");
  $_cml_settings[ 'cml_show_in_menu_size' ] = get_option("cml_show_in_menu_size", "small");
  $_cml_settings[ 'cml_show_in_menu_as' ] = get_option("cml_show_in_menu_as", 1);
  $_cml_settings[ 'cml_append_flags_to' ] = get_option("cml_append_flags_to");
  $_cml_settings[ 'cml_show_items_as' ] = get_option("cml_show_items_as", 1);
  $_cml_settings[ 'cml_show_items_size' ] = get_option("cml_show_items_size", "small");
  $_cml_settings[ 'cml_show_float_items_as' ] = get_option("cml_show_float_items_as", 1);
  $_cml_settings[ 'cml_show_float_items_size' ] = get_option("cml_show_float_items_size", "small");
  $_cml_settings[ 'cml_add_items_to' ] = get_option( 'cml_add_items_to' );
?>
