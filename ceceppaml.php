<?php
/*
Plugin Name: Ceceppa Multilingua
Plugin URI: http://www.ceceppa.eu/it/interessi/progetti/wp-progetti/ceceppa-multilingua-per-wordpress/
Description: Come rendere il tuo sito wordpress multilingua :).How make your wordpress site multilanguage.
Version: 0.8.3
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

define('CECEPPA_DB_VERSION', 6);

define('CECEPPA_ML_TABLE', $wpdb->base_prefix . 'ceceppa_ml');
define('CECEPPA_ML_CATS', $wpdb->base_prefix . 'ceceppa_ml_cats');
define('CECEPPA_ML_POSTS', $wpdb->base_prefix . 'ceceppa_ml_posts');
define('CECEPPA_ML_PAGES', $wpdb->base_prefix . 'ceceppa_ml_posts');

//Memorizzo gli id degli articoli/pagine al fine di poter "filtrare" i post per lingua
define('CECEPPA_ML_RELATIONS', $wpdb->base_prefix . 'ceceppa_ml_relations');
//define('CECEPPA_ML_PAGES', $wpdb->base_prefix . 'ceceppa_ml_pages');

//Tabella delle traduzioni. Al momento mi appoggio a una tabella per le traduzioni in quanto non ho trovato nessun modo
//per generare un file .po direttamente da php
define('CECEPPA_ML_TRANS', $wpdb->base_prefix . 'ceceppa_ml_trans');     

require_once(ABSPATH . 'wp-content/plugins/ceceppa-multilingua/functions.php');
require_once(ABSPATH . 'wp-content/plugins/ceceppa-multilingua/utils.php');
require_once(ABSPATH . 'wp-content/plugins/ceceppa-multilingua/shortcode.php');
require_once(ABSPATH . 'wp-content/plugins/ceceppa-multilingua/widgets.php');

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

  public function __construct() {
    global $wpdb;

    /*
    * Se utilizzo add_action per caricare gli script quando richiesto,
    * non mi vengono "caricati" nelle verie funzioni a disposizione :(
    */
    $this->register_scripts();
    //add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));

    //Creo le tabelle al primo avvio
    if(get_option('cml_db_version') != CECEPPA_DB_VERSION) $this->create_table();

    /* 
     * Recupero le impostazioni della lingua di default 
     */
    $this->_default_language = $wpdb->get_var("SELECT cml_language FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
    $this->_default_language_id = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
    $this->_default_language_slug = $wpdb->get_var("SELECT cml_language_slug FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");
    $this->_default_category = $wpdb->get_var("SELECT cml_category_id FROM " . CECEPPA_ML_TABLE . " WHERE cml_default = 1");

    /*
     * Opzioni disponibili per l'amministratore:
     *
     *   *) Configurazione lingue
     *   *) impostazioni plugin
     *   *) gestione campi extra nelle categorie
     */
    if(is_admin()) {
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

      /*
	* Aggiungo il box di collegamento nei post e nelle pagine 
	*/
      add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
      add_action('edit_post', array(&$this, 'save_extra_post_fields'));
      add_action('delete_post', array(&$this, 'delete_extra_post_fields'));
      add_action('edit_page_form', array(&$this, 'save_extra_page_fields'));
      add_action('delete_page', array(&$this, 'delete_extra_post_fields'));

      //Aggiungo le banidere all'elenco dei post
      add_action('manage_pages_custom_column', array(&$this, 'add_flag_column'), 10, 2);
      add_filter('manage_pages_columns' , array(&$this, 'add_flags_columns'));
      add_action('manage_posts_custom_column', array(&$this, 'add_flag_column'), 10, 2);
      add_filter('manage_posts_columns' , array(&$this, 'add_flags_columns'));

      //Filtri
      add_filter('parse_query', array(&$this, 'filter_all_posts_query'));
      add_action( 'restrict_manage_posts', array(&$this, 'filter_all_posts_page'));
      
      /*
      * Traduco i titoli dei Widget
      */
      add_filter('widget_title', array(&$this, 'admin_translate_widget_title'));
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
      */
      if(get_option('cml_option_flags_on_post') ||
	  get_option('cml_option_flags_on_page') ||
	  get_option('cml_option_flags_on_cats')) :
	
	if(get_option('cml_option_flags_on_pos') == "bottom") {
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
    }
    
    //Update current language
    add_action( 'init', array(&$this, 'update_current_lang'));

    /*
    * Nella pagina "menu", aggiungo una lista per ogni lingua
    */
    add_action('init', array(&$this, 'add_menus'));
    add_action('wp_footer', array(&$this, 'restore_default_menu'));
    
    //Translate post_link
    add_filter('post_link', array(&$this, 'translate_post_link'), 0, 3);
    add_filter('term_link', array(&$this, 'translate_term_link'), 0);
    add_filter('term_name', array(&$this, 'translate_term_name'), 0, 1);

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
    
    load_plugin_textdomain('ceceppaml', false, dirname(plugin_basename( __FILE__ )) . '/po/');

    $results = $wpdb->get_results("SELECT * FROM " . CECEPPA_ML_TABLE . " ORDER BY cml_language"); //WHERE cml_default = 1 
    foreach($results as $result) :
        register_nav_menus(array("cml_menu_$result->cml_language_slug" => $result->cml_language));
    endforeach;
        
    $locations = get_theme_mod('nav_menu_locations');

    //Se non inizia per cml_ allora sarà quella definita dal tema :)
    if(is_array($locations)) :
      $keys = array_keys($locations);
      foreach($keys as $key) :
	if(substr($key, 0, 4) != "cml_") :
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
      
      //From qTranslate
      $link = (strpos($link, "wp-admin/") === false) ? preg_replace('#[^?&]*/#i', '', $link) : preg_replace('#[^?&]*wp-admin/#i', '', $link);
      if(strpos($link, "?")===0||strpos($link, "index.php?")===0) {
          if(current_user_can('manage_options')) 
              $link = 'options-general.php?page=ceceppaml-language-page&godashboard=1&lang='.$language; 
          else
              $link = 'edit.php?lang='.$language;
      }

      add_menu_page($result->cml_language, $result->cml_language, 'read', $link, null, //array(&$this, 'switch_language'), 
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
    add_menu_page('Ceceppa ML Options', __('Ceceppa Multilingua', 'ceceppaml'), 'administrator', 'ceceppaml-language-page', array(&$this, 'form_languages'), WP_PLUGIN_URL . '/ceceppa-multilingua/images/logo.png');
    //add_submenu_page('ceceppaml-language-page', __('Elenco articoli', 'ceceppaml'), __('Elenco articoli', 'ceceppaml'), 'manage_options', 'ceceppaml-posts-page', array(&$this, 'get_posts'));
    add_submenu_page('ceceppaml-language-page', __('Widget titles', 'ceceppaml'), __('Widget titles', 'ceceppaml'), 'manage_options', 'ceceppaml-translations-page', array(&$this, 'form_translations'));
    add_submenu_page('ceceppaml-language-page', __('Settings', 'ceceppaml'), __('Settings', 'ceceppaml'), 'manage_options', 'ceceppaml-options-page', array(&$this, 'form_options'));
  }

  /**
    * Aggiungo le bandiere vicino al titolo del post nella pagina "Tutti gli articoli"
    */
  function add_flags_columns($columns) {
    $langs = cml_get_languages();

    //Non sono riuscito a trovare un altro modo per ridimensionare la larghezza del th...
    wp_enqueue_style('ceceppaml-style-all-posts', WP_PLUGIN_URL . '/ceceppa-multilingua/css/all_posts.php?langs=' . count($langs));

    foreach($langs as $lang) :
      $img .= "<img src=\"" . cml_get_flag_by_lang_id($lang->id, "small") . "\" width=\"20\" />";
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

        $langs = cml_get_languages();
        foreach($langs as $lang) :
            //Recupero la lingua del post/pagina
            $xid = ($post_type == 'post') ? $this->get_language_id_by_post_id($id) :
                                            $this->get_language_id_by_page_id($id);

            $link = cml_get_linked_post($xid, null, $id, $lang->id);
            if(!empty($link)) {
                echo '<a href="' . get_edit_post_link($link) . '">';
                echo '    <img src="' . cml_get_flag_by_lang_id($lang->id, "small") . '" title="' . __('Edit post', 'ceceppaml') . '"/>';
                echo '</a>';
            } else {
                global $wpdb;
    
                //Cerco di recuperare l'id     dell'articolo collegato alla lingua di default
                $query = sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, $id);
                $xid = $wpdb->get_var($query);
    
                $id = (empty($xid)) ? $id : $xid;
                echo '<a href="' . get_bloginfo("url") . '/wp-admin/post-new.php?post_type=' . $post_type . '&link-to=' . $id . '&page-lang=' . $lang->id . '">';
                echo '    <img class="add" src="' . WP_PLUGIN_URL . '/ceceppa-multilingua/images/add.png" title="' . __('Translate', 'ceceppaml') . '" />';
                echo '</a>';
            }
        endforeach;
    }

    /*
     * Aggiungo le bandiere sotto al titolo del post
     */
    function add_flags_on_top($title, $id = -1) {
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
            return $title . cml_show_availables_langs(array("class" => "cml_flags_on_top"));
        } else {
            return $title;
        }
    }

    function add_flags_on_bottom($title) {
        if(is_single() && !get_option('cml_option_flags_on_post')) return $title;
        if(is_page() && !get_option('cml_option_flags_on_page')) return $title;
        if(is_category() && !get_option('cml_option_flags_on_cats')) return $title;

        return $title . cml_show_availables_langs(array("class" => "cml_flags_on_top"));
    }

  /**
   * NUOVA CATEGORIA
   * Campi necessari per collegare la nuova categoria con quella della lingua di default
   */
  function category_add_form_fields($tag) {
    $t_id = $tag->term_id;
    $linked_cat = get_option("cml_category_$t_id");
        
        wp_enqueue_script('ceceppaml-cat');
?>
        <div class="form-field">
        <?php
            $langs = cml_get_languages();
            
            foreach($langs as $lang) :
                if(!$lang->cml_default) : ?>
                    <label for="cat_name[<?php echo $lang->id ?>]">
                        <img src="<?php echo cml_get_flag($lang->cml_flag) ?>" />
                        <?php echo $lang->cml_language ?>
                    </label>
                    <input type="text" name="cat_name[<?php echo $lang->id ?>]" id="cat_name[<?php echo $lang->id ?>]" size="40" />
                <?php 
                endif;
            endforeach;
        ?>
        </div>
    <div class="form-field">
      <label for="linked_lang"><?php _e('Language of this category', 'ceceppaml'); ?></label>
      <?php cml_dropdown_langs("cat_lang", null); ?>
    </div>
    <div class="form-field">
      <label for="linked_cat">
                <?php _e('Link to the category', 'ceceppaml'); ?><br />
                <em><?php _e('Do not use it. It exists only for backward compatibility.') ?></em>
            </label>
      <?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'linked_cat', 'show_option_none' => ' ', 'hierarchical' => true)); ?>
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
      $langs = cml_get_languages();
      
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
	    <input type="text" name="cat_name[<?php echo $lang->id ?>]" id="cat_name_<?php echo $lang->id ?>" size="40" value="<?php echo get_option("cml_category_" . $t_id . "_lang_$id", "k") ?>"/>
	</td>
    </tr>
    <?php
	    endif;
	endforeach;
    ?>
    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="cat_lang">
	  <?php _e('Language of this category'); ?>
	</label>
      </th>
      <td>
        <?php cml_dropdown_langs("cat_lang", $cat_lang); ?><br />
        <span class="description"><?php _e('Choose the language of current category only if it will exists in this language', 'ceceppaml'); ?></span>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="linked_cat">
	  <?php _e('Link to the category'); ?>
	  <em><?php _e('Do not use it. It exists only for backward compatibility.') ?></em>
	</label>
      </th>
      <td>
        <?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'linked_cat', 'show_option_none' => ' ', 'hierarchical' => true, 'selected' => $linked_cat)); ?>
        <br />
        <span class="description"><?php _e('Select the category that you have to connect with to the one you are modifying', 'ceceppaml'); ?></span>
      </td>
    </tr>
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
        cml_category_name TEXT,
        cml_category_id INTEGER,
        cml_category_slug TEXT,
        cml_locale TEXT,
        cml_page_id INT,
        cml_page_slug TEXT,
            cml_enabled INT,
        PRIMARY KEY  id (id)
        );";

        dbDelta($sql);
      }

        $query = "UPDATE " . CECEPPA_ML_TABLE . " SET cml_enabled = 1 WHERE cml_enabled IS NULL";
        $wpdb->query($query);
        //$wpdb->update(CECEPPA_ML_TABLE, array("cml_enabled" => 1), array("IS NULL"));

        if($first_time) :
        /* Per comodità  creo nel database un record con la lingua corrente */
        $locale = get_locale();
        $language = __("Default language");

            require_once(ABSPATH . "/wp-content/plugins/ceceppa-multilingua/locales_codes.php");
            $keys = array_keys($_langs);
            foreach($keys as $key) {
                if($_langs[$key] == $locale) {
                    $language = $key;
                }
            }

        /* Creo nel database la riga per la lingua corrente di wordpress */
        $insert = sprintf("INSERT INTO %s (cml_default, cml_language, cml_language_slug, cml_locale, cml_enabled, cml_flag) VALUES('%d', '%s', '%s', '%s', '%d', '%s')",
                  CECEPPA_ML_TABLE, "1", $language, strtolower(substr($language, 0, 2)), $locale, 1, $locale);

        $wpdb->query($insert);
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
        PRIMARY KEY  id (id))";

      dbDelta($query);
