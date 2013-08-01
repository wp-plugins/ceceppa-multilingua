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

class CeceppaMLWidgetRecentPosts extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'cececepml-recent-posts', // Base ID
      __('CML: Recent Posts', 'ceceppaml'), // Name
      array( 'description' => __('The most recent posts on your site'), ) // Args
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
      $count = $number + 20;

      /*
       * :O A cosa mi serve filtrare i post per lingua?
       * Non è stato fatto a caso.. :D
       * Mi serve richiamare la funzione in_array per evitare di trovarmi i "post in evidenza" dappertutto :O
       * Quando l'utente scegli di impostare un articolo come "in evidenza", wordpress fa si che tutte le chiamate tramite
       * WP_Query includa sempre questi post, quindi me li ritrovato in tutte le lingue con le relative traduzioni :'(...
       */
      $ids = $wpCeceppaML->get_posts_for_language();
      $the_args = array('post_status'=>'publish',
				      'post__in' => $ids,
				      'orderby' => 'post_date',
				      'order' => 'DESC',
				      'posts_per_page' => $number);

      $the_query = new WP_Query($the_args);

      $i = 1;
      echo "<ul>\n";
      while($the_query->have_posts()) :
	$the_query->next_post();

	if(in_array($the_query->post->ID, $ids)) :
	  echo '<li><a href="' . get_permalink($the_query->post->ID) . '" title="' . get_the_title($the_query->post->ID) . '">' . get_the_title($the_query->post->ID) . '</a></li>';

	  $i++;
	  if($i > $number) break;
	endif;
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
      __('CML: Language Chooser', 'ceceppaml'), // Name
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
    $classname = array_key_exists('classname', $instance) ? ($instance['classname']) : 'cml_widget_flag';

    echo $before_widget;
    if (!empty($title) && $hide_title != 1)
      echo $before_title . $title . $after_title;

    $display = $instance['display'];
    if(empty($display)) $display = "flag";

    $size = $instance['size'];

    if($display != "dropdown") :
      cml_show_flags($display, $size, $classname, "cml_widget_$display");
    else :
      $dd = intval($instance['msdropdown']);

      if($dd == 1) {
	wp_enqueue_style('ceceppaml-dd', '/wp-content/plugins/ceceppa-multilingua/css/dd.css');

	wp_enqueue_script('ceceppa-dd', WP_PLUGIN_URL . '/ceceppa-multilingua/js/jquery.dd.min.js', array('jquery'));
	wp_enqueue_script('ceceppa-widget', WP_PLUGIN_URL . '/ceceppa-multilingua/js/ceceppa.widget.js');
      }
      $this->_dropdown($args);
    endif;

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

    $dd = array_key_exists('msdropdown', $instance) ? $instance['msdropdown'] : null;
    $display = isset($instance['display']) ? $instance['display'] : "";
    $size = isset($instance['size']) ? $instance['size'] : "small";
    $hide_title = array_key_exists('hide-title', $instance) ? $instance['hide-title'] : 0;
    $classname = array_key_exists('classname', $instance) ? $instance['classname'] : 'cml_widget_flag';
?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>">
        <strong><?php _e('Title:'); ?></strong>
      </label> 
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      <br />
      <label for="<?php echo $this->get_field_id('hide-title'); ?>">
        <?php _e('Hide title:', 'ceceppaml') ?>
        <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" value="1" <?php checked($hide_title, 1) ?> />
      </label>
<!-- Visualizza -->
      <p>
        <label for="<?php echo $this->get_field_id('Visualizza'); ?>">
          <strong><?php _e('Show:'); ?></strong>
        </label>
      </p>
      <blockquote>
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
          <?php _e('Use the jQuery plugin "msDropDown (list)"', 'ceceppaml') ?>
				</label>
				</blockquote>
      </p>
      </blockquote>
      <br />
