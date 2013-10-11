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

$tab = isset( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 0;

$tiny = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "tiny");
$small = cml_get_flag_by_lang_id($wpCeceppaML->get_default_lang_id(), "small");
?>

<div class="wrap">
  <div class="icon32">
    <img src="<?php echo CECEPPA_PLUGIN_URL ?>images/logo.png" height="24"/>
  </div>
  <h2 class="nav-tab-wrapper">
    <?php if( isset($_GET['page']) && $_GET['page'] == "ceceppaml-flags-page" ) : ?>
    <a class="nav-tab <?php echo $tab == 0 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-options-page&tab=0"><?php _e('Flags', 'ceceppaml') ?></a>
    <?php else: 
      if( $tab < 1 ) $tab = 1 ?>
    <a class="nav-tab <?php echo $tab == 1 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-options-page&tab=1"><?php _e('Actions', 'ceceppaml') ?></a>
    <a class="nav-tab <?php echo $tab == 2 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-options-page&tab=2"><?php _e('Filters', 'ceceppaml') ?></a>
    <a class="nav-tab <?php echo $tab == 3 ? "nav-tab-active" : "" ?>" href="?page=ceceppaml-options-page&tab=3"><?php _e('Advanced', 'ceceppaml') ?></a>
    <?php endif; ?>
  </h2>

  <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-2">
    <div id="post-body-content">
    <form class="ceceppa-form-options" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $_GET['page'] ?>">
    <?php wp_nonce_field('cml_edit_settings','cml_nonce_edit_settings'); ?>
    <input type="hidden" name="options" value="1"  />
    <input type="hidden" name="tab" value="<?php echo $tab ?>"  />
	<div class="cml-options">
	<?php 
	
	  if($tab == 0) : 
	    require_once('form_options_flags.php');

	    do_meta_boxes('cml_options_page_flags','advanced',null);
	  endif;
	  
	  
	  if ( $tab == 3 ) :
	    require_once( 'form_options_advanced.php' );
	  endif;
	?>

<!--  -->
<!-- AZIONI -->
<!--  -->
<?php if($tab == 1) : ?>
	<table id="ceceppaml-table" class="widefat">
	    <tbody>
<!-- Detect -->
	    <tr>
	    <td><center>
		<strong><?php _e('Detect browser language:', 'ceceppaml') ?></strong><br /><br />
		<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/directions.png" />
	    </center></td>
	    <td>
		<strong><em><?php _e('Detect browser language and:', 'ceceppaml') ?></em></strong>
		<blockquote>
		    <input type="radio" id="redirect" name="redirect" value="auto" <?php echo ((get_option('cml_option_redirect', 'auto') == 'auto') ? 'checked' : '') ?> />
		    <label for="redirect">
		      <?php _e('Automatically redirects the browser depending on the user\'s language.', 'ceceppaml'); ?>
		    </label>
		    <br /><br />
		    <label>
		      <input type="radio" id="no-redirect" name="redirect" value="nothing" <?php echo ((get_option('cml_option_redirect') == 'nothing') ? 'checked' : '') ?>/>
		      <?php _e('Do nothing', 'ceceppaml') ?>
		    </label>
		    <br />
		</blockquote>
	    </td>
	    </tr>
