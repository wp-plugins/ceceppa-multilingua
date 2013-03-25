<div class="wrap">

   <h2><?php _e('Widget\'s titles', 'ceceppaml'); ?></h2>
<?php
function cml_widgets_title($buffer) {
  global $wpdb;

  preg_match_all("(<h3(.*?)/h3>)", $buffer , $widgets);

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
			preg_match('(>.*?<)', $title, $t);
			$title = $t[0];
			$title = str_replace(array(">", "<"), "", $title);
			
			if(!empty($title)) $wtitles[$title] = $title;;
	 endforeach;
	 
	 if(is_array($wtitles)) :
			foreach($wtitles as $title) :
						echo "<tr>";
				 
						echo "<td style=\"height:2.5em\">\n";
						echo "\t<input type=\"hidden\" name=\"string[]\" value=\"$title\" />\n";
						echo $title . "</td>";
						$i = 0;
	 
						foreach($langs as $lang) :
							 $d = cml_translate($title, $lang->id);
							 echo "<td>\n";
							 echo "<input type=\"text\" name=\"lang_" . $lang->id . "[]\" value=\"$d\" /></td>\n";
							 
							 $i++;
						endforeach;
						echo "</tr>";
		 endforeach;
	 endif;	 ?>
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

//Recupero tutte le sidebar
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

		$content .= ob_get_contents();
	 endforeach;
  endif;
ob_end_clean();

cml_widgets_title($content);
?>