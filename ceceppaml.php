<?php
/*
Plugin Name: Ceceppa Multilingua
Plugin URI: http://www.ceceppa.eu/it/interessi/progetti/wp-progetti/ceceppa-multilingua-per-wordpress/
Description: Come rendere il tuo sito wordpress multilingua :).How make your wordpress site multilanguage.
Version: 0.9.17
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
global $wpdb;

define('CECEPPA_DB_VERSION', 12);

define('CECEPPA_ML_TABLE', $wpdb->base_prefix . 'ceceppa_ml');
define('CECEPPA_ML_CATS', $wpdb->base_prefix . 'ceceppa_ml_cats');
define('CECEPPA_ML_POSTS', $wpdb->base_prefix . 'ceceppa_ml_posts');
define('CECEPPA_ML_PAGES', $wpdb->base_prefix . 'ceceppa_ml_posts');

//Memorizzo gli id degli articoli/pagine al fine di poter "filtrare" i post per lingua
define('CECEPPA_ML_RELATIONS', $wpdb->base_prefix . 'ceceppa_ml_relations');

//Tabella delle traduzioni. Al momento mi appoggio a una tabella per le traduzioni in quanto non ho trovato nessun modo
//per generare un file .po direttamente da php
define('CECEPPA_ML_TRANS', $wpdb->base_prefix . 'ceceppa_ml_trans');     

define('CECEPPA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CECEPPA_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once(CECEPPA_PLUGIN_PATH . 'functions.php');
require_once(CECEPPA_PLUGIN_PATH . 'utils.php');
require_once(CECEPPA_PLUGIN_PATH . 'shortcode.php');
require_once(CECEPPA_PLUGIN_PATH . 'widgets.php');

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
  protected $_filte_form_class = "searchform";
  protected $_no_translate_menu_item = false;

  public function __construct() {
    global $wpdb;

    /*
    * Se utilizzo add_action per caricare gli script quando richiesto,
    * non mi vengono "caricati" nelle verie funzioni a disposizione :(
    */
    //$this->register_scripts();
    add_action('wp_enqueue_scripts', array(&$this, 'register_scripts'));

    //Creo le tabelle al primo avvio
    if(get_option('cml_db_version') != CECEPPA_DB_VERSION) $this->create_table();

    /* 
     * Recupero le impostazioni della lingua di default 
     */
    $this->_default_language = $wpdb->get_var("SELECT cml_language FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
    $this->_default_language_id = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
    $this->_default_language_slug = $wpdb->get_var("SELECT cml_language_slug FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");

    /*
     * Opzioni disponibili per l'amministratore:
     *
     *   *) Configurazione lingue
     *   *) impostazioni plugin
     *   *) gestione campi extra nelle categorie
     */
    if(is_admin()) {
      add_action('admin_enqueue_scripts', array(&$this, 'register_scripts'));

      /* 
      * IT: Aggiungo la pagina delle opzioni
      * EPO: Agodojn
      * EN: Settings
      */
      add_action('admin_menu', array(&$this, 'add_option_page'));
      add_action('admin_menu', array(&$this, 'add_menu_flags'));

      /*
      * IT: Campi extra nelle categorie
      * EN: Category extra fields
      * EPO: 
      */
      add_action('edit_category_form_fields', array(&$this, 'category_edit_form_fields'));
      add_action('category_add_form_fields', array(&$this, 'category_add_form_fields'));
      add_action('edited_category', array(&$this, 'save_extra_category_fileds'));
      add_action('created_category', array(&$this, 'save_extra_category_fileds'));
      add_action('deleted_term_taxonomy', array(&$this, 'delete_extra_category_fields'));
      add_action('edit_tag_form_fields', array(&$this, 'category_edit_form_fields'));
      add_action('edited_term', array(&$this, 'save_extra_category_fileds'));
      add_action('add_tag_form_fields', array(&$this, 'category_add_form_fields'));
      add_action('created_term', array(&$this, 'save_extra_category_fileds'));

      /*
	* Aggiungo il box di collegamento nei post e nelle pagine 
	*/
      add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
      add_action('edit_post', array(&$this, 'save_extra_post_fields'));
      add_action('delete_post', array(&$this, 'delete_extra_post_fields'));
      add_action('edit_page_form', array(&$this, 'save_extra_post_fields'));
      add_action('delete_page', array(&$this, 'delete_extra_post_fields'));

      //Aggiungo le banidere all'elenco dei post
      add_action('manage_pages_custom_column', array(&$this, 'add_flag_column'), 10, 2);
      add_filter('manage_pages_columns' , array(&$this, 'add_flags_columns'));
      add_action('manage_posts_custom_column', array(&$this, 'add_flag_column'), 10, 2);
      add_filter('manage_posts_columns' , array(&$this, 'add_flags_columns'));

      //Filtri
      add_filter('parse_query', array(&$this, 'filter_all_posts_query'));
      add_action('restrict_manage_posts', array(&$this, 'filter_all_posts_page'));
      
      /*
      * Traduco i titoli dei Widget
      */
      add_filter('widget_title', array(&$this, 'admin_translate_widget_title'));
      
      /*
       * Visualizzo alcune informazioni quali:
       *   Avvisi su categorie e tag non tradotti
       */
      add_action( 'admin_notices', array(&$this, 'admin_notices'));
      
      if(array_key_exists("cml-hide-notice", $_GET)) update_option('cml_show_admin_notice', 0);
      if(get_option('cml_show_admin_notice', 1))
	add_action( 'admin_notices', array(&$this, 'show_admin_notice'));

      if(isset($_GET['cml_update_posts'])) $this->update_all_posts_language();
      if(isset($_GET['cml_hide_update_posts'])) update_option("cml_need_update_posts", false);
    } else {
      /*
      * Filtro gli articoli per lingua
      * Filter posts by language
      */
      if(get_option("cml_option_filter_posts", false)) {
	add_action('pre_get_posts', array(&$this, 'filter_posts_by_language'));
      }

      /*
      * Nascondo i post "collegati", quindi tra quelli collegati visualizzo solo quelli
      * della lingua corrente
      */
      if(get_option("cml_option_filter_translations", true) || array_key_exists("ht", $_GET)) {
	add_action('pre_get_posts', array(&$this, 'hide_translations'));
      }

      //Per le categorie forzo l'opzione 'hide_translations' così evito di passare il "poco estetico" parametro "ht=1"
      add_action('pre_get_posts', array(&$this, 'hide_category_translations'));

      /*
      * Filtro i risultati della ricerca in modo da visualizzare solo gli articoli inerenti
      * alla lingua che l'utente stà visualizzando
      */
      $this->_filter_search = get_option('cml_option_filter_search', false);
      $this->_filte_form_class = get_option('cml_option_filter_form_class', $this->_filte_form_class);

      /*
      * Filtro alcune query per lingua (Articoli più letti/commentati)
      */
      if(get_option('cml_option_filter_query')) {
	add_filter('query', array(&$this, 'filter_query'));
      }

      /*
      * Traduco i titoli dei Widget
      */
      add_filter('widget_title', array(&$this, 'translate_widget_title'));

      /*
      * Serve a reindirizzare il browser
      */
      $this->_redirect_browser = get_option('cml_option_redirect', 'auto');
      add_action('plugins_loaded', array(&$this, 'redirect_browser'));

      /*
      * Devo visualizzare le bandiere delle lingue disponibili?
      * Di defaut le abilito su post e pagine
      */
      if(get_option('cml_option_flags_on_post', true) ||
	  get_option('cml_option_flags_on_page', true) ||
	  get_option('cml_option_flags_on_cats')) :
	
	if(get_option('cml_option_flags_on_pos', 'top') == "bottom") {
	    add_filter("the_content", array(&$this, 'add_flags_on_bottom'));
	} else {
	    add_filter("the_title", array(&$this, 'add_flags_on_top'), 10, 2);
	}
      endif;

      /*
      * Devo visualizzare l'avviso?
      */
      $this->_show_notice = get_option('cml_option_notice', 'notice');
      $this->_show_notice_pos = get_option('cml_option_notice_pos', 'top');
      if($this->_show_notice != 'nothing' && !is_admin()) //!is_home() && 
	add_action('the_content', array(&$this, 'show_notice'));

      //Commenti
      $this->_comments = get_option('cml_option_comments', 'group');
      if($this->_comments == 'group') 
	add_filter('query', array(&$this, 'get_comments'));

      //Aggiorno la lingua corrente quando sto per visualizzare un post
      add_filter('pre_get_posts', array(&$this, 'update_current_lang'));

      //E' stata utilizzata una pagina statica come homepage?
      if(array_key_exists("sp", $_GET))
	add_filter('pre_get_posts', array(&$this, 'get_static_page'));

      //Translate menu items    
      add_filter('wp_setup_nav_menu_item', array(&$this, 'translate_menu_item'));

      add_filter('wp_get_object_terms', array(&$this, 'translate_object_terms'));
      add_filter('list_cats', array(&$this, 'translate_category'));
      add_filter('get_terms', array(&$this, 'translate_object_terms'));
    }
    
    //Update current language
    add_action( 'init', array(&$this, 'update_current_lang'));
    add_filter('wp_head', array(&$this, 'update_current_lang'));
    add_filter('wp_title', array(&$this, 'update_current_lang'));

    /*
    * Nella pagina "menu", aggiungo una lista per ogni lingua
    */
    add_action('init', array(&$this, 'add_menus'));
    add_action('wp_footer', array(&$this, 'restore_default_menu'));
    
    //Translate post_link
    add_filter('post_link', array(&$this, 'translate_post_link'), 0, 3);
    add_filter('term_link', array(&$this, 'translate_term_link'), 0);
    add_filter('term_name', array(&$this, 'translate_term_name'), 0, 1);

    //Aggiungo lo switch delle lingue nella barra dell'amministratore
    add_action( 'admin_bar_menu', array(&$this, 'add_bar_menu'), 1000);

    /*
    * Locale
    */
    //add_filter('query_vars', array(&$this, 'add_lang_query_vars'));
    if(get_option("cml_option_change_locale", 1) == 1 || is_admin()) :
      add_filter('locale', array(&$this, 'setlocale'));
    endif;
  }

  /*
   * Aggiungo per ogni lingua il collegamento al menu, escluso per quella principale
   */
  function add_menus() {
    global $wpdb;

    $r = load_plugin_textdomain('ceceppaml', false, dirname( plugin_basename( __FILE__ ) ) . '/po/');

    $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_language"); //WHERE cml_default = 1 
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

    $query = "SELECT * FROM " . CECEPPA_ML_TABLE . " order by cml_language";
    $results = $wpdb->get_results($query);
    
    foreach($results as $result) :
      $url = get_bloginfo('wpurl');
      $link = add_query_arg('lang', $result->cml_language_slug);
      
      $language = $result->cml_language_slug;

      //From qTranslate
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
    }
  
  /**
   * Aggiungo la pagina delle opzioni nella barra laterale di Wordpress
   */
  function add_option_page() {
    add_menu_page('Ceceppa ML Options', __('Ceceppa Multilingua', 'ceceppaml'), 'administrator', 'ceceppaml-language-page', array(&$this, 'form_languages'), CECEPPA_PLUGIN_URL . '/images/logo.png');
    //add_submenu_page('ceceppaml-language-page', __('Elenco articoli', 'ceceppaml'), __('Elenco articoli', 'ceceppaml'), 'manage_options', 'ceceppaml-posts-page', array(&$this, 'get_posts'));
    add_submenu_page('ceceppaml-language-page', __('Widget titles', 'ceceppaml'), __('Widget titles', 'ceceppaml'), 'manage_options', 'ceceppaml-widgettitles-page', array(&$this, 'form_widgettitles'));
    add_submenu_page('ceceppaml-language-page', __('My translations', 'ceceppaml'), __('My translations', 'ceceppaml'), 'manage_options', 'ceceppaml-translations-page', array(&$this, 'form_translations'));
    add_submenu_page('ceceppaml-language-page', __('Settings', 'ceceppaml'), __('Settings', 'ceceppaml'), 'manage_options', 'ceceppaml-options-page', array(&$this, 'form_options'));
    add_submenu_page('ceceppaml-language-page', __('Shortcode', 'ceceppaml'), __('Shortcode', 'ceceppaml'), 'manage_options', 'ceceppaml-shortcode-page', array(&$this, 'shortcode_page'));
  }

  /**
    * Aggiungo le bandiere vicino al titolo del post nella pagina "Tutti gli articoli"
    */
  function add_flags_columns($columns) {
    $langs = cml_get_languages(0);

    //Non sono riuscito a trovare un altro modo per ridimensionare la larghezza del th...
    wp_enqueue_style('ceceppaml-style-all-posts', WP_PLUGIN_URL . '/ceceppa-multilingua/css/all_posts.php?langs=' . count($langs));

    foreach($langs as $lang) :
      $a = add_query_arg(array("cml_language" => $lang->id));
      $img .= "<a href=\"$a\" title=\"" . __('Show only posts/pages in: ', 'ceceppaml') . "$lang->cml_language\"><img src=\"" . cml_get_flag_by_lang_id($lang->id, "small") . "\" width=\"20\" /></a>";
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
            $xid = ($post_type == 'post') ? $this->get_language_id_by_post_id($id) :
                                            $this->get_language_id_by_page_id($id);

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
     * Aggiungo le bandiere sotto al titolo del post
     */
    function add_flags_on_top($title, $id = -1) {
	if(isset($this->_title_applied)) return $title;
        if($id < 0) return $title;
        if(is_single() && !get_option('cml_option_flags_on_post')) return $title;
        if(is_page() && !get_option('cml_option_flags_on_page')) return $title;
        if(is_category() && !get_option('cml_option_flags_on_cats')) return $title;
        if(!in_the_loop()) return $title;

        global $post;
        /* Mi serve per evitare che mi trovi bandiere ovunque :D.
         * Non posso utilizzare in_the_loop senno rischio di trovarmi bandiere anche vicino ai
         * "post correlati" a piè di pagina :(
         * Ho bisogno di modificare le "curly quotes" in "double quote", sennò il confronto fallisce :(
        */
        if(esc_attr($post->post_title) == removesmartquotes($title)) {
	    $this->_title_applied = true;

	    return $title . cml_show_available_langs(array("class" => "cml_flags_on_top"));
        } else {
            return $title;
        }
    }

    function add_flags_on_bottom($title) {
        if(is_single() && !get_option('cml_option_flags_on_post')) return $title;
        if(is_page() && !get_option('cml_option_flags_on_page')) return $title;
        if(is_category() && !get_option('cml_option_flags_on_cats')) return $title;

        return $title . cml_show_available_langs(array("class" => "cml_flags_on_top"));
    }

  /**
   * NUOVA CATEGORIA
   * Campi necessari per collegare la nuova categoria con quella della lingua di default
   */
  function category_add_form_fields($tag) {
        wp_enqueue_script('ceceppaml-cat');
?>
        <div class="form-field">
        <?php
            $langs = cml_get_languages(1, 0);

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
    <tr class="form-field">
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
    global $wpdb;

    //Server per poter utilizare la funzione dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    /*
     * CECEPPA_ML_TABLE: Contiene le informazioni sulle lingue da gestire
     */
    $table_name = CECEPPA_ML_TABLE;
    $first_time = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name;
    //if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
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
        PRIMARY KEY  id (id)
        ) ENGINE=InnoDB CHARACTER SET=utf8;";

        dbDelta($sql);
      }

        $query = "UPDATE " . CECEPPA_ML_TABLE . " SET cml_enabled = 1 WHERE cml_enabled IS NULL";
        $wpdb->query($query);

        if($first_time) :
	  update_option("cml_need_update_posts", true);

	  /* Per comodità  creo nel database un record con la lingua corrente */
	  $locale = get_locale();
	  $language = __("Default language");

	  require_once(CECEPPA_PLUGIN_PATH . "locales_codes.php");
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

	  add_action( 'admin_notices', array(&$this, 'successfully_installed'));
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
//    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $query = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        cml_post_lang_1 INT(11),
        cml_post_id_1 INT(11),
        cml_post_lang_2 INT(11),
        cml_post_id_2 INT(11),
        PRIMARY KEY  id (id)) ENGINE=InnoDB CHARACTER SET=utf8;";

      dbDelta($query);
