<?php
/*
Plugin Name: Ceceppa Multilingua
Plugin URI: http://www.ceceppa.eu/it/interessi/progetti/wp-progetti/ceceppa-multilingua-per-wordpress/
Description: Adds userfriendly multilingual content management and translation support into WordPress.
Version: 1.3.20
Author: Alessandro Senese aka Ceceppa
Author URI: http://www.ceceppa.eu/chi-sono
License: GPL3
Tags: multilingual, multi, language, admin, tinymce, qTranslate, Polyglot, bilingual, widget, switcher, professional, human, translation, service, multilingua
*/
/**
 * Ceceppa Multilanguage Blog :)
 * 
 * Most of flags are downloaded from http://blog.worldofemotions.com/danilka/
 * 
 */
// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

global $wpdb;

define('CECEPPA_DB_VERSION', 19);

define('CECEPPA_ML_TABLE', $wpdb->base_prefix . 'ceceppa_ml');
define('CECEPPA_ML_CATS', $wpdb->base_prefix . 'ceceppa_ml_cats');
define('CECEPPA_ML_POSTS', $wpdb->base_prefix . 'ceceppa_ml_posts');
define('CECEPPA_ML_PAGES', $wpdb->base_prefix . 'ceceppa_ml_posts');

/* Url modification mode */
define('PRE_NONE', 0);
define('PRE_LANG', 1);
define('PRE_PATH', 2);
define('PRE_DOMAIN', 3);

//Memorizzo gli id degli articoli/pagine al fine di poter "filtrare" i post per lingua
define('CECEPPA_ML_RELATIONS', $wpdb->base_prefix . 'ceceppa_ml_relations');

//Tabella delle traduzioni. Al momento mi appoggio a una tabella per le traduzioni in quanto non ho trovato nessun modo
//per generare un file .po direttamente da php
define('CECEPPA_ML_TRANS', $wpdb->base_prefix . 'ceceppa_ml_trans');     

define('CECEPPA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CECEPPA_PLUGIN_PATH', plugin_dir_path(__FILE__));

//LOCALE DIR
define('LOCALE_DIR', WP_CONTENT_DIR . "/languages");

//THEME LOCALE PATH
$cml_theme_locale_path = null;

//UPLOAD DIR
$upload_dir = wp_upload_dir();
define( 'CECEPPA_UPLOAD_DIR', $upload_dir[ 'basedir' ] . "/ceceppaml" );
define( 'CECEPPA_UPLOAD_URL', $upload_dir[ 'baseurl' ] . "/ceceppaml" );

require_once(CECEPPA_PLUGIN_PATH . 'functions.php');
require_once(CECEPPA_PLUGIN_PATH . 'utils.php');
require_once(CECEPPA_PLUGIN_PATH . 'shortcode.php');
require_once(CECEPPA_PLUGIN_PATH . 'widgets.php');
require_once(CECEPPA_PLUGIN_PATH . 'deprecated.php');

require_once(CECEPPA_PLUGIN_PATH . 'settings.php');

class CeceppaML {
  protected $_current_lang;              //Nome della lingua corrente
  protected $_current_lang_id;           //Id della lingua corrente
  protected $_current_lang_locale;       //"Locale Wordpress"
  protected $_default_language;         //Lingua di default
  protected $_default_language_id;      //Id della lingua predefinita
  protected $_default_language_slug;    //
  protected $_default_category;         //Categoria predefinita
  protected $_redirect_browser = 'auto';
  protected $_show_notice = 'notice';
  protected $_filter_search = true;
  protected $_filter_form_class = "searchform";
  protected $_no_translate_menu_item = false;
  protected $_url_mode = PRE_PATH;
  protected $_posts_of_lang = array();	//Al fine di evitare chiamate continue al db precarico nella variabile i post associati ad ogni lingua
  protected $_translations_by_lang = array();  //Memorizzo gli id di tutte le traduzioni per ogni lingua

  public function __construct() {
    global $wpdb, $_cml_settings;

    $this->_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $this->_homeUrl = home_url() . "/";
    $this->_base_url = str_replace("http://" . $_SERVER['HTTP_HOST'], "", get_option( 'home' ) );
    $this->_request_url = str_replace($this->_homeUrl, "", $this->_url);
    $this->_permalink_structure = get_option( "permalink_structure" );

    /*
    * Se utilizzo add_action per caricare gli script quando richiesto,
    * non mi vengono "caricati" nelle verie funzioni a disposizione :(
    */
    //$this->register_scripts();
    add_action('wp_enqueue_scripts', array(&$this, 'register_scripts'));

    //Creo le tabelle al primo avvio
    //Attivazione
    register_activation_hook( __FILE__, array( &$this, 'create_table' ) );
    if( is_admin() && $_cml_settings[ 'cml_db_version' ] < CECEPPA_DB_VERSION ) :
      require_once( CECEPPA_PLUGIN_PATH . "fix.php" );

      cml_fix_database();
    endif;

    /* 
     * Recupero le impostazioni della lingua di default 
     */
    $this->_default_language = $_cml_settings[ 'default_language' ];
    $this->_default_language_id = $_cml_settings[ 'default_language_id' ];
    $this->_default_language_slug = $_cml_settings[ 'default_language_slug' ];
    $this->_default_language_locale = $_cml_settings[ 'default_language_locale' ];
    $this->_url_mode = $_cml_settings[ 'url_mode' ];

    /* Il permalink di default ?p=## e la struttura /en/ non vanno per nulla d'accordo */
    if( empty( $this->_permalink_structure ) && $this->_url_mode == PRE_PATH ) $this->_url_mode = PRE_LANG;

    //Wow
    $this->preload_posts();

    /*
     * Opzioni disponibili per l'amministratore:
     *
     *   *) Configurazione lingue
     *   *) impostazioni plugin
     *   *) gestione campi extra nelle categorie
     */
    if(is_admin()) {
      require_once(CECEPPA_PLUGIN_PATH . 'includes/quickedit.php');

      add_action('admin_enqueue_scripts', array(&$this, 'register_scripts'));

      /* 
      * IT: Aggiungo la pagina delle opzioni
      * EPO: Agodojn
      * EN: Settings
      */
      add_action('admin_menu', array(&$this, 'add_option_page'));
      add_action('admin_menu', array(&$this, 'add_menu_flags'));

      /*
	* Aggiungo il box di collegamento nei post e nelle pagine 
	*/
      add_action('admin_init', array(&$this, 'add_meta_boxes'));
      add_action('edit_post', array(&$this, 'save_extra_post_fields'));
      add_action('delete_post', array(&$this, 'delete_extra_post_fields'));
      add_action('trash_post', array(&$this, 'delete_extra_post_fields'));
      add_action('untrash_post', array(&$this, 'code_optimization'));
      add_action('edit_page_form', array(&$this, 'save_extra_post_fields'));
      add_action('delete_page', array(&$this, 'delete_extra_post_fields'));
      add_action('publish_my_custom_post_type', array(&$this, 'save_extra_post_fields'));

      //Aggiungo le banidere all'elenco dei post, anche costum :)
      add_action('admin_init', array(&$this, 'add_flags_to_post_types'), 0);

      //Quickedit
      add_action('quick_edit_custom_box',  array(&$this, 'quick_edit_box'), 10, 2);

      //Filtri
      add_filter('parse_query', array(&$this, 'filter_all_posts_query'));
      add_action('restrict_manage_posts', array(&$this, 'filter_all_posts_page'));
      
      /*
      * Traduco i titoli dei Widget
      */
      add_filter('widget_title', array(&$this, 'admin_translate_widget_title'));
      
      /*
       * Visualizzo alcune informazioni....
       */
      if( isset( $_GET['cml-hide-experimental'] ) ) update_option('cml_experimental_category', 0);
      if(isset( $_GET['cml-hide-major-release'] ) ) update_option('cml_major_release', 0);
      add_action( 'admin_notices', array(&$this, 'admin_notices'));

      if(array_key_exists("cml-hide-notice", $_GET)) update_option('cml_show_admin_notice', 0);
      if( $_cml_settings[ 'cml_show_admin_notice' ] )
       add_action( 'admin_notices', array(&$this, 'show_admin_notice'));

      if(isset($_GET['cml_update_posts'])) :
       add_action('plugins_loaded', array(&$this, 'update_all_posts_language'));
      endif;

      if(isset($_GET['cml_remove_lang_notice'])) update_option("cml_check_language_file_exists", 0);
      if(isset($_GET['cml_hide_update_posts'])) update_option("cml_need_update_posts", false);
      if(isset($_GET['cml_need_code_optimization'])) update_option("cml_need_code_optimization", 0);
      
      /* Ottimizzazione codice */
      if( $_cml_settings[ 'cml_code_optimization' ] ) add_action( 'init', array( &$this, 'code_optimization' ) );

      /*
      * IT: Campi extra nelle categorie
      * EN: Category extra fields
      * EPO: 
      */
      add_action( 'category_add_form_fields', array( &$this, 'category_add_form_fields' ) );
      add_action( 'edited_category', array( &$this, 'save_extra_category_fileds' ) );
      add_action( 'created_category', array( &$this, 'save_extra_category_fileds' ) );
      add_action( 'deleted_term_taxonomy', array( &$this, 'delete_extra_category_fields' ) );
      add_action( 'edited_term', array( &$this, 'save_extra_category_fileds' ) );
      add_action( 'created_term', array( &$this, 'save_extra_category_fileds' ) );
      
      //Tengo d'occhio le modifiche apportate al menu
      add_action( 'wp_update_nav_menu', array( &$this, 'update_nav_menu' ), 0, 2 );
    } else {
      /*
      * Filtro gli articoli per lingua
      * Filter posts by language
      */
      if( $_cml_settings[ 'cml_option_filter_posts' ] ) {
        add_action( 'pre_get_posts', array( &$this, 'filter_posts_by_language' ), 0 );
      }


      /*
      * Nascondo i post "collegati", quindi tra quelli collegati visualizzo solo quelli
      * della lingua corrente
      */
      if( $_cml_settings[ "cml_option_filter_translations" ] || array_key_exists( "ht", $_GET ) ) {
        add_action('pre_get_posts', array(&$this, 'hide_translations'));
      }

      /*
      * Filtro i risultati della ricerca in modo da visualizzare solo gli articoli inerenti
      * alla lingua che l'utente stà visualizzando
      */
      $this->_filter_search = $_cml_settings[ 'cml_option_filter_search' ];
      $this->_filter_form_class = $_cml_settings[ 'cml_option_filter_form_class' ];

      /*
      * Filtro alcune query per lingua (Articoli più letti/commentati)
      */
      if( $_cml_settings[ 'cml_option_filter_query' ] ) {
        add_filter('query', array(&$this, 'filter_query'));
      }

      /*
      * Traduco i titoli dei Widget
      */
      add_filter('widget_title', array(&$this, 'translate_widget_title'));

      /*
      * Serve a reindirizzare il browser
      */
      $this->_redirect_browser = $_cml_settings[ 'cml_option_redirect' ];
      if( $this->_redirect_browser != 'nothing' )
        add_action( 'plugins_loaded', array( &$this, 'redirect_browser' ) );

      /*
      * Devo visualizzare le bandiere delle lingue disponibili?
      * Di defaut le abilito su post e pagine
      */
      if($_cml_settings[ 'cml_option_flags_on_post' ] ||
    	  $_cml_settings[ 'cml_option_flags_on_page' ] ||
    	  $_cml_settings[ 'cml_option_flags_on_custom_type' ] ) :

        if( $_cml_settings[ 'cml_option_flags_on_pos' ] == "bottom" ) {
            add_filter( "the_content", array( &$this, 'add_flags_on_bottom' ) );
        } else {
            add_filter( "the_title", array( &$this, 'add_flags_on_top' ), 10, 2 );
        }
      endif;

      /*
      * Devo visualizzare l'avviso?
      */
      $this->_show_notice = $_cml_settings[ 'cml_option_notice' ];
      $this->_show_notice_pos = $_cml_settings[ 'cml_option_notice_pos' ];
      if($this->_show_notice != 'nothing' && !is_admin()) //!is_home() && 
        add_action('the_content', array(&$this, 'show_notice'));

      //Commenti
      $this->_comments = $_cml_settings[ 'cml_option_comments' ];
      if($this->_comments == 'group') :
        add_filter('query', array(&$this, 'get_comments'));

        /*
        * Per i post "collegati" recupero il numero corretto di commenti facendo una somma di
        * tutti quelli presenti nei vari post.
        * Solo però se l'utente sceglie di "raggruppare" i commenti
        */
        add_filter('get_comments_number', array(&$this, 'get_comments_number'));
      endif;

      //E' stata utilizzata una pagina statica come homepage?
      if( cml_use_static_page() && $this->is_homepage() ) :
        //is_home a questo "punto" non funziona :(
        $this->update_current_lang();
    
        //Se è una lingua valida controllo...
        if( $this->_current_lang_id != $this->_default_language_id )
          add_filter( 'pre_get_posts', array( &$this, 'get_static_page' ), 0 );
      endif;
  
      //Filtro il link degli archivi :)
      add_filter('get_archives_link', array(&$this, 'translate_archives_link'));

      //Translate menu items    
      if( $_cml_settings[ "cml_option_action_menu" ] )
        add_filter('wp_setup_nav_menu_item', array( &$this, 'translate_menu_item' ) );

      //Translate categories
      add_filter('wp_get_object_terms', array(&$this, 'translate_object_terms'));
      add_filter('list_cats', array(&$this, 'translate_category'));
      add_filter('get_terms', array(&$this, 'translate_object_terms'));
      
      //Devo aggiungere le bandiere al menu?
      if( $_cml_settings[ "cml_add_flags_to_menu" ] == 1) :
        add_filter( 'wp_nav_menu_items', array(&$this, "add_flags_to_menu"), 10, 2 );
      endif;
      add_filter('wp_nav_menu_objects', array(&$this, 'get_nav_menu_items'));

      //Devo accodare le bandiere?
      if( $_cml_settings[ "cml_append_flags" ] == true ) :
    	add_action('wp_head', array(&$this, 'append_flags_to_element'));
      endif;

      //Elemento volante?
      if( $_cml_settings[ "cml_add_float_div" ] == true) :
        add_action('wp_footer', array(&$this, 'add_flying_flags'));
      endif;
    
      //La funzione clean_url si occupa di eliminare lo slug della lingua dal link,
      //affinché wp processi il link correttamente
      add_filter( 'pre_get_posts', array(&$this, 'clear_url') );

      //Titolo del blog/sito
      add_filter( 'bloginfo', array( &$this, 'bloginfo' ), 0 );
    }

    //Update current language
    add_action( 'init', array( &$this, 'update_current_lang' ) );

    /*
    * Nella pagina "menu", aggiungo una lista per ogni lingua
    */
    add_action('init', array(&$this, 'add_menus'));
    add_action('wp_footer', array(&$this, 'restore_default_menu'));
    
    //Translate post_link e page_link
    add_filter('post_link', array( &$this, 'translate_post_link' ), 0, 3);
    add_filter('post_type_link', array( &$this, 'translate_post_link' ), 0, 3);
    add_filter('page_link', array( &$this, 'translate_page_link' ), 0);

    add_filter('term_link', array( &$this, 'translate_term_link' ), 0);
    add_filter('term_name', array( &$this, 'translate_term_name' ), 0, 1);
    add_filter('single_cat_title', array( &$this, 'translate_term_name' ) );

    //Disabled by default
    $this->_translate_term_link = $_cml_settings["cml_option_translate_categories"];

    //Non posso tradurre il link delle categorie per il permalink di default :D
    if($this->_url_mode == PRE_PATH) :
      add_filter( 'tag_link', array( &$this, 'translate_category_url' ), 0 );
      add_filter( 'category_link', array( &$this, 'translate_category_url' ) );
    endif;
    
    //hide_category_translations si occupa anche di "sistemare" il link alla categoria "originale"
    if( $_cml_settings[ "cml_option_filter_translations" ] || array_key_exists( "ht", $_GET) || $this->_translate_term_link ) {
      //Se l'utente non vuole nascondere le traduzioni non vedo perché lo devo "forzare"? :)
      add_action('pre_get_posts', array(&$this, 'hide_category_translations'));
    }

    //Aggiungo lo switch delle lingue nella barra dell'amministratore
    add_action( 'admin_bar_menu', array(&$this, 'add_bar_menu'), 1000);

    //Next and Prev post
    add_filter( 'get_previous_post_where', array( &$this, 'get_previous_next_post_where' ) );
    add_filter( 'get_next_post_where', array( &$this, 'get_previous_next_post_where' ) );
    add_filter( 'wp_link_pages_link', array( &$this, 'link_pages_link'), 0, 2 );
    add_filter( 'get_pagenum_link', array( &$this, 'get_pagenum_link'), 0 );

    /*
    * Locale
    */
    //add_filter('query_vars', array(&$this, 'add_lang_query_vars'));
    if( !is_admin() && $_cml_settings[ "cml_option_change_locale" ] == 1 || ( is_admin() && $_cml_settings[ 'cml_option_change_locale_admin'] ) ) :
      add_filter( 'locale', array( &$this, 'setlocale' ), 0, 1 );
    endif;
  }

