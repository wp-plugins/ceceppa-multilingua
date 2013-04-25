      <div class="wrap">
	&nbsp;&nbsp; <h2><?php _e("Plugin configuration:", 'ceceppaml') ?></h2>

<!-- Reindirizzamento -->
    <form class="ceceppa-form" name="wrap" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=ceceppaml-options-page">
    <input type="hidden" name="options" value="1"  />
		<ul class="cml-settings">
			<li>
				<div class="CSSTableGenerator cml-options">
				<table id="ceceppaml-table">
					<tbody>
					<tr>
						<td></td>
						<td><?php _e('Actions', 'ceceppaml') ?></td>
					</tr>
					<tr>
					<td><center>
						<strong><?php _e('Url Modification mode:', 'ceceppaml') ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
					</center></td>
					<td>
						<label for="url-mode-path">
							<input type="radio" name="url-mode" id="url-mode-path" value="2" <?php checked(get_option("cml_modification_mode", 2), 2) ?> />
							<?php _e('Use Pre-Path Mode (Default, puts /en/ in front of URL)', 'ceceppaml') ?><i>(www.example.com/en/)</i>
						</label>
						<br />
						<label for="url-mode-domain">
							<input type="radio" name="url-mode" id="url-mode-domain" value="3" <?php checked(get_option("cml_modification_mode"), 3) ?> />
							<?php _e('Use Pre-Domain Mode', 'ceceppaml') ?><i>(en.example.com)</i>
						</label>
						<br /><br />
						<input type="checkbox" id="add-slug" name="add-slug" value="1" <?php checked(get_option('cml_add_slug_to_link', true), true) ?> />
						<label for="add-slug"><?php _e('Enabled', 'ceceppaml') ?></label><br />
					</td>
					</tr>
					<tr>
					<td><center>
						<strong><?php _e('Detect browser language:', 'ceceppaml') ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/directions.png" />
					</center></td>
					<td>
						<strong><em><?php _e('Detect browser language and:', 'ceceppaml') ?></em></strong>
						<blockquote>
							<input type="radio" id="redirect" name="redirect" value="auto" <?php echo ((get_option('cml_option_redirect', 'auto') == 'auto') ? 'checked' : '') ?> />
							<label for="redirect"><?php _e('Automatically redirects the browser depending on the user\'s language. <strong><em>Append the suffix &lang= to the home page</em></strong>', 'ceceppaml') ?></label><br>
							<input type="radio" id="no-redirect" name="redirect" value="nothing" <?php echo ((get_option('cml_option_redirect') == 'nothing') ? 'checked' : '') ?>/>
							<label for="no-redirect"><?php _e('Do nothing', 'ceceppaml') ?></label><br />
						</blockquote>
					</td>
					</tr>
				<tr>
					<td><center>
						<strong><?php _e('Show language\'s flag:', 'ceceppaml') ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
					</center></td>
					<td>
						<strong><?php _e('Show the list flag of available languages on:', 'ceceppaml') ?></strong>
						<blockquote>
							<input type="checkbox" id="flags-on-posts" name="flags-on-posts" value="1" <?php echo ((get_option('cml_option_flags_on_post', 'flags-on-posts') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-posts"><?php _e('Posts', 'ceceppaml') ?></label> <br />
							<input type="checkbox" id="flags-on-pages" name="flags-on-pages" value="1" <?php echo ((get_option('cml_option_flags_on_page', 'flags-on-pages') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-pages"><?php _e('Pages', 'ceceppaml') ?></label><br />
							<input type="checkbox" id="flags-on-cats" name="flags-on-cats" value="1" <?php echo ((get_option('cml_option_flags_on_cats', 'flags-on-cats') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-cats"><?php _e('Categories', 'ceceppaml') ?></label><br />
						</blockquote>
						<strong><?php _e('Where:', 'ceceppaml') ?></strong>
						<blockquote>
							<input type="radio" name="flags_on_pos" value="top" id="flags_on_top" <?php echo ((get_option('cml_option_flags_on_pos', 'top') == 'top') ? 'checked' : '') ?> />
							<label for="flags_on_top"><?php _e('On the top of page/post/category', 'ceceppaml') ?></label><br>
							<input type="radio" name="flags_on_pos" value="bottom" id="flags_on_bottom" <?php echo ((get_option('cml_option_flags_on_pos') == 'bottom') ? 'checked' : '') ?> />
							<label for="flags_on_bottom"><?php _e('On the bottom of post/page/category', 'ceceppaml') ?></label><br>
						</blockquote>
					</td>
				</tr>
				<tr>
<!-- Avviso -->
				<td rowspan="2"><center>
					<strong><?php _e('Show notice', 'ceceppaml') ?></strong><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/notice.png" />
				</center></td>
				<td><strong><em><?php _e('When the post/page/category that user is viewing is available, based on the information provided by the browser, in their its language:', 'ceceppaml') ?></em></strong>
					<blockquote>
						<input type="radio" id="show-notice" name="notice" value="notice" <?php echo ((get_option('cml_option_notice', 'notice') == 'notice') ? 'checked' : '') ?> />
						<label for="show-notice"><?php _e('Add notice to:', 'ceceppaml') ?></label><br>
					<blockquote>
						<input type="checkbox" id="notice-post" name="notice-post" value="1" <?php echo ((get_option('cml_option_notice_post', 'notice-post') == 1) ? 'checked' : '') ?> />
							<label for="notice-post"><?php _e('Posts', 'ceceppaml') ?></label> <br />
						<input type="checkbox" id="notice-page" name="notice-page" value="1" <?php echo ((get_option('cml_option_notice_page', 'notice-page') == 1) ? 'checked' : '') ?> />
							<label for="notice-page"><?php _e('Pages', 'ceceppaml') ?></label><br />
						<input type="checkbox" id="notice-cats" name="notice-cats" value="1" <?php echo ((get_option('cml_option_notice_cats', 'notice-cats') == 1) ? 'checked' : '') ?> />
							<label for="notice-cats"><?php _e('Categories', 'ceceppaml') ?></label><br />
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
							<strong><?php _e('Before:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_before" value="<?php echo stripslashes(get_option('cml_option_notice_before', '<h4 class=\'cml-notice\'>')) ?>" size="100" /><br />
							<strong><?php _e('After:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_after" value="<?php echo stripslashes(get_option('cml_option_notice_after', '</h4>')) ?>" size="100" /><br />
						</blockquote>
					</blockquote>
				</td>
				<tr>
<!-- Commenti -->
					<tr>
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
				<tr>
					<td colspan="2" style="text-align: center; font-size:110%"><strong><?php _e('Filters', 'ceceppaml') ?></strong></td>
				</tr>
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
					</blockquote>
				</td>
				</tr>
<!-- Filtra Post in base alla lingua -->
				<tr>
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
				<tr>
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
				</div>
				<br />
				<input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Save', 'ceceppaml') ?>" />
			</li>
			<li>
				<div id="donate" class="cml-donate">
				<h3><?php _e('Donate') ?></h3>
				<div class="content">
					<?php _e('If you like this plugin, please donate to support development and maintenance :)', 'ceceppaml') ?>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="E8X3DAVGNSD6E">
						<input type="image" src="https://www.paypalobjects.com/it_IT/IT/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.">
						<img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
			</li>
		</ul>
		</form>
</div>