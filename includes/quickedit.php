<?php
/* http://shibashake.com/wordpress-theme/expand-the-wordpress-quick-edit-menu */
//Funzioni necessarie per modificare la lingua nel quick edit box
add_action('admin_footer', 'cml_quick_edit_javascript');
 
function cml_quick_edit_javascript() {
    global $current_screen;
//     if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return; 
     
    ?>
    <script type="text/javascript">
    <!--
    function set_inline_widget_set(widgetId, lang, keys, values) {
        // revert Quick Edit menu so that it refreshes properly
        inlineEditPost.revert();
        var $select = jQuery('select.post_lang');
        
        // check option manually
        $select.children().map(function() {
	  var $this = jQuery(this);
	  var val = $this.val();

	  if(val == lang) {
	    $this.attr('selected', true);
	  } else {
	    $this.removeAttr('selected', true);
	  }
        });
        
        //Json
        $keys = keys.split( "," );
        $values = values.split( "," );

        $selects = jQuery( 'select.cml_linked_post' );
        $selects.each( function() {
	  $select = jQuery( this );

	  //Remove selecte attributes
	  $select.find( 'option' ).removeAttr( 'selected', true );
	  
	  //Recupero il nome della lingua
	  var name = $select.attr( 'name' );
	  var key_id = $keys.indexOf( name.replace( "linked_", "" ) );
	  if( key_id > 0 ) {
	    var post_id = $values[ key_id ];

	    // check option manually
	    $select.children().map(function() {
	      var $this = jQuery(this);
	      var val = $this.val();

	      if( val == post_id ) {
		$this.attr('selected', true);
	      } else {
		$this.removeAttr('selected', true);
	      }
	    });
	  }
        });
      }
    //-->
    </script>
    <?php
}

// Add to our admin_init function
add_filter('post_row_actions', 'cml_expand_quick_edit_link', 10, 2);
add_filter('page_row_actions', 'cml_expand_quick_edit_link', 10, 2);
 
function cml_expand_quick_edit_link($actions, $post) {
    global $current_screen;

//     if (($current_screen->id != 'edit-post') || ($current_screen->post_type != 'post')) return $actions; 

    $lang = get_option("cml_page_lang_{$post->ID}");
    
    //json encode doesn't works: "Uncaught SyntaxError: Unexpected token ILLEGAL " -.-"
//     $linked = json_encode( $posts  );
    $posts = cml_get_linked_posts( $post->ID );
    if( is_array( $posts ) ) $posts = $posts[ 'indexes' ];
    $keys = join(",", array_keys( $posts ) );
    $vals = join(",", array_values( $posts ) );

    $widget_id = get_post_meta( $post->ID, 'post_widget', TRUE); 
    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
    $actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '" ';
    $actions['inline hide-if-no-js'] .= " onclick=\"set_inline_widget_set('{$widget_id}', '{$lang}', '{$keys}', '{$vals}')\">"; 
    $actions['inline hide-if-no-js'] .= __( 'Quick&nbsp;Edit' );
    $actions['inline hide-if-no-js'] .= '</a>';

    return $actions;    
}
?>