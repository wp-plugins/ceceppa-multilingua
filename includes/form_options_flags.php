
<?php 

function cml_show_flags_on() {
  global $wpCeceppaML;

  $tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
  $small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>
  <blockquote>
    <div class="block-left">
      <strong><?php _e('Show the list flag of available languages on:', 'ceceppaml') ?></strong>
      <blockquote>
	  <input type="checkbox" id="flags-on-posts" name="flags-on-posts" value="1" <?php echo ((get_option('cml_option_flags_on_post', '1') == 1) ? 'checked' : '') ?> />
	  <label for="flags-on-posts"><?php _e('Posts', 'ceceppaml') ?></label> <br />
	  <input type="checkbox" id="flags-on-pages" name="flags-on-pages" value="1" <?php echo ((get_option('cml_option_flags_on_page', '1') == 1) ? 'checked' : '') ?> />
	  <label for="flags-on-pages"><?php _e('Pages', 'ceceppaml') ?></label><br />
	  <input type="checkbox" id="flags-on-custom" name="flags-on-custom" value="1" <?php echo ((get_option('cml_option_flags_on_custom_type', '0') == 1) ? 'checked' : '') ?> />
	  <label for="flags-on-custom"><?php _e('Custom posts type', 'ceceppaml') ?></label><br />
      <label>
        <input type="checkbox" id="flags-on-loop" name="flags-on-loop" value="1" <?php echo ( ( get_option( 'cml_option_flags_on_the_loop', '0' ) == 1 ) ? 'checked' : '' ) ?> />
        <?php _e( 'Loop', 'ceceppaml' ); ?>
      </label>
      </blockquote>
      <strong><?php _e('Where:', 'ceceppaml') ?></strong>
      <blockquote>
	  <input type="radio" name="flags_on_pos" value="top" id="flags_on_top" <?php echo ((get_option('cml_option_flags_on_pos', 'top') == 'top') ? 'checked' : '') ?> />
	  <label for="flags_on_top"><?php _e('On the top of page/post/category', 'ceceppaml') ?></label><br>
	  <input type="radio" name="flags_on_pos" value="bottom" id="flags_on_bottom" <?php echo ((get_option('cml_option_flags_on_pos') == 'bottom') ? 'checked' : '') ?> />
	  <label for="flags_on_bottom"><?php _e('On the bottom of post/page/category', 'ceceppaml') ?></label><br>
      </blockquote>
  </div>
    <div class="block-right">
      <strong><?php _e('Flags size:', 'ceceppaml'); ?>:</strong>
      <ul>
	<li>
	  <label>
	    <input type="radio" id="flag-size" name="flag-size" value="small" <?php checked(get_option("cml_option_flags_on_size", "small"), "small"); ?> />
	    <img src="<?php echo $small ?>" />
	    <?php _e('Small', 'ceceppaml') ?> (32x23)
	  </label>
	</li>
	<li>
	  <label>
	    <input type="radio" id="flag-size" name="flag-size" value="tiny" <?php checked(get_option("cml_option_flags_on_size", "small"), "tiny"); ?> />
	    <img src="<?php echo $tiny ?>" />
	    <?php _e('Tiny', 'ceceppaml') ?> (16x11)
	  </label>
	</li>
      </ul>
      </br />
      <label>
	<input type="checkbox" name="flags-translated-only" value="1" <?php checked( get_option( "cml_options_flags_on_translations", 0 ), 1 ) ?> >
	<strong><?php _e( 'Show flags only on translated page.', 'ceceppaml' ) ?></strong>
      </label>
    </div>
  </blockquote>
<?php } ?>

<?php function cml_option_flags_float() {
global $wpCeceppaML;

$tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
$small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>
<!-- Float css -->
	<blockquote>
	  <strong><?php _e('Customize css:', 'ceceppaml') ?></strong><br />
	  <textarea name="custom-css" rows="10" cols="100"><?php echo get_option( 'cml_float_css', file_get_contents( CECEPPA_PLUGIN_PATH . "/css/float.css" ) ); ?></textarea>
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
	  <div class="block-left">
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
	  </div>
	  <div class="block-right">
	    <?php _e( 'Add to', 'ceceppaml' ) ?>
	    <select name="cml_add_items_to">
	      <option value=""><?php _e( 'All menus', 'ceceppaml' ) ?></option>
	    <?php
	      $locations = get_nav_menu_locations();

	      $menu = array();
	      $sel = get_option( 'cml_add_items_to' );

	      foreach( $locations as $key => $location ) :
		echo '<option value="' . $key . '" ' . selected( $key, $sel ) . '>' . $key . '</option>';
	      endforeach;
	    ?>
	    </select>
	  </div>
	  <div>&nbsp;</div>
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