  /*
   * Aggiungo per ogni lingua il collegamento al menu, escluso per quella principale
   */
  function add_menus() {
    global $wpdb;

    load_plugin_textdomain('ceceppaml', false, dirname( plugin_basename( __FILE__ ) ) . '/po/');

    $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_sort_id"); //WHERE cml_default = 1 
    foreach($results as $result) :
        register_nav_menus(array("cml_menu_$result->cml_language_slug" => $result->cml_language));
    endforeach;

    $locations = get_theme_mod('nav_menu_locations');

    //Se non inizia per cml_ allora sarà quella definita dal tema :)
    if(is_array($locations)) :
      $keys = array_keys($locations);
      foreach($keys as $key) :
	if(!empty($key) && substr($key, 0, 4) != "cml_") :
	  $menu = $locations[$key];

	  break;
	endif;
      endforeach;

      if(!empty($menu)) {
	$this->_default_menu = $key;
	$this->_default_menu_id = $menu;
      }
    endif;
  }

  function restore_default_menu() {
    if(empty($this->_defalt_menu_id)) return;

    $locations = get_theme_mod('nav_menu_locations');

    $locations[$this->_default_menu] = $this->_default_menu_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  /*
   * Aggingo al menù pulsanti per ogni lingua "Creata"
   */
  function add_menu_flags() {
    global $wpdb;

    $query = "SELECT * FROM " . CECEPPA_ML_TABLE . " order by cml_sort_id";
    $results = $wpdb->get_results($query);
    
    foreach($results as $result) :
      $url = get_bloginfo('wpurl');
      $link = add_query_arg('lang', $result->cml_language_slug);
      
      $language = $result->cml_language_slug;

      $link = (strpos($link, "wp-admin/") === false) ? preg_replace('#[^?&]*/#i', '', $link) : preg_replace('#[^?&]*wp-admin/#i', '', $link);
      if(strpos($link, "?")===0||strpos($link, "index.php?")===0) {
          if(current_user_can('manage_options')) 
              $link = 'options-general.php?page=ceceppaml-language-page&godashboard=1&lang='.$language; 
          else
              $link = 'edit.php?lang='.$language;
      }

      $italic = ($this->_default_language_id == $result->id) ? "<u>" : "";
      $_italic = (empty($italic)) ? "" : "</u>";

      if($this->_current_lang_id == $result->id) :
	$italic = '<span class="cml_language_selected">';
	$_italic = "</span>";
      endif;

    add_menu_page($result->cml_language, $italic . $result->cml_language . $_italic, 'read', $link, null, //array(&$this, 'switch_language'), 
		    WP_PLUGIN_URL . '/ceceppa-multilingua/flags/tiny/' . $result->cml_flag . '.png');
    endforeach;
  }

  /**
   * Aggiungo un box nell'editor degli articoli e delle pagine con lo scopo di collegare gli articoli/pagine tradotti con quelli
   * scritti nella lingua di default
   */
  function add_meta_boxes() {
    add_meta_box('ceceppaml-meta-box', __('Post data', 'ceceppaml'), array(&$this, 'post_meta_box'), 'post', 'side', 'high');
    add_meta_box('ceceppaml-meta-box', __('Page data', 'ceceppaml'), array(&$this, 'page_meta_box'), 'page', 'side', 'high');
    
    add_meta_box('ceceppaml-menu-box', 'CeceppaML: ' . __('Flags', 'ceceppaml'), array(&$this, 'menu_meta_box'), 'nav-menus', 'side', 'default');

    //Aggiungo il box a tutti i tipi di post non "predefiniti"
    $post_types = get_post_types( array( '_builtin' => FALSE ), 'names'); 
    $posts = array("post", "page");
    foreach ($post_types as $post_type ) :
      if(!in_array($post_type, $posts)) :
	add_meta_box('ceceppaml-meta-box', __('Post data', 'ceceppaml'), array(&$this, 'post_meta_box'), $post_type, 'side', 'high');
      endif;
    endforeach;
  }
  
  /**
   * Aggiungo la pagina delle opzioni nella barra laterale di Wordpress
   */
  function add_option_page() {
    $page[] = add_menu_page('Ceceppa ML Options', __('Ceceppa Multilingua', 'ceceppaml'), 'administrator', 'ceceppaml-language-page', array(&$this, 'form_languages'), CECEPPA_PLUGIN_URL . '/images/logo_mini.png');

    $page[] = add_submenu_page( 'ceceppaml-language-page', '<div class="separator" /></div>', '<div class="separator" /></div>', 'administrator', '', null );

    $page[] = add_submenu_page('ceceppaml-language-page', __('Widget titles', 'ceceppaml'), __('Widget titles', 'ceceppaml'), 'manage_options', 'ceceppaml-widgettitles-page', array(&$this, 'form_widgettitles'));
    $page[] = add_submenu_page('ceceppaml-language-page', __('My translations', 'ceceppaml'), __('My translations', 'ceceppaml'), 'manage_options', 'ceceppaml-translations-page', array(&$this, 'form_translations'));

    $page[] = add_submenu_page( 'ceceppaml-language-page', '<div class="separator" /></div>', '<div class="separator" /></div>', 'administrator', '', null );

    $page[] = add_submenu_page('ceceppaml-language-page', __('Site Title'), __( 'Site Title' ) . "/" . __( 'Tagline' ), 'manage_options', 'ceceppaml-translations-title', array(&$this, 'form_translations'));
    $page[] = add_submenu_page('ceceppaml-language-page', __('Translate your theme', 'ceceppaml'), __( 'Translate your theme', 'ceceppaml' ), 'manage_options', 'ceceppaml-translations-plugins-themes', array(&$this, 'form_translations'));

    $page[] = add_submenu_page( 'ceceppaml-language-page', '<div class="separator" /></div>', '<div class="separator" /></div>', 'administrator', '', null );

    $page[] = add_submenu_page('ceceppaml-language-page', __('Show flags', 'ceceppaml'), __('Show flags', 'ceceppaml'), 'manage_options', 'ceceppaml-flags-page', array( &$this, 'form_options' ) );
    $page[] = add_submenu_page('ceceppaml-language-page', __('Settings', 'ceceppaml'), __('Settings', 'ceceppaml'), 'manage_options', 'ceceppaml-options-page', array(&$this, 'form_options'));

    $page[] = add_submenu_page( 'ceceppaml-language-page', '<div class="separator" /></div>', '<div class="separator" /></div>', 'administrator', '', null );

    $page[] = add_submenu_page('ceceppaml-language-page', __('Shortcode & Functions', 'ceceppaml'), __('Shortcode & Functions', 'ceceppaml'), 'manage_options', 'ceceppaml-shortcode-page', array(&$this, 'shortcode_page'));
    
    foreach( $page as $p ) :
      add_action( 'load-' . $p, array( &$this, 'add_help_tab' ) );
    endforeach;

    add_action( 'load-nav-menus.php', array( &$this, 'add_tips_to_help_tab' ) );
    add_action( 'load-options-reading.php', array( &$this, 'add_tips_to_help_tab' ) );
    add_action( 'load-options-general.php', array( &$this, 'add_tips_to_help_tab' ) );
  }

  function add_help_tab() {
    require_once 'includes/help_tab.php';
  }

  function add_tips_to_help_tab() {
    global $pagenow;
    $screen = get_current_screen();
    
    if( $pagenow == 'nav-menus.php' ) :
      $screen->add_help_tab( array(
			  'id'       => 'cml_nav_menu_help',
			  'title'    => 'Ceceppa Multilingua',
			  'content'  => "<p>" . $this->nav_menu_notice_text() . "</p>" ) );
    endif;
    
    if( $pagenow == 'options-reading.php' ) :
      $screen->add_help_tab( array(
			  'id'       => 'cml_nav_menu_help',
			  'title'    => 'Ceceppa Multilingua',
			  'content'  => "<p>" . $this->options_reading_help_text() . "</p>" ) );
    endif;
    
    if( $pagenow == 'options-general.php' ) :
      $screen->add_help_tab( array(
			  'id'       => 'cml_nav_menu_help',
			  'title'    => 'Ceceppa Multilingua',
			  'content'  => "<p>" . $this->options_reading_help_text() . "</p>" ) );
    endif;
  }

  /*
   * Devo richiamare questa funzione sull'evento "admin_init" sennò mi "perdo" alcuni tipi di post :(
   */
  function add_flags_to_post_types() {
    $post_types=get_post_types('','names');
    foreach ($post_types as $type ) :
      add_action("manage_${type}_posts_custom_column", array(&$this, 'add_flag_column'), 10, 2);
      add_filter("manage_${type}_posts_columns" , array(&$this, 'add_flags_columns'));
    endforeach;

    //Aggiungo i campi extra alle "custom taxonomies"
    $taxonomies=get_taxonomies();
    foreach($taxonomies as $taxonomy) :
      add_action("${taxonomy}_edit_form_fields", array( &$this, 'category_edit_form_fields' ) ); 
      add_action("${taxonomy}_add_form_fields", array( &$this, 'category_add_form_fields' ) );
    endforeach;
  }

  /**
    * Aggiungo le bandiere vicino al titolo del post nella pagina "Tutti gli articoli"
    */
  function add_flags_columns($columns) {
    wp_enqueue_script("ceceppa-tipsy");
    wp_enqueue_script("ceceppaml-js");
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppa-tipsy');

    $langs = cml_get_languages(0);

    //Non sono riuscito a trovare un altro modo per ridimensionare la larghezza del th...
    wp_enqueue_style('ceceppaml-style-all-posts', WP_PLUGIN_URL . '/ceceppa-multilingua/css/all_posts.php?langs=' . count($langs));

    $clang = isset( $_GET['cml_language'] ) ? intval ( $_GET['cml_language'] ) : $this->_default_language_id;
    foreach($langs as $lang) :
      $class = ( $lang->id == $clang ) ? "cml-filter-current" : "";

      $a = add_query_arg( array( "cml_language" => $lang->id ) );
      $img .= "<a class=\"$class\" href=\"$a\" title=\"" . __('Show only posts/pages in: ', 'ceceppaml') . "<b>$lang->cml_language</b>\"><img src=\"" . cml_get_flag_by_lang_id($lang->id, "small") . "\" width=\"20\" /></a>";
    endforeach;

    $cols = array_merge(array_slice($columns, 0, 2),
			array("cml_flags" => $img),
			array_slice($columns, 2));

    return $cols;
  }

    function add_flag_column($col_name, $id) {
        if($col_name !== "cml_flags") return;

        if (!isset($_GET['post_type']))
            $post_type = 'post';
        else
            $post_type = $_GET['post_type'];

        $langs = cml_get_languages(0);
        foreach($langs as $lang) :
            //Recupero la lingua del post/pagina
            $xid = $this->get_language_id_by_post_id($id);

            $link = cml_get_linked_post($xid, null, $id, $lang->id);
            if(!empty($link)) {
                $title = "<br />" . get_the_title($link);
                echo '<a href="' . get_edit_post_link($link) . '">';
                echo '    <img class="_tipsy" src="' . cml_get_flag_by_lang_id($lang->id, "small") . '" title="' . __('Edit post: ', 'ceceppaml') . $title . '"/>';
                echo '</a>';
            } else {
                global $wpdb;
    
                //Cerco di recuperare l'id     dell'articolo collegato alla lingua di default
                $query = sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, $id);
                $xid = $wpdb->get_var($query);
    
                $id = (empty($xid)) ? $id : $xid;
                echo '<a href="' . get_bloginfo("url") . '/wp-admin/post-new.php?post_type=' . $post_type . '&link-to=' . $id . '&page-lang=' . $lang->id . '">';
                echo '    <img class="add" src="' . CECEPPA_PLUGIN_URL . 'images/add.png" title="' . __('Translate', 'ceceppaml') . '" />';
                echo '</a>';
            }
        endforeach;
    }

    /*
     * Permetto di modificare lingua e articolo collegato nella finestra quickedit
     */
    function quick_edit_box($column_name, $post_type) {
      if($column_name != 'cml_flags') return;
      ?>
      <fieldset class="inline-edit-col-left">
      <div class="inline-edit-col">
	  <span class="title">Language:</span>
	  <?php wp_nonce_field('cml_edit_post','cml_nonce_edit_field'); ?>
	  <?php cml_dropdown_langs("post_lang", 0, false, true, null, "", 0); ?>
      </div>
      </fieldset>
      <?php
    }

    /*
     * Aggiungo le bandiere sotto al titolo del post
     */
    function add_flags_on_top($title, $id = -1) {
      global $_cml_settings;

      if( isset( $this->_title_applied ) ) return $title;
        if( $id < 0 ) return $title;
        if( is_single() && ! $_cml_settings['cml_option_flags_on_post'] ) return $title;
        if( is_page() && ! $_cml_settings[ 'cml_option_flags_on_page' ] ) return $title;
        if( cml_is_custom_post_type() && ! $_cml_settings[ 'cml_option_flags_on_custom_type' ] ) return $title;
        if( ! in_the_loop() || is_category() ) return $title;

        global $post;
        /* Mi serve per evitare che mi trovi bandiere ovunque :D.
         * Non posso utilizzare in_the_loop senno rischio di trovarmi bandiere anche vicino ai
         * "post correlati" a piè di pagina :(
         * Ho bisogno di modificare le "curly quotes" in "double quote", sennò il confronto fallisce :(
        */
        if( esc_attr( $post->post_title ) == removesmartquotes( $title ) ) :
          if( ! in_the_loop() || ! $_cml_settings[ 'cml_option_flags_on_the_loop' ] )
            $this->_title_applied = true;
  
          $size = $_cml_settings['cml_option_flags_on_size'];
          
          $args = array( "class" => "cml_flags_on_top", "size" => $size );
          $flags = ( $_cml_settings[ 'cml_options_flags_on_translations' ] ) ?
                cml_shortcode_other_langs_available( $args ) : cml_show_available_langs( $args );
          return $title . $flags;
        endif;

	return $title;
    }

    function add_flags_on_bottom($title) {
        if( is_single() && ! $_cml_settings['cml_option_flags_on_post'] ) return $title;
        if(is_page() && ! $_cml_settings[ 'cml_option_flags_on_page' ] ) return $title;
        if(cml_is_custom_post_type() && ! $_cml_settings[ 'cml_option_flags_on_custom_type' ] ) return $title;

	$size = $_cml_settings['cml_option_flags_on_size'];
        return $title . cml_show_available_langs(array("class" => "cml_flags_on_top", "size" => $size));
    }