<!-- Url -->
	    <tr class="alternate">
	    <td><center>
		<strong><?php _e('Url Modification mode:', 'ceceppaml') ?></strong><br /><br />
		<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
	    </center></td>
	    <td>
		<label for="url-mode-path">
		    <input type="radio" name="url-mode" id="url-mode-path" value="2" <?php checked(get_option("cml_modification_mode", 2), 2) ?> />
		    <?php _e('Use Pre-Path Mode (Default, puts /en/ in front of URL)', 'ceceppaml') ?><i>(www.example.com/en/)</i><br />
		    <b style="padding-left:20px"><?php _e('This mode doesn\'t work with default permalink!!!', 'ceceppaml'); ?></b> (?p=##)
		</label>
		<br /><br />
		<label for="url-mode-domain">
		    <input type="radio" name="url-mode" id="url-mode-domain" value="3" <?php checked(get_option("cml_modification_mode"), 3) ?> />
		    <?php _e('Use Pre-Domain Mode', 'ceceppaml') ?><i>(en.example.com)</i>
		</label>
		<br /><br />
		<label>
		  <input type="radio" id="url-mode" name="url-mode" value="1" <?php checked( get_option( 'cml_modification_mode' ), 1 ); ?> />&nbsp;<?php _e('Append the suffix <strong>&amp;lang=</strong> to the url', 'ceceppaml') ?>
		</label>
		<br /><br />
		<label>
		  <input type="radio" id="url-mode" name="url-mode" value="0" <?php checked( get_option('cml_modification_mode'), 0 ); ?> />&nbsp;<?php _e('None', 'ceceppaml') ?>
		</label>
		<br /><br />
	    </td>
	    </tr>
<!-- Categorie -->
	    <tr>
	    <td id="cats-tags" ><center>
		<strong><?php _e('Categories & Tags', 'ceceppaml'); ?></strong><br /><br />
		<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/category.png" />
	    </center></td>
	    <td>
		<br />
		<input type="checkbox" id="categories" name="categories" value="1" <?php checked(get_option('cml_option_translate_categories'), true) ?> />
		<label for="categories"><?php _e('Translate the url for categories', 'ceceppaml') ?>&nbsp;</label><br>
	    </td>
	    </tr>
<!-- Translate menu -->
	    <tr>
	    <td id="cats-tags" style="vertical-align: middle">
		<div id="icon-themes" class="icon32" style=""></div>
		<strong style="line-height: 50px;"><?php _e( 'Translate menu', 'ceceppaml' ); ?></strong>
		</td>
	    <td>
		<br />
			<label>
			<input type="checkbox" id="action-menu" name="action-menu" value="1" <?php checked(get_option('cml_option_action_menu', true), true) ?> />
			<?php _e( 'Translate menu items', 'ceceppaml' ) ?>
			</label>
			<br /><br />
			<label>
				<input type="checkbox" id="menu-hide-items" name="menu-hide-items" value="1" <?php checked(get_option('cml_option_menu_hide_items', false), true) ?> />
				<?php _e( 'Hide items that doesn\'t exists in current language', 'ceceppaml' ) ?>
			</label>
			<br /><br />
	    </td>
	    </tr>


<!-- Avviso -->
	<td rowspan="2">
	  <center>
	    <strong><?php _e('Show notice', 'ceceppaml') ?></strong><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/notice.png" />
	  </center>
	</td>
	<td>
	    <strong><em><?php _e('When the post/page/category that user is viewing is available, based on the information provided by the browser, in their its language:', 'ceceppaml') ?></em></strong>
	    <blockquote>
	      <input type="radio" id="show-notice" name="notice" value="notice" <?php echo ((get_option('cml_option_notice', 'notice') == 'notice') ? 'checked' : '') ?> />
	      <label for="show-notice"><?php _e('Add notice to:', 'ceceppaml') ?></label><br>
	    <blockquote>
	      <input type="checkbox" id="notice-post" name="notice-post" value="1" <?php echo ((get_option('cml_option_notice_post', 'notice-post') == 1) ? 'checked' : '') ?> />
	      <label for="notice-post"><?php _e('Posts', 'ceceppaml') ?></label> <br />
	      <input type="checkbox" id="notice-page" name="notice-page" value="1" <?php echo ((get_option('cml_option_notice_page', 'notice-page') == 1) ? 'checked' : '') ?> />
	      <label for="notice-page"><?php _e('Pages', 'ceceppaml') ?></label><br />
	    </blockquote>
	      <input type="radio" id="no-notice" name="notice" value="nothing" <?php echo ((get_option('cml_option_notice') == 'nothing') ? 'checked' : '') ?>/>
	      <label for="no-notice"><?php _e('Ignore', 'ceceppaml') ?></label><br />
	    </blockquote>
	</td>
      </tr>
      <tr>
	<td>
<!-- Avviso: dove -->
	    <strong><em><?php _e('Setting where to show the alert', 'ceceppaml') ?></strong></em>
	    <blockquote>
		    <input type="radio" name="notice_pos" value="top" id="notice_top" <?php echo ((get_option('cml_option_flags_on_pos', 'top') == 'top') ? 'checked' : '') ?> />
		    <label for="notice_top"><?php _e('On the top of page/post/category', 'ceceppaml') ?></label><br>
		    <input type="radio" name="notice_pos" value="bottom" id="notice_bottom" <?php echo ((get_option('cml_option_flags_on_pos') == 'bottom') ? 'checked' : '') ?> />
		    <label for="notice_bottom"><?php _e('On the bottom of page/post/category', 'ceceppaml') ?></label><br>
		<blockquote>
		    <br /><strong><em><?php _e('Cusomize notice:', 'ceceppaml') ?></em></strong><br /><br />
		    <strong><?php _e('Before:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_before" value="<?php echo stripslashes( esc_html ( get_option('cml_option_notice_before', '<h4 class=\'cml-notice\'>') ) ) ?>" size="100" /><br />
		    <strong><?php _e('After:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_after" value="<?php echo stripslashes(get_option('cml_option_notice_after', '</h4>')) ?>" size="100" /><br />
		</blockquote>
	    </blockquote>
	</td>
	<tr>
<!-- Commenti -->
	    <tr class="alternate">
	    <td><center>
		<strong><?php _e('Comments', 'ceceppaml'); ?></strong><br /><br />
		<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/comments.png" />
	    </center></td>
	    <td>
		<input type="radio" id="group" name="comments" value="group" <?php echo ((get_option('cml_option_comments', 'group') == 'group') ? 'checked' : '') ?> />
		<label for="group"><?php _e('Group', 'ceceppaml') ?>&nbsp;<i>(<?php _e('View the comments for this article are available in each language', 'ceceppaml'); ?>)</i></label><br>
		<input type="radio" id="no-group" name="comments" value="" <?php echo ((get_option('cml_option_comments') == '') ? 'checked' : '') ?> />
		<label for="no-group"><?php _e('Ungroup', 'ceceppaml') ?>&nbsp;<i>(<?php _e('Each post show only own comments', 'ceceppaml');?>)</i></label><br>
	    </td>
	    </tr>
	  </table>
<? elseif($tab == 2) : ?>
	  <table id="ceceppaml-table" class="widefat">
<!-- Applica locale wordpress in base alla bandierina -->
	<tr>
	<td><center>
	    <strong><?php _e('Change wordpress language', 'ceceppaml') ?></strong><br /><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
	</center></td>
	<td>
	    <strong><?php _e('Set the language of wordpress, in according of selected language', 'ceceppaml') ?></strong>
	    <blockquote>
		<input id="change-locale" type="checkbox" value="1" name="change-locale" <?php echo ((get_option('cml_option_change_locale', 1) == 1) ? 'checked' : '') ?> />
		<label for="change-locale"><?php _e('Enable') ?></label>
		<blockquote>
		<label>
		  <input type="checkbox" name="change-locale-admin" value="1" <?php echo checked( get_option( 'cml_option_change_locale_admin', 1 ), 1 ) ?> />
		  <?php _e( 'change language also in the admin panel', 'ceceppaml' ); ?>
		</label>
	    </blockquote>
	</td>
	</tr>
<!-- Filtra Post in base alla lingua -->
	<tr class="alternate">
	<td><center>
	    <strong><?php _e('Filter posts', 'ceceppaml') ?></strong><br /><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/posts.png" />
	</center></td>
	<td>
	    <strong><?php _e('Show only posts of the current language', 'ceceppaml') ?></strong>
	    <blockquote>
		<input id="filter-posts" type="checkbox" value="1" name="filter-posts" <?php echo ((get_option('cml_option_filter_posts', 0) == 1) ? 'checked' : '') ?> />
		<label for="filter-posts"><?php _e('Enable') ?></label>
	    </blockquote>
	</td>
	</tr>
<!-- Filtra Post tradotti -->
	<tr>
	<td><center>
	    <strong><?php _e('Filter translations', 'ceceppaml') ?></strong><br /><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/posts.png" />
	</center></td>
	<td>
	    <strong><?php _e('Hide translations of posts of the current language', 'ceceppaml') ?></strong>
	    <blockquote>
		<input id="filter-translations" type="checkbox" value="1" name="filter-translations" <?php echo ((get_option('cml_option_filter_translations', 0) == 1) ? 'checked' : '') ?> />
		<label for="filter-translations"><?php _e('Enable') ?></label>
	    </blockquote>
	</td>
	</tr>
<!-- Filtra query -->
	<tr class="alternate">
	<td><center>
	    <strong><?php _e('Filter query', 'ceceppaml') ?></strong><br /><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/filter.png" />
	</center></td>
	<td>
	    <strong><?php _e('Allows you to filter the result of some widgets to display only those records relating to the current language.', 'ceceppaml') ?></strong>
	    <br />
	    <br /><?php _e('Supported widgets::', 'ceceppaml') ?>
	    <ul>
		<li><?php _e('Least reads posts', 'ceceppaml') ?></li>
		<li><?php _e('Most commented', 'ceceppaml') ?></li>
	    </ul>
	    <blockquote>
		<input id="filter-query" type="checkbox" value="1" name="filter-query" <?php echo ((get_option('cml_option_filter_query') == 1) ? 'checked' : '') ?> />
		<label for="filter-query"><?php _e('Enable') ?></label>
	    </blockquote>
	</td>
	</tr>
	<tr>
<!-- Filtra ricerca -->
	<td><center>
	    <strong><?php _e('Filter search', 'ceceppaml') ?></strong><br /><br /><br />
	    <img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/search.png" />
	</center></td>
	<td>
	    <strong><?php _e('Allows you to filter the result of the search for show only the posts relating to the current language.', 'ceceppaml') ?></strong>
	    <blockquote>
		<labelf for="filter-form"><strong><?php _e('Form html class:') ?></strong></label>
		<input id="filter-form" type="text" name="filter-form" value="<?php echo get_option('cml_option_filter_form_class', 'searchform') ?>" />
		<br /><br />
		<input id="filter-search" type="checkbox" value="1" name="filter-search" <?php echo ((get_option('cml_option_filter_search') == 1) ? 'checked' : '') ?> />
		<label for="filter-search"><?php _e('Enable') ?></label>
	    </blockquote>
	</td>
	</tr>
	    </tbody>
	</table>
<?php endif; ?>
	  <?php submit_button() ?>
      </div>
    </form>
  </div>
  
<!-- DONATE   -->
  <div id="postbox-container-1" class="postbox-container cml-donate">
    <?php do_meta_boxes('cml_donate_box','advanced',null); ?>
  </div>
  </div>
  </div>
</div>
