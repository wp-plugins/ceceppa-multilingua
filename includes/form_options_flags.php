<?php function cml_option_flags_float() { ?>
<?php
global $wpCeceppaML;

$tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
$small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>
<!-- Float css -->
	<blockquote>
	  <strong><?php _e('Customize css:', 'ceceppaml') ?></strong><br />
	  <textarea name="custom-css" rows="10" cols="100"><?php echo file_get_contents(CECEPPA_PLUGIN_PATH . "/css/float.css"); ?></textarea>
	</blockquote>
	<br /><br />
	<div class="block-left">
	  <strong><?php _e('Display items as:', 'ceceppaml') ?></strong>
	  <blockquote>
	    <ul>
	      <li>
		<label>
		    <input type="radio" name="float-as" id="float-as" value="1" <?php checked(get_option("cml_show_float_items_as", 1), 1) ?> />
		    <?php _e('Flag + text.', 'ceceppaml') ?>
		</label>
	      </li>
	      <li>
		<label>
		    <input type="radio" name="float-as" id="float-as" value="2" <?php checked(get_option("cml_show_float_items_as", 1), 2) ?> />
		    <?php _e('Text only', 'ceceppaml') ?>
		</label>
	      </li>
	      <li>
		<label>
		    <input type="radio" name="float-as" id="float-as" value="3" <?php checked(get_option("cml_show_float_items_as", 1), 3) ?> />
		    <?php _e('Flag only', 'ceceppaml') ?>
		</label>
	      </li>
	    </ul>
	  </blockquote>
	</div>
	  <div class="block-right">
	    <strong><?php _e('Flag\'s size:', 'ceceppaml'); ?>:</strong>
	    <ul>
	      <li>
		<label>
		  <input type="radio" id="float-size" name="float-size" value="small" <?php checked(get_option("cml_show_float_items_size", "small"), "small"); ?> />
		  <img src="<?php echo $small ?>" />
		  <?php _e('Small', 'ceceppaml') ?> (32x23)
		</label>
	      </li>
	      <li>
		<label>
		  <input type="radio" id="float-size" name="float-size" value="tiny" <?php checked(get_option("cml_show_float_items_size", "small"), "tiny"); ?> />
		  <img src="<?php echo $tiny ?>" />
		  <?php _e('Tiny', 'ceceppaml') ?> (16x11)
		</label>
	      </li>
	    </ul>
	  </div>
	<br />
<?php } ?>

<?php function cml_options_flags_to_element() { ?>
<?php
global $wpCeceppaML;

$tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
$small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>
<!-- Append flag to element -->
	<blockquote>
	<label>
	    <?php _e('Id or class of element to add flags:', 'ceceppaml') ?>.
	    <input type="text" name="id-class" id="id-class" value="<?php echo get_option("cml_append_flags_to") ?>" />
	    <br /><i><?php _e('Don\'t forget to add # for id, or . for class', 'ceceppaml') ?></i>
	    <br /><br />
	    <div class="block-left">
	      <strong><?php _e('Display items as:', 'ceceppaml') ?></strong>
	      <blockquote>
		<ul>
		  <li>
		    <label>
			<input type="radio" name="show-items-as" id="show-items-as" value="1" <?php checked(get_option("cml_show_items_as", 1), 1) ?> />
			<?php _e('Flag + text.', 'ceceppaml') ?>
		    </label>
		  </li>
		  <li>
		    <label>
			<input type="radio" name="show-items-as" id="show-items-as" value="2" <?php checked(get_option("cml_show_items_as", 1), 2) ?> />
			<?php _e('Text only', 'ceceppaml') ?>
		    </label>
		  </li>
		  <li>
		    <label>
			<input type="radio" name="show-items-as" id="show-items-as" value="3" <?php checked(get_option("cml_show_items_as", 1), 3) ?> />
			<?php _e('Flag only', 'ceceppaml') ?>
		    </label>
		  </li>
		</ul>
	      </blockquote>
	    </div>
	  <div class="block-right">
	    <strong><?php _e('Flag\'s size:', 'ceceppaml'); ?>:</strong>
	    <ul>
	      <li>
		<label>
		  <input type="radio" id="item-as-size" name="item-as-size" value="small" <?php checked(get_option("cml_show_items_size", "small"), "small"); ?> />
		  <img src="<?php echo $small ?>" />
		  <?php _e('Small', 'ceceppaml') ?> (32x23)
		</label>
	      </li>
	      <li>
		<label>
		  <input type="radio" id="item-as-size" name="item-as-size" value="tiny" <?php checked(get_option("cml_show_items_size", "small"), "tiny"); ?> />
		  <img src="<?php echo $tiny ?>" />
		  <?php _e('Tiny', 'ceceppaml') ?> (16x11)
		</label>
	      </li>
	    </ul>
	  </div>
	</label>
	</blockquote>
<?php } ?>

<?php function cml_options_flags_to_menu() { ?>
<?php
global $wpCeceppaML;

$tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
$small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>
<!-- Add to menu -->
	<blockquote>
	  <strong><?php _e('Style:', 'ceceppaml') ?></strong>
	  <ul>
	    <li>
	      <label>
		  <input type="radio" name="add-as" id="add-as" value="1" <?php checked(get_option("cml_add_items_as", 1), 1) ?> />
		  <?php _e('Add an element for each language', 'ceceppaml') ?>
	      </label>
	    </li>
	    <li>
	      <label>
		  <input type="radio" name="add-as" id="add-as" value="2" <?php checked(get_option("cml_add_items_as", 1), 2) ?> />
		  <?php _e('Add elements in a submenu:', 'ceceppaml') ?>
	      </label>
	    </li>
	  </ul>
	  <div class="block-left">
	    <strong><?php _e('Display items as:', 'ceceppaml') ?></strong>
	    <blockquote>
	      <ul>
		<li>
		  <label>
		      <input type="radio" name="show-as" id="show-as" value="1" <?php checked(get_option("cml_show_in_menu_as", 1), 1) ?> />
		      <?php _e('Flag + text.', 'ceceppaml') ?>
		  </label>
		</li>
		<li>
		  <label>
		      <input type="radio" name="show-as" id="show-as" value="2" <?php checked(get_option("cml_show_in_menu_as", 1), 2) ?> />
		      <?php _e('Text only', 'ceceppaml') ?>
		  </label>
		</li>
		<li>
		  <label>
		      <input type="radio" name="show-as" id="show-as" value="3" <?php checked(get_option("cml_show_in_menu_as", 1), 3) ?> />
		      <?php _e('Flag only', 'ceceppaml') ?>
		  </label>
		</li>
	      </ul>
	    </blockquote>
	  </div>
	  <div class="block-right">
	    <strong><?php _e('Flag\'s size:', 'ceceppaml'); ?>:</strong>
	    <ul>
	      <li>
		<label>
		  <input type="radio" id="submenu-size" name="submenu-size" value="small" <?php checked(get_option("cml_show_in_menu_size", "small"), "small"); ?> />
		  <img src="<?php echo $small ?>" />
		  <?php _e('Small', 'ceceppaml') ?> (32x23)
		</label>
	      </li>
	      <li>
		<label>
		  <input type="radio" id="submenu-size" name="submenu-size" value="tiny" <?php checked(get_option("cml_show_in_menu_size", "small"), "tiny"); ?> />
		  <img src="<?php echo $tiny ?>" />
		  <?php _e('Tiny', 'ceceppaml') ?> (16x11)
		</label>
	      </li>
	    </ul>
	  </div>
	</blockquote>
<?php } ?>