  /**
   * NUOVA CATEGORIA
   * Campi necessari per collegare la nuova categoria con quella della lingua di default
   */
  function category_add_form_fields($tag) {
        wp_enqueue_script('ceceppaml-cat');
?>
        <div class="form-field cml-form-field">
        <?php
            $langs = cml_get_languages(0, 0);

            foreach($langs as $lang) : ?>
                    <label for="cat_name[<?php echo $lang->id ?>]">
                        <img src="<?php echo cml_get_flag($lang->cml_flag) ?>" />
                        <?php echo $lang->cml_language ?>
                    </label>
                    <input type="text" name="cat_name[<?php echo $lang->id ?>]" id="cat_name[<?php echo $lang->id ?>]" size="40" />
                <?php 
            endforeach;
        ?>
        </div>
<?php
  }

  /**
   * EDIT CATEGORIA
   * Campi necessari per modifica l'abbinamento della categoria con quella della lingua di default.
   */
  function category_edit_form_fields($tag) {
    wp_enqueue_script('ceceppaml-cat');

    $t_id = $tag->term_id;
    $linked_cat = get_option("cml_category_$t_id");
    $cat_lang = get_option("cml_category_lang_$t_id");
?>
    <?php
      $langs = cml_get_languages(0);

      foreach($langs as $lang) :
	  if(!$lang->cml_default) :
	      $id = $lang->id;
    ?>
    <tr class="form-field cml-form-field">
	<td>
	    <img src="<?php echo cml_get_flag($lang->cml_flag); ?>" />
	    <?php echo $lang->cml_language ?>
	</td>
	<td>
	    <input type="text" name="cat_name[<?php echo $lang->id ?>]" id="cat_name_<?php echo $lang->id ?>" size="40" value="<?php echo get_option("cml_category_" . $t_id . "_lang_$id", $tag->name) ?>"/>
	</td>
    </tr>
    <?php
	    endif;
	endforeach;
    ?>
<?php 
  }

  /** 
   * Creo le tabelle necessarie al funzionamento del plugin
   */
  function create_table() {
    global $wpdb, $_cml_settings;

    //Server per poter utilizare la funzione dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /*
     * CECEPPA_ML_TABLE: Contiene le informazioni sulle lingue da gestire
     */
    $table_name = CECEPPA_ML_TABLE;
    $first_time = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
    {
      /**
	* Tabella contenente le lingue da gestire
	*
	*  cml_default     - indica se è la lingua predefinita
	*  cml_flag        - bandiera della lingua
	*  cml_language    - nome della nuova lingua
	*  cml_category_id - categoria base a cui è collegata la nuova lingua
	*  cml_category    - descrizione della categoria a cui è collegata la lingua 
	*/
      $sql = "CREATE TABLE $table_name (
      id INT(11) NOT NULL AUTO_INCREMENT,
      cml_default INT(1),
      cml_flag VARCHAR(100),
      cml_language TEXT,
      cml_language_slug TEXT,
      cml_notice_post TEXT,
      cml_notice_page TEXT,
      cml_notice_category TEXT,
      cml_locale TEXT,
      cml_enabled INT,
      cml_sort_id INT,
      cml_flag_path TEXT,
      PRIMARY KEY  id (id)
      ) ENGINE=InnoDB CHARACTER SET=utf8;";

      dbDelta($sql);
    }

    $query = "UPDATE " . CECEPPA_ML_TABLE . " SET cml_enabled = 1 WHERE cml_enabled IS NULL";
    $wpdb->query($query);

    if($first_time) :
      update_option("cml_need_update_posts", true);
      update_option("cml_first_install", true);

      /* Per comodità  creo nel database un record con la lingua corrente */
      $locale = get_locale();
      $language = __("Default language");

      require_once(CECEPPA_PLUGIN_PATH . "includes/locales_codes.php");
      $keys = array_keys($_langs);
      foreach($keys as $key) {
	  if($_langs[$key] == $locale) {
	      $language = $key;
	  }
      }

	/* Creo nel database la riga per la lingua corrente di wordpress */
	$wpdb->insert(CECEPPA_ML_TABLE,
		      array('cml_default' => 1,
			    'cml_language' => $language,
			    'cml_language_slug' => strtolower(substr($language, 0, 2)),
			    'cml_locale' => $locale,
			    'cml_enabled' => 1,
			    'cml_flag' => $locale),
		      array('%d', '%s', '%s', '%s', '%d', '%s'));
	  
