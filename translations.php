<div class="wrap">
   <h2><?php _e('My translations', 'ceceppaml'); ?></h2>
   <br />
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-translations-page">
    <input type="hidden" name="form" value="1" />
    <table class="ceceppaml CSSTableGenerator">
      <tbody>
      <tr>
	  <td><?php _e('Text', 'ceceppaml') ?></td>
<?php
	  $langs = cml_get_languages(1, 0);
	  foreach($langs as $lang) {
	    if(!$lang->cml_default) :
	      echo "<td><img src='" . cml_get_flag($lang->cml_flag) . "'/>&nbsp;$lang->cml_language</td>";
	      $lid[] = $lang->id;
	    endif;
	  }
?>
      <td style="width: 40px">
	<img src="<?php ECHO CECEPPA_PLUGIN_URL . "images/remove.png" ?>" height="16"/>
      </td>
      </tr>
<?php 
  $results = $wpdb->get_results("SELECT min(id) as id, cml_text FROM " . CECEPPA_ML_TRANS . " WHERE cml_type='S' GROUP BY cml_text");

  foreach($results as $result) :
      echo "<tr>";

      echo "<td style=\"height:2.5em\">\n";
      echo "\t<input type=\"hidden\" name=\"id[]\" value=\"$result->id\" />\n";
      echo "\t<input type=\"hidden\" name=\"string[]\" value=\"$result->cml_text\" />\n";
      echo $result->cml_text . "</td>";
      $i = 0;

      foreach($langs as $lang) :
	$d = cml_translate($result->cml_text, $lang->id);
	echo "<td>\n";
	echo "<input type=\"hidden\" name=\"lang_id[][]\" value=\"$lang->id\" />\n";
	echo "<input type=\"text\" name=\"value[][]\" value=\"$d\" /></td>\n";

	$i++;
?>
      <td>
	<input type="checkbox" name="remove[<?php echo $result->id ?>]" value="1">
      </td>
<?php
      endforeach;
    echo "</tr>";
  endforeach;
?>
     </tbody>
    </table>
    <div style="text-align:right">
      <input type="button" class="ceceppa-salva" name="add" value="<?php _e('Add', 'ceceppaml') ?>" onclick="addRow(<?php echo count($langs) . ", '" . join(",", $lid) ?>')" />
      <input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Update', 'ceceppaml') ?>" />
    </div>
    </form>
</div>
