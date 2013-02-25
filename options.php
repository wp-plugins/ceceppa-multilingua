	<div class="wrap">
	&nbsp;&nbsp; <h2><?php _e("Configurazione plugin:", 'ceceppaml') ?></h2>

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
						<td>Azione</td>
					</tr>
					<tr>
					<td><center>
						<strong><?php _e('Rileva lingua browser:', 'ceceppaml') ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/directions.png" />
					</center></td>
					<td>
						<strong><em><?php _e('Rileva la lingua del browser e:', 'ceceppaml') ?></em></strong>
						<blockquote>
							<input type="radio" id="redirect" name="redirect" value="auto" <?php echo ((get_option('cml_option_redirect', 'auto') == 'auto') ? 'checked' : '') ?> />
							<label for="redirect"><?php _e('Reindirizza automaticamente il browser a seconda della lingua dell\'utente. <strong><em>Aggiunge il suffisso &lang= alla home del sito</em></strong>', 'ceceppaml') ?></label><br>
							<input type="radio" id="no-redirect" name="redirect" value="nothing" <?php echo ((get_option('cml_option_redirect') == 'nothing') ? 'checked' : '') ?>/>
							<label for="no-redirect"><?php _e('Non fare nulla', 'ceceppaml') ?></label><br />
						</blockquote>
					</td>
					</tr>
				<tr>
					<td><center>
						<strong><?php _e('Visualizza icona lingue:', 'ceceppaml') ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
					</center></td>
					<td>
						<strong><?php _e('Visualizza le bandiere delle lingue disponibili su:', 'ceceppaml') ?></strong>
						<blockquote>
							<input type="checkbox" id="flags-on-posts" name="flags-on-posts" value="1" <?php echo ((get_option('cml_option_flags_on_post', 'flags-on-posts') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-posts"><?php _e('Articoli', 'ceceppaml') ?></label> <br />
							<input type="checkbox" id="flags-on-pages" name="flags-on-pages" value="1" <?php echo ((get_option('cml_option_flags_on_page', 'flags-on-pages') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-pages"><?php _e('Pagine', 'ceceppaml') ?></label><br />
							<input type="checkbox" id="flags-on-cats" name="flags-on-cats" value="1" <?php echo ((get_option('cml_option_flags_on_cats', 'flags-on-cats') == 1) ? 'checked' : '') ?> />
							<label for="flags-on-cats"><?php _e('Categorie', 'ceceppaml') ?></label><br />
						</blockquote>
						<strong><?php _e('Dove:', 'ceceppaml') ?></strong>
						<blockquote>
							<input type="radio" name="flags_on_pos" value="top" id="flags_on_top" <?php echo ((get_option('cml_option_flags_on_pos', 'top') == 'top') ? 'checked' : '') ?> />
							<label for="flags_on_top"><?php _e('All\'inizio dell\'articolo/categoria/pagina', 'ceceppaml') ?></label><br>
							<input type="radio" name="flags_on_pos" value="bottom" id="flags_on_bottom" <?php echo ((get_option('cml_option_flags_on_pos') == 'bottom') ? 'checked' : '') ?> />
							<label for="flags_on_bottom"><?php _e('Alla fine dell\'articolo/categoria/pagina', 'ceceppaml') ?></label><br>
						</blockquote>
					</td>
				</tr>
				<tr>
<!-- Avviso -->
				<td rowspan="2"><center>
					<strong><?php _e('Visualizza avviso', 'ceceppaml') ?></strong><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/notice.png" />
				</center></td>
				<td><strong><em><?php _e('Quando l\'articolo/pagina/categoria che l\'utente st&agrave; visualizzando &egrave; disponibile, in base alle informazioni fornite dal browser, nella sua lingua:', 'ceceppaml') ?></em></strong>
					<blockquote>
						<input type="radio" id="show-notice" name="notice" value="notice" <?php echo ((get_option('cml_option_notice', 'notice') == 'notice') ? 'checked' : '') ?> />
						<label for="show-notice"><?php _e('Aggiungi avviso a:', 'ceceppaml') ?></label><br>
					<blockquote>
						<input type="checkbox" id="notice-post" name="notice-post" value="1" <?php echo ((get_option('cml_option_notice_post', 'notice-post') == 1) ? 'checked' : '') ?> />
							<label for="notice-post"><?php _e('Articoli', 'ceceppaml') ?></label> <br />
						<input type="checkbox" id="notice-page" name="notice-page" value="1" <?php echo ((get_option('cml_option_notice_page', 'notice-page') == 1) ? 'checked' : '') ?> />
							<label for="notice-page"><?php _e('Pagine', 'ceceppaml') ?></label><br />
						<input type="checkbox" id="notice-cats" name="notice-cats" value="1" <?php echo ((get_option('cml_option_notice_cats', 'notice-cats') == 1) ? 'checked' : '') ?> />
							<label for="notice-cats"><?php _e('Categorie', 'ceceppaml') ?></label><br />
					</blockquote>
					<input type="radio" id="no-notice" name="notice" value="nothing" <?php echo ((get_option('cml_option_notice') == 'nothing') ? 'checked' : '') ?>/>
							<label for="no-notice"><?php _e('Ignora', 'ceceppaml') ?></label><br />
					</blockquote>
				</td>
					</tr>
					<tr>
				<td>
<!-- Avviso: dove -->
					<strong><em><?php _e('Imposta dove visualizzare l\'avviso', 'ceceppaml') ?></strong></em>
					<blockquote>
							<input type="radio" name="notice_pos" value="top" id="notice_top" <?php echo ((get_option('cml_option_flags_on_pos', 'top') == 'top') ? 'checked' : '') ?> />
							<label for="notice_top"><?php _e('All\'inizio dell\'articolo/categoria/pagina', 'ceceppaml') ?></label><br>
							<input type="radio" name="notice_pos" value="bottom" id="notice_bottom" <?php echo ((get_option('cml_option_flags_on_pos') == 'bottom') ? 'checked' : '') ?> />
							<label for="notice_bottom"><?php _e('Alla fine dell\'articolo/categoria/pagina', 'ceceppaml') ?></label><br>
						<blockquote>
							<br /><strong><em><?php _e('Personalizza avviso:', 'ceceppaml') ?></em></strong><br /><br />
							<strong><?php _e('Prima:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_before" value="<?php echo stripslashes(get_option('cml_option_notice_before', '<h4 class=\'cml-notice\'>')) ?>" size="100" /><br />
							<strong><?php _e('Dopo:', 'ceceppaml') ?></strong>&nbsp;<input type="text" name="notice_after" value="<?php echo stripslashes(get_option('cml_option_notice_after', '</h4>')) ?>" size="100" /><br />
						</blockquote>
					</blockquote>
				</td>
				<tr>
<!-- Commenti -->
					<tr>
					<td><center>
						<strong><?php _e('Commenti', 'ceceppaml'); ?></strong><br /><br />
						<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/comments.png" />
					</center></td>
					<td>
						<input type="radio" id="group" name="comments" value="group" <?php echo ((get_option('cml_option_comments', 'group') == 'group') ? 'checked' : '') ?> />
						<label for="group"><?php _e('Raggruppa', 'ceceppaml') ?>&nbsp;<i>(<?php _e('Visualizza per l\'articolo i commenti disponibili in ogni lingua', 'ceceppaml'); ?>)</i></label><br>
						<input type="radio" id="no-group" name="comments" value="" <?php echo ((get_option('cml_option_comments') == '') ? 'checked' : '') ?> />
						<label for="no-group"><?php _e('Separati', 'ceceppaml') ?>&nbsp;<i>(<?php _e('Ogni articolo visualizza solo i propri commenti', 'ceceppaml');?>)</i></label><br>
					</td>
					</tr>
				<tr>
					<td colspan="2" style="text-align: center; font-size:110%"><strong><?php _e('Filtri', 'ceceppaml') ?></strong></td>
				</tr>
<!-- Applica locale wordpress in base alla bandierina -->
				<tr>
				<td><center>
					<strong><?php _e('Cambia lingua Wordpress', 'ceceppaml') ?></strong><br /><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/flags.png" />
				</center></td>
				<td>
					<strong><?php _e('Imposta la lingua di wordpress, e dei plugin tradotti, in base alla lingua selezionata', 'ceceppaml') ?></strong>
					<blockquote>
						<input id="change-locale" type="checkbox" value="1" name="change-locale" <?php echo ((get_option('cml_option_change_locale', 1) == 1) ? 'checked' : '') ?> />
						<label for="change-locale"><?php _e('Attiva') ?></label>
					</blockquote>
				</td>
				</tr>
<!-- Filtra Post in base alla lingua -->
				<tr>
				<td><center>
					<strong><?php _e('Filtra articoli', 'ceceppaml') ?></strong><br /><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/posts.png" />
				</center></td>
				<td>
					<strong><?php _e('Visualizza solo gli articoli inerenti alla lingua corrente', 'ceceppaml') ?></strong>
					<blockquote>
						<input id="filter-posts" type="checkbox" value="1" name="filter-posts" <?php echo ((get_option('cml_option_filter_posts', 1) == 1) ? 'checked' : '') ?> />
						<label for="filter-posts"><?php _e('Attiva') ?></label>
					</blockquote>
				</td>
				</tr>
<!-- Filtra query -->
				<tr>
				<td><center>
					<strong><?php _e('Filtra query', 'ceceppaml') ?></strong><br /><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/filter.png" />
				</center></td>
				<td>
					<strong><?php _e('Permette di filtrare il risultato di alcuni widget, in modo da visualizzare solo i record inerenti alla lingua corrente.', 'ceceppaml') ?></strong>
					<br />
					<br /><?php _e('Widget supportati:', 'ceceppaml') ?>
					<ul>
						<li><?php _e('Articoli pi&ugrave; letti', 'ceceppaml') ?></li>
						<li><?php _e('Articoli pi&ugrave; commentati', 'ceceppaml') ?></li>
					</ul>
					<blockquote>
						<input id="filter-query" type="checkbox" value="1" name="filter-query" <?php echo ((get_option('cml_option_filter_query') == 1) ? 'checked' : '') ?> />
						<label for="filter-query"><?php _e('Attiva') ?></label>
					</blockquote>
				</td>
				</tr>
				<tr>
<!-- Filtra ricerca -->
				<td><center>
					<strong><?php _e('Filtra ricerca', 'ceceppaml') ?></strong><br /><br /><br />
					<img src="<?php echo WP_PLUGIN_URL ?>/ceceppa-multilingua/images/search.png" />
				</center></td>
				<td>
					<strong><?php _e('Permette di filtrare il risultato della ricerca, visualizzando solo gli articoli inerenti alla lingua corrente.', 'ceceppaml') ?></strong>
					<blockquote>
						<labelf for="filter-form"><strong><?php _e('Form html class:') ?></strong></label>
						<input id="filter-form" type="text" name="filter-form" value="<?php echo get_option('cml_option_filter_form_class', 'searchform') ?>" />
						<br /><br />
						<input id="filter-search" type="checkbox" value="1" name="filter-search" <?php echo ((get_option('cml_option_filter_search') == 1) ? 'checked' : '') ?> />
						<label for="filter-search"><?php _e('Attiva') ?></label>
					</blockquote>
				</td>
				</tr>

					</tbody>
				</table>
				</div>
				<br />
				<input type="submit" class="ceceppa-salva" name="action" value="<?php _e('Salva', 'ceceppaml') ?>" />
			</li>
			<li>
				<div id="donate" class="cml-donate">
				<h3><?php _e('Fai una donazione') ?></h3>
				<div class="content">
					<?php _e('Se hai gradito <strong>"Ceceppa Multilingua"</strong>, sostieni con una donazione il suo sviluppo e il suo mantenimento :)', 'ceceppaml') ?>
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