<!-- Dimensione bandiere -->
      <p>
        <label for="<?php echo $this->get_field_id('Dimensione bandiere'); ?>">
          <strong><?php _e('Flag\'s size:', 'ceceppaml'); ?></strong>
        </label>
      </p>
      <blockquote>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="small" <?php echo ($size == "small" || empty($size)) ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Small', 'ceceppaml') ?> (32x23)
        </label>
      </p>
      <p>
        <label>
          <input type="radio" id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" value="tiny" <?php echo ($size == "tiny") ? "checked=\"checked\"" : ""; ?>/>
          <?php _e('Tiny', 'ceceppaml') ?> (16x11)
        </label>
      </p>
      </blockquote>
<!-- Classe css -->
      <p>
        <label for="<?php echo $this->get_field_id('classname'); ?>">
          <strong><?php _e('Css ClassName:', 'ceceppaml'); ?></strong>
        </label>
      </p>
      <blockquote>
      <p>
          <input type="text" id="<?php echo $this->get_field_id('classname'); ?>" name="<?php echo $this->get_field_name('classname'); ?>" value="<?php echo $classname ?>" />
      </p>
      </blockquote>
<?php 
	}

	function _dropdown($args) {
		global $wpCeceppaML;

		cml_dropdown_langs("cml-widget-flags", $wpCeceppaML->get_current_lang_id(), true);
	}
};

class CeceppaMLWidgetText extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'cececepml-widget-text', // Base ID
      __('CML: Text', 'ceceppaml'), // Name
      array( 'description' => __('You can write arbitrary text or HTML separately for each language', 'ceceppaml'), ) // Args
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

    $lang_id = $wpCeceppaML->get_current_lang_id();

    $title = isset( $instance['title'] ) ? $instance['title'] : "";
    if( isset( $instance['title-' . $lang_id] ) ) $title = $instance['title-' . $lang_id]; 
    $title = apply_filters( 'widget_title', $title );

    echo $before_widget;
      echo $before_title . $title . $after_title;

      if(isset($instance['text-' . $lang_id]))
	echo $instance['text-' . $lang_id];

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
    $title = isset( $instance['title'] ) ? $instance['title'] : "";
    $langs = cml_get_languages(0);
?>
    <p>
    <?php foreach($langs as $lang) : ?>
    <?php 
      //per compatibilità con versioni precedenti, dove non permettevo l'inserimento di "titoli" multipli
      if( isset( $instance['title-' . $lang->id] ) ) $title = $instance['title-' . $lang->id]; 
    ?>
    <label>
      <?php _e('Title:'); ?>&nbsp;&nbsp;&nbsp;<img src="<?php echo cml_get_flag($lang->cml_flag) ?>" />&nbsp;<?php echo $lang->cml_language ?>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title-' . $lang->id ); ?>" name="<?php echo $this->get_field_name( 'title-' . $lang->id ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </label> 
    <?php endforeach; ?>
    <br />

    <!-- Testo per ogni lingua -->
    <br />
    <?php _e('Text:'); ?>
<?php
    foreach($langs as $lang) :
      $text = isset($instance['text-' . $lang->id]) ? $instance['text-' . $lang->id] : "";
?>
    <br />
    <label for="<?php echo $this->get_field_id('text-' . $lang->id); ?>">
      <strong><img src="<?php echo cml_get_flag($lang->cml_flag) ?>" />&nbsp;<?php echo $lang->cml_language ?>:</strong><br />
      <textarea id="<?php echo $this->get_field_id( 'text-' . $lang->id ); ?>" name="<?php echo $this->get_field_name('text-' . $lang->id); ?>" type="text" style="width: 100%; min-height: 80px"><?php echo $text; ?></textarea>
    </label> 
<?php endforeach; ?>
    <br />
<?php
  }
};

function unichr($u) {
    return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
}

function cml_load_textdomain() {
  load_plugin_textdomain('ceceppaml', false, dirname( plugin_basename( __FILE__ ) ) . '/po/');
}

add_action( 'widgets_init', 'cml_load_textdomain');
add_action( 'widgets_init', create_function( '', 'register_widget( "CeceppaMLWidgetChooser" );' ) );
add_action( 'widgets_init', create_function( '', 'register_widget( "CeceppaMLWidgetRecentPosts" );' ) );
add_action( 'widgets_init', create_function( '', 'register_widget( "CeceppaMLWidgetText" );' ) );
?>