//    }

    /**
     * Creo la tabella che conterrà  gli abbinamenti tra le categorie.
     *
     * Anche in questo caso per rendere più flessibile il plugin utilizzo una tabella a 4 colonne:
     *
     *  cml_cat_lang_1 - id della lingua 1
     *  cml_cat_id_1      - id della categoria della lingua 1
     *  cml_cat_lang_2 - id della lingua 2 (lingua "padre")
     *  cml_cat_id_2      - id della categoria della lingua 2 (categoria "padre")
     */
    $table_name = CECEPPA_ML_CATS;
//    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $query = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        cml_cat_lang_1 INT(11),
        cml_cat_id_1 INT(11),
        cml_cat_lang_2 INT(11),
        cml_cat_id_2 INT(11),
        PRIMARY KEY  id (id))";

      dbDelta($query);
//    }

    $table_name = CECEPPA_ML_RELATIONS;
      $query = "CREATE TABLE $table_name (
        cml_type VARCHAR(10),
        cml_id INT(11),
        cml_lang_id INT(11),
        PRIMARY KEY id (cml_id))";

      dbDelta($query);
        /*
         * Importo tutte le categorie "tradotte"?
         */
        
    /**
     * Per le traduzioni momentaneamente mi appoggio ad un database.
     * Appena trovo il modo di gestire tutto da php e generare file po al volo rimuovo la tabella :)
     */
    $table_name = CECEPPA_ML_TRANS;
    //if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
      $query = "CREATE TABLE  $table_name (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        cml_text TEXT NOT NULL ,
        `cml_lang_id` INT NOT NULL ,
        `cml_translation` TEXT)";

      dbDelta($query);
    //}

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
        $d = isset($_GET['cml_language']) ? $_GET['cml_language'] : "";

        //All languages
        cml_dropdown_langs("cml_language", $d, false, true, __('Show all languages', 'ceceppaml'), -1);

        $c = checked(array_key_exists('hide-tr', $_GET), true, false);
        echo "<label><input name=\"hide-tr\" type=\"checkbox\" value=\"1\" $c />" . __('Hide translations', 'ceceppaml') . "</label>&nbsp;&nbsp;&nbsp;";
    }

    /**
     * Filtro la query de "Tutti gli articoli"
     *
     */
    function filter_all_posts_query($query) {
        global $pagenow;
        
        if (!array_key_exists('post_type', $_GET))
            $post_type = 'post';
        else
            $post_type = $_GET['post_type'];

        $id = array_key_exists('cml_language', $_GET) ? intval($_GET['cml_language']) : 0;
        if(is_admin() && $pagenow == "edit.php") :
	  if($id > 0) :
	    if($_GET['post_type'] == "post") {
	      $query->query_vars['post__in'] = $this->get_language_posts($id);
	    } else {
	      $query->query_vars['post__in'] = $this->get_language_pages($id);
	    }
	  endif;
	  
	  if(array_key_exists("hide-tr", $_GET)) :
	    $this->hide_translations($query);
	  endif;
	endif;
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

      $cat = $c->cml_category_id;

      if(!empty($lang)) {
	$this->_current_lang = $lang;
	$this->_current_lang_id = $c->id; //$this->get_language_id_by_category($cat);
      }
    endif;

    //Se non è stata specificata nessuna categoria "padre" per la lingua, recupero tutte quelle associate a questa lingua :)
    //Questo accade quando l'utente sceglie una struttura non ad albero
    /*
    if($cat == 0) {
      $cat = implode(",", $this->get_language_categories());
      //Se $cat è vuoto, vuol dire che alla lingua non è stata assegnata alcuna categoria.. e ciò non è buono :)
    }
    */

    //Recupero tutti i post associati alla lingua corrente
    $posts = $this->get_language_posts($this->_current_lang_id);

    /*
    if(!empty($cat))
      set_query_var('cat', $cat);
    */

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
            $query = sprintf("SELECT * FROM %s WHERE cml_post_lang_1 = %d OR cml_post_lang_2 = %d",
                                             CECEPPA_ML_POSTS, $this->_current_lang_id, $this->_current_lang_id);
            
            $results = $wpdb->get_results($query);
            foreach($results as $result) :
                if($result->cml_post_id_1 > 0 && $result->cml_post_lang_1 == $this->_current_lang_id) $posts[] = $result->cml_post_id_2;
                if($result->cml_post_id_2 > 0 && $result->cml_post_lang_2 == $this->_current_lang_id) $posts[] = $result->cml_post_id_1;
            endforeach;

            $this->_exclude_posts = !empty($posts) ? $posts : array();
        endif;

        if($wp_query != null && is_object($wp_query)) :
	  $wp_query->query_vars['post__not_in'] = $this->_exclude_posts;
	  
	  return $wp_query;
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
  
  function get_language_id_by_category($cat_id) {
    global $wpdb;

    $val = $wpdb->get_var(sprintf("SELECT id FROM %s WHERE cml_category_id = %d", CECEPPA_ML_TABLE, $cat_id));
    if(empty($val)) {
      $val = get_option("cml_category_lang_$cat_id");
    }
    
    return $val;
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
    $val = get_option("cml_post_lang_$post_id", 0);
    if($val > 0) return $val;

    $cats = get_the_category($post_id);
    if(empty($cats)) return $this->_default_language_id;

    $cats_id = $cats[0]->term_id;
    $val = get_option("cml_category_lang_$cats_id");

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

        //Se è stata scelta una struttura ad albero, recupero il padre e dal padre la lingua correntta
    $parent_id = $this->get_page_parent_by_id($page_id);
    $r = $wpdb->get_var(sprintf("SELECT id FROM %s WHERE cml_page_id = %d", CECEPPA_ML_TABLE, $parent_id));
        
        /*
         * Non è stata scelta una struttra ad albero...
         * Recupero dalla tabella CECEPPA_ML_POSTS la lingua della pagina, se specificata
         */
        if(empty($r)) {
            $query = sprintf("SELECT cml_post_lang_1 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_PAGES, $page_id);
            
            $r = $wpdb->get_var($query);
        }
        
        //Restituisco la lingua di default
        if(empty($r)) $r = $this->_default_language_id;

        return $r;
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
          $query = "";
          $id = $_POST['id'][$i];

          //     if($form == "languages") {
          $category_id = intval($_POST['category'][$i]);
          $category = get_cat_name($category_id);
          $category_slug = get_category($category_id)->slug;

          $page_id = $_POST['page'][$i];
          $page_slug = get_page($page_id)->post_name;

          list($lang, $lang_slug) = explode("@", $_POST['flags'][$i]);
          
          //Se è vuoto, è una "nuova lingua"
          if(empty($id)) {
            if(!empty($_POST['language'][$i])) {
              $query = sprintf("INSERT INTO %s (cml_default, cml_flag, cml_language_slug, cml_language, cml_category_id, cml_category_name, cml_category_slug, cml_locale, cml_notice_post, cml_notice_page, cml_notice_category, cml_page_id, cml_page_slug, cml_enabled) VALUES('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d')",
              CECEPPA_ML_TABLE,
              intval($_POST['default'][$i]),
              $lang,
              $_POST['language_slug'][$i],
              $_POST['language'][$i],
              $_POST['category'][$i],
              $category, $category_slug,
              $_POST['locale'][$i],
              utf8_encode($_POST['notice_post'][$i]),
              utf8_encode($_POST['notice_page'][$i]),
              utf8_encode($_POST['notice_category'][$i]),
              $page_id, $page_slug,
                            1);
            }
          } else {
              $query = sprintf("UPDATE %s set cml_default = %d, cml_flag = '%s', cml_language = '%s', cml_language_slug = '%s', " .
              "cml_category_id = %d, cml_category_name = '%s', cml_category_slug = '%s', cml_locale = '%s', cml_notice_post = '%s', " .
              "cml_notice_page = '%s', cml_notice_category = '%s', cml_page_id = %d, cml_page_slug = '%s', cml_enabled = %d WHERE id = %d",
              CECEPPA_ML_TABLE,
              intval($_POST['default'] == $id),
              $lang,
              $_POST['language'][$i],
              $_POST['language_slug'][$i],
              $_POST['category'][$i],
              $category,
              $category_slug,
              $_POST['locale'][$i],
              utf8_encode($_POST['notice_post'][$i]),
              utf8_encode($_POST['notice_page'][$i]),
              utf8_encode($_POST['notice_category'][$i]),
              $page_id, $page_slug,
                            $_POST['lang-enabled'][$i],
              $id);
              }
      //     }

        //Eseguo solo se la query nn è vuota :)
        if(!empty($query)) {
          $wpdb->query($query);

          $cat_id = intval($_POST['category'][$i]);
          if(empty($id)) $wpdb->get_var('SELECT id FROM ' . CECEPPA_ML_TABLE , ' WHERE cml_category_id = ' . $cat_id);
          $page_id = intval($_POST['page'][$i]);

          $this->update_category($id, $cat_id, $page_id);
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

      if(array_key_exists("posts", $_POST))
	update_option("cml_option_post_redirect", $_POST['posts']);

      //Flags
      @update_option("cml_option_flags_on_post", intval($_POST['flags-on-posts']));
      @update_option("cml_option_flags_on_page", intval($_POST['flags-on-pages']));
      @update_option("cml_option_flags_on_cats", intval($_POST['flags-on-cats']));

      //Change locale
      update_option("cml_option_change_locale", intval($_POST['change-locale']));

      //Filter posts
      update_option("cml_option_filter_posts", intval($_POST['filter-posts']));

      //Filter translations
      update_option("cml_option_filter_translations", intval($_POST['filter-translations']));
            
      //Filter query
      update_option("cml_option_filter_query", intval($_POST['filter-query']));
            
      //Filter search
      update_option("cml_option_filter_search", intval($_POST['filter-search']));
      update_option("cml_option_filter_form_class", $_POST['filter-form']);

      //Avviso
      update_option("cml_option_notice", $_POST['notice']);
      update_option("cml_option_notice_pos", $_POST['notice_pos']);
      update_option("cml_option_notice_after", $_POST['notice_after']);
      update_option("cml_option_notice_before", $_POST['notice_before']);
      update_option("cml_option_notice_post", intval($_POST['notice-post']));
      update_option("cml_option_notice_page", intval($_POST['notice-page']));
      update_option("cml_option_notice_cats", intval($_POST['notice-cats']));

      //Commenti
      update_option('cml_option_comments', $_POST['comments']);
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

  /*
   * Form per la traduzione dei titoli dei widget
   */
  function form_translations() {
    global $wpdb;

    //Css
    wp_enqueue_style('ceceppaml-style');
    wp_enqueue_style('ceceppaml-dd');

    if(isset($_REQUEST['form'])) {
      $sql = "";

      //Nuovo record o da modificare?
      $wpdb->query("DELETE FROM " . CECEPPA_ML_TRANS);

      $langs = cml_get_languages();
      for($i = 0; $i < count($_REQUEST['string']); $i++) :
                $id = $_REQUEST['id'][$i];
            
                //Per ogni lingua
                foreach($langs as $lang) :
                    $lang = $lang->id;
                    
            //       if(empty($id)) {
                        $text = $_REQUEST['lang_' . $lang][$i];
                        $sql = sprintf("INSERT INTO %s (cml_text, cml_lang_id, cml_translation) VALUES('%s', '%s', '%s')",
                                CECEPPA_ML_TRANS,
                                $_REQUEST['string'][$i],
                                $lang,
                                utf8_encode($text));
            //       } else {
            //         $sql = sprintf("UPDATE %s SET cml_translation = '%s' WHERE cml_text = '%s' AND cml_lang_id = %d",
            //                 CECEPPA_ML_TRANS, $_REQUEST["lang_$lang"][$i],
            //                 $id, $lang);
            //       }
                    
                    if(!empty($sql))
                        $wpdb->query($sql);
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

    $langs = cml_get_languages();

    //Ho specificato in quale lingua sarà il nuovo post?
    if(array_key_exists("post-lang", $_GET) || array_key_exists("page-lang", $_GET)) {
      $post_lang = intval($_GET['post-lang']) + intval($_GET['page-lang']);
    }

    //Lingua dell'articolo
    echo "<h4>" . __('Language of this post', 'ceceppaml') . "</h4>";
    $lang_id = empty($post_lang) ? $this->get_language_id_by_post_id($tag->ID) : $post_lang;
    cml_dropdown_langs("post_lang", $lang_id, false, true);

    echo "<h4>" . __('Link to post', 'ceceppaml') . "</h4>";
    echo "<select name='linked_post' style=\"width:100%\" class='link-category'>";
    echo "<option value=''>" . __('No post linked', 'ceceppaml') . "</option>";

    //Ho passato come parametro l'id del post da collegare?
    if(array_key_exists("link-to", $_GET)) {
      $linked_post = intval($_GET['link-to']);
    }

    //Recupero l'id del post collegato
    $t_id = $tag->ID;
    $linked_to = $wpdb->get_var(sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d",
		    CECEPPA_ML_POSTS, $t_id));

    //Elenco degli articoli
    $args = array('numberposts' => -1, 'order' => 'ASC', 'orderby' => 'title', 'posts_per_page' => 9999,
		    'status' => 'publish,inherit,pending,private,future,draft');

    $posts = new WP_Query($args);

    //Scorro gli articoli
    $linked_post = (empty($linked_to)) ? $linked_post : $linked_to;
    while($posts->have_posts()) :
      $posts->next_post();

      $id = $posts->post->ID;
      $selected = ($id == $linked_post) ? "selected" : "";

      $lang_id = $this->get_language_id_by_post_id($id);
      $flag = cml_get_flag_by_lang_id($lang_id);
      echo "<option value=\"$lang_id@$id\" data-image=\"$flag\" $selected>&nbsp;&nbsp;&nbsp;" . get_the_title($id) . "</option>";
    endwhile;
    echo "</select>";
        
      //Visualizzo le traduzioni disponibili per l'articolo, oppure i tasti + per aggiungerne una
      ?>
      <div class="cml-box-translations">
	  <h4><?php _e('Translations:', 'ceceppaml') ?></h4>
	  <ul>
      <?php
	$id = $this->get_language_id_by_post_id($t_id);

	foreach($langs as $lang) :
	  if($lang->id != $id) :

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

    $linked_page = $wpdb->get_var(sprintf("SELECT cml_post_id_2 FROM %s WHERE cml_post_id_1 = %d",
                                                                            CECEPPA_ML_PAGES, $tag->ID));

    $page_lang = array_key_exists("page-lang", $_GET) ? intval($_GET['page-lang']) : get_option("cml_page_lang_$tag->ID");
    $linked = array_key_exists("page-lang", $_GET) ? intval($_GET['link-to']) : $linked_page;

    $args = array('post_status' => 'publish,inherit,pending,private,future,draft',
		  'post_type' => 'page');
    $pages = get_pages($args);

    echo "<h4>" . __('Language of this page', 'ceceppaml') . "</h4>";
    cml_dropdown_langs("page_lang", $page_lang);

    echo "<h4>" . __('Link to the page', 'ceceppaml') . "</h4>";
    echo "<select name=\"linked_page\" class=\"linked_page\">";
    echo "<option>" . __('No page linked', 'ceceppaml') . "</option>";
    foreach ( $pages as $page ) :
      //Recupero il padre della pagina corrente per sapere a quale lingua appartiene
      $parent = $page->post_parent;
      $page_parent = $this->get_page_parent($page);

      //Lingua della pagina
      $page_lang = $this->get_language_id_by_page_id($page);
      if(empty($page_lang)) {
	$page_lang = get_option("cml_page_lang_$page_parent");
      }

      $selected = ($page->ID == $linked) ? "selected" : "";
      $flag = cml_get_flag_by_lang_id($page_lang);

      $option = "<option value=\"$page_lang@$page->ID\" data-image=\"$flag\" $selected>";
      $option .= $page->post_title;
      $option .= '</option>';
      echo $option;
    endforeach;

    echo "</select>";

        $langs = cml_get_languages();
        $t_id = $tag->ID;
        ?>
        <div class="cml-box-translations">
            <h4><?php _e('Translations:', 'ceceppaml') ?></h4>
            <ul>
        <?php
            $id = $this->get_language_id_by_page_id($t_id);

            foreach($langs as $lang) :
                if($lang->id != $id) :

                $link = cml_get_linked_post($id, null, $t_id, $lang->id);
                $href = empty($link) ? (get_bloginfo("url") . "/wp-admin/post-new.php?post_type=page&page-lang=$lang->id&link-to=$t_id") : get_edit_post_link($link);
                $icon = empty($link) ? "add" : "go";
                $title = empty($link) ? __('Translate page', 'ceceppaml') : __('Edit page', 'ceceppaml');
        ?>
            <li class="<?php echo empty($link) ? "no-translation" : "" ?>">
                <a href="<?php echo $href ?>">
                    <img src="<?php echo cml_get_flag_by_lang_id($lang->id, "small") ?>" title="<?php echo $lang->cml_language ?>" />
                    <?php if(empty($link)) { ?>
                    <span class="cml_add_text"><?php _e('Add translation', 'ceceppaml') ?></span>
                    <?php } else { ?>
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

    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

    //Controllo che la lingua esista nel mio db, altrimenti vis quella di default
    $slug = $wpdb->get_var(sprintf("SELECT id FROM %s WHERE cml_flag = '%s'",
                CECEPPA_ML_TABLE, $lang));

    $lang = (empty($slug)) ? $this->_default_language_slug : $lang;
    if($this->_redirect_browser == 'auto') {
      //Redirect abilitato
      $location = home_url() . "/?lang=$lang";
    }

    if(!empty($location)) {
      wp_redirect($location, $status );
      exit;
    }
  }

  /**
   * Redirect category/post/page??
   */
  function show_notice($content) {
    global $wpdb;

    if(isCrawler()) return $content;
    
    $browser_langs = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);

    //Se la lingua del browser coincide con una di quella attuale della pagina, ignoro tutto
    foreach($browser_langs as $lang) :
      @list($code1, $code2) = explode(",", $lang);

      if($code1 == $this->_current_lang_locale) return $content;
      if($code2 == $this->_current_lang_locale) return $content;

      $locale[] = str_replace("-", "_", $code1);
      $locale[] = str_replace("-", "_", $code2);
    endforeach;

    //Controllo se la lingua del browser è tra quelle gestite, se non lo è non visualizzo alcun messaggio.
    $browser_lang_id = $this->get_language_id_by_locale(array_unique($locale));
    if(empty($browser_lang_id)) return $content;

    //Recupero l'd della lingua dal database
    $lang_id = $this->get_current_lang_id();

    if(is_category()) {
      if(get_option("cml_option_notice_cats") != 1) return $content;

      $id = $this->get_category_id(single_cat_title("", false));
      $link = cml_get_linked_cat($lang_id, null, $id, $browser_lang_id);

      $link = get_category_link($link);
    }
    
    if(is_page()) {
      if(get_option("cml_option_notice_page") != 1) return $content;

      //$link = cml_get_linked_page($lang_id, null, get_the_ID(), $browser_lang_id);
            $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);

      $link = ($link == get_the_ID() || $link == null) ? null : get_permalink($link);
    }
    
    if(is_single()) {
      if(get_option("cml_option_notice_post") != 1) return $content;

      $link = cml_get_linked_post($lang_id, null, get_the_ID(), $browser_lang_id);

      $link = ($link == get_the_ID() || $link == null) ? null : get_permalink($link);
    }

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
  
   //Save extra category extra fields callback function
  function save_extra_category_fileds($term_id) {
    global $wpdb;

    if(isset($_POST['linked_cat'])) {
      $query = "";

      //Categoria collegata
      $linked_cat = intval($_POST['linked_cat']);

      //Lingua della categoria
      $cat_lang = intval($_POST['cat_lang']);

      //Se è stata scelta una categoria "genitore", recupero la lingua corretta dalla categoria padre
      $category = get_category($term_id);
      $parent = $category->category_parent;
      if($parent > 0) {
        //Recupero dalla mia tabella l'id della lingua per la categoria collegata
        $linked_lang = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_category_slug = '" . $this->get_category_parent($term_id) . "'");

        if(empty($linked_lang)) {
          $l_parent = get_category(intval($_POST['linked_cat']));
          $linked_lang = get_option("cml_category_lang_$l_parent->category_parent");    //Recupero la lingua della categoria a cui lo sto collegando
        }

        //Recupero dalla mia tabella l'id della lingua relativa al padre della categoria appena creata/modificata
        $cat_lang = $wpdb->get_var("SELECT id FROM " . CECEPPA_ML_TABLE . " WHERE cml_category_slug = '" . $this->get_category_parent($term_id) . "'");
        if(empty($cat_lang)) $cat_lang = get_option("cml_category_lang_$parent");   //Recupero la lingua dal padre
      } else {
        //E' stata selezionata una lingua per la categoria corrente e nessun genitore
                if(intval($_POST['linked_cat']) == -1)
                    $linked_lang = -1; //get_option("cml_category_lang_$linked_cat");
                else
                    $linked_lang = $this->get_language_id_by_category($linked_cat);
      }

      //Elimino i vecchi collegamenti presenti nel database
      $query = sprintf("SELECT id FROM %s WHERE cml_cat_id_1 = %d", CECEPPA_ML_CATS, intval($term_id));
      $id = intval($wpdb->get_var($query));

      if($id > 0) {
        $query = sprintf("DELETE FROM %s WHERE id = %d", CECEPPA_ML_CATS, $id);

        $wpdb->query($query);
      }

      //Aggiungo un record se è stata specificata la categoria o la lingua collegata :)
      if($linked_cat >= 0 || $cat_lang > 0) {
        $query = sprintf("INSERT INTO %s (cml_cat_lang_1, cml_cat_id_1, cml_cat_lang_2, cml_cat_id_2) VALUES ('%d', '%d', '%d', '%d')",
              CECEPPA_ML_CATS,
              intval($cat_lang),
              intval($term_id),
              intval($linked_lang),
              intval($linked_cat));
        $wpdb->query($query);
      }

      //Aggiorno la lingua di tutte le categorie collegate
      $args = array('child_of' => $term_id, 'hide_empty' => 0);
      $categories = get_categories($args);
      foreach($categories as $cat) :
          $this->save_extra_category_update_posts($cat->term_id, $cat_lang);

          update_option("cml_category_lang_$cat->term_id", $cat_lang);
      endforeach;

      $this->save_extra_category_update_posts($term_id, $cat_lang);

      $keys = array_keys($_POST['cat_name']);
      foreach($keys as $key) :
	  update_option("cml_category_" . $term_id . "_lang_$key", $_POST['cat_name'][$key]);
      endforeach;

      update_option("cml_category_$term_id", $linked_cat);
      update_option("cml_category_lang_$term_id", $cat_lang);
    }
  }

    function delete_extra_category_fields($term_id) {
        delete_option("cml_category_$term_id");
        delete_option("cml_category_lang_$term_id");
    }

  /**
   * Questa funzione viene richiamata quando salvo una categoria e serve ad aggiornare
   * la lingua di tutti i post collegati alla categoria e ai suoi figli
   */
  function save_extra_category_update_posts($cat_id, $cat_lang) {
    global $wpdb;

    //Aggiorno la lingua in tutti i post collegati alle categorie
    $args = array('category' => $cat_id);
    $posts = get_posts($args);

    foreach($posts as $post) :
      //Aggiorno la lingua del post corrente
      $query = sprintf("UPDATE %s SET cml_post_lang_1 = %d WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, $cat_lang, $post->ID);
      $wpdb->query($query);

      $query = sprintf("UPDATE %s SET cml_post_lang_2 = %d WHERE cml_post_id_2 = %d", CECEPPA_ML_POSTS, $cat_lang, $post->ID);
      $wpdb->query($query);
    endforeach;
  }

  /* 
   * Salvo il collegamento tra i post
   */
  function save_extra_post_fields($term_id) {
      global $wpdb;

      $post_id = $term_id;
      //if(isset($_POST['linked_post'])) :
	  $query = "";

	  @list($linked_lang, $linked_post) = split("@", $_POST['linked_post']);
  
	  //Recupero dalla mia tabella l'id della lingua :)
	  if(empty($_POST['post_lang']))
	    $post_lang = $this->get_language_id_by_post_id($term_id);
	  else
	    $post_lang = intval($_POST['post_lang']);

	  //Elimino i vecchi collegamenti presenti nel database
	  $query = sprintf("SELECT id FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, intval($post_id));
	  $id = intval($wpdb->get_var($query));
	  if($id > 0) {
	      $query = "DELETE FROM " . CECEPPA_ML_POSTS . " WHERE id = " .intval($id);
	      $wpdb->query($query);
	  }
//      endif;

      $query = "";
      if($post_lang > 0) :
	  $query = sprintf("INSERT INTO %s (cml_post_lang_1, cml_post_id_1, cml_post_lang_2, cml_post_id_2) VALUES ('%d', '%d', '%d', '%d')",
		      CECEPPA_ML_POSTS,
		      intval($post_lang),
		      intval($post_id),
		      intval($linked_lang),
		      intval($linked_post));
      
      endif;

      if(!empty($query)) 
	$wpdb->query($query);
      
      if(isset($_POST['linked_page']))
	$this->save_extra_page_fields($term_id);

      if(!isset($post_lang))
	remove_option("cml_post_lang_$term_id");

      if(!isset($post_lang))
	remove_option("cml_post_lang_$term_id");
  }

  function delete_extra_post_fields($id) {
    global $wpdb;

    $sql = sprintf("DELETE FROM %s WHERE cml_post_id_1 = %d OR cml_post_id_2 = %d", CECEPPA_ML_POSTS, $id, $id);

    $wpdb->query($sql);
  }

  function save_extra_page_fields($page_id) {
        global $wpdb;

        $query = "";

        list($linked_lang, $linked_page) = split("@", $_POST['linked_page']);

        $page_parent = $this->get_page_parent_by_id($page_id);
        $page_lang = intval($_POST['page_lang']);

        //Se è stata specificata la pagina padre, recupero l'info della lingua da questa :)
        if($page_parent != $page_id && $page_parent > 0) {
            //Recupero la lingua corretta se è stata scelta una struttura ad albero
            $page_lang = $wpdb->get_var(sprintf("SELECT id FROM %s WHERE cml_page_id = %d",
                                    CECEPPA_ML_TABLE, $page_parent));
            
            //
            if(empty($page_lang)) {
                $query = sprintf("SELECT cml_post_lang_1 FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_PAGES, $page_parent);

                $page_lang = $wpdb->get_var($query);
            }
        }

        //Elimino i vecchi collegamenti presenti nel database
//      $query = sprintf("SELECT id FROM %s WHERE cml_page_id_1 = %d", CECEPPA_ML_PAGES, intval($page_id));
        $query = sprintf("SELECT id FROM %s WHERE cml_post_id_1 = %d", CECEPPA_ML_POSTS, intval($page_id));

        $id = intval($wpdb->get_var($query));
        if($id > 0) {
            //$query = sprintf("DELETE FROM %s WHERE id = %d", CECEPPA_ML_PAGES, intval($id));
            $query = sprintf("DELETE FROM %s WHERE id = %d", CECEPPA_ML_PAGES, intval($id));
            $wpdb->query($query);
        }

        if((intval($linked_lang) > 0 AND intval($linked_page) > 0) OR ($page_lang > 0 AND $page_id > 0)) {
//                $query = sprintf("INSERT INTO %s (cml_page_lang_1, cml_page_id_1, cml_page_lang_2, cml_page_id_2) VALUES ('%d', '%d', '%d', '%d')",
            $query = sprintf("INSERT INTO %s (cml_post_lang_1, cml_post_id_1, cml_post_lang_2, cml_post_id_2) VALUES ('%d', '%d', '%d', '%d')",
                        CECEPPA_ML_PAGES,
                        intval($page_lang),
                        intval($page_id),
                        intval($linked_lang),
                        intval($linked_page));
        } else {
            //Aggiorno la lingua del post corrente
            $query = sprintf("UPDATE %s SET cml_post_lang_1 = %d WHERE cml_post_id_1 = %d", CECEPPA_ML_PAGES, $page_lang, $page_id);
            $wpdb->query($query);
        
            $query = sprintf("UPDATE %s SET cml_post_lang_2 = %d WHERE cml_post_id_2 = %d", CECEPPA_ML_PAGES, $page_lang, $page_id);
        }

        $wpdb->query($query);

        update_option("cml_page_$page_id", $linked_page);
        update_option("cml_page_lang_$page_id", $page_lang);
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
   * Aggiorno le categorie della tabella CECEPPA_ML_CATS, CECEPPA_ML_POSTS, CECEPPA_ML_PAGES
   *
   * I passi necessari per la configurazione del plugin sono
   *   1. Creazione e collegamento delle varie categorie
   *   2. Creazione e collegamento della lingua con la rispettiva categoria
   *
   * In fase di creazione delle categorie relative alle altre lingue che vogliamo configurare non ho a disposizione
   * l'id della lingua in quanto il collegamento tra lingua e categoria, passo 2, avviene in un secondo momento,
   * oppure perchè l'utente apporta delle modifiche alle lingue in un secondo momento.
   *
   * Quindi quando eseguo il passo 2 vado a correggere nella tabella delle categorie l'id della lingua "collegata"
   */
  function update_category($lang_id, $id, $pge_id) {
    //Aggiorno la categoria principale
    $this->update_cml_category('cat', $lang_id, $id);
    $this->update_cml_category('pages', $lang_id, $page_id);

    //Aggiorno le categorie
    $args = array(
      'type'                     => 'post',
      'child_of'                 => $id,
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

    //Aggiorno i figlio
    $categories = get_categories($args);
    foreach($categories as $category) {
      $this->update_cml_category('cat', $lang_id, $category->term_id);
    }

    //Recupero tutti i post presenti nella categoria $id
    if(intval($id) > 0) {
      query_posts("cat=$id");
      while (have_posts()) : the_post();
        $this->update_cml_category('posts', $lang_id, get_the_ID());
      endwhile;
    }
  }

  function update_cml_category($table, $lang_id, $cat_id) {
    global $wpdb;

    if($table == 'cat') {
      $queries[] = sprintf("UPDATE %s SET cml_cat_lang_1 = %d WHERE cml_cat_id_1 = %d",
            CECEPPA_ML_CATS,
            $lang_id,
            $cat_id);

      $queries[] = sprintf("UPDATE %s SET cml_cat_lang_2 = %d WHERE cml_cat_id_2 = %d",
            CECEPPA_ML_CATS,
            $lang_id,
            $cat_id);
    } 

    if($table == 'pages') {
//      $queries[] = sprintf("UPDATE %s SET cml_page_lang_1 = %d WHERE cml_page_id_1 = %d",
      $queries[] = sprintf("UPDATE %s SET cml_post_lang_1 = %d WHERE cml_post_id_1 = %d",
            CECEPPA_ML_PAGES,
            $lang_id,
            $cat_id);

//      $queries[] = sprintf("UPDATE %s SET cml_page_lang_2 = %d WHERE cml_page_id_2 = %d",
      $queries[] = sprintf("UPDATE %s SET cml_post_lang_2 = %d WHERE cml_post_id_2 = %d",
            CECEPPA_ML_PAGES,
            $lang_id,
            $cat_id);
    }

    if($table == 'posts') {
      $queries[] = sprintf("UPDATE %s SET cml_post_lang_1 = %d WHERE cml_post_id_1 = %d",
            CECEPPA_ML_POSTS,
            $lang_id,
            $cat_id);

      $queries[] = sprintf("UPDATE %s SET cml_post_lang_2 = %d WHERE cml_post_id_2 = %d",
            CECEPPA_ML_POSTS,
            $lang_id,
            $cat_id);
    }

    foreach($queries as $query) {
      $wpdb->query($query);
    }
  }
  
  /**
   * Aggiorno la lingua utilizzata al momento
   */
  function update_current_lang() {
    global $wpdb;

    $lang = ""; //$_COOKIE['cml_current_lang'];
        
    //Serve a far sì che "il cambio menu" funzioni correttamente anche
    //con il permalink di default: ?p=#
    $the_id = get_the_ID();
    $permalink = get_option("permalink_structure");
    if(!is_admin() && empty($permalink) && empty($the_id)) {
	$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$the_id  = url_to_postid($url);
    }

    //Categoria
    if(is_category()) {
      $id = $this->get_category_id(single_cat_title("", false));
      $parent = get_category_by_slug($this->get_category_parent($id));

      $lang = $this->get_language_id_by_category($parent->term_id);
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
            $mods = get_theme_mods();
            $locations = get_theme_mod('nav_menu_locations');

            //Se non inizia per cml_ allora sarà quella definita dal tema :)
            if(is_array($locations)) :
                $keys = array_keys($locations);
                foreach($keys as $key) :
                    if(substr($key, 0, 4) != "cml_") {
                        $menu = $this->_current_lang;
                        if(!empty($locations["cml_menu_$menu"])) {
                            $locations[$key] = $locations["cml_menu_$menu"];
                            set_theme_mod('nav_menu_locations', $locations);
                        }

                        //Esco dal ciclo
                        break;
                    }
                endforeach;
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

  /** 
   * Restituisco l'id della categoria associata alla lingua corrente
   */
  function get_language_category($lang_id = "") {
    global $wpdb;

        if(empty($lang_id)) $lang_id = $this->_current_lang_id;
    $query = sprintf("SELECT cml_category_id FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $lang_id);

    return intval($wpdb->get_var($query));
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
    if($lang != $this->_language_lang_id) :
      $query = sprintf("SELECT * FROM %s WHERE cml_post_lang_1 = %d OR cml_post_lang_2 = %d",
					CECEPPA_ML_POSTS, $lang, $lang);

      $results = $wpdb->get_results($query);
      foreach($results as $result) :
	  if($result->cml_post_id_1 > 0 && $result->cml_post_lang_1 == $lang) $posts[] = $result->cml_post_id_1;
	  if($result->cml_post_id_2 > 0 && $result->cml_post_lang_2 == $lang) $posts[] = $result->cml_post_id_2;
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

      //Recupero la pagina base, se specificata
      $parent = $wpdb->get_var(sprintf("SELECT cml_page_id FROM %s WHERE id = %d", CECEPPA_ML_TABLE, $lang_id));

      if(!empty($parent)) {
	/*
	  * Se è stata utilizzata una struttura ad albero, recupero l'id del padre e di tutti i figli
	  */
	$args = array(
	  'post_type'    => 'page',
	  'parent'       => -1,
	  'child_of'     => $parent,
	  'orderby'      => 'name',
	  'order'        => 'ASC');

	$pages = get_pages($args);
	foreach($pages as $page) :
	    $ids[] = $page->ID;
	endforeach;
	
	$ids[] = $parent;
      }

      $ids = array_merge($ids, $this->get_language_posts($lang_id));
      
      return empty($ids) ? array(0) : array_unique($ids);
  }

  /**
   * Recupero l'id della pagina padre
   */
  function get_page_parent($page) {
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

    remove_filter('query', array(&$this, get_comments));

    if(is_single()) {
      $id_1 = "cml_post_id_1";
      $id_2 = "cml_post_id_2";
      $table = CECEPPA_ML_POSTS;
    }
    
    if(is_page()) {
      $id_1 = "cml_page_id_1";
      $id_2 = "cml_page_id_2";
      $table = CECEPPA_ML_PAGES;
    }

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
        if($this->_static_page) return;
    
        //Recupero l'id della lingua
        $slug = $_GET['lang'];    
        $lang_id = $this->get_language_id_by_slug($slug);

        //Id attuale
        $id = $query->query_vars['page_id'];
        
        //Recupero l'id collegato
        $nid = cml_get_linked_post($this->_default_language_id, null, $id, $lang_id);
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
        if($this->_current_lang_id != $this->_default_language_id) :
            $id = get_cat_ID($item->title);
            if(!empty($id)) :
                $lang = $this->_current_lang_id;
                $id = $id->term_id;

                $item->title = get_option("cml_category_$id_lang_$lang", $item->title);
            endif;
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

	$id = get_cat_ID(trim($name));
        if($id > 0) :
            $lang = $this->_current_lang_id;

            $n = get_option("cml_category_" . $id . "_lang_" . $lang);
            $name = empty($n) ? $name : $n;
        endif;

        return str_repeat($simbolo . " ", $depth) . $name;
    }
    
    function translate_term_link($link) {
        //Lingua dell'articolo
        $lang_id = $this->get_language_id_by_post_id(get_the_ID());
        if($lang_id == $this->_default_language_id || empty($lang_id)) return $link;

        $slug = strtolower($this->get_language_slug_by_id($lang_id));
        return $link . "?lang=$slug&ht=1";
    }
    
    function translate_object_terms($obj) {
      if(empty($obj)) return $obj;

      if(is_object($obj)) :
	if($obj->taxonomy == 'category') {
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
	foreach($obj as $o) :
	  $nobj[] = $this->translate_object_terms($o);
	endforeach;
      endif;
      
      return $nobj;
    }
    
    function translate_category($name) {
      return $this->translate_term_name($name);
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

function create_custom_taxonomies(){
    register_taxonomy('guide', 'posttypename', array( 'hierarchical' => true, 'label' => 'Tutorial'));
    register_taxonomy('taxonomy2', 'posttypename', array( 'hierarchical' => true, 'label' => 'Taxonomy2'));
    flush_rewrite_rules( false );/* Please read "Update 2" before adding this line */
}
add_action('init', 'create_custom_taxonomies' );
?>