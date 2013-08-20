<?php

add_meta_box("cml_donate_box", __('Donate:', 'ceceppaml'), 'cml_donate_box_content', "cml_donate_box");

function cml_donate_box_content() {
?>
    <div class="content">
	<?php _e('If you like this plugin, please donate to support development and maintenance :)', 'ceceppaml') ?>
	<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G22CM5RA4G4KG">
	  <img src="https://www.paypalobjects.com/it_IT/IT/i/btn/btn_donateCC_LG.gif" alt="PayPal - Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.">
	</a>
    </div>
<?php
}
?>
