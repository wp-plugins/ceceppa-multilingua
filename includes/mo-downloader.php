<?php
/*
 * In questa funzione mi occupo di recuperare le traduzioni, ovvero i file .mo dall'svn di wordpress
 * per la precisione da: http://svn.automattic.com/wordpress-i18n/
 */
global $wpCeceppaML;
if(!is_object($wpCeceppaML)) die("Access denied");

require_once("donate.php");

class CMLMoDownloader {
  protected $_failed = array();

  public function __construct() {
  }
  
  public function init() {
    if(isset($_GET['download-lang'])) :
      global $wpdb;

      $id = intval($_GET['download-lang']);

      $lang = cml_get_language_title($id);
      $this->download_language($id, $lang);
    endif;

    $this->show_form();
  }
  
  function show_form() {
?>
  <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
    <table class="wp-list-table widefat mo-table">
      <thead>
      <tr>
	<th>Language</th>
	<th>.mo</th>
	<th><img src="<?php echo CECEPPA_PLUGIN_URL ?>images/addlang.png" height="12" title="<?php _e('language file for standard theme', 'ceceppaml') ?>"></th>
	<th><img src="<?php echo CECEPPA_PLUGIN_URL ?>images/admin.png" height="12" title="<?php _e('language file for admin interface', 'ceceppaml') ?>"></th>
      </tr>
      </thead>
      <tbody id="the-list">
<?php
    $langs = cml_get_languages();
    foreach($langs as $lang) :
      $link = "#";

      if(substr($lang->cml_locale, 0, 2) != "en") :
	$alternate = @empty($alternate) ? "alternate" : "";
	echo "<tr class=\"${alternate}\">";

	$flag = $lang->cml_flag;
	echo "<td><img src=\"" . CECEPPA_PLUGIN_URL . "flags/tiny/$flag.png\" height=\"11\" />&nbsp;$lang->cml_language</td>";

	echo "<td>$lang->cml_locale</td>";

	//Esiste il file della lingua?
	$this->check_mo_exists($lang->id, $lang->cml_locale);
	$this->check_mo_exists($lang->id, "admin-" . $lang->cml_locale);

	echo "</tr>";
      endif;
    endforeach;
?>
    </tbody>
    </table>
    </div>
<?php
  }

  function check_mo_exists($id, $locale) {
    $moFile = WP_CONTENT_DIR . "/languages/$locale.mo";
    $exists = file_exists($moFile) && filesize($moFile) > 0;

    $img = ($exists) ? "enabled" : "download";
    $title = ($exists) ? __('Exists', 'ceceppaml') : __('Download', 'ceceppaml');

    if(!$exists)  {
      $link = add_query_arg('download-lang', $id);
    }
    @$link = '<a href="' . $link . '">';

    if(isset($this->_failed[$id])) :
      $img = "remove";
      $title = __('Download failed', 'ceceppaml');
    endif;

    echo '<td>' . $link . '<img src="' . CECEPPA_PLUGIN_URL . 'images/' . $img . '.png" height="16" title="' . $title . '"/></a></td>';
  }

  function download_language($id, $language) {
    global $wpdb, $wp_version;

    $version = substr($wp_version, 0, 3);
    $exists = false;

    $locale = $wpdb->get_var("SELECT cml_locale FROM " . CECEPPA_ML_TABLE . " WHERE id = " . intval($id));
    
    //Controllo se esiste la cartella
    if(!file_exists(LOCALE_DIR)) mkdir(LOCALE_DIR);

    //Scarico il file ##_## e anche il file per tradurre l'interfaccia admin-##_##
    $done = $this->download($language, $locale, $locale);
    $done .= $this->download($language, $locale, "admin-$locale");
    if($done == 0) :
      //Per alcune lingue non esiste la cartella ##_##, provo solo in ##
      $done = $this->download($language, substr($locale, 0, 2), substr($locale, 0, 2), $locale);
      $done .= $this->download($language, substr($locale, 0, 2), "admin-" . substr($locale, 0, 2), "admin-" . $locale);
    endif;

    if($done >= 2) :
      echo "<div class='updated'><br />" . sprintf(__('Language file for: <b>%s</b> succesfully installed', 'ceceppaml'), $language) . " :)<br /><br /></div>";
    endif;
  }

  function failed($language, $filename) {
    echo "<div class='error'><br />";
    echo sprintf(__('Language file for: <b>%s</b> failed', 'ceceppaml'), $language) . " :'(<br />";
    echo sprintf(__('Filename: <b>%s</b>', 'ceceppaml'), $filename) . ".mo<br /><br /></div>";
  }
  
  function download($language, $locale, $filename, $outfile = null) {
    if(empty($outfile)) $outfile = $filename;

    //Cerco di aprire il file dal server svn
    if(!$fp = @fopen("http://svn.automattic.com/wordpress-i18n/$locale/branches/$version/messages/$filename.mo", "r"))
    if(!$fp = @fopen("http://svn.automattic.com/wordpress-i18n/$locale/trunk/messages/$filename.mo", "r")) :
      @$this->_failed[$id] = 1;

      @$this->failed($language, $filename);
      return 0;
    endif;

    if(is_resource($fp)) :
      //Provo ad aprire il file in uscita
      $out = LOCALE_DIR . "/$outfile.mo";
      if(!$fo = @fopen($out, "w")) :
	$this->_failed[$id] = 1;

	$this->failed($language, $filename);
	return 0;
      endif;

      while(!feof($fp)) :
	// try to get some more time
	@set_time_limit(30);
	$fc = fread($fp, 8192);
	fwrite($fo, $fc);
      endwhile;

      fclose($fp);
      fclose($fo);
    endif;
    
    return 1;
  }
}
?>