//    }
        
    /**
     * Per le traduzioni momentaneamente mi appoggio ad un database.
     * Appena trovo il modo di gestire tutto da php e generare file po al volo rimuovo la tabella :)
     *
     */
    $table_name = CECEPPA_ML_TRANS;
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 9) :
      if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ) {
	$sql = "ALTER TABLE " . CECEPPA_ML_TRANS . " ADD COLUMN `cml_type` TEXT";
	$wpdb->query($sql);
	$wpdb->query("UPDATE " . CECEPPA_ML_TRANS . " SET cml_type = 'W'");
      }
    endif;

    $query = "CREATE TABLE  $table_name (
      `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
      cml_text TEXT NOT NULL ,
      `cml_lang_id` INT NOT NULL ,
      `cml_translation` TEXT,
      `cml_type` TEXT) ENGINE=InnoDB CHARACTER SET=utf8;";

    dbDelta($query);
    //}

    //Rimuovo le colonne non più necessarie
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 9) :
      $wpdb->query("ALTER table " . CECEPPA_ML_TABLE . " DROP cml_category_name, DROP cml_category_id, DROP cml_category_slug, DROP cml_page_id, DROP cml_page_slug");
    endif;

    //modifico il charset della tabella
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 9) :
      $alter = "ALTER TABLE  " . CECEPPA_ML_TABLE . " CHANGE  `cml_language`  `cml_language` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL,"
		. "CHANGE  `cml_notice_post`  `cml_notice_post` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_notice_page`  `cml_notice_page` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_notice_category`  `cml_notice_category` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL ,"
		. "CHANGE  `cml_locale`  `cml_locale` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL";
      $wpdb->query($alter);

      $alter = "ALTER TABLE  `wp_ceceppa_ml_trans` CHANGE  `cml_text`  `cml_text` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NOT NULL ,"
		. "CHANGE  `cml_translation`  `cml_translation` TEXT CHARACTER SET utf8 COLLATE utf8_general_mysql500_ci NULL DEFAULT NULL";
      $wpdb->query($alter);
    endif;

    //Fix dovuto alla 0.9.1, tutte le lingue venivano impostate come default se l'utente face click su update :(
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) != 9) :
      $wpdb->query("UPDATE " . CECEPPA_ML_TABLE . " SET cml_default = 0");
      $wpdb->query("UPDATE " . CECEPPA_ML_TABLE . " SET cml_default = 1 WHERE id = 1");
    endif;

    //Ricreo tutti gli indici "cml_post_lang_##" in "cml_page_lang"
    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 10) :
      $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_POSTS);
      foreach($results as $result) :
	  if($result->cml_post_lang_1 > 0) update_option("cml_page_lang_" . $result->cml_post_id_1, $result->cml_post_lang_1);
	  if($result->cml_post_lang_2 > 0) update_option("cml_page_lang_" . $result->cml_post_id_2, $result->cml_post_lang_2);

	  delete_option("cml_post_lang_" . $result->cml_post_id_1);
	  delete_option("cml_post_lang_" . $result->cml_post_id_2);
      endforeach;
    endif;

    if(get_option("cml_db_version", CECEPPA_DB_VERSION) <= 12) :
      $sql = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_id_1 NOT IN (select ID from $wpdb->posts)";
      $wpdb->query($sql);

      $sql = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_id_2 NOT IN (select ID from $wpdb->posts)";
      $wpdb->query($sql);
    endif;

    //for updates
    update_option("cml_db_version", CECEPPA_DB_VERSION);
  }

    function register_scripts() {
      //Javascript
      wp_register_script('ceceppa-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.dd.min.js');
      wp_register_script('ceceppaml-js', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.js', array('ceceppa-dd'));
      wp_register_script('ceceppa-tipsy', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.tipsy.js');
      wp_register_script('ceceppaml-cat', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa-cat.js');
      //    wp_enqueue_script('ceceppa-search', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.search.js', array('jquery'));

      //Css
      wp_register_style('ceceppaml-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/ceceppaml.css');
      wp_register_style('ceceppaml-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/css/dd.css');
      wp_register_style('ceceppa-tipsy', WP_PLUGIN_URL . '/ceceppa-multilingua/css/tipsy.css');
      wp_register_style('ceceppaml-widget-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/widget.css');
      wp_enqueue_style('ceceppaml-common-style', WP_PLUGIN_URL . '/ceceppa-multilingua/css/common.css');
    }

    function enqueue_script_search() {
        wp_enqueue_script('ceceppa-search', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.search.js', array('jquery'));
    }

  /*
   * Filtro gli articoli più letti aggiungendo alla
   * query la condizione sugli id dei post
   */
  function filter_least_read_post($query, $pos) {
    //Recupero tutti i post collegati alla lingua corrente
    $posts = $this->get_language_posts();
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
    $posts = $this->get_language_posts();
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
    $posts = $this->get_language_posts();
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
        $d = isset($_GET['cml_language']) ? $_GET['cml_language'] : $this->_default_language_id;

        //All languages
        cml_dropdown_langs("cml_language", $d, false, true, __('Show all languages', 'ceceppaml'), -1);
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

        $id = array_key_exists('cml_language', $_GET) ? intval($_GET['cml_language']) : $this->_default_language_id;
        if(is_admin() && $pagenow == "edit.php") :
	  if($id > 0) :
	    if(!isset($_GET['post_type']) || $_GET['post_type'] == "post") {
	      $posts = $this->get_language_posts($id);;
	    } else {
	      $posts = $this->get_language_pages($id);
	    }

	    $query->query_vars['post__in'] = $posts;
	  endif;
	endif;

	return $query;
    }
    
  /*
  * Dico a wordpress di restituire i post appartenenti alla categoria $ceceppa_current_language_category
  *
  */
  function filter_posts_by_language($wp_query) {
    if(!is_search()) {
      if(!is_home() || is_admin() || isCrawler()) return;
    } else {
      if(!$this->_filter_search) return;

      $this->update_current_lang();
    }

    global $wpdb;

    //Categoria base della lingua predefinita
    //$cat = $wpdb->get_var(sprintf("SELECT cml_category_id FROM %s WHERE cml_default = 1", CECEPPA_ML_TABLE));

    if(isset($_GET['lang'])) :
      $lang = $_GET['lang'];

      //Recupero la categoria base associata alla lingua
      $query = sprintf("SELECT * FROM %s WHERE cml_language_slug = '%s'", CECEPPA_ML_TABLE, $lang);
      $c = $wpdb->get_row($query);

      if(!empty($lang)) {
	$this->_current_lang = $lang;
	$this->_current_lang_id = $c->id;
      }
    endif;

    //Recupero tutti i post associati alla lingua corrente
    $posts = $this->get_language_posts($this->_current_lang_id);

    if(!empty($posts))
	set_query_var('post__in', $posts);
  }

    /*
     * Nascondo i post tradotti
     */
    function hide_translations($wp_query) {
        global $wpdb;

        if($wp_query != null && (is_page() || is_single() || isCrawler())) return;
        if(!is_admin()) $this->update_current_lang();

        if(empty($this->_exclude_posts)) :
	    /*
	     * A cosa servono cml_fake_id_1 e cml_fake_id_2?
	     * Per poter visualizzare un articolo in tutte le lingue, basta che non gli assegno nessuna lingua.
	     * Se scrivo un articolo e non gli assegno nessuna lingua, e poi per tale articolo ne creo una traduzione, tale post non era più "visibile"
	     * nell'archivio delle categorie. 
	     * In quanto il 4 if che segue lo marcava come "post__not_in". Dato che i post senza lingua vengono contrassegnati con "0" con una case when
	     * assegno un id "temporaneo" a tale articolo, così da ingannare il plugin ;)
	     * Non posso rimuovere il 4 if perché l'esclusione delle traduzioni si basa sull'idea di nascondere tutti i post che sono traduzioni della lingua
	     * corrente.... l'idea fallisce nel momento in cui ho, per esempio, 3 lingue e traduco un articolo per solo 2 di queste lingue, es. per la lingua 1 e 2.
	     * Ora se voglio visualizzare gli articoli della lingua 3 mi ritroverei con l'articolo tradotto precedentemente, sia per la lingua 1 che 2, in quanto
	     * non esiste una traduzione nella lingua 3... e non posso nasconderla :(
	     * A questo serve il 4 if :)
	     */
            $query = sprintf("SELECT *, case when cml_post_lang_1 > 0 then cml_post_lang_1 else  %d end as cml_fake_id_1, case when cml_post_lang_2 > 0 then cml_post_lang_2 else  %d end as cml_fake_id_2 FROM %s", $this->_current_lang_id, $this->_current_lang_id, CECEPPA_ML_POSTS);
	    $results = $wpdb->get_results($query);

	    foreach($results as $result) :
                if($result->cml_post_id_2 > 0 && $result->cml_fake_id_1 == $this->_current_lang_id) $posts[] = $result->cml_post_id_2;
                if($result->cml_post_id_1 > 0 && $result->cml_fake_id_2 == $this->_current_lang_id) $posts[] = $result->cml_post_id_1;
                if(($result->cml_post_id_2 > 0 && $result->cml_post_id_1 > 0) &&
		      $result->cml_fake_id_1 != $this->_current_lang_id && $result->cml_fake_id_2 != $this->_current_lang_id) :
		      
		      //Rimuovo il secondo xkè sicuramente è una traduzione
		      $posts[] = $result->cml_post_id_2;
		      
		      //Per il primo controllo se è una traduzione di un articolo in questa lingua
		      $nid = cml_get_linked_post($result->cml_fake_id_1, null, $result->cml_post_id_1, $this->_current_lang_id);
		      if(!empty($nid)) $posts[] = $result->cml_post_id_1;
		endif;
                if($result->cml_fake_id_1 == $result->cml_fake_id_2 && !empty($posts)) array_pop($posts);

                //Può capitare che ho n (> 2) lingue e alcuni articoli non sono tradotti in queste, allora nascondo gli articoli
                //per evitare che vengano visualizzati entrambi.
                if($result->cml_fake_id_1 != $this->_current_lang_id && $result->cml_fake_id_2 != $this->_current_lang_id):
		  //Nascondo l'id 2 che è sicuramente una traduzione...
		  $posts[] = $result->cml_post_id_2;
		endif;
            endforeach;

	    $this->_exclude_posts = !empty($posts) ? $posts : array();

	    if(is_tag()) :
	      /*
	        Può capitare che l'utente invece di tradurre un tag ne usa uno nuovo per l'articolo. In questo caso
	        aggiungendo all'url ?lang=##, viene visualizzato il messaggio 404 se per quella lingua non c'è nessun
	        articolo con quel tag, il ché può essere un po' noioso.
	        Dagli articoli da escludere rimuovo tutti quelli che non hanno quel tag. Così se assegno 2 tag diversi
	        all'articolo e alla sua traduzione non mi ritrovo con un 404.
	        Il metodo, a causa dei vari cicli da eseguire, probabilmente porterà dei rallentamenti nel caricamento delle pagine
	        dei tag, però è anche l'unico modo di evitare pagine 404 se all'utente piace assegnare tag diversi invece di tradurli...
	      */
	      $tag_name = $wp_query->query_vars['tag'];
	      $i = 0;

	      foreach($this->_exclude_posts as $id) :
		$tags = wp_get_post_tags($id);
		$lang_id = $this->get_language_id_by_post_id($id);

		foreach($tags as $tag) :
		  if($tag->name == $tag_name && $lang_id != $this->_current_lang_id) :

		    //Controllo che per questa articolo non esista nessuna traduzione nella lingua corrente con la stessa categoria
		    $nid = cml_get_linked_post($lang_id, null, $id, $this->_current_lang_id);
		    echo $nid . ", " . $id;
		    if(!empty($nid)) :
		      //Verifico le categorie dell'articolo collegato
		      $_tags = wp_get_post_tags($nid);
		      $found = false;
		      foreach($_tags as $_tag) :
			if($_tag->name == $tag_name) :
			  $found = true;
			  break;
			endif;
		      endforeach;
		      
		      if(!$found) :
			unset($this->_exclude_posts[$i]);
			break;
		      endif;
		    endif;
		  endif;
		endforeach;
		
		$i++;
	      endforeach;
	    endif;
        endif;

        if($wp_query != null && is_object($wp_query)) :
	  $wp_query->query_vars['post__not_in'] = $this->_exclude_posts;

	  return;
	endif;
//        set_query_var('post__not_in', $this->_exclude_posts);

        return $this->_exclude_posts;
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
  function get_category_id($cat_name){
    $term = get_term_by('name', $cat_name, 'category');

    return (is_object($term)) ? $term->term_id : $term;
  }

  /**
   * Recupero il "padre" della categoria specificata
   */
  function get_category_parent($cat_id) {
    if(empty($cat_id)) return;

    $cats = split("/", get_category_parents($cat_id, false, "/", true));

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
    
    $query = sprintf("SELECT id FROM %s WHERE cml_locale IN ('%s')", CECEPPA_ML_TABLE, implode("','", $locale));

    return $wpdb->get_var($query);
  }

  function get_language_id_by_slug($slug) {
    global $wpdb;

    $query = sprintf("SELECT id FROM %s WHERE cml_language_slug = '%s'", CECEPPA_ML_TABLE, $slug);
    return $wpdb->get_var($query);
  }
  
  function get_language_id_by_post_id($post_id) {
    global $wpdb;

    //E' stata specificata una lingua "manuale"?
    $val = get_option("cml_page_lang_$post_id", 0);
    if($val > 0) return $val;

    /*
      * Alla categoria non è stata associata alcuna lingua,
      * controllo nella tabella CECEPPA_ML_POSTS se è stata associata
      * direttamente all'articolo
      */
    if(empty($val)) :
	$query = sprintf("SELECT cml_post_lang_1 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, $post_id);
	$val = $wpdb->get_var($query);
    endif;

    return $val;
  }

  function get_language_id_by_page_id($page_id) {
    global $wpdb;

    return get_option("cml_page_lang_$page_id", $this->_default_language_id);
  }

  /**
   * Form per la gestione delle lingue
   */
  function form_languages() {
    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_script('ceceppa-tipsy');

    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');
    wp_enqueue_style('ceceppa-tipsy');

    //$this->create_table();

    if(isset($_POST['form'])) :
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
				  'cml_enabled' => $_POST['lang-enabled'][$i]),
			     array('id' => $id),
			     array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
			     array('%d'));
	  }
      endfor;

      //Delete
      if(!empty($_POST['delete'])) :
        $delete = intval($_POST['delete']);

        $wpdb->query("DELETE FROM " . CECEPPA_ML_TABLE . " WHERE id = " . $delete);
        $wpdb->query(sprintf("DELETE FROM " . CECEPPA_ML_POSTS . " WHERE cml_post_lang_1 = %d OR cml_post_lang_2 = %d", $delete, $delete));
      endif;
    endif;

    include dirname( __FILE__  ).'/languages.php';
  }

  /**
   * Form per le impostazioni del plugin
   */
  function form_options() {
    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');

    if(array_key_exists("options", $_POST)) {
      //Add slug & url mode
      update_option("cml_add_slug_to_link", $_POST['add-slug']);
      update_option("cml_modification_mode", $_POST['url-mode']);

      //Redirect
      update_option("cml_option_redirect", $_POST['redirect']);
      update_option("cml_option_redirect_type", $_POST['redirect-type']);

      if(array_key_exists("posts", $_POST))
	update_option("cml_option_post_redirect", $_POST['posts']);

      //Flags
      @update_option("cml_option_flags_on_post", intval($_POST['flags-on-posts']));
      @update_option("cml_option_flags_on_page", intval($_POST['flags-on-pages']));
      @update_option("cml_option_flags_on_cats", intval($_POST['flags-on-cats']));
      @update_option("cml_option_flags_on_pos", $_POST['flags_on_pos']);

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

      //Avviso
      @update_option("cml_option_notice", $_POST['notice']);
      @update_option("cml_option_notice_pos", $_POST['notice_pos']);
      @update_option("cml_option_notice_after", $_POST['notice_after']);
      @update_option("cml_option_notice_before", $_POST['notice_before']);
      @update_option("cml_option_notice_post", intval($_POST['notice-post']));
      @update_option("cml_option_notice_page", intval($_POST['notice-page']));

      //Commenti
      @update_option('cml_option_comments', $_POST['comments']);
    }

    include dirname(__FILE__) . '/options.php';
  }

    /*
     * Visualizzo gli articoli presenti con eventuali traduzioni
     */
  function get_posts() {
    global $wpdb;

    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppa-tipsy');

    include dirname(__FILE__) . '/posts.php';
  }

  function form_translations() {
    global $wpdb;

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
    endif;

    require_once('translations.php');
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

      $langs = cml_get_languages(0);
      for($i = 0; $i < count($_POST['string']); $i++) :
	  //Per ogni lingua
	  foreach($langs as $lang) :
	    $lang = $lang->id;

	    //       if(empty($id)) {
	    $text = $_POST['lang_' . $lang][$i];
	    //$text = htmlentities($text);

	    $title = $_POST['string'][$i];
	    //$title = htmlentities($title);
	    $query = sprintf("INSERT INTO %s (cml_text, cml_lang_id, cml_translation, cml_type) VALUES (HEX('%s'), '%d', HEX('%s'), 'W')",
				CECEPPA_ML_TRANS, addslashes($title), $lang, addslashes($text));

	    $wpdb->query($query);
	  endforeach;
      endfor;
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
    include dirname(__FILE__) . '/titles.php';
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

    $langs = cml_get_languages(0);

    //Ho specificato in quale lingua sarà il nuovo post?
    if(array_key_exists("post-lang", $_GET) || array_key_exists("page-lang", $_GET)) {
      $post_lang = @intval($_GET['post-lang']) + @intval($_GET['page-lang']);
    }

    //Lingua dell'articolo
    echo "<h4>" . __('Language of this post', 'ceceppaml') . "</h4>";
    $lang_id = empty($post_lang) ? $this->get_language_id_by_post_id($tag->ID) : $post_lang;
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
    $linked_to = empty($linked_post) ? $linked_to : $linked_post;

    //Elenco degli articoli
    $args = array('numberposts' => -1, 'order' => 'ASC', 'orderby' => 'title', 'posts_per_page' => 9999,
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
	  $href = empty($link) ? (get_bloginfo("url") . "/wp-admin/post-new.php?link-to=$t_id&post-lang=$lang->id") : get_edit_post_link($link);
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

  function page_meta_box($tag) {
    global $wpdb;
    
    wp_enqueue_script('ceceppa-tipsy');
    wp_enqueue_script('ceceppaml-js');
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');
    wp_enqueue_style('ceceppa-tipsy');

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

  function redirect_browser() {
    /*
     * Se  non è abilitato il redirect del browser nella homepage
     * devo filtrare i contenuti in base alla lingua corrente
     */
    if($this->_redirect_browser == 'nothing' || isCrawler()) return;
    if(is_admin()) return;

    //Non posso utilizzare la funzione is_home, quindi controllo "manualmente"
    $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $home = home_url() . "/";

    //Sto nell'home?
    if($url != $home || isset($_GET['lang'])) {
      return;
    }

    //Recupero info sulla disponibilità della lingua del browser
    global $wpdb;

    $lang = $this->get_browser_lang();
    $lang = (empty($slug)) ? $this->_default_language_slug : $lang;
    //Redirect abilitato?
    if($this->_redirect_browser == 'auto') {
      //Pagina statica?
      $type = get_option("cml_option_redirect_type");
      $permalink = get_option("permalink_structure");
      if($type == 'slug' && !empty($permalink)) :
	//Devo azzerare le opzioni sennò vado in loop :(
	update_option('show_on_front', 'posts');
	update_option('page_parent', 0);

	$location = home_url() . "/$lang/";
      else:
	$location = home_url() . "/?lang=$lang";
      endif;
    }

    if(!empty($location)) {
      $this->_redirect_browser = 'nothing';

      wp_redirect($location, $status);
      exit;
    }
  }

  /**
   * Redirect category/post/page??
   */
  function show_notice($content) {
    global $wpdb;

    if(isCrawler()) return $content;
    
    //Recuper la lingua del browser
    $browser_lang_id = $this->get_browser_lang();

    if(empty($browser_lang_id)) return $content;

    //Recupero l'd della lingua dal database
    $lang_id = $this->_current_lang_id;

    if(is_page()) {
      if(get_option("cml_option_notice_page") != 1) return $content;

      $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);
    }
    
    if(is_single()) {
      if(get_option("cml_option_notice_post") != 1) return $content;

      $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);
    }

    $link = (!isset($link) || $link == get_the_ID() || $link == null) ? null : get_permalink($link);
    if(!empty($link)) :
      $notice = cml_get_notice($browser_lang_id);
      $before = stripcslashes(get_option('cml_option_notice_before', '<h5 class="cml-notice">'));
      $after = stripcslashes(get_option('cml_option_notice_after', '</h5>'));
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

    $browser_langs = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $lang = null;

    //Se la lingua del browser coincide con una di quella attuale della pagina, ignoro tutto
    foreach($browser_langs as $lang) :
      @list($code1, $code2) = explode(",", $lang);

      if($code1 == $this->_current_lang_locale) return $content;
      if($code2 == $this->_current_lang_locale) return $content;

      $locale1 = str_replace("-", "_", $code1);
      $locale2 = str_replace("-", "_", $code2);
      
      //Per ogni codice che trovo verifico se è gestito, appena ne trovo 1 mi fermo
      //Perché il mio browser mi restituisce sia it-IT, che en-EN, quindi mi devo fermare appena trovo un riscontro
      //Senno mi ritrovo sempre la lingua en-EN come $browser_langs;
      $lang = $this->get_language_id_by_locale(array($locale1, $locale2));
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

    $keys = array_keys($_POST['cat_name']);
    foreach($keys as $key) :
	update_option("cml_category_" . $term_id . "_lang_$key", $_POST['cat_name'][$key]);
    endforeach;
  }

  function delete_extra_category_fields($term_id) {
    $langs = cml_get_languages();
    foreach($langs as $lang) :
      delete_option("cml_category" . $term_id . "_lang_" . $lang->id);
    endforeach;

    delete_option("cml_category_$term_id");
    delete_option("cml_category_lang_$term_id");
  }

  /* 
   * Salvo il collegamento tra i post
   */
  function save_extra_post_fields($term_id) {
      global $wpdb, $pagenow;

      //Dalla 3.5.2 questa funzione viene richiamata 2 volte :O, la seconda volta $_POST però è vuoto :O
      if(empty($_POST)) return;

      $post_id = is_object($term_id) ? $term_id->ID : $term_id;

      @list($linked_lang, $linked_post) = split("@", $_POST['linked_post']);

      //Recupero dalla mia tabella l'id della lingua :)
      if(empty($_POST['post_lang']))
	$post_lang = 0;
      else
	$post_lang = intval($_POST['post_lang']);

      //Elimino i vecchi collegamenti presenti nel database
      $query = sprintf("SELECT id FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, intval($post_id));
      $id = intval($wpdb->get_var($query));
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

      if(!empty($query)) 
	$wpdb->query($query);

      if(!isset($post_lang))
	delete_option("cml_page_lang_$post_id");

      update_option("cml_page_$post_id", $linked_post);
      update_option("cml_page_lang_$post_id", $post_lang);
  }

  function delete_extra_post_fields($id) {
    global $wpdb;

    $sql = sprintf("DELETE FROM %s WHERE cml_post_id_1 = %d OR cml_post_id_2 = %d", CECEPPA_ML_POSTS, $id, $id);

    $wpdb->query($sql);
  }

  function add_lang_query_vars($vars) {
    $vars[] = 'lang';

    return $vars;
  }

  /*
   * Cambio il locale in base alla lingua selezionata
   */  
  function setlocale($locale) {
        global $wpdb;
        global $wp_rewrite;

        if(array_key_exists("lang", $_GET)) {
            $slug = $_GET['lang'];

            $locale = $wpdb->get_var(sprintf("SELECT cml_locale FROM %s WHERE cml_language_slug = '%s'",
                                                                             CECEPPA_ML_TABLE, $slug));
        }  else {
            $l = $this->_current_lang_locale;

            /*
             * posso modificare il locale solo prima che wp inizi a "stampare"
             * il contenuto della pagina (o così mi è sembrato di capire), putroppo
             * i metodi is_single(), is_page(), etc... non sono disponibili prima che wordpress
             * abbia stampato l'header della pagina.
             * A questo punto però non posso più modificare il locale :O
             * quindi ho trovato sta "toppa" che permette di recuperare l'id della pagina/post
             * e di conseguenza riesco a modificare il locale prima che qualsiasi output venga inviato
             * al browser :)
             */
            if(is_object($wp_rewrite)) {
                //Cerco di recuperare l'id della pagina/articolo dall'url
                $url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $id = url_to_postid($url);
                
                if($id > 0) {
                    $lid = $this->get_language_id_by_post_id($id);
                    $l = $this->get_language_locale_by_id($lid);
                } else {
                    $l = $this->get_language_locale_by_id($this->get_default_lang_id());
                }
            }

            $locale = empty($l) ? $locale : $l;
        }

    return $locale;
  }

  /**
   * Traduco il titolo dei widget :)
   */
  function translate_widget_title($title) {
    if(is_admin()) 
      return $title;

    return cml_translate($title, $this->_current_lang_id);
  }

  function admin_translate_widget_title($title) {
    $this->_titles[] = $title;
  }
  
  /**
   * Aggiorno la lingua utilizzata al momento
   */
  function update_current_lang() {
    global $wpdb;

    $lang = ""; //$_COOKIE['cml_current_lang'];
        
    //Serve a far sì che "il cambio menu" funzioni correttamente anche
    //con il permalink di default: ?p=#
    $post = get_post();
    if(is_page() || is_single()) :
      if(empty($post)) return;

      $the_id = get_the_ID();
    endif;

    $permalink = get_option("permalink_structure");
    if(!is_admin() && empty($permalink) && empty($the_id)) {
	$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$the_id  = url_to_postid($url);
    }

    if(is_single()) {
      $lang = $this->get_language_id_by_post_id($the_id);
    }

    if(is_page()) {
      $lang = $this->get_language_id_by_page_id($the_id);
    }

    if(is_home() || is_search() || array_key_exists("lang", $_GET)) {
      if(isset($_GET['lang'])) {
        $lang = $_GET['lang'];
      } else
        $lang = $this->get_default_language_slug();

      $lang = $this->get_language_id_by_slug($lang);
    }

    if(!empty($lang)) {
      //Aggiorno le info sulla lingua corrente
      $this->_current_lang = $this->get_language_slug_by_id($lang);
      $this->_current_lang_id = $lang;

      //Aggiorno il menu del tema :)
      if(!isset($this->_menu_changed)) :
	$this->change_menu();
      endif;

      if($this->_filter_search) {
	  //For Fix Notice
	  //add_action('wp_enqueue_scripts', array(&$this, 'enqueue_script_search')); //Non funziona :(
	  $this->enqueue_script_search();

	  $array = array('lang' => $this->_current_lang,
					'form_class' => $this->_filte_form_class);
	  wp_localize_script('ceceppa-search', 'cml_object', $array);

	  //Evito che esegua più di una volta questo if
	  //$this->_filter_search = false;
      }

      //Recupero il campo "Locale Wordpress"
      $query = sprintf("SELECT cml_locale FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $lang);
      $this->_current_lang_locale = $wpdb->get_var($query);

    } else {
            //Se per qualche ragione non riesco a determinare la lingua corrente, prendo quella predefinita
            $this->_current_lang_id = $this->_default_language_id;
            $this->_current_lang = $this->_default_language_slug;
    }
  }


  /*
   * Cambio il menu predefinito del tema, in accordo con quello della lingua corrente
   */
  function change_menu() {
    $mods = get_theme_mods();
    $locations = get_theme_mod('nav_menu_locations');

    //Se non inizia per cml_ allora sarà quella definita dal tema :)
    if(is_array($locations)) :
	$keys = array_keys($locations);
	foreach($keys as $key) :
	  if(!empty($key) && substr($key, 0, 4) != "cml_") {
	    $menu = $this->_current_lang;

	    if(!empty($locations["cml_menu_$menu"])) {
		$locations[$key] = $locations["cml_menu_$menu"];

		$this->_no_translate_menu_item = true;
		set_theme_mod('nav_menu_locations', $locations);
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
   * @return gli id dei post associati a quelle categorie
   */
  function get_language_posts($lang = null) {
    if(empty($lang)) $lang = $this->_current_lang_id;

    $posts_id = $this->get_language_posts_id($lang);

    return !empty($posts_id) ? array_unique($posts_id) : array();
  }

  function get_language_posts_id($lang) {
    global $wpdb;

    /**
     * Al fine di velocizzare il processo evito di eseguire la stessa query
     * se la lingua non è cambiata
     */
    if(!isset($this->_language_lang_id) || $lang != $this->_language_lang_id) :
      $query = sprintf("SELECT *, case when cml_post_lang_1 > 0 then cml_post_lang_1 else %d end as cml_fake_id_1, case when cml_post_lang_2 > 0 then cml_post_lang_2 else %d end as cml_fake_id_2 FROM %s", $lang, $lang, CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);

      $posts = array();
      foreach($results as $result) :
	  if($result->cml_fake_id_1 != $result->cml_fake_id_2) :
	    if($result->cml_fake_id_1 == $lang) $posts[] = $result->cml_post_id_1;
	    if($result->cml_fake_id_2 == $lang) $posts[] = $result->cml_post_id_2;
	  endif;
      endforeach;

      //Se ad un posto non ho assegnato nessuna lingua rischio di "escluderlo", quini aggiungo all'elenco tutti i post figli di nessuno :)
      $query = sprintf("SELECT ID from $wpdb->posts WHERE id NOT IN (SELECT cml_post_id_1 FROM %s)", CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);
      foreach($results as $result) :
	$posts[] = $result->ID;
      endforeach;

      /*
       * Rimuovo dall'elenco tutti i post tutti i post a cui è associata una traduzione.
       * Se installo il plugin su un sito esistente non posso obbligare l'autore a impostare la lingua
       * a tutti gli articoli già esistenti.
       * Però devo fare anche in modo che articoli senza lingua non compaiano se in queste lingue esistono delle traduzioni
       */
      $query = sprintf("SELECT ID from $wpdb->posts WHERE id IN (SELECT cml_post_id_2 FROM %s)", CECEPPA_ML_POSTS);
      $results = $wpdb->get_results($query);
      foreach($results as $result) :
	if(in_array($result->ID, $posts)) :
	  $key = array_search($result->ID, $posts);
	  unset($posts[$key]);
	endif;
      endforeach;

      $this->_language_posts_id = $posts;
      $this->_language_lang_id = $lang;
    endif;

    return $this->_language_posts_id;
  }
  
  /**
    * Recupero l'elenco di tutte le pagine collegate alla lingua specificata
    *
    * @param lang_id - lingua da ricercare
    */
  function get_language_pages($lang_id) {
    global $wpdb;

    $ids = $this->get_language_posts($lang_id);

    return empty($ids) ? array(0) : array_unique($ids);
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
    $this->update_current_lang();

    return $this->_current_lang;
  }
  
  function get_current_lang_id() {
    $this->update_current_lang();

    return $this->_current_lang_id;
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
    
    /*
     * Modifico l'id della query in modo che punti all'articolo tradotto
     */
    function get_static_page($query) {
        if(isset($this->_static_page) && $this->_static_page) return;
    
        //Recupero l'id della lingua
        $slug = $_GET['lang'];    
        $lang_id = $this->get_language_id_by_slug($slug);

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
        $s = get_option("permalink_structure");
        if(empty($s) || strrpos($s, "%category%") === false) return $permalink;

        if($lang_id == null) $lang_id = $this->get_language_id_by_post_id($post->ID);
        if($lang_id <= 0) return $permalink;

        //Devo aggiungere la lingua al link?
        if(get_option("cml_add_slug_to_link", true))
                $slug = "/" . strtolower($this->get_language_slug_by_id($lang_id));

        $homeUrl = home_url();
        //Devo utilizzare url del tipo en.example.com, it.example.com
        if(get_option("cml_modification_mode") == 3 && !empty($slug)) :
            $homeUrl = str_replace("http://", "http://$slug.", $homeUrl);
            $slug = "";
        endif;

        $plinks = split("/", str_replace(home_url(), "", $permalink));
        if($lang_id != $this->_default_language_id) :
	  array_pop($plinks);
	  $title = array_pop($plinks);

	  foreach($plinks as $plink) :
	    //Cerco la traduzione della categoria nella lingua del post :)
	    $_cat = get_category_by_slug($plink);
	    if(is_object($_cat)) :
	      $id = $_cat->term_id;

	      if(!empty($plink)) :
		$cat = strtolower(get_option("cml_category_" . $id . "_lang_" . $lang_id, $plink));
		$url = str_replace(" ", "-", $cat);
		$url = urlencode($url);
		$cats[] = $url;
	      endif;
	    endif;
	  endforeach;

	  //Ricreo il permalink con le categorie tradotte... :)
	  if(!empty($cats))
	    return $homeUrl . "$slug/" . join("/", $cats) . "/$title/";
        endif;

        //Aggiungo il suffisso /%lang%/
        return $homeUrl . $slug . join("/", $plinks);
    }
    
    function translate_menu_item($item) {
	//Se l'utente ha scelto un menu differente per la lingua corrente
	//non devo applicare nessun tipo di filtro agli elementi del menu, esco :)
	if($this->_no_translate_menu_item == true) :
	  remove_filter('wp_setup_nav_menu_item', array(&$this, 'translate_menu_item'));
	  return $item;
	endif;

        if($this->_current_lang_id != $this->_default_language_id) :
	    switch($item->object) : 
	    case 'page':
	      $page_id = cml_get_linked_post($this->_default_language_id, null, $item->object_id, $this->_current_lang_id);

	      if(!empty($page_id)) :
		//Su un sito mi è capitato che get_the_title() restituisse una stringa vuota, nonstante l'id della pagina fosse corretto
		$title = get_the_title($page_id);
		if(empty($title)) :
		  $page = get_page($page_id);
		  $title = $page->post_title;
		endif;

	        $item->ID = $page_id;
		$item->title = $title;
		$item->object_id = $page_id;
		$item->url = get_permalink($page_id);
	      endif;

	      break;
	    case 'category':
	      $id = get_cat_ID($item->title);
	      if(!empty($id)) :
		  $lang = $this->_current_lang_id;

		  $item->title = get_option("cml_category_" . $id . "_lang_" . $lang, $item->title);
	      endif;
	      break;
	    case 'custom':
	      $item->title = cml_translate($item->title, $this->_current_lang_id);
	      break;
	    default:
	      return $item;
	    endswitch;
        endif;

        return $item;
    }
    
    function translate_term_name($name) {
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
    
    function translate_term_link($link, $lang = null) {
      if(!is_category()) {
	//Lingua dell'articolo
	if(!is_admin()) {
	  if(is_page())
	    $lang_id = $this->get_language_id_by_page_id(get_the_ID());
	  else
	    $lang_id = $this->get_language_id_by_post_id(get_the_ID());
	} else {
	  $lang_id = $this->_current_lang_id;
	}
      } else {
	if(!empty($lang))
	  $lang_id = $lang;
	else
	  $lang_id = $this->_current_lang_id;
      }

      if(!empty($lang_id)) :
	$slug = strtolower($this->get_language_slug_by_id($lang_id));
      else :
	$slug = $this->_default_language_slug;
      endif;

      $link = add_query_arg(array("lang" => $slug), $link);
      return $link;
    }
    
    function translate_object_terms($obj) {
      if(empty($obj)) return $obj;

      $nobj = $obj;
      if(is_object($obj)) :
	if($obj->taxonomy == 'category' || $obj->taxonomy == 'post_tag') {
	  $term_id = $obj->term_id;
	  $post_id = (isset($obj->object_id)) ? $obj->object_id : -1;

	  if($post_id > 1 || $this->_current_lang_id != $this->_default_language_id) :
	    $lang = ($post_id > 1) ? $this->get_language_id_by_post_id($post_id) : $this->_current_lang_id;
	    if($lang == $this->_default_language_id) return $obj;	//La lingua del post è la stessa di quella dell'articolo?

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

    function show_admin_notice() {
?>
	<div class="updated">
	<?php _e('If you like this plugin, you can:', 'ceceppaml') ?>
	  <ul style="list-style: circle;padding-left: 30px">
	    <li>
	      <a href="http://wordpress.org/support/view/plugin-reviews/ceceppa-multilingua" target="_blank">
		<?php _e('Add your own review here', 'ceceppaml') ?></a>
	    </li>
	    <li>
		<?php _e('Promote the plugin on your blog', 'ceceppaml') ?>
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
	  <?php       
	      $link = add_query_arg('cml-hide-notice', 1);
	  ?>
	  <div style="text-align: right;width:100%">
	    <a href="<?php echo $link ?>"><?php _e('Dismiss') ?></a>
	  </div>
	</div>

<?php
    }
    
    function admin_notices() {
      global $pagenow;
      
      wp_enqueue_script('ceceppaml-js');
      wp_enqueue_script('ceceppa-tipsy');

      //Css
      wp_enqueue_style('ceceppaml-style');
      wp_enqueue_style('ceceppaml-dd');
      wp_enqueue_style('ceceppa-tipsy');

      if(get_option("cml_need_update_posts", false)) :
	echo "<div class=\"updated\">\n";
	echo "<p>\n";

	  _e('It is necessary assign default language to existing posts. ', 'ceceppaml'); echo "<br />";
	  echo "<a href='edit.php?&cml_update_posts=1'>" . sprintf(__('<strong>If your default language is "%s"</strong> click here for automatically assign this language to all existing posts.', 'ceceppaml'), $this->_default_language) . "</a>"; 
	    echo "<br />";
	  _e('Otherwhise add correct default language first', 'ceceppaml'); echo "<br />";
	  echo "<br />";
	  echo '<a href="?&cml_hide_update_posts=1">' . __('Hide this message.', 'ceceppaml') . '</a>';
	echo "</p>\n";
	echo "</div>";
      endif;

      if($pagenow == 'nav-menus.php') :
?>
	<div class="updated">
	    <p>
	      Ceceppa Multilingua: <strong><?php _e('Tip', 'ceceppaml') ?></strong><br /><br />
	      <?php _e('All items will be automatically translated when user switch between languages.', 'ceceppaml') ?><br />
	      <?php _e('If you add an custom item ("Links"), you must add translation of navigation label in "Ceceppa Multilingua" -> "My translations"', 'ceceppaml') ?>
	      <br /><br />
	      <?php _e('If you need to customize "Navigation Label" or if you want to have different items for each languages, create a menu for each language', 'ceceppaml') ?>
	      <?php _e('and assigns it to the corresponding menu, otherwise assign it only to the primary menu', 'ceceppaml') ?>
	      <br /><br />
	      <strong><?php _e('In same cases is displayed a blank menu for non default language. If that happens you must create different menu for each language. :(', 'ceceppaml') ?></strong>
	    </p>
	</div>
<?php
      endif;
      
      if($pagenow == 'options-reading.php') :
?>
	<div class="updated">
	    <p>
	      Ceceppa Multilingua: <strong><?php _e('Tip', 'ceceppaml') ?></strong><br /><br />
	      <?php _e('You can to use a static page as homepage for your site/blog.', 'ceceppaml') ?><br />
	      <?php _e('Select a desided page for front and post page and translate them in all languages.', 'ceceppaml') ?><br />
	    </p>
	</div>
<?php
      endif;

    }
    
    function admin_mo_notice() {
?>
      <div class="updated">
	<p>
	  <?php _e('Locale file for %s not found.') ?>
	</p>
      </div>
<?php
    }

    function successfully_installed() {
?>
      <div class="updated">
	<p>
	  <?php _e('Plugin successfully installed', 'ceceppaml') ?><br /><br />
	  <label>
	    <?php _e('Language detected:', 'ceceppaml') ?>
	    <strong>
		<img src="<?php echo cml_get_flag_by_lang_id($this->_installed_language_id) ?>" />
		<?php echo $this->_installed_language ?>
	    </strong>
	  </label>
	  <br /><br />
	  <a href="<?php echo home_url('wp-admin') ?>/?page=ceceppaml-language-page">
	    <?php _e('Click here for advanced settings or for add another language', 'ceceppaml') ?>
	  </a>
	  <br />
	</p>
      </div>
<?php
    }
    
  function add_bar_menu() {
    global $wp_admin_bar;

    if(is_admin()) return;

    $langs = cml_get_languages(0);
    foreach($langs as $lang) :
      $language = $lang->cml_language_slug;
      $link = add_query_arg('lang', $language);

      $title = '<img src="' . cml_get_flag($lang->cml_flag) . '">&nbsp;' . $lang->cml_language;
      $wp_admin_bar->add_menu( array( 'id' => 'cml_lang' . $lang->id, 'title' => $title, 'href' => $link) );
    endforeach;
  }
  
  function hide_category_translations($wp_query) {
    if(!is_category()) return;
    
    return $this->hide_translations($wp_query);
  }
  
  //Imposto in automatico la lingua in tutti i post
  function update_all_posts_language() {
    global $wpdb;

    $posts = get_posts(array('order' => 'ASC',
      'orderby' => 'title',
      'numberposts' => -1,
      'status' => 'publish, draft'));

    foreach($posts as $post) :
      update_option("cml_page_lang_" . $post->ID, $this->_default_language_id);
      $wpdb->insert(CECEPPA_ML_POSTS, 
		    array("cml_post_lang_1" => $this->_default_language_id,
			  "cml_post_id_1" => $post->ID, 
			  "cml_post_lang_2" => 0,
			  "cml_post_id_2" => 0),
		    array('%d', '%d', '%d', '%d'));
    endforeach;

    $posts = get_pages(array('order' => 'ASC',
      'orderby' => 'title',
      'numberposts' => -1,
      'status' => 'publish, draft, private'));

    foreach($posts as $post) :
      update_option("cml_page_lang_" . $post->ID, $this->_default_language_id);

      $wpdb->insert(CECEPPA_ML_POSTS, 
	      array("cml_post_lang_1" => $this->_default_language_id,
		    "cml_post_id_1" => $post->ID, 
		    "cml_post_lang_2" => 0,
		    "cml_post_id_2" => 0),
	      array('%d', '%d', '%d', '%d'));
    endforeach;
    
    update_option("cml_need_update_posts", false);
  }
  
  function shortcode_page() {
    require_once(CECEPPA_PLUGIN_PATH . "shortcode_page.php");
  }
}

function removesmartquotes($content) {
     $content = str_replace('&#8220;', '&quot;', $content);
     $content = str_replace('&#8221;', '&quot;', $content);
     $content = str_replace('&#8216;', '&#39;', $content);
     $content = str_replace('&#8217;', '&#39;', $content);
    
     return $content;
}

$wpCeceppaML = new CeceppaML();
?>