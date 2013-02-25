<?php

class CeceppaMLWidgetChooser extends WP_Widget {
  public function __construct() {
		parent::__construct(
	 		'cececepml-chooser', // Base ID
			'Ceceppa Multilingua Language Chooser', // Name
			array( 'description' => __( 'Mostra l\'elenco delle lingue disponibili', 'ceceppaml' ), ) // Args
		);
  }

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		extract($args);
    
    //Aggiungo lo stile ;)
    wp_enqueue_style('ceceppaml-widget-style');

		$title = apply_filters('widget_title', $instance['title'] );
    $hide_title = intval($instance['hide-title']);

		echo $before_widget;
		if (!empty($title) && $hide_title != 1)
			echo $before_title . $title . $after_title;

    $display = $instance['display'];
    if(empty($display)) $display = "flag";

    $size = $instance['size'];
		if($display != "dropdown") {
			cml_show_flags($display, $size, true, "cml_widget_$display");
		} else {
			$dd = intval($instance['msdropdown']);

			if($dd == 1) {
	      wp_enqueue_style('ceceppaml-dd', '/wp-content/plugins/ceceppa-multilingua/css/dd.css');

		    wp_enqueue_script('ceceppa-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.dd.min.js', array('jquery'));
				wp_enqueue_script('ceceppa-widget', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.widget.js');
			}
			$this->_dropdown($args);
		}

		echo $after_widget;
	}

  	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance['title'] = strip_tags( $new_instance['title'] );

		return $new_instance;
	}
  
	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
    $title = "";

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
    
		$dd = $instance['msdropdown'];
    $display = $instance['display'];
    $size = $instance['size'];
		?>
		<p>
      <label for="<?php echo $this->get_field_id('title'); ?>">
        <?php _e('Titolo:'); ?>
      </label> 
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      <br />
      <label for="<?php echo $this->get_field_id('hide-title'); ?>">
        <?php _e('Nascondi titolo:', 'ceceppaml') ?>
        <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" value="1" <?php echo ($instance['hide-title'] == 1) ? "checked" : ""; ?> />
      </label>
<!-- Visualizza -->
      <p>
        <label for="<?php echo $this->get_field_id('Visualizza'); ?>">
          <?php _e('Visualizza:'); ?>
        </label>
      </p>
      <p>
        <label>
					<input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="flag" <?php echo ($display == "flag" || empty($display)) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Bandiera', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="text" <?php echo ($display == "text") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Nome della lingua', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="both" <?php echo ($display == "both") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Nome e bandiera', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="dropdown" <?php echo ($display == "dropdown") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Elenco', 'ceceppaml') ?>
        </label>
				<blockquote>
				<label>
          <input type="checkbox" id="<?php echo $this->get_field_id('msdropdown'); ?>" name="<?php echo $this->get_field_name('msdropdown'); ?>" value="1" <?php echo ($dd == 1) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Utilizza il plugin msDropDown', 'ceceppaml') ?>
				</label>
				</blockquote>
      </p>
      <br />
<!-- Dimensione bandiere -->
      <p>
        <label for="<?php echo $this->get_field_id('Dimensione bandiere'); ?>">
          <?php _e('Dimensione bandiere:'); ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="small" <?php echo ($size == "small" || empty($size)) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Piccole (32x23)', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="tiny" <?php echo ($size == "tiny") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Minuscole (16x11)', 'ceceppaml') ?>
        </label>
      </p>

		</p>
		<?php 
	}
	
	function _dropdown($args) {
		global $wpCeceppaML;

		cml_dropdown_langs("cml-widget-flags", $wpCeceppaML->get_current_lang_id(), true);
	}
};

add_action( 'widgets_init', create_function( '', 'register_widget( "CeceppaMLWidgetChooser" );' ) );
?>