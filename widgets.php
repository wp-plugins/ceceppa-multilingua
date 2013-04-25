<?php

class CeceppaMLWidgetRecentPosts extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'cececepml-recent-posts', // Base ID
      'CML: Recent Posts', // Name
      array( 'description' => __('The most recent posts on your site', 'ceceppaml'), ) // Args
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
    global $wpCeceppaML;

    extract($args);

    $title = apply_filters('widget_title', $instance['title'] );

    echo $before_widget;
      echo $before_title . $title . $after_title;

      $number = ($instance['number'] > 0) ? $instance['number'] : 10;
      
      $the_args = array('post_status'=>'publish',
				      'post__in' => $wpCeceppaML->get_language_posts(),
				      'orderby' => 'post_date',
				      'order' => 'ASC');
      $the_query = new WP_Query($the_args);

      echo "<ul>\n";
      while($the_query->have_posts()) :
	$the_query->next_post();

	echo '<li><a href="' . get_permalink($the_query->post->ID) . '" title="' . get_the_title($the_query->post->ID) . '">' . get_the_title($the_query->post->ID) . '</a></li>';
      endwhile;
      echo "</ul>\n";

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
  public function form($instance) {
    $title = isset($instance['title']) ? $instance['title'] : "";
    $number = isset($instance['number']) ? $instance['number'] : 0;
?>
    <p>
    <label for="<?php echo $this->get_field_id('title'); ?>">
      <?php _e('Title:'); ?>
    </label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    <br />
    <label for="<?php echo $this->get_field_id('number'); ?>">
      <?php _e('Number of posts to show:'); ?>
    </label> 
    <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3"/>
    <br />
<?php
  }
};

class CeceppaMLWidgetChooser extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'cececepml-chooser', // Base ID
      'CML: Language Chooser', // Name
      array( 'description' => __( 'Show the list of available languages', 'ceceppaml' ), ) // Args
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
    $hide_title = array_key_exists('hide-title', $instance) ? intval($instance['hide-title']) : 0;

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
        <?php _e('Title:'); ?>
      </label> 
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      <br />
      <label for="<?php echo $this->get_field_id('hide-title'); ?>">
        <?php _e('Hide title:', 'ceceppaml') ?>
        <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" value="1" <?php echo ($instance['hide-title'] == 1) ? "checked" : ""; ?> />
      </label>
<!-- Visualizza -->
      <p>
        <label for="<?php echo $this->get_field_id('Visualizza'); ?>">
          <?php _e('Show:'); ?>
        </label>
      </p>
      <p>
        <label>
					<input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="flag" <?php echo ($display == "flag" || empty($display)) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Flag', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="text" <?php echo ($display == "text") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Name of the language', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="both" <?php echo ($display == "both") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Flag and name', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('display'); ?>" name="<?php echo $this->get_field_name('display'); ?>" value="dropdown" <?php echo ($display == "dropdown") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('List', 'ceceppaml') ?>
        </label>
				<blockquote>
				<label>
          <input type="checkbox" id="<?php echo $this->get_field_id('msdropdown'); ?>" name="<?php echo $this->get_field_name('msdropdown'); ?>" value="1" <?php echo ($dd == 1) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Use the jQuery plugin "msDropDown"', 'ceceppaml') ?>
				</label>
				</blockquote>
      </p>
      <br />
<!-- Dimensione bandiere -->
      <p>
        <label for="<?php echo $this->get_field_id('Dimensione bandiere'); ?>">
          <?php _e('Flag\'s size:'); ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="small" <?php echo ($size == "small" || empty($size)) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Small (32x23)', 'ceceppaml') ?>
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="tiny" <?php echo ($size == "tiny") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Tiny (16x11)', 'ceceppaml') ?>
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
add_action( 'widgets_init', create_function( '', 'register_widget( "CeceppaMLWidgetRecentPosts" );' ) );
?>