	$this->_installed_language = $language;
	$this->_installed_language_id = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE);
    endif;

    /**
     * Creo la tabella che conterrà  gli abbinamenti tra i post.
     * La mia necessità è quella di creare un collegamento bidirezionale tra i post per "passare" da una lingua all'altra.
     *
     * Utilizzerò una tabella dove nelle prime 2 collonne saranno memorizzati tutti i figli e nelle seconde due i "padri":
     *    id_categoria_1 | id_post_1 | id_categoria_2 | id_post2
     * 
     * Per poter funzionare correttamente tutti gli articoli dovranno essere collegati alla lingua principale, 
     * o comunque, nel caso vogliamo scrivere un articolo che non "prevede" la lingua principale, dovranno avere tutti un unico post di riferimento.
     * Esempio:
     *  Voglio gestire 3 lingue nel mio blog: Italiano [IT] (predefinita), Inglese [EN], Esperanto [EPO].
     *  
     *     1. Scrivo l'articolo in italiano
     *     2. Scrivo l'articolo in inglese e lo collego a quello italiano
     *     3. Scrivo l'articolo in esperanto e lo collego a quello italiano
     *
     *  Supponendo che gli id degli articoli siano rispettivamente 1, 2, 3 nella tabella avrò memorizzato i seguenti valori:
     *
     *     [EN]  | 2 | [IT] | 1
     *     [EPO] | 3 | [IT] | 1
     *
     *  Ora se voglio passare dall'articolo italiano a quello inglese mi basta fare una query impostando come condizioni:
     *    * id del post italiano, id della lingua italiana e id della lingua inglese
     *
     *  Mentre se voglio passare dalla lingua inglese a quella esperanto, analogamente a quanto fatto prima, dovrò eseguire 2 query
     *  per ottenere l'id del post che desidero.
     * 
     * Ora supponiamo di voler scrivere un post solo in Esperanto e Inglese una delle due lingue dovrà diventare il "fulcro" per le altre
     * ES:
     *     1. Scrivo il post in esperanto
     *     2. Scrivo il post in inglese e lo collego a quello esperanto
     *     3. Scrivo il post in un'altra lingua, che non sia l'italiano, e la collego a quello in esperanto.
     *
     * Anche in questo caso tramite la struttura della tabella che implementato riesco a passare tranquillamenta da una lingua all'altra
     *
     * Quindi per ogni post che scriverò dovrò avere uno E UNO SOLTATO di post di riferimento a cui tutti gli altri si agggancerano.
     * Cambi la lingua di default???? Non è un problema, scrivi tranquillamenta i nuovi post in riferimento alla nuova lingua :)
     *
     * Lo stesso ragionamento vale anche per la tabella delle categorie che trovi sotto.
     */
    $table_name = CECEPPA_ML_POSTS;
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $query = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        cml_post_lang_1 INT(11),
        cml_post_id_1 INT(11),
        cml_post_lang_2 INT(11),
        cml_post_id_2 INT(11),
        PRIMARY KEY  id (id)) ENGINE=InnoDB CHARACTER SET=utf8;";

      dbDelta($query);
   }
        
    /**
     * Per le traduzioni momentaneamente mi appoggio ad un database.
     * Appena trovo il modo di gestire tutto da php e generare file po al volo rimuovo la tabella :)
     *
     */
    $table_name = CECEPPA_ML_TRANS;
    if( $_cml_settings[ 'cml_db_version' ] <= 9 ) :
      if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ) {
	$sql = "ALTER TABLE " . CECEPPA_ML_TRANS . " ADD COLUMN `cml_type` TEXT";
	$wpdb->query($sql);
	$wpdb->query("UPDATE " . CECEPPA_ML_TRANS . " SET cml_type = 'W'");
      }
    endif;

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $query = "CREATE TABLE  $table_name (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	cml_text TEXT NOT NULL ,
	`cml_lang_id` INT NOT NULL ,
	`cml_translation` TEXT,
	`cml_type` TEXT) ENGINE=InnoDB CHARACTER SET=utf8;";

      dbDelta($query);
    }

      //Memorizzo
    $table_name = CECEPPA_ML_CATS;
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 14) :
      $wpdb->query("DROP TABLE $table_name");
    endif;
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $query = "CREATE TABLE  $table_name (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	cml_cat_id INT NOT NULL,
	cml_cat_name VARCHAR(10000) NOT NULL ,
	`cml_cat_lang_id` INT NOT NULL ,
	`cml_cat_translation` VARCHAR(10000)) ENGINE=InnoDB CHARACTER SET=utf8;";
      dbDelta($query);
    }

    //for updates
    update_option("cml_db_version", CECEPPA_DB_VERSION);
  }

    function register_scripts() {
      global $_cml_settings;

      //Javascript
      wp_register_script('ceceppa-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.dd.min.js');
      wp_register_script('ceceppaml-js', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.js', array('ceceppa-dd'));
      wp_register_script('ceceppa-tipsy', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.tipsy.js');
      wp_register_script('ceceppaml-cat', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa-cat.js');
      wp_register_script('ceceppaml-tag', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa-tag.js');
      //    wp_enqueue_script('ceceppa-search', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.search.js', array('jquery'));

      //Css
      wp_register_style('ceceppaml-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/ceceppaml.css');
      wp_register_style('ceceppaml-lang', WP_PLUGIN_URL . '/ceceppa-multilingua/css/cmllang.css');
      wp_register_style('ceceppaml-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/css/dd.css');
      wp_register_style('ceceppa-tipsy', WP_PLUGIN_URL . '/ceceppa-multilingua/css/tipsy.css');
      wp_register_style('ceceppaml-widget-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/widget.css');
      wp_enqueue_style('ceceppaml-common-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/common.css');
      
      if( $_cml_settings["cml_add_float_div"] ) :
	if( file_exists( CECEPPA_UPLOAD_DIR . "/float.css" ) )
	  wp_enqueue_style('ceceppaml-flying', CECEPPA_UPLOAD_URL . '/float.css' );
	else
	  wp_enqueue_style('ceceppaml-flying', WP_PLUGIN_URL . '/ceceppa-multilingua/css/float.css');
      endif;
    }

    function enqueue_script_search() {
        wp_enqueue_script('ceceppa-search', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.search.js', array('jquery'));
    }

    function enqueue_script_append_to() {
        wp_enqueue_script('ceceppa-append', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.append.js', array('jquery'));
    }

  /*
   * Filtro gli articoli più letti aggiungendo alla
   * query la condizione sugli id dei post
   */
  function filter_least_read_post($query, $pos) {
    //Recupero tutti i post collegati alla lingua corrente
    $posts = $this->get_posts_of_language();
    $where = " AND post_id IN (" . implode(", ", $posts) . ") ";

    //Aggiungo il $where prima della clausula ORDER
    $query = substr($query, 0, $pos) . $where . substr($query, $pos);
    return $query;
//    echo preg_replace( '~LIMIT \d+, \d+~', $replacement, $query );
  }

  /*
   * Filtro gli articoli più commentati aggiungendo nella
   * query la condizione sugli id dei post
   */
  function filter_most_commented($query, $pos) {
    //Recupero tutti i post collegati alla lingua corrente
    $posts = $this->get_posts_of_language();
    $where = " AND id IN (" . implode(", ", $posts) . ") ";
    
    //Aggiungo il $where prima della clausula ORDER
    $query = substr($query, 0, $pos) . $where . substr($query, $pos);
    return $query;
  }

  /*
   * Filtro l'archivio
   */
  function filter_archives($query, $pos) {
    //Recupero tutti i post collegati alla lingua corrente
    $posts = $this->get_posts_of_language();

    $where = " AND id IN (" . implode(", ", $posts) . ") ";

    //Aggiungo il $where prima della clausula ORDER
    $query = substr($query, 0, $pos) . $where . substr($query, $pos);
    return $query;
  }

    /**
     * Aggiungo il filtro "lingua" alla pagina "Tutti gli articoli", così
     * posso filtrare l'elenco a solo gli articoli della lingua che mi interessa
     */
    function filter_all_posts_page() {
      //Se sto nel cestino di default visualizzo tutti gli articoli :)
      $d = $this->_default_language_id;
      if(isset($_GET['post_status']) && in_array($_GET['post_status'], array("draft", "trash"))) $d = 0;
      $d = isset($_GET['cml_language']) ? $_GET['cml_language'] : $d;

      //All languages
      echo '<label class="cml-filter-label">&nbsp;' . __( 'Show only posts/page in:', 'ceceppaml' ) . '</label>';
      cml_dropdown_langs("cml_language", $d, false, true, __('Show all languages', 'ceceppaml'), -1);
      
      $checked = isset ( $_GET['cml_no_translation'] ) ? 1 : 0;
      if( empty($_GET) ) $checked = 1;

      echo '<input class="_tipsy" name="cml_no_translation" type="checkbox" title="' . __( 'Show also posts withouth translation', 'ceceppaml' ) . ' " value="1" ' . checked( $checked, 1, false ) . '/>&nbsp;&nbsp;&nbsp;';
    }

    /**
     * Filtro la query de "Tutti gli articoli"
     *
     */
    function filter_all_posts_query($query) {
        global $pagenow, $wpdb;

        if (!array_key_exists('post_type', $_GET))
            $post_type = 'post';
        else
            $post_type = $_GET['post_type'];

        //Se sto nel cestino di default visualizzo tutti gli articoli :)
	$d = $this->_default_language_id;
	if(isset($_GET['post_status']) && in_array($_GET['post_status'], array("draft", "trash"))) $d = 0;
        $id = array_key_exists('cml_language', $_GET) ? intval($_GET['cml_language']) : $d;

        if(is_admin() && $pagenow == "edit.php") :
	  if($id > 0) :
	    $posts = $this->get_posts_of_language($id);;

	    //non devo visualizzare i post senza traduzione
	    if( isset( $_GET['cml_no_translation'] ) || empty( $_GET ) ) :
	      $query->query_vars['post__not_in'] = get_option( "cml_hide_posts_for_lang_" . $d );
	    else:
	      $query->query_vars['post__in'] = $posts;
	    endif;
	  endif;
	endif;

	return $query;
    }
    
  /*
  * Dico a wordpress di restituire i post appartenenti alla categoria $ceceppa_current_language_category
  *
  */
  function filter_posts_by_language( $wp_query ) {
    global $wpdb;

    //if( ! in_the_loop() ) return;
    if( ! is_search() ) {
      if( is_single() || is_admin() || isCrawler() || is_page() || is_preview() ) return;
    } else {
      if( ! $this->_filter_search ) return;
    }

    //Recupero tutti i post associati alla lingua corrente
    $posts = $this->get_posts_of_language( $this->_current_lang_id );

    /*
     * Dato che ho avuto un pò di noie con i nav_menu_items che mi provocavano
     * menù "doppi" in homepage, nascondo i post delle altre lingue, invece di
     * forzare solo quelli della lingua corrente, in quanto nel file fix.php
     * ignoro i nav_menu_items, ma seleziono solo i post e pagine...
     */
    $langs = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " WHERE cml_enabled = 1");
    
    if( ! in_the_loop() ) {
      $exclude = get_option( 'cml_exclude_posts_for_' . $this->_current_lang_id );
      if( empty( $exclude ) ) {
        $exclude = array();
        foreach( $langs as $lang ) {
          if( $lang->id == $this->_current_lang_id ) continue;
          
          $exclude = array_merge( $exclude, $this->get_posts_of_language( $lang->id ) );
        }
        //Escludo tutti i post senza lingua, che quindi devono essere visibili anche nella mia lingua...
        $posts = $this->get_posts_of_language( $this->_current_lang_id );
        foreach( $exclude as $i => $h ) {
          if( in_array( $h, $posts ) ) {
            unset( $exclude[ $i ] );
          }
        }
      }
    } else {
      //Recupero tutti i post associati alla lingua corrente
      $posts = $this->get_posts_of_language( $this->_current_lang_id );
  
      if( ! empty ( $posts ) ) :
        $wp_query->query_vars[ 'post__in' ] = $posts;
      endif;
    }

    if( !isset( $this->_hide_posts ) || empty( $this->_hide_posts ) ) :
      $this->_hide_posts = array();

      $this->_hide_posts = get_option( "cml_hide_posts_for_lang_" . $this->_current_lang_id );
    endif;

    if( ! is_array( $this->_hide_posts ) ) $this->_hide_posts = array();
    if( ! is_array( $exclude ) ) $exclude = array();

    $wp_query->query_vars[ 'post__not_in' ] = array_merge( $this->_hide_posts, $exclude );

    $this->change_menu();
  }

    /*
     * Nascondo i post tradotti
     */
    function hide_translations( $wp_query ) {
      global $wpdb;

      if( $wp_query != null && ( is_page() || is_single() || isCrawler() ) ) return;
      if(is_preview() || isset($_GET['preview'])) return;

      if( !isset( $this->_hide_posts ) || empty( $this->_hide_posts ) ) :
	$this->_hide_posts = array();

	$this->_hide_posts = get_option( "cml_hide_posts_for_lang_" . $this->_current_lang_id );
      endif;

      //Al momento utilizzo la vecchia funzione non ottimizzata per la visualizzazione dei tag
      if( is_tag() ) cml_deprecated_hide_translations_for_tags($wp_query);

      if($wp_query != null && is_object($wp_query) && is_array($this->_hide_posts)) :
	$wp_query->query_vars['post__not_in'] = $this->_hide_posts;

	return $this->_hide_posts;
      endif;

      return $this->_hide_posts;
    }

  /**
    * Questa funzione si occupa di
    * "Filtrare tutte le query WordPress allo scopo di filtrare automaticamente gli articoli più letti, più commentati, etc"
    *
    * Invece di:
    *   1) recuperare tutte le categorie associate alla lingua
    *   2) apportare modifiche alla query, per esempio modificando la voce post_id IN (....)
    *
    *
    * preferisco aggiungere un'altra clausola alla query del tipo: category_id IN (....), evitando quindi di 
    * eseguire anche il punto 2
  */
  function filter_query($query) {
//     echo "zk".$query;

    //Filtro la query "Articoli più letti" (Least Read Post)
    $pos = strpos($query, 'ORDER BY m.meta_value');
    if(FALSE === $pos)
    {
    } else {
      return $this->filter_least_read_post($query, $pos);
    }
    
    //Articoli più commentati
    $pos = strpos($query, 'ORDER BY comment_count');
    if(FALSE === $pos)
    {
    } else {
      return $this->filter_most_commented($query, $pos);
    }

    //Archivio
    $pos = strpos($query, 'GROUP BY YEAR(post_date)');
    if(FALSE === $pos)
    {
    } else {
      return $this->filter_archives($query, $pos);
    }

    /*
    //Tag cloud: don't work, query is too complex to manipulate :(
    $pos = strpos($query, 'ORDER BY tt.count DESC');
    if(FALSE === $pos) {
    } else {
      return $this->filter_tag_cloud($query, $pos);
    }
    */

    //Non ho trovato niente (Nothing to do)
    return $query;
  }
  
  /**
    * Restituisco l'id della categoria a partire dal nome
    *
    * @return category id
    */
  function get_category_id( $cat_name ) {
    $term = get_term_by('name', $cat_name, 'category');

    return (is_object($term)) ? $term->term_id : $term;
  }

  /**
   * Recupero il "padre" della categoria specificata
   */
  function get_category_parent($cat_id) {
    if(empty($cat_id)) return;

    $cats = explode("/", get_category_parents($cat_id, false, "/", true));

    return $cats[0];
  }

    function get_language_locale_by_id($id) {
        global $wpdb;
        $query = sprintf("SELECT cml_locale FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $id);

        return $wpdb->get_var($query);
    }
  function get_language_slug_by_id($id) {
    global $wpdb;

    return $wpdb->get_var(sprintf("SELECT cml_language_slug FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $id));
  }
    
  function get_language_id_by_locale($locale) {
    global $wpdb;
    
    $query = sprintf("SELECT id FROM %s WHERE cml_locale IN ('%s')", CECEPPA_ML_TABLE, $locale);

    return $wpdb->get_var($query);
  }

  function get_language_id_by_slug($slug) {
    global $wpdb;

    $query = sprintf("SELECT id FROM %s WHERE cml_language_slug = '%s'", CECEPPA_ML_TABLE, $slug);
    return $wpdb->get_var($query);
  }
  
  function get_language_id_by_post_id($post_id) {
    global $wpdb;

    $lang = get_option( "cml_page_lang_$post_id" );
    if(!empty($lang) || $lang == 0) return $lang;

    if( !isset( $this->_language_detected ) ) $this->update_current_lang();

    //Cerco prima nella lingua corrente
    $array = $this->_posts_of_lang[$this->_current_lang_id];
    if(is_array($array) && in_array($post_id, $array)) 
      return $this->_current_lang_id;

    //Evito di cercare 2 volte nello stesso posto :)
    $tmp = $this->_posts_of_lang;
    unset($tmp[$this->_current_lang_id]);
    $keys = array_keys($tmp);
    foreach($keys as $key) :
      $val = @in_array($post_id, $tmp[$key]);
      if(!empty($val)) :
	return $key > 0 ? $key : $this->_current_lang_id;
      endif;
    endforeach;

    //Ricerca fallita, mi affido al database
    $query = sprintf("SELECT cml_post_lang_1 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, $post_id);
    $val = $wpdb->get_var($query);

    return $val;
  }

  function get_language_id_by_page_id($page_id) {
    return $this->get_language_id_by_post_id($page_id);
  }

  /**
   * Form per la gestione delle lingue
   */
  function form_languages() {
    wp_enqueue_style("ceceppaml-style");

    require_once(CECEPPA_PLUGIN_PATH . 'includes/languages.php');

    new CeceppaMLLanguages();
  }

  /**
   * Form per le impostazioni del plugin
   */
  function form_options() {
    require_once(CECEPPA_PLUGIN_PATH . "includes/options.php");

    new CeceppaMLOptions();
  }

  function form_translations() {
    require_once(CECEPPA_PLUGIN_PATH . "includes/translations.php");
    
    new CeceppaMLTranslations();
  }

  /*
   * Form per la traduzione dei titoli dei widget
   */
  function form_widgettitles() {
    global $wpdb;

    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');

    if(isset($_POST['form'])) {
      $sql = "";

      //Nuovo record o da modificare?
      $wpdb->query("DELETE FROM " . CECEPPA_ML_TRANS);

      if(isset($_POST['action'])) :
	$langs = cml_get_languages(0);
	//Per ogni lingua
	foreach($langs as $lang) :
	  if(!empty($_POST['lang_' . $lang->id])) :
	    $i = 0;
	    $titles = $_POST['lang_' . $lang->id];

	    foreach($titles as $text) :
	      //       if(empty($id)) {
	      $title = $_POST['string'][$i];
	      //$text = htmlentities($text);

	      //$title = htmlentities($title);
	      $query = sprintf("INSERT INTO %s (cml_text, cml_lang_id, cml_translation, cml_type) VALUES (HEX('%s'), '%d', HEX('%s'), 'W')",
				  CECEPPA_ML_TRANS, strtolower(addslashes($title)), $lang->id, addslashes($text));

	      $wpdb->query($query);
	      
	      $i++;
	    endforeach;
	  endif;
	endforeach;
      endif; //-
    }

    //Evito che l'output vada a video
    ob_start();
    
    //Richiamo la sidebar, così wordpress mi richiamerà la funzione admin_translate_widget_title per ogni titolo dei widget
    global $wp_registered_sidebars;

    ob_start();
      if ( !function_exists('dynamic_sidebar') ) { //|| !dynamic_sidebar("Sidebar") ) {
	echo "No widgets...";
	return;
      }

      if(is_array($wp_registered_sidebars)) :
	$keys = array_keys($wp_registered_sidebars);

	foreach($keys as $key) :
	  dynamic_sidebar($key);
	endforeach;
      endif;
    
    //Cancello l'output
    ob_end_clean();

    //Stampo a video i titoli
    require_once(CECEPPA_PLUGIN_PATH . 'includes/widgettitles.php');
    cml_widgets_title($this->_titles);

    $this->_titles = "";
  }

  /**
   * Visualizzo l'elenco dei post disponibili e 
   * seleziono dall'elenco quello collegato al post corrente
   */
  function post_meta_box($tag) {
    global $wpdb;
    
    wp_enqueue_script('ceceppa-tipsy');
    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');
    wp_enqueue_style('ceceppa-tipsy');
    wp_enqueue_style('ceceppaml-lang');

    $langs = cml_get_languages(0);

    //Ho specificato in quale lingua sarà il nuovo post?
    if(array_key_exists("post-lang", $_GET) || array_key_exists("page-lang", $_GET)) {
      $post_lang = @intval($_GET['post-lang']) + @intval($_GET['page-lang']);
    }

    //Lingua dell'articolo
    echo "<h4>" . __( 'Language of this post', 'ceceppaml' ) . "</h4>";
    $lang_id = empty( $post_lang ) ? $this->get_language_id_by_post_id( $tag->ID) : $post_lang;
    cml_dropdown_langs("post_lang", $lang_id, false, true, null, "", 0);

    echo "<h4>" . __('This is a translation of', 'ceceppaml') . "</h4>";
    echo "<select name='linked_post' style=\"width:100%\" class='link-category'>";
    echo "<option value=''>" . __('This post is not a translation', 'ceceppaml') . "</option>";

    //Ho passato come parametro l'id del post da collegare?
    if(array_key_exists("link-to", $_GET)) {
      $linked_post = intval($_GET['link-to']);
    }

    //Recupero l'id del post collegato
    $t_id = $tag->ID;
    $linked_to = $wpdb->get_var(sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d AND cml_post_id_2 > 0",
		    CECEPPA_ML_POSTS, $t_id));
    $linked_to = empty( $linked_post ) ? $linked_to : $linked_post;

    if( empty( $lang_id ) ) $lang_id = $this->_default_language_id;
    $not = ( get_option( "cml_hide_posts_for_lang_" . $lang_id ) );
    $not = array_merge ( $not, $this->_posts_of_lang[ $lang_id ] );
    $untranslated = array_merge( array( $linked_to ), get_option( "cml_posts_of_lang_" . 0 ) );

    //Cancello dai post da escludere quello collegato 
    foreach ( $untranslated as $v ) :
      if( ( $key = array_search( $v, $not ) ) !== false ) {
	  unset( $not[ $key ] );
      }
    endforeach;

    //Elenco degli articoli
    $args = array('numberposts' => -1, 'order' => 'ASC', 'orderby' => 'title', 'posts_per_page' => 9999,
		  //Mi serve per farlo funzionare con i custom posts :)
		  'post_type' => get_post_type(),
		  //Escludo gli articoli "collegati" dall'elenco
		  'post__not_in' => $not,
		  'status' => 'publish,inherit,pending,private,future,draft');

    $posts = new WP_Query($args);

    //Scorro gli articoli
    $linked_post = (empty($linked_to)) ? $linked_post : $linked_to;
    while($posts->have_posts()) :
      $posts->next_post();

      $id = $posts->post->ID;
      if($id != $t_id) :
	$selected = ($id == $linked_post) ? "selected" : "";

	$lang_id = $this->get_language_id_by_post_id($id);
	$flag = cml_get_flag_by_lang_id($lang_id);
	echo "<option value=\"$lang_id@$id\" data-image=\"$flag\" $selected>&nbsp;&nbsp;&nbsp;" . get_the_title($id) . "</option>";
      endif;
    endwhile;
    echo "</select>";
        
      //Visualizzo le traduzioni disponibili per l'articolo, oppure i tasti + per aggiungerne una
      ?>
      <div class="cml-box-translations">
	  <h4><?php _e('Translations:', 'ceceppaml') ?></h4>
	  <ul>
      <?php
	$id = $this->get_language_id_by_post_id($t_id);
	if(empty($post_lang)) $post_lang = -1;

	foreach($langs as $lang) :
	  if($lang->id != $id && $lang->id != $post_lang) :

	  $link = cml_get_linked_post($id, null, $t_id, $lang->id);
	  $post_type = get_post_type();
	  $href = empty($link) ? (get_bloginfo("url") . "/wp-admin/post-new.php?link-to=$t_id&post-lang=$lang->id&post_type=${post_type}") : get_edit_post_link($link);
	  
	  $icon = empty($link) ? "add" : "go";
	  $title = empty($link) ? __('Translate post', 'ceceppaml') : __('Edit post', 'ceceppaml');
	  $msg = empty($link) ? __('Add translation', 'ceceppaml') : (__('Switch to:', 'ceceppaml') . "<br />" . get_the_title($link));
      ?>
	  <li class="<?php echo empty($link) ? "no-translation" : "" ?> _tipsy" title="<?php echo $msg ?>">
	      <a href="<?php echo $href ?>">
		  <img src="<?php echo cml_get_flag_by_lang_id($lang->id, "small") ?>" title="<?php echo $lang->cml_language ?>" />
		  <?php if(!empty($link)) { ?>
		  
<!-- Esiste la traduzione -->
		  <span class="cml_add_text"><?php echo  get_the_title($link) ?></span>
		  <?php } ?>
<!-- Non esiste-->
		  <span class="cml_add_button">
			  <!-- <img class="add" src="<?php echo CECEPPA_PLUGIN_URL ?>/images/<?php echo $icon ?>.png" title="<?php echo $title ?>" width="12" /> -->
		  </span>
	      </a>
	  </li>
      <?php endif; ?>
      <?php endforeach; ?>
	  </ul>
      </div>
      <?php
  }

  function page_meta_box($tag) {
    global $wpdb;
    
    wp_enqueue_script('ceceppa-tipsy');
    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');
    wp_enqueue_style('ceceppa-tipsy');
    wp_enqueue_style('ceceppaml-lang');

    $langs = cml_get_languages(0);

    $post_lang = -1;
    //Ho specificato in quale lingua sarà il nuovo post?
    if(array_key_exists("post-lang", $_GET) || array_key_exists("page-lang", $_GET)) {
      $post_lang = @intval($_GET['post-lang']) + @intval($_GET['page-lang']);
    }

    //Lingua dell'articolo
    echo "<h4>" . __('Language of this page', 'ceceppaml') . "</h4>";
    $lang_id = (empty($post_lang) || $post_lang < 0) ? $this->get_language_id_by_page_id($tag->ID) : $post_lang;
    cml_dropdown_langs("post_lang", $lang_id, false, true, null, "", 0);

    echo "<h4>" . __('This is a translation of', 'ceceppaml') . "</h4>";
    echo "<select name='linked_post' style=\"width:100%\" class='link-category'>";
    echo "<option value=''>" . __('This page is not a translation', 'ceceppaml') . "</option>";

    //Ho passato come parametro l'id del post da collegare?
    if(array_key_exists("link-to", $_GET)) {
      $linked_post = intval($_GET['link-to']);
    }

    //Recupero l'id del post collegato
    $t_id = $tag->ID;
    $linked_to = $wpdb->get_var(sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d AND cml_post_id_2 > 0",
		    CECEPPA_ML_POSTS, $t_id));
    $linked_to = empty($linked_post) ? $linked_to : $linked_post;

    $args = array('post_status' => 'publish,inherit,pending,private,future,draft',
		  'post_type' => 'page');
    $pages = get_pages($args);

    foreach ($pages as $page) :
      if($page->ID != $tag->ID) :
	$page_lang = $this->get_language_id_by_page_id($page->ID);

	$selected = ($page->ID == $linked_to) ? "selected" : "";
	$flag = cml_get_flag_by_lang_id($page_lang);

	$option = "<option value=\"$page_lang@$page->ID\" data-image=\"$flag\" $selected>";
	$option .= $page->post_title;
	$option .= '</option>';
	echo $option;
      endif;
    endforeach;

    echo "</select>";
?>
      <div class="cml-box-translations">
	  <h4><?php _e('Translations:', 'ceceppaml') ?></h4>
	  <ul>
      <?php
	$id = $this->get_language_id_by_page_id($t_id);

	foreach($langs as $lang) :
	  if($lang->id != $id && $lang->id != $post_lang) :

	  $link = cml_get_linked_post($id, null, $t_id, $lang->id);
	  $href = empty($link) ? (get_bloginfo("url") . "/wp-admin/post-new.php?post_type=page&link-to=$t_id&post-lang=$lang->id") : get_edit_post_link($link);
	  $icon = empty($link) ? "add" : "go";
	  $title = empty($link) ? __('Translate post', 'ceceppaml') : __('Edit post', 'ceceppaml');
	  $msg = empty($link) ? __('Add translation', 'ceceppaml') : __('Switch to post/page', 'ceceppaml');
      ?>
	  <li class="<?php echo empty($link) ? "no-translation" : "" ?> _tipsy" title="<?php echo $msg ?>">
	      <a href="<?php echo $href ?>">
		  <img src="<?php echo cml_get_flag_by_lang_id($lang->id, "small") ?>" title="<?php echo $lang->cml_language ?>" />
		  <?php if(!empty($link)) { ?>
		  <span class="cml_add_text"><?php echo  get_the_title($link) ?></span>
		  <?php } ?>
		  <span class="cml_add_button">
			  <!-- <img class="add" src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/<?php echo $icon ?>.png" title="<?php echo $title ?>" width="12" /> -->
		  </span>
	      </a>
	  </li>
      <?php endif; ?>
      <?php endforeach; ?>
	  </ul>
      </div>
      <?php
  }

  function menu_meta_box() {
    global $_nav_menu_placeholder, $nav_menu_selected_id;

    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
    ?>
    <div id="cml-language-switch" class="posttypediv">
        <div id="tabs-panel-lang-switch" class="tabs-panel tabs-panel-active">
            <ul id ="cml-language-switch-checklist" class="categorychecklist form-no-clear">
                <li>
                    <label class="menu-item-title">
                      <input type="checkbox" id="cml-menu-item" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" class="menu-item-checkbox" value="1" />
                      <?php _e( 'Current language', 'ceceppaml' ) ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php _e( 'Current language', 'ceceppaml' ); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="#cml-current">
                </li>
                
                <li>
                    <label class="menu-item-title">
                      <input type="checkbox" id="cml-menu-item" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" class="menu-item-checkbox" value="1" />
                      <?php _e( 'All languages', 'ceceppaml' ) ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php _e( 'All languages', 'polylang' ); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="#cml-others">
                </li>

                <li>
                    <label class="menu-item-title">
                      <input type="checkbox" id="cml-menu-item" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" class="menu-item-checkbox" value="1" />
                      <?php _e( 'All languages excluded current', 'ceceppaml' ) ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php _e( 'All languages excluded current', 'ceceppaml' ); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="#cml-no-current">
                </li>
            </ul>
        </div>
        <p class="button-controls">
            <span class="add-to-menu">
                <input type="submit" <?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-cml-language-switch">
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
  }

  
  function redirect_browser() {
    /*
     * Se  non è abilitato il redirect del browser nella homepage
     * devo filtrare i contenuti in base alla lingua corrente
     */
    //L'untente non è interessato al redirect
    if( $this->_redirect_browser == 'nothing' || isCrawler() ) return;
    if( is_admin() ) return;

    //Non posso utilizzare la funzione is_home, quindi controllo "manualmente"
    //Sto nell'home?
    if( $this->_url != $this->_homeUrl || isset($_GET['lang']) ) {
	return;
    }

    //Recupero info sulla disponibilità della lingua del browser
    global $wpdb;

    $lang = $this->get_browser_lang();
    $slug = ( empty( $lang ) ) ? $this->_default_language_slug : $this->get_language_slug_by_id($lang);
    //Redirect abilitato?
    if($this->_redirect_browser == 'auto') {
      $location = $this->get_home_url( $slug );
    }

    if( $this->_redirect_browser == 'default' ) {
      $location = $this->get_home_url( $this->_default_language_slug );
    }

    if( ! empty( $location ) ) {
      $this->_redirect_browser = 'nothing';

      wp_redirect($location, $status);
      exit;
    }
  }

 function clear_url() {
    if( $this->_url_mode != PRE_PATH || isset( $this->_clean_applied ) ) return;

    //Se è un articolo non rimuovo lo slug della lingua, altrimenti scompare anche dall'url :O
    $id = cml_get_page_id_by_path( $this->_url, array('post') );
    if( strpos( $this->_permalink_structure, "%category%" ) !== false && !empty( $id ) ) return;

    $url = $this->_request_url;

    if(preg_match("#^([a-z]{2})(/.*)?$#i", $url, $match)) :
      $this->_force_current_language = $this->get_language_id_by_slug( $match[1] );

      $url = substr( $url, 3 );

      $this->_clean_request = $this->_base_url . "/" . $url;
      $this->_clean_url = $this->_homeUrl . $url;

      //Inganno wordpress :D
      $_SERVER['REQUEST_URI'] = $this->_clean_request;
      
      $this->_clean_applied = true;

      return $this->_clean_request;
    endif;
  }

  /**
   * Redirect category/post/page??
   */
  function show_notice($content) {
    global $wpdb, $_cml_settings;

    if(isCrawler()) return $content;
    
    //Recuper la lingua del browser
    $browser_lang_id = $this->get_browser_lang();

    if(empty($browser_lang_id)) return $content;

    //Recupero l'd della lingua dal database
    $lang_id = $this->_current_lang_id;

    if(is_page()) {
      if( $_cml_settings[ "cml_option_notice_page" ] != 1) return $content;

      $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);
    }
    
    if(is_single()) {
      if( $_cml_settings["cml_option_notice_post"] != 1) return $content;

      $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);
    }

    $link = (!isset($link) || $link == get_the_ID() || $link == null) ? null : get_permalink($link);
    if(!empty($link)) :
      $notice = cml_get_notice($browser_lang_id);
      $before = stripcslashes( $_cml_settings['cml_option_notice_before'] );
      $after = stripcslashes( $_cml_settings['cml_option_notice_after'] );
      $flag = cml_get_flag_by_lang_id($browser_lang_id, "small");

      if(!empty($notice)) :
          $c = "$before<a href='$link'><img src='$flag' />&nbsp;$notice</a>$after";

        if($this->_show_notice_pos == 'top')
          $content = $c . $content;
        else
          $content .= $c;
      endif;
    endif;

    return $content;
  }
  
  function get_browser_lang() {
    if(isset($this->_browser_lang)) return $this->_browser_lang;

    global $wpdb;

    $browser_langs = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $lang = null;

    //Se la lingua del browser coincide con una di quella attuale della pagina, ignoro tutto
    foreach($browser_langs as $blang) :
      @list($code1, $code2) = explode(",", $blang);

      $locale[] = str_replace("-", "_", $code1);
      $locale[] = str_replace("-", "_", $code2);
      
      //Per ogni codice che trovo verifico se è gestito, appena ne trovo 1 mi fermo
      //Perché il mio browser mi restituisce sia it-IT, che en-EN, quindi mi devo fermare appena trovo un riscontro
      //Senno mi ritrovo sempre la lingua en-EN come $browser_langs;
      $i = 0;
      while(empty($lang) && $i < count($locale)) :
	$l = $locale[$i];

	if(strlen($l) > 2) :
	  $lang = $this->get_language_id_by_locale($l);
	else:
	  //Se ho solo 2 caratteri, cerco negli "slug"
	  $query = sprintf("SELECT id FROM %s WHERE cml_language_slug = '%s'", CECEPPA_ML_TABLE, $l);
	  $lang = $wpdb->get_var($query);
	endif;

	$i++;
      endwhile;

      if(!empty($lang)) {
	break;
      }
    endforeach;

    $this->_browser_lang = $lang;

    return $this->_browser_lang;
  }

   //Save extra category extra fields callback function
  function save_extra_category_fileds($term_id) {
    global $wpdb, $pagenow;

    //In wp 3.6 viene richiamata questa funzione anche quando salvo i menu... :O
    if(strpos($pagenow, "nav-menus") !== FALSE) return;

    //In Quickedit non devo fare nulla
    if(!isset($_POST['cat_name'])) return;

    $keys = array_keys($_POST['cat_name']);
    foreach($keys as $key) :
	update_option("cml_category_" . $term_id . "_lang_$key", $_POST['cat_name'][$key]);
	cml_add_category_translation($term_id, $_POST['name'], $key, $_POST['cat_name'][$key]);
    endforeach;
  }

  function delete_extra_category_fields($term_id) {
    global $wpdb;

    $langs = cml_get_languages();
    foreach($langs as $lang) :
      delete_option("cml_category" . $term_id . "_lang_" . $lang->id);
    endforeach;

    delete_option("cml_category_$term_id");
    delete_option("cml_category_lang_$term_id");

    //Cancello la voce dal database
    $query = sprintf("DELETE FROM %s WHERE cml_cat_id = %d", CECEPPA_ML_CATS, $term_id);
    $wpdb->query($query);
  }

  /* 
   * Salvo il collegamento tra i post
   */
  function save_extra_post_fields( $term_id ) {
    global $wpdb, $pagenow;

    //Dalla 3.5.2 questa funzione viene richiamata 2 volte :O, la seconda volta $_POST però è vuoto :O
    if( empty( $_POST ) ) return;

    $post_id = is_object($term_id) ? $term_id->ID : $term_id;

    @list($linked_lang, $linked_post) = explode("@", $_POST['linked_post']);

    //Recupero dalla mia tabella l'id della lingua :)
    if( empty( $_POST['post_lang'] ) )
      $post_lang = 0;
    else
      $post_lang = intval( $_POST['post_lang'] );

    /* If is a page check the parent_id */
    if( $_POST[ 'post_type' ] == 'page' && intval( $_POST[ 'parent_id' ] ) > 0 ) {
      //Recover the language of parent
      $post_lang = $this->get_language_id_by_page_id( intval( $_POST[ 'parent_id' ] ) );
    }

    $this->set_language_of_post( $post_id, $post_lang, $linked_lang, $linked_post );
  }
    
  function set_language_of_post( $post_id, $post_lang, $linked_lang, $linked_post ) {
      global $wpdb;

      //Elimino i vecchi collegamenti presenti nel database
      $query = sprintf("SELECT id FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, intval($post_id));
      $id = intval( $wpdb->get_var( $query ) );
      if($id > 0) {
	  $query = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE id = " .intval($id);
	  $wpdb->query($query);
      }

      $query = "";
      if($post_lang > 0) :
	  $wpdb->insert(CECEPPA_ML_POSTS,
		      array('cml_post_lang_1' => intval($post_lang),
			    'cml_post_id_1' => intval($post_id),
			    'cml_post_lang_2' => intval($linked_lang),
			    'cml_post_id_2' => intval($linked_post)),
		      array('%d', '%d', '%d', '%d'));
      endif;

      if( ! isset ( $post_lang ) )
	delete_option("cml_page_lang_$post_id");

      update_option("cml_page_${post_id}", $linked_post);
      update_option("cml_page_lang_${post_id}", $post_lang);

      //Ricreo la struttura degli articoli, questo metodo rallenterà soltanto chi scrive l'articolo... tollerabile :D
      $this->code_optimization();
  }

  function delete_extra_post_fields($id) {
    global $wpdb;

    $sql = sprintf("DELETE FROM %s WHERE cml_post_id_1 = %d OR cml_post_id_2 = %d", CECEPPA_ML_POSTS, $id, $id);

    $wpdb->query($sql);
    
    if(get_post_status($id) != "trash") {
      delete_option("cml_page_lang_$id");
      delete_option("cml_page_$id");
    }

    //Ricreo la struttura degli articoli, questo metodo rallenterà soltanto chi scrive l'articolo... tollerabile :D
    $this->code_optimization();
  }

  function add_lang_query_vars($vars) {
    $vars[] = 'lang';

    return $vars;
  }

  /*
   * Cambio il locale in base alla lingua selezionata
   */  
  function setlocale( $locale ) {
    global $wpdb, $pagenow;

    if( $pagenow == "wp-login.php" ) return $locale;

    /*
     * Nel pannello di amministrazione di alcuni non riesco a "mantenere" la
     * lingua scelta perché quando richiamo la funzione get_language_by_url
     * non esiste la funzione is_logged_in, e quindi non riesco a recuperare la lingua
     * scelta in precedenza, per questo forzo l'aggiornamento della lingua ogni qual volta
     * viene richiamata la funzione setlocale
     */
    if( is_admin() ) unset( $this->_language_detected );

    //Per gli utenti "loggati" memorizzo la lingua selezionata, nel pannello di amministrazione
    $this->update_current_lang();

    $logged_in = function_exists( 'is_user_logged_in' ) && is_user_logged_in();
    if( is_admin() && $logged_in ) :
      global $current_user;

      get_currentuserinfo();

      $user = $current_user->user_login;
      update_option( "cml_${user}_locale", $this->_current_lang_locale );
    endif;

    //Nella pagina dei widget devo forzare il locale in "en_US"
    //per evitare che i titoli dei widget nella colonna a sx cambino al cambiare
    //della lingua...
    if( isset( $_GET ['page'] ) && $_GET[ 'page' ] == 'ceceppaml-widgettitles-page' ) :
      $locale = "en_US";
    endif;

    return $this->_current_lang_locale;
  }

  /**
   * Traduco il titolo dei widget :)
   */
  function translate_widget_title($title) {
    if(is_admin()) 
      return $title;

    return cml_translate($title, $this->_current_lang_id, 'W', true);
  }

  function admin_translate_widget_title($title) {
    $this->_titles[] = $title;
  }
  
  /**
   * Aggiorno la lingua utilizzata al momento
   */
  function update_current_lang() {
    global $wpdb;

    $lang = "";

    //Ho già identificato la lingua corretta, è inutile fare ulteriori elaborazioni ;)
    if( isset( $this->_language_detected ) ) return $this->_current_lang_id;

    if( array_key_exists( "lang", $_GET ) ) :
      $lang = $_GET['lang'];

      $lang = $this->get_language_id_by_slug($lang);
    else :
      $lang = $this->get_language_by_url();
    endif;

    if( isset( $this->_force_current_language ) ) :
      $lang = $this->_force_current_language;
    endif;

    if( !empty( $lang ) ) {
      //Aggiorno le info sulla lingua corrente
      $this->_current_lang = $this->get_language_slug_by_id($lang);
      $this->_current_lang_id = $lang;

      if( $this->_filter_search ) {
        //For Fix Notice
        //add_action('wp_enqueue_scripts', array(&$this, 'enqueue_script_search')); //Non funziona :(
        $this->enqueue_script_search();
    
        $array = array('lang' => $this->_current_lang, 'form_class' => $this->_filter_form_class);
        wp_localize_script('ceceppa-search', 'cml_object', $array);
      }

      //Recupero il campo "Locale Wordpress"
      $query = sprintf( "SELECT * FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $lang );
      $row = $wpdb->get_row( $query );

      $this->_current_lang_id = $lang;
      $this->_current_lang  = $row->cml_language;
      $this->_current_lang_locale = $row->cml_locale;
      $this->_current_lang_slug = $row->cml_language_slug;
    } else {
      //Se per qualche ragione non riesco a determinare la lingua corrente, prendo quella predefinita
      $this->_current_lang_id = $this->_default_language_id;
      $this->_current_lang = $this->_default_language_slug;
      $this->_current_lang_slug = $this->_default_language_slug;
      $this->_current_lang_locale = $this->_default_language_locale;
    }
    
    //Aggiorno il menu del tema :)
    $this->change_menu();

    $this->_language_detected = true;
  }

  function get_language_by_url() {
    $lang = "";

    //it.example.com
    if( $this->_url_mode == PRE_DOMAIN  && !is_admin() ) :
      preg_match( "/^([a-z]{2})/", $this->_url, $matches );
      
      if( !empty($matches) ) :
        $lang = $matches[0];
      endif;
    endif;

    if( empty( $lang ) ) :
      //get_currentuserinfo non esiste quando viene chiamato "set_locale"
      $logged_in = function_exists( 'is_user_logged_in' ) && is_user_logged_in();

      if( is_admin() && $logged_in ) :
        //Recupero l'info dalle opzioni
        global $current_user;
        get_currentuserinfo();
    
        $user = $current_user->user_login;
        $locale = get_option( "cml_${user}_locale" ); //, $this->_default_language_locale );
    
        $lang = $this->get_language_id_by_locale( $locale );

        $this->_force_current_language = $this->get_language_id_by_locale($locale);
      else:
    	$this->clear_url();

      if( !empty( $this->_force_current_language ) ) :
        return $this->_force_current_language;
      else:
	  //Se non sono riuscito a recuperare la lingua dal link, recupero l'info dall'articolo

	  //Funzione con alcuni tipi di permalink, quali ?p=##, archives/ e non nel pannello di admin (almeno in alcuni casi)
	  $the_id = 0;

	  //Posso recuperare l'info dal numero dell'articolo?
	  $the_id = $this->get_post_id_by_url( $this->_url );
	  
	  if( empty( $the_id ) ) :
	    $the_id = cml_get_page_id_by_path($this->_request_url);
	  endif;

	  //Qualcosa è andato storto, non modifico il "locale"
	  if( empty( $the_id ) ) :
	    return $this->_default_language_id;
	  else:
	    $lang= $this->get_language_id_by_page_id( $the_id );
	  endif;

	endif; //!empty
      endif;
    endif;

    return $lang;
  }

  /*
   * Recupero l'id del post per i link di tipo "?p=##" oppure "/##"
   */
  function get_post_id_by_url( $url ) {
    $structure = explode( "/", $this->_permalink_structure );

    if( empty( $this->_permalink_structure ) || end( $structure ) == "%post_id%" ) :
      preg_match('/([0-9]+)$/', $url, $matches );

      if( !empty( $matches ) )
	return end( $matches );
    endif;
    
    return null;
  }

  /*
   * Cambio il menu predefinito del tema, in accordo con quello della lingua corrente
   */
  function change_menu() {
    global $_cml_settings;

    if( ! $_cml_settings[ "cml_option_action_menu" ] ) return;

    $mods = get_theme_mods();
    $locations = get_theme_mod( 'nav_menu_locations' );

    //Se non inizia per cml_ allora sarà quella definito dal tema :)
    if( is_array( $locations ) ) :
	$keys = array_keys( $locations );
	foreach( $keys as $key ) :
	  if( ! empty( $key ) && substr( $key, 0, 4 ) != "cml_" ) {
	    $menu = $this->_current_lang_slug;

	    if( ! empty ( $locations["cml_menu_$menu"] ) ) {
	      //Se ho scelto un menu diverso per la lingua corrente, non devo "tradurre" le etichette
	      $this->_no_translate_menu_item = ( $locations[$key] != $locations["cml_menu_$menu"] );

	      $locations[$key] = $locations["cml_menu_$menu"];
	      $this->_current_menu_id = $locations[$key];

	      set_theme_mod( 'nav_menu_locations', $locations );
	    }

	    //Esco dal ciclo
	    break;
	  }
	endforeach;
    endif;
  }

  /**
   * Recupero tutte le categorie della lingua corrente:
   *
   *  1) Se ho utilizzato una struttura ad albero:
   *    - recupero la categoria padre associata alla lingua, e poi tutte le categorie figlie
   *  2) Se ho associato ogni categoria della lingua corrente in un'altra lingua:
   *    - recupero le associazioni dalla tabella "CECEPPA_ML_CATS" ed il gioco è fatto
   *  3) Ho specificato la lingua per ogni categoria
   *
   * @return array contenente le categorie
   */
  function get_language_categories($lang_id = "") {
    global $wpdb;

    if(empty($lang_id)) $lang_id = $this->_current_lang_id;
    $cats = array();

    if($this->get_language_category($lang_id) > 0) {
      /*
       * Se è stata utilizzata una struttura ad albero, recupero l'id del padre e di tutti i figli
       */
      $args = array(
        'type'                     => 'post',
        'child_of'                 => $this->get_language_category($lang_id),
        'parent'                   => '',
        'orderby'                  => 'name',
        'order'                    => 'ASC',
        'hide_empty'               => 0,
        'hierarchical'             => 1,
        'exclude'                  => '',
        'include'                  => '',
        'number'                   => '',
        'taxonomy'                 => 'category',
        'pad_counts'               => false );

      $categories = get_categories($args);
      foreach($categories as $category) {
        $cats[] = $category->term_id;
      }
    }

    /*
     *  Recupero dal database tutti i collegamentri fra le varie categorie
     */
    $query = sprintf("SELECT * FROM %s WHERE cml_cat_lang_1 = %d OR cml_cat_lang_2 = %d", 
                    CECEPPA_ML_CATS, $lang_id, $lang_id);

    //Recupero tutte le categorie dalla uery
    $results = $wpdb->get_results($query);

    foreach($results as $result) :
      if($result->cml_cat_lang_1 == $lang_id) $cats[] = $result->cml_cat_id_1;
      if($result->cml_cat_lang_2 == $lang_id) $cats[] = $result->cml_cat_id_2;
    endforeach;

    return array_unique($cats);
  }

  /*
   * Recupero tutti gli id dei post collegati alla lingua
   *
   * @param cats - id delle categorie appartenenti alla lingua
   *                il parametro è facoltativo, se non specificato viene richiamata la funzione get_language_categories();
   *
   * @return gli id dei post associati alla lingua richiesta
   */
  function get_posts_of_language( $lang = null ) {
    if( empty( $lang ) ) $lang = $this->_current_lang_id;
  
   //Gli articoli senza lingua sono "figli di tutti"
   if( empty( $this->_posts_of_lang ) ) $this->preload_posts();

   $posts = $this->_posts_of_lang[ $lang ];

   return !empty($posts) ? array_unique($posts) : array();
  }
  
  /**
    * Recupero l'elenco di tutte le pagine collegate alla lingua specificata
    *
    * @param lang_id - lingua da ricercare
    */
  function get_pages_of_language($lang) {
    return $this->get_posts_of_language($lang);
  }

  /**
   * Recupero l'id della pagina padre
   */
  function get_page_parent($page) {
    if(!is_object($page)) return $page;

    $page_parent = ($page->post_parent > 0) ? $page->post_parent : $page->ID;

    while($page->post_parent > 0) {
      $page = get_page($page->post_parent);
      
      if($page->post_parent != 0) $page_parent = $page->post_parent;
    }
    
    return $page_parent;
  }

  function get_page_parent_by_id($page_id) {
    return $this->get_page_parent(get_page($page_id));
  }

  function get_default_lang_id() {
    return $this->_default_language_id;
  }

  /**
   * Restituisco lo slug della lingua di default
   */
  function get_default_language_slug() {
    return $this->_default_language_slug;
  }
  
  function get_default_category() {
    return $this->_default_category;
  }

  /**
   * restituisco lo slug della lingua "in uso"
   */
  function get_current_lang() {
    return $this->_current_lang;
  }

  /*
   * Restituisce l'oggetto contenente tutte le informazioni sulla lingua corrente
   */
  function get_current_language() {
    global $wpdb;

    if( !isset( $this->_language_detected ) ) $this->update_current_lang();

    $query = sprintf("SELECT * FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $this->_current_lang_id);
    $info = $wpdb->get_row($query);

    return $info;
  }

  function get_current_lang_id( $update = false ) {
    if( $update || !isset( $this->_language_detected ) ) $this->update_current_lang();

    return $this->_current_lang_id;
  }
  
  function get_current_language_slug() {
    return $this->_current_lang_slug;
  }

  function get_comments($query) {
    if(FALSE === strpos($query, 'comment_post_ID = '))
    {
        return $query; // not the query we want to filter
    }

    global $wpdb;

    remove_filter('query', array(&$this, 'get_comments'));

    if(is_single() || is_page()) {
      $id_1 = "cml_post_id_1";
      $id_2 = "cml_post_id_2";
      $table = CECEPPA_ML_POSTS;
    }
    /*
    if(is_page()) {
      $id_1 = "cml_page_id_1";
      $id_2 = "cml_page_id_2";
      $table = CECEPPA_ML_PAGES;
    }
*/
    //Recupero tutti i post collegati a quello che l'utente stà visualizzando
    $post_ids = array(get_the_ID());
    $results = $wpdb->get_results(sprintf("SELECT * FROM %s WHERE %s = %d OR %s = %d",
                                          $table, $id_1, get_the_ID(), $id_2, get_the_ID()));

    foreach($results as $result) :
        $a = array($result->$id_1, $result->$id_2);
        $post_ids = array_merge($post_ids, $a);
    endforeach;

    $replacement = 'comment_post_ID IN(' . implode( ',', $post_ids ) . ')';
    return preg_replace( '~comment_post_ID = \d+~', $replacement, $query );
  }
  
  function get_comments_number($count) {
    //Cerco tutti i post collegati a quello corrente ;)
    $langs = cml_get_languages();

    $id = get_the_ID();
    $lang_id = $this->get_language_id_by_post_id($id);
    $ids = array($id);
    foreach($langs as $lang) :
      $linked = cml_get_linked_post($lang_id, $lang, $id, $lang->id);
      if(!empty($linked)) $ids[] = $linked;
    endforeach;

    //Eseguo la query
    if(!empty($ids)) :
      global $wpdb;

      asort($ids);
      $ids = @implode(",", $ids);
      $query = "SELECT count(*) FROM $wpdb->comments WHERE comment_approved = 1 AND comment_post_ID IN ($ids)";
      $count = $wpdb->get_var($query);
    endif;
    
    return $count;
  }
 
    /*
     * Modifico l'id della query in modo che punti all'articolo tradotto
     */
    function get_static_page($query) {
      if( isset( $this->_static_page ) ) return;

      //Recupero l'id della lingua
      $lang_id = $this->_current_lang_id;

      //Id attuale
      $id = $query->query_vars['page_id'];
      
      //Recupero l'id collegato
      $nid = cml_get_linked_post($this->_default_language_id, null, $id, $lang_id);

      if(empty($nid)) $nid = $id;
      $query->query_vars['page_id'] = $nid;

      $this->_static_page = true;
    }
    
    /*
     * Se ho modificato il permalink cerco di recuperare
     * l'articolo corretto :)
     */
    function translate_post_link($permalink, $post, $leavename, $lang_id = null) {
      global $page;

      if( $page >= 2 ) return $permalink;   //Fix: "La pagina web ha generato un loop di reindirizzamento"
      if( is_preview() ) return $permalink;

      if($lang_id == null) $lang_id = $this->get_language_id_by_post_id( $post->ID );
      if($lang_id == 0) $lang_id = $this->_current_lang_id;

      $slug = $this->get_language_slug_by_id( $lang_id );

      if( empty( $this->_permalink_structure ) || strrpos( $this->_permalink_structure, "%category%" ) === false) return $this->convert_url ( $slug,  $permalink );

      $homeUrl = home_url();
      $plinks = explode("/", str_replace($homeUrl, "", $permalink));
      if(substr( $this->_permalink_structure , -1 ) == "/") array_pop($plinks);
      $title = array_pop($plinks);

      //Non traduco le categorie per la lingua di default
      if($lang_id != $this->_default_language_id) :
	foreach( $plinks as $plink ) :
	  //Cerco la traduzione della categoria nella lingua del post :)
	  $_cat = get_category_by_slug($plink);
	  if(is_object($_cat)) :
	    $id = $_cat->term_id;

	    if( !empty( $plink ) ) :
	      $cat = strtolower( get_option( "cml_category_" . $id . "_lang_" . $lang_id, $plink ) );
	      $url = str_replace(" ", "-", $cat);
	      $plink = urlencode($url);
	    endif;
	  endif;

	  $cats[] = $plink;
	endforeach;
      endif;

      //Ricreo il permalink con le categorie tradotte... :)
      if( empty( $cats ) ) $cats = $plinks;
      $url = home_url() . join( "/", $cats ) . "/$title/";

      return $this->convert_url( $slug, $url );
    }
    
    function translate_page_link( $permalink ) {
      global $page;
      
      if( $page >= 2 ) return $permalink;   //Fix: "La pagina web ha generato un loop di reindirizzamento"
      if( is_admin() ) return $permalink;

      //Se è stata scelta la modalità suffix: ?lang=## lo slug è quello della lingua attuale?
      if( $this->_url_mode == PRE_LANG ) :
	$slug = $this->_current_lang_slug;
      else:
	if( empty( $this->_permalink_structure ) ) :
	  $page_id = $this->get_post_id_by_url( $permalink );
	else:
	  $page_id = cml_get_page_id_by_path( $permalink, array('page') );
	endif;

	$slug = $this->get_language_id_by_post_id( $page_id );
	if( empty( $slug) ) $slug = $this->_current_lang_id;

	$slug = $this->get_language_slug_by_id($slug);
      endif;

      return $this->convert_url( $slug, $permalink );
    }

    function translate_menu_item($item) {
      //Se l'utente ha scelto un menu differente per la lingua corrente
      //non devo applicare nessun tipo di filtro agli elementi del menu, esco :)
      //Questo è vero solo per le pagine... altrimenti non mi traduce il nome delle categorie
      if( $this->_no_translate_menu_item == true && $item->object == 'page' ) :
        remove_filter( 'wp_setup_nav_menu_item', array(&$this, 'translate_menu_item') );
        return $item;
      endif;

      if($this->_current_lang_id != $this->_default_language_id) :
	    switch($item->object) :
	    case 'page':
	    case 'post':
	      $page_id = cml_get_linked_post( $this->_default_language_id, null, $item->object_id, $this->_current_lang_id );

	      if( ! empty($page_id) ) :
            //Su un sito mi è capitato che get_the_title() restituisse una stringa vuota, nonstante l'id della pagina fosse corretto
            $page = get_post( $page_id );
            if( empty( $page ) || ! is_object( $page ) ) return $item;

            $item->ID = $page_id;
            $item->title = $page->post_title;
            $item->post_title = $page->post_title;
            $item->object_id = $page_id;
            $item->url = get_permalink( $page_id );
          endif;

	      break;
	    case 'category':
	      $id = $item->object_id;
	      if(!empty($id)) :
		  $lang = $this->_current_lang_id;

		  $item->title = get_option("cml_category_" . $id . "_lang_" . $lang, $item->title);
	      endif;
	      break;
	    case 'custom':
	      $item->title = cml_translate($item->title, $this->_current_lang_id);

	      if($item->url == $this->_homeUrl) :
            $item->url = add_query_arg( array("lang" => $this->get_language_slug_by_id($this->_current_lang_id)), $this->_homeUrl);
	      endif;

	      break;
	    default:
	      return $item;
	    endswitch;
      endif;

      return $item;
    }
    
    function translate_term_name( $name ) {
      if($this->_current_lang_id == $this->_default_language_id) return $name;

      $depth = 0;
      $pos = strpos($name, " ");
      $simbolo = html_entity_decode("&#8212;");
      if($pos !== FALSE) :
	$depth = substr_count($name, $simbolo);

	$name = str_replace($simbolo, "", $name);
      endif;

      //$id = ($_GET['taxonomy'] == 'category') ? get_cat_ID(trim($name)) : get_term_ID(trim($nanme));
      $where = isset($_GET['taxonomy']) ? $_GET['taxonomy'] : 'category';
      $term = get_term_by('name', trim($name), $where);
      if(!empty($term)) :
	$id = $term->term_id;
	if($id > 0) :
	    $lang = $this->_current_lang_id;

	    $n = get_option("cml_category_" . $id . "_lang_" . $lang);
	    $name = empty($n) ? $name : $n;
	endif;
      endif;

      return str_repeat($simbolo . " ", $depth) . $name;
    }
    
    function translate_term_link( $link, $lang = null ) {
      $slug = $this->_current_lang_slug;
      $lang_id = $this->_current_lang_id;

      //L'utente ha scelto di tradurre il path delle categorie?
      if ( ! empty ( $this->_permalink_structure ) && !is_admin() ) :
        if( ( isset( $this->_force_current_language ) && $this->_force_current_language != $this->_default_language_id ) 
          || isset($this->_force_category_lang) || $this->_translate_term_link == 1) :

          $id = get_the_ID();
          if( !empty( $id ) )
            $lang_id = $this->get_language_id_by_page_id( get_the_ID() );
  
          //I tag mi arrivano con il parametro della lingua, lo tolgo sennò faccio casino :D
          $link = remove_query_arg( "lang", $link );
          return $this->translate_term_url( $link, $lang_id );
        endif;
      elseif( ! empty( $this->_force_category_lang ) ) :
    	$link = add_query_arg( array( 'lang' => $this->_force_category_lang ), $link );
      endif; //!empty

      if( $this->_current_lang_id != $this->_default_language_id  ) :
    	$link = $this->convert_url( $slug, $link );
      endif;

      return $link;
    }

    function translate_term_url( $link, $lang_id ) {
      if( isset( $this->_force_category_lang ) ) $lang_id = $this->_force_category_lang;
      if( $lang_id == 0 ) $lang_id = $this->get_current_lang_id();

      $homeUrl = $this->_homeUrl;
      $plinks = explode("/", str_replace($homeUrl, "", $link));

        //L'ultimo elemento della split è vuoto...
      $last = end( $plinks );
      if( empty( $last ) )
        array_pop($plinks);
	
      //Elimino i primi 2 elementi. 1 e la lingua e l'altro è "category"
      if( $this->_url_mode == PRE_PATH ) $cats[] = array_shift($plinks);
      $cats[] = array_shift($plinks);

      foreach( $plinks as $plink ) :
        //Cerco la traduzione della categoria nella lingua del post :)
        if( !empty( $plink ) ) :
          $id = get_category_by_slug( $plink );
  
          if( is_object( $id ) ) :
            $id = $id->term_id;
            
            $cat_name = strtolower( get_option( "cml_category_" . $id . "_lang_" . $lang_id, $plink ) );
            if( $this->_translate_term_link ) $plink = $cat_name;
          endif;
  
          $url = str_replace( " ", "-", $plink );
          $url = urlencode($url);
          $cats[] = $url;
        endif;
      endforeach;
  
      $slug = $this->get_language_slug_by_id( $lang_id );

      //Quale modalità è stata scelta?
      if($this->_url_mode == PRE_DOMAIN) :
          $homeUrl = str_replace("http://", "http://$slug.", $homeUrl);
      endif;
  
      $lang_arg = ( $this->_url_mode == PRE_LANG ) ? ("?lang=" . $slug) : "";

      //Ricreo il permalink con le categorie tradotte... :)
      if( !empty( $cats )) :
        return $homeUrl . join( "/", $cats ) . "/" . $lang_arg;
      endif; //!empty
      
      return $link;
    }

    function translate_category_url($url) {
      $homeUrl = home_url();
      $plinks = explode("/", str_replace($homeUrl, "", $url));

      //Se sto nel loop recupero la lingua dall'articolo
      if( in_the_loop() ) :
        $id = $this->get_language_id_by_post_id( get_the_ID() );
        $slug = $this->get_language_slug_by_id( $id );
      else:
    	$slug = $this->get_language_slug_by_id( $this->_current_lang_id );
      endif;
      
      if( empty( $slug ) ) $slug = $this->get_language_slug_by_id( $this->_current_lang_id );

      return $this->convert_url( $slug, $url );
    }

    function translate_object_terms($obj) {
      if(empty($obj)) return $obj;

      $nobj = $obj;
      if(is_object($obj)) :
	if($obj->taxonomy == 'category' || $obj->taxonomy == 'post_tag') {
	  $term_id = $obj->term_id;
	  $post_id = (isset($obj->object_id)) ? $obj->object_id : -1;

	  if($post_id > 1 || $this->_current_lang_id != $this->_default_language_id) :
	    $lang = $this->_current_lang_id;

	    $n = get_option("cml_category_" . $term_id . "_lang_" . $lang);
	    $obj->name = empty($n) ? $obj->name : $n;
	  endif;
	}
	
	return $obj;
      endif;

      if(is_array($obj)) :
	$nobj = null;
	foreach($obj as $o) :
	  $nobj[] = $this->translate_object_terms($o);
	endforeach;
      endif;
      
      return $nobj;
    }
    
    function translate_category($name) {
      return $this->translate_term_name($name);
    }

    /*
     * Converto l'url a seconda della modalità _url_mode selezionata
     */
    function convert_url( $slug, $permalink ) {
      if( isset( $this->_force_category_lang ) ) $slug = $this->get_language_slug_by_id( $this->_force_category_lang );

      switch( $this->_url_mode ):
      case PRE_DOMAIN :
	if( strpos( $permalink, "http://www." ) === FALSE ) :
	  $permalink = $this->add_slug_to_url( $slug );
	else:
	  $permalink = str_replace( "http://www.", "http://$slug.", $permalink );
	endif;

	return $permalink;
	break;
      case PRE_PATH: 
	//Aggiungo il suffisso /%lang%/
	$plinks = explode("/", str_replace( $this->_homeUrl, "", $permalink ));

	return $this->_homeUrl . $slug . "/" . join( "/", $plinks );
	break;
      case PRE_LANG:
	return add_query_arg( array( "lang" => $slug ), $permalink );
	break;
      default:
	return $permalink;
      endswitch;
    }

    function show_admin_notice() {
?>
	<div class="updated">
	<h2><img src="<?php echo CECEPPA_PLUGIN_URL ?>/images/logo.png" height="16" />&nbsp;Ceceppa Multilingua</h2>
	<?php _e('If you like this plugin, you can:', 'ceceppaml') ?>
	  <ul style="list-style: circle;padding-left: 30px">
	    <li>
	      <a href="http://wordpress.org/support/view/plugin-reviews/ceceppa-multilingua" target="_blank">
		<?php _e('Add your own review here', 'ceceppaml') ?></a>
	    </li>
	    <li>
		<?php _e('Promote the plugin on your blog or via twitter, facebook, google +', 'ceceppaml') ?>...
	    </li>
	    <li>
	      <?php _e('Translate the plugin in your own language.', 'ceceppaml') ?>
	      <a href="http://www.ceceppa.eu/chi-sono/" target="_blank">
		<?php _e('Contact me if you don\'t know how to do.', 'ceceppaml') ?>
	      </a>
	    </li>
	    <li>
	      <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G22CM5RA4G4KG">
		<?php _e('Donate to support development and maintenance.', 'ceceppaml') ?>
	      </a>
	    </li>
	  </ul>
	  <br />
	  <a href="http://wordpress.org/support/view/plugin-reviews/ceceppa-multilingua" target="_blank">
	    <?php _e('If you have any question or you need new features contact me :)', 'ceceppaml') ?>
	  </a>
	  <br /></br>
	  <div style="text-align: right;width:100%">
	    <?php $link = add_query_arg('cml-hide-notice', 1); ?>
	    <a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	  </div>
	</div>

<?php
    }
    
    function admin_notices( $force = false ) {
      global $pagenow;
      
      if( get_option("cml_check_language_file_exists", 1) )
	$this->check_language_file_exists();

//OPTIMIZATION
      if(get_option("cml_need_code_optimization", 1)) :
	echo "<div class=\"updated\">\n";
	echo "<p>\n";

	  _e('Sorry but due a bug it\'s necessary to fix language of all pages.', 'ceceppaml'); echo "<br />";
	  "<br />";
	  _e('Edit a page (or a post) and publish it withouth modify.', 'ceceppaml'); echo "<br />";

	echo "</p>\n";
	echo "</div>";
      endif;

// UPDATE POSTS
      if( get_option("cml_need_update_posts", false) || $force ) :
	echo "<div class=\"updated\">\n";
	echo "<p>\n";

	  _e('It is necessary assign default language to existing posts. ', 'ceceppaml'); echo "<br />";
	  echo "<a href='edit.php?&cml_update_posts=1'>" . sprintf(__('<strong>If your default language is "%s"</strong> click here for automatically assign this language to all existing posts.', 'ceceppaml'), $this->_default_language) . "</a>"; 
	    echo "<br /><br />";
	  _e('Otherwhise add correct default language first', 'ceceppaml'); echo "<br />";
	  echo "<br />";

	  $link = add_query_arg('cml_hide_update_posts', 1);
	  if( !$force ) echo '<a href="' . $link . '">' . __('Hide this message.', 'ceceppaml') . '</a>';
	echo "</p>\n";
	echo "</div>";
      endif;

//USE STATIC PAGE
      if( get_option( "cml_need_use_static_page", false ) ) :
	if( $this->_url_mode != PRE_PATH || cml_use_static_page() ) {
	  update_option( 'cml_need_use_static_page', 0 );
	} else {
	  echo '<div id="message" class="error">';
	  echo "<p>\n";

	    _e('If you want to use an page as homepage, you must set it as static page', 'ceceppaml'); echo "<br />";
	  echo "</div>";
	}
      endif;

//FIRST INSTALL
      if( get_option("cml_first_install", 0) ) :
// 	$this->successfully_installed();
	
	update_option("cml_first_install", 0);
      endif;

//MENU page
      if( isset( $_GET['cml_hide_notice_nav_menus'] ) ) update_option( 'cml_show_notice_nav_menus', 0 );
      if($pagenow == 'nav-menus.php' && get_option( 'cml_show_notice_nav_menus', 1 ) ) :
?>
	<div class="updated">
	    <p>
	      <?php echo $this->nav_menu_notice_text(); ?>
	      
	      <div style="text-align: right;width:100%">
		<?php $link = add_query_arg( 'cml_hide_notice_nav_menus', 1 ); ?>
		<a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	      </div>

	    </p>
	</div>
<?php
      endif;
      
//SETTINGS -> READING page
      if( isset( $_GET['cml_hide_notice_options_reading'] ) ) update_option( 'cml_show_notice_options_reading', 0 );
      if($pagenow == 'options-reading.php' && get_option( 'cml_show_notice_options_reading', 1 ) ) :
?>
	<div class="updated">
	    <p>
	      <?php echo $this->options_reading_help_text(); ?>
	      
	      <div style="text-align: right;width:100%">
		<?php $link = add_query_arg( 'cml_hide_notice_options_reading', 1 ); ?>
		<a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	      </div>
	    </p>
	</div>
<?php
      endif;
     
// SETTINGS -> General page (Site Title / Tagline)     
      if( isset( $_GET['cml_hide_notice_options_general'] ) ) update_option( 'cml_show_notice_options_general', 0 );
      if($pagenow == 'options-general.php' && get_option( 'cml_show_notice_options_general', 1 ) ) :
?>
	<div class="updated">
	    <p>
	      <?php echo $this->options_general_help_text(); ?>
	      
	      <div style="text-align: right;width:100%">
		<?php $link = add_query_arg( 'cml_hide_notice_options_general', 1 ); ?>
		<a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	      </div>
	    </p>
	</div>
<?php
      endif;
      
// TRANSLATE THEME !!!!
      if( array_key_exists( "cml_remove_translate_theme_notice", $_GET ) ) update_option( "cml_show_notice_translate_theme", 0 );
      if( get_option( 'cml_show_notice_translate_theme', 1 ) ) :
?>
	<div class="updated">
	      <h2><img src="<?php echo CECEPPA_PLUGIN_URL ?>/images/logo.png" height="16" />&nbsp;Ceceppa Multilingua</h2>
	      <?php _e( 'With "Ceceppa Multilingua" you can also <b>translate your theme</b>, if it support localization.', 'ceceppaml' ); ?>.<br /><br />
	      <?php _e( 'Go to "Ceceppa Multilingua" -> "Translate your theme"', 'ceceppaml' ); ?>,&nbsp;
	      <a href="<?php echo home_url('wp-admin') ?>/admin.php?page=ceceppaml-translations-plugins-themes&cml_remove_translate_theme_notice=1"><?php _e( 'or click here', 'ceceppaml' ) ?></a>.

	      <br /><br />
	      <b><?php _e( 'This feature require PHP >= 5.3.0', 'ceceppaml' ); ?></b>
	      <div style="text-align: right;width:100%">
		<?php $link = add_query_arg( array( "cml_remove_translate_theme_notice" => 1 ) ) ?>
		<a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	      </div>
	</div>
<?php
      endif;
    }
    
    /*
     * Controllo se i file della lingua esistono
     */
    function check_language_file_exists() {
      $error = false;

      $langs = cml_get_languages();
      foreach($langs as $lang) :
	if(substr($lang->cml_locale, 0, 2) != "en") :
	  //Controllo se esiste il file
	  $moFile = LOCALE_DIR . "/$lang->cml_locale.mo";
	  $exists = file_exists($moFile) && filesize($moFile) > 0;
	  if(!$exists) {
	    $error = true;
	    break;
	  }
	endif;
      endforeach;
      
      if($error) :
?>
	<div class="updated">
	    <p>
	      <?php _e('Some language file was not found.', 'ceceppaml') ?>
	      <?php $link = add_query_arg(array("page" => "ceceppaml-language-page", "tab" => 1)); ?><br />
	      <a href="<?php echo $link ?>"><?php _e('Click here for check and install them.', 'ceceppaml') ?></a>
	      <br />
	      <div style="text-align: right;width:100%">
		<?php $link = add_query_arg(array("cml_remove_lang_notice" => 1)) ?>
		<a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	      </div>
	    </p>
	</div>
<?php
      else:
	update_option("cml_check_language_file_exists", 0);
      endif;
    }

    function successfully_installed() {
?>
      <div class="updated">
	  <h2><img src="<?php echo CECEPPA_PLUGIN_URL ?>/images/logo.png" height="16" />&nbsp;Ceceppa Multilingua</h2>
	  <?php _e('Plugin successfully installed', 'ceceppaml') ?><br /><br />
	  <label>
	    <?php _e('Language detected:', 'ceceppaml') ?>
	    <strong>
		<img src="<?php echo cml_get_flag_by_lang_id( $this->_installed_language_id ) ?>" />
		<?php echo $this->_installed_language ?>
	    </strong>
	  </label>
	  <br /><br />
	  <a href="<?php echo home_url('wp-admin') ?>/?page=ceceppaml-language-page">
	    <?php _e('Click here for advanced settings or for add another language', 'ceceppaml') ?>
	  </a>
	  <br />
      </div>
<?php
    }
    
  function add_bar_menu() {
    global $wp_admin_bar;

    if(is_admin()) return;

    $langs = cml_get_languages(0);
    $id = get_the_ID();
    foreach( $langs as $lang ) :
      if( !cml_is_homepage() ) :
	if( is_single() || is_page() ) :
	  $linked = cml_get_linked_post( $this->_current_lang, $lang, $id, $lang->id );
	
	  if(empty($linked)) :
	    $url = $this->get_home_url( $lang->cml_language_slug );
	  else:
	    $url = get_permalink($linked);
	  endif;
	else:
	  $url = $this->convert_url( $lang->cml_language_slug, $this->_clean_url );	  
	endif;
      else:
	$url = $this->get_home_url( $lang->cml_language_slug );
      endif;

      $title = '<img src="' . cml_get_flag($lang->cml_flag) . '">&nbsp;' . $lang->cml_language;
      $wp_admin_bar->add_menu( array( 'id' => 'cml_lang' . $lang->id, 'title' => $title, 'href' => $url) );
    endforeach;
  }
  
  function hide_category_translations($wp_query) {
    //Se non è una categoria è inutile che viene qui :)
    if(!is_category()) return;

    /*
      * Wow, ho trovato un modo per tradurre il link delle categorie :D :D :
      * Se l'utente sta visualizzando la categoria, verifico se il nome della categoria è una traduzione
      * nella query di wordpress imposto cambio il nome della categoria in quello non tradotto...
      * Così nell'url l'utente verdà il percorso alla categoria tradotto :)
      *
      * Magari sarà poco etico... ma funziona :)
      */
    if(!isset($this->_change_category_applied)) :
      global $wpdb;


      //Se ho attivato la modalità PRE_PATH recupero lo slug dopo /category/, è quello che farà fede :)
      if($this->_translate_term_link) :
	$cat = $wp_query->query['category_name'];

	$cats = explode( "/", $cat );
	if( !is_array( $cats ) ) $cats = array( $cat );

	foreach($cats as $cat) :
	  //Se la categoria esiste non è una traduzione :)
	  $term = term_exists($cat, 'category');
	  if($term == 0 || $term == null) :
	    $cat = strtolower( str_replace("-", " ", $cat) );
	    $query = sprintf("SELECT *, UNHEX(cml_cat_name) as cml_cat_name FROM %s WHERE cml_cat_translation = '%s'", CECEPPA_ML_CATS, bin2hex($cat));

	    $name = $wpdb->get_row($query);
	    
	    //Se ho scelto la modalità PRE_PATH l'id della lingua lo "rivelo" dallo "slug" della lingua
	    if($this->_url_mode == PRE_PATH) :
	      $this->clear_url();
	    endif;

	    if( !isset( $this->_force_current_language ) || empty( $this->_force_current_language) ) :
	      if( !empty( $name ) )
		$this->_force_current_language = $name->cml_cat_lang_id;
	      else
		$this->_force_current_language = $this->_default_language_id;
	      endif;

	    $name = (!empty($name)) ? strtolower($name->cml_cat_name) : "";
	  endif;
	  
	  $new[] = empty($name) ? $cat : $name;
	endforeach;
	
	$wp_query->query['category_name'] = join("/", $new);
	$wp_query->query_vars['category_name'] = end($new);
	
	$taxquery = array("taxonomy" => "category",
			    "terms" => array(join("/", $new)),
			    "include_children" => 1,
			    "field" => "slug",
			    "operator" => "IN");

	$wp_query->tax_query->queries[0] = $taxquery;

	$this->_change_category_applied = true;
      endif;

      unset($this->_force_current_language);
      $this->_change_category_applied = true;

      unset($this->_hide_posts);
    endif;

    $this->hide_translations($wp_query);
  }
  
  function shortcode_page() {
    wp_enqueue_style("ceceppaml-style");
    wp_enqueue_style('ceceppaml-help', WP_PLUGIN_URL . '/ceceppa-multilingua/css/help.css');

    require_once(CECEPPA_PLUGIN_PATH . "includes/shortcode_page.php");
  }
  
  function code_optimization() {
    require_once(CECEPPA_PLUGIN_PATH . "fix.php");

    cml_fix_rebuild_posts_info();

    update_option("cml_code_optimization", 0);
    update_option("cml_need_code_optimization", 0);
  }
  
  /*
   * 
   */
  function preload_posts() {
    if( !is_admin() ) :
      $langs = cml_get_languages( 0 );
    else:
      $langs = cml_get_languages( 1 );
    endif;
    
    foreach($langs as $lang) :
      $this->_posts_of_lang[ $lang->id ] = get_option("cml_posts_of_lang_" . $lang->id);
    endforeach;
  }
  
  function add_flags_to_menu( $items, $args ) {
    global $_cml_settings;

    if( $_cml_settings["cml_add_items_as"] == 2) return $this->add_flags_in_submenu($items, $args);

    $to = $_cml_settings[ 'cml_add_items_to' ];
    if( ! empty( $to ) && $to != $args->theme_location ) return $items;

    $langs = cml_get_languages();
    $size = $_cml_settings["cml_show_in_menu_size"];

    foreach($langs as $lang) :
      $items .= $this->add_item_to_menu($lang, true, $size);
    endforeach;

    return $items;
  }
 
  function add_flags_in_submenu($items, $args) {
    global $_cml_sttings;

    $size = $_cml_settings["cml_show_in_menu_size"];

    //Lingua corrente
    $items .= $this->add_item_to_menu($this->get_current_language(), false, $size);
    
    //Submenu
    $items .= '<ul class="sub-menu">';
      
      //Altre lingue
      $langs = cml_get_languages();
      foreach($langs as $lang) :
	if($lang->id != $this->_current_lang_id) :
	  $items .= $this->add_item_to_menu($lang, true, $size);
	endif;
      endforeach;
    $items .= '</ul>';

    $items .= '</li>';

    return $items;
  }

  function add_item_to_menu( $lang, $close = true, $size = "small" ) {
    global $_cml_settings;

    $display = $_cml_settings["cml_show_in_menu_as"];

    $item = '<li class="menu-item menu-cml-flag">';

    $url = cml_get_the_link( $lang );

    $item .= '<a href="' . $url . '">';

    if( $display != 2 ) :
      $item .= "<img src=\"" . cml_get_flag_by_lang_id($lang->id, $size) . "\" title=\"$lang->cml_language\"/>";
    endif;
    
    if( $display < 3 ) :
      $item .= " " . $lang->cml_language;
    endif;
    
    $item .= "</a>";
    
    if($close) $item .= "</li>";
    
    return $item;
  }

  function append_flags_to_element() {
    global $_cml_settings;

    $appendTo = $_cml_settings[ "cml_append_flags_to" ];
    if(empty($appendTo)) return;

    $show = array("", "both", "text", "flag");
    $as = intval( $_cml_settings[ "cml_show_items_as" ] );
    $size = $_cml_settings[ "cml_show_items_size" ];

    $this->enqueue_script_append_to();
    wp_localize_script('ceceppa-append', 'cml_append_to', array('element' => $appendTo));

    echo '<div class="cml_append_flags" style="display: none">';
    cml_show_flags($show[$as], $size);
    echo '</div>';
  }

  function add_flying_flags() {
    global $_cml_settings;

    $show = array("", "both", "text", "flag");
    $as = intval( $_cml_settings[ "cml_show_float_items_as" ] );
    $size = $_cml_settings[ "cml_show_float_items_size" ];

    echo '<div id="flying-flags">';
      cml_show_flags( $show[$as], $size );
    echo '</div>';
  }
  
  /*
   * Questa funzione mi serve per poter passare tra le varie lingue della stessa 
   * categoria, perché la funzione get_category_link mi restituisce il link
   * rispetto alla lingua corrente, mentre a me serve il link per una 
   * lingua specifica.
   */
  function force_category_lang($lang) {
    $this->_force_category_lang = $lang;
  }

  function unset_category_lang() {
    unset($this->_force_category_lang);
  }

  function translate_archives_link( $link ) {
    $url = preg_match('/href=\'(.+)\' /', $link, $match);
    $href = $match[0];
    $url = substr($href, 0, strlen($href) - 2);
    $url .= "?lang=$this->_current_lang_slug'";
    
    $link = str_replace($href, $url, $link);
    return $link;
  }

  /*
   * Devo richiamare questa funzione solo dopo aver completato il caricamento
   * del plugin
   */
  function update_all_posts_language() {
    require_once(CECEPPA_PLUGIN_PATH . "fix.php");

    cml_update_all_posts_language();
    cml_fix_rebuild_posts_info();
  }
  
  //Restituisco l'url della homepage in base alle impostazioni de "url_mode"
  function get_home_url( $slug ) {
    if( empty( $slug) ) $slug = $this->_default_language_slug;

    switch( $this->_url_mode ) :
    case PRE_PATH:
      $link = $this->_homeUrl . $slug;
      break;
    case PRE_DOMAIN:
      $link = $this->add_slug_to_url( $slug );
      break;
    default:
      $link = $this->_homeUrl . "?lang=$slug";
    endswitch;

    return $link;
  }
  
  function add_slug_to_url( $slug ) {
    $url = explode( ".", $this->_url );
    $url[0] = $slug;

    return "http://" . join( ".", $url );
  }
  
  function is_homepage() {
    switch( $this->_url_mode ) :
    case PRE_PATH:
      return ( strlen( $this->_request_url ) <= 3 );
      break;
    case PRE_DOMAIN:
      break;
    case PRE_LANG:
      $url = remove_query_arg( "lang", $this->_url );
      return $url == $this->_homeUrl;
      break;
    endswitch;
  }
  
  function get_previous_next_post_where( $where ) {
    $posts = $this->get_posts_of_language();
    $where .= " AND p.id IN (" . implode(", ", $posts) . ") ";
    
    return $where;
  }

  function get_pagenum_link( $link ) {
    return $this->convert_url( $this->_current_lang_slug, $link );
  }

  function wp_title( $title ) {
    return get_option( "cml_site_title_" . $this->_current_lang_id, $title );
  }

  function bloginfo ( $info ) {
    return cml_translate( $info, $this->_current_lang_id, 'M' );
  }
  
  function link_pages_link( $link, $i ) {
    if( $i == 1 ) return $link;

    if( strpos( "href", $link ) === FALSE ) :
      return $link;
    endif;
    
    $link = preg_replace( '/\?lang=[a-z]{2}/', '', $link );
    preg_match( '/\"(.+?)\"/', $link, $l );
    return str_replace( end( $l ), $this->convert_url( $this->_current_lang_slug, end( $l ) ), $link );
  }
  
  function nav_menu_notice_text() {
    $text = "Ceceppa Multilingua: <strong>" . __( 'Tip', 'ceceppaml' ) . "</strong><br /><br />";
    $text .= __('All items will be automatically translated when user switch between languages.', 'ceceppaml') . "<br />";
    $text .= '<font style="color: #f00">' . __('Add only pages existing in your default language, not their translation.', 'ceceppaml') . '</font><br />';
    $text .= __('If you add an custom item ("Links"), you must add translation of navigation label in "Ceceppa Multilingua" -> "My translations"', 'ceceppaml');
    $text .= "<br /><br />";
    $text .= __( 'If you want to have different items for each languages, create a menu for each language', 'ceceppaml' );
    $text .= __( 'and assigns it to the corresponding menu, otherwise assign it only to the primary menu', 'ceceppaml');
    $text .= "<br /><br />";

    return $text;
  }
  
  function options_reading_help_text() {
    $text = "Ceceppa Multilingua: <strong>" . __( 'Tip', 'ceceppaml' ) . "</strong><br /><br />";
    $text .= __( 'You can to use a static page as homepage for your site/blog.', 'ceceppaml' ) . "<br />";
    $text .= __( 'Select a desided page for front and post page and translate them in all languages.', 'ceceppaml' ) . "<br />";
    
    return $text;
  }
  
  function options_general_help_text() {
    $link = home_url('wp-admin') . "/admin.php?page=ceceppaml-translations-title";

    $text = "Ceceppa Multilingua: <strong>" . __( 'Tip', 'ceceppaml' ) . "</strong><br /><br />";
    $text .= "<a href=\"$link\">" . __( 'Clicke here for translate the "Site Title" and "Tagline" in other languages' , 'ceceppaml' ) . "</a><br /><br />";
    $text .= '<span style="color: red"><strongs>' . __('N.B.: If you have translated the title of the website you have to upgrade it if you make changes', 'ceceppaml') . "<br />";
    
    return $text;
  }
  
  function has_translations( $id ) {
    global $wpdb;

    //Mi basta che abbia almeno una traduzione per restituire true :)
    $query = sprintf( "SELECT * FROM %s WHERE ( cml_post_id_1 = %d AND cml_post_id_2 > 0 ) OR ( cml_post_id_2 = %d AND cml_post_id_1 > 0 )", CECEPPA_ML_POSTS, $id, $id );
    $row = $wpdb->get_row( $query );

    return $row !== NULL;
  }
  
  //Rigenero gli id per lingua
  function update_nav_menu() {
    require_once 'fix.php';
    
    cml_fix_rebuild_posts_info();
  }
  
  function get_nav_menu_items( $items ) {
    $new = array();

    $size = get_option( 'cml_show_in_menu_size', 'small' );
    $what = get_option( 'cml_show_in_menu_as', 1 );

    $hide = get_option( 'cml_option_menu_hide_items', false );

    foreach( $items as $item ) {
      if( $hide && $item->type == 'post_type' ) {
        //Esiste nella lingua corrente?
        if( ! in_array( $item->object_id, $this->get_posts_of_language( ) ) ) {
          unset( $item );
        }
      }

      if( $item->url == '#cml-current' ) {
        $item->title = "";
        if( $what == 1 || $what == 2 )
          $item->title = $lang->cml_language;

        if( $what == 1 || $what == 3 ) {
          $item->title = '<img src="' . cml_get_flag( $lang->cml_flag, $size ) . '" />&nbsp;&nbsp;' . $item->title;
        }

        $item->url = cml_get_the_link( $lang );
      }

      if( $item->url == '#cml-others' || $item->url == '#cml-no-current' ) {
        if( $item->url == '#cml-no-current' ) $lang = cml_get_current_language();

        $langs = cml_get_languages();
        foreach( $langs as $l ) {
          if( isset( $lang ) && $l->id == $lang->id ) continue;

          $linfo = cml_get_language_info( $l->id );
          
          $clone = clone $item;
          $clone->title = "";

          if( $what == 1 || $what == 2 )
            $clone->title = $linfo->cml_language;

          if( $what == 1 || $what == 3 ) {
            $clone->title = '<img src="' . cml_get_flag( $linfo->cml_flag, $size ) . '" />&nbsp;&nbsp;' . $clone->title;
          }

          $clone->url = cml_get_the_link( $l );

          $new[] = $clone;
        }
        
        unset( $item );
      }

      $new[] = $item;
    }

    return $new;
  }
}

function removesmartquotes($content) {
     $content = str_replace('&#8220;', '&quot;', $content);
     $content = str_replace('&#8221;', '&quot;', $content);
     $content = str_replace('&#8216;', '&#39;', $content);
     $content = str_replace('&#8217;', '&#39;', $content);
    
     return $content;
}

/*
 * Recupero il percorso dove il tema va a leggere i file di "localizzazione"
 */
function cml_grab_theme_locale( $mofile, $domain ) {
  global $pagenow, $cml_theme_locale_path;

  //Recupero il nome del tema corrente
  $theme = wp_get_theme();

  $path = get_template_directory();	//Path del tema
  $info = pathinfo( $mofile );

  if( strcasecmp( $info[ 'dirname' ], $path ) > 0 ) :
    $cml_theme_locale_path = $info[ 'dirname' ];

    //Nome del tema e path "locale"
    update_option( 'cml_current_theme', $theme->Name );
    update_option( 'cml_current_theme_locale', $info[ 'dirname' ] );

    //Fatto
    remove_filter( 'load_textdomain_mofile', 'cml_grab_theme_locale', 0, 2 );
  endif;
  
  return $mofile;
}

if( is_admin() && array_key_exists( "page", $_GET ) && $_GET[ 'page' ] == "ceceppaml-translations-plugins-themes") :
  add_filter( 'load_textdomain_mofile', 'cml_grab_theme_locale', 0, 2 );
endif;

$wpCeceppaML = new CeceppaML();

?>