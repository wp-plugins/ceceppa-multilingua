<div class="wrap">

   <h2><?php _e('Widget\'s titles', 'ceceppaml'); ?></h2>
<?php
function cml_widgets_title($buffer) {
  global $wpdb;

  preg_match_all("(<h3>(.*?)</h3>)", $buffer , $widgets);

  $langs = cml_get_languages();
?>
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-translations-page">
    <input type="hidden" name="form" value="1" />
    <table class="ceceppaml CSSTableGenerator">
      <tbody>
      <tr>
		<td><?php _e('Widget\'s title', 'ceceppaml') ?></td>
<?php
	  foreach($langs as $lang) {
	    echo "<td><img src='" . cml_get_flag($lang->cml_flag) . "'/>&nbsp;$lang->cml_language</td>";
	  }
?>
      </tr>
<?php 
  $titles = $widgets[1];

  foreach($titles as $title) :
    if($title[0] != '<') {
      echo "<tr>";


      echo "<td style=\"height:2.5em\">\n";
      echo "\t<input type=\"hidden\" name=\"string[]\" value=\"$title\" />\n";
      echo "$title</td>";
      $i = 0;
      foreach($langs as $lang) {
	  $d = cml_translate($title, $lang->id);
// 	  setlocale($lang->cml_locale);
// 	  echo "<td><input type=\"text\" name=\"$lang->id[]\" value=\"" . _($title) . "\" /></td>";
	  echo "<td>\n";
	  echo "<input type=\"text\" name=\"lang_" . $lang->id . "[]\" value=\"$d\" /></td>\n";
	  
	  $i++;
      }
	  echo "</tr>";
    }

  endforeach; ?>
     </tbody>
    </table>
    <div style="text-align:right">
      <input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Update', 'ceceppaml') ?>" />
    </div>
    </form>
</div>
<?php
}

function cml_print_title($title) {
  $valore = array_pop($title);

  while(!empty($valore)) {
      echo "<td>$valore</td>";

    $valore = array_pop($title);
  }  
}

ob_start();
  if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("Sidebar") ) {
    echo ":(";
  }
$content = ob_get_contents();
ob_end_clean();

cml_widgets_title($content);
?>