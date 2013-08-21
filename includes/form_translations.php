<?php
/*  Copyright 2013  Alessandro Senese (email : senesealessandro@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $wpCeceppaML;

//Non posso richiamare lo script direttamente dal browser :)
if(!is_object($wpCeceppaML)) die("Access denied");

global $wpdb;

$tab = isset( $_GET['page'] ) ? intval( $_GET['tab'] ) : 0;
$tab = ( $_GET['page'] == 'ceceppaml-translations-title' ) ? 1 : $tab;
$tab = ( $_GET['page'] == 'ceceppaml-translations-plugins-themes' ) ? 3 : $tab;
?>

<div class="wrap">
  <div class="icon32">
    <img src="<?php echo CECEPPA_PLUGIN_URL ?>images/logo.png" height="32"/>
  </div>
  <h2 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo $tab == 0 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-translations-page"><?php _e('My translations', 'ceceppaml') ?></a>
    <a class="nav-tab <?php echo $tab == 1 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-translations-title"><?php _e('Site Title') ?>/<?php _e('Tagline') ?></a>
<!--     <a class="nav-tab <?php echo $tab == 2 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-translations-plugins-themes&tab=2"><?php _e('Plugins') ?></a> -->
    <a class="nav-tab <?php echo $tab == 3 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-translations-plugins-themes&tab=3"><?php _e('Theme') ?></a>
    </h2>
    <br />
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $_GET['page'] ?>">
<?php 
  if ( $tab == 0) : 
?>

<div class="updated">
    <p>
      <?php _e('These translations are used by plugin for translate custom links in the menu.', 'ceceppaml') ?><br />
      <br />
      <?php _e('You can use them also with shortcode "cml_text".', 'ceceppaml') ?>
      <a href="?page=ceceppaml-shortcode-page&tab=0#strings"><?php _e( 'Click here to see the shortcode page', 'ceceppaml' ); ?></a>
      <br />
    </p>
</div>
    <input type="hidden" name="form" value="1" />
    <table class="wp-list-table widefat wp-ceceppaml">
      <thead>
      <tr>
	  <th><img src="<?php echo cml_get_flag_by_lang_id(cml_get_default_language_id()) ?>" />&nbsp;<?php echo cml_get_language_title(cml_get_default_language_id()) ?></th>
<?php
	  $langs = cml_get_languages(1, 0);
	  foreach($langs as $lang) {
	    if(!$lang->cml_default) :
	      echo "<th><img src='" . cml_get_flag($lang->cml_flag) . "'/>&nbsp;$lang->cml_language</th>";
	      $lid[] = $lang->id;
	    endif;
	  }
?>
      <th style="width: 40px">
	<img src="<?php ECHO CECEPPA_PLUGIN_URL . "images/remove.png" ?>" height="16"/>
      </th>
      </tr>
      </thead>
<?php 
  $results = $wpdb->get_results("SELECT min(id) as id, UNHEX(cml_text) as cml_text FROM " . CECEPPA_ML_TRANS . " WHERE cml_type='S' GROUP BY cml_text");

  $c = 0;
  $size = 100 / (count($langs) + 1);
  foreach($results as $result) :
      $title = html_entity_decode($result->cml_text);
	//Non posso utilizzare htmlentities perché sennò su un sito in lingua russa mi ritrovo tutti simboli strani :'(
      $title = str_replace("\"", "&quot;", stripslashes($title));

      $alternate = @empty($alternate) ? "alternate" : "";
      echo "<tr class=\"${alternate}\">";

      echo "<td style=\"height:2.5em;width: $size%\">\n";
      echo "\t<input type=\"hidden\" name=\"id[]\" value=\"$result->id\" />\n";
      echo "\t<input type=\"hidden\" name=\"string[]\" value=\"$title\" />\n";
      echo stripslashes($title) . "</td>";
      $i = 0;

      foreach($langs as $lang) :
	$d = cml_translate($title, $lang->id, 'S');
	$d = str_replace("\"", "&quot;", stripslashes($d));
	echo "<td>\n";
	echo "<input type=\"hidden\" name=\"lang_id[$c][$i]\" value=\"$lang->id\" />\n";
	echo "<input type=\"text\" name=\"value[$c][$i]\" value=\"$d\"  style=\"width: 100%\" /></td>\n";

	$i++;
      endforeach;

?>
      <td>
	<input type="checkbox" name="remove[<?php echo $result->id ?>]" value="1">
      </td>
<?php

    echo "</tr>";
    
    $c++;
  endforeach;
?>
     </tbody>
    </table>
    <div style="text-align:right">
      <p class="submit">
	<input type="button" class="button button-secondaty" name="add" value="<?php _e('Add', 'ceceppaml') ?>" onclick="addRow(<?php echo count($langs) . ", '" . join(",", $lid) ?>')" />
	<?php submit_button( __('Update', 'ceceppaml', 'ceceppaml'), "button-primary", "action", false, 'class="button button-primary"' ); ?>
      </p>
    </div>
<?php 
endif;

if( $tab == 1 ) :
  echo '<input type="hidden" name="form" value="2" />';
  
  $langs = cml_get_languages( 0, 0 );
  
  $blog_title = get_bloginfo('name');
  $blog_tagline = get_bloginfo('description');

  echo '<dl class="site-title">';
  foreach ( $langs as $lang ) :
    $title = cml_translate ( $blog_title, $lang->id, 'M' );
    $tagline = cml_translate ( $blog_tagline, $lang->id, 'M' );

    echo '<dt><h3><img src="' . cml_get_flag_by_lang_id( $lang->id ) . '" />&nbsp;' . cml_get_language_title( $lang->id ) . '</h3>';
    echo '<input type="hidden" name="id[]" value="' . $lang->id . '" /></dt>';
    echo '<dd><span>' . __('Site Title') . ':</span><input class="regular-text" type="text" name="title[]" value="' . $title . '" /></dd>';
    echo '<dd><span>' . __('Tagline') . ':</span><input class="regular-text" type="text" name="tagline[]" value="' . $tagline . '" /></dd>';
  endforeach;

  echo "</dl>";
  
  submit_button( __('Update', 'ceceppaml', 'ceceppaml'), "button-primary", "action", false, 'class="button button-primary"' );
  
  endif; 
  
if( $tab == 3 ) :
  echo '<input type="hidden" name="form" value="3" />';

  require_once 'form_translations_theme.php';
endif;
?>
    </form>
</div>
