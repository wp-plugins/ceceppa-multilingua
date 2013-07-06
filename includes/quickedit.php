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
    function set_inline_widget_set(widgetId, lang) {
        // revert Quick Edit menu so that it refreshes properly
        inlineEditPost.revert();
        var $select = jQuery('select.post_lang');
        console.log($select.options);
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
    $widget_id = get_post_meta( $post->ID, 'post_widget', TRUE); 
    $actions['inline hide-if-no-js'] = '<a href="#" class="editinline" title="';
    $actions['inline hide-if-no-js'] .= esc_attr( __( 'Edit this item inline' ) ) . '" ';
    $actions['inline hide-if-no-js'] .= " onclick=\"set_inline_widget_set('{$widget_id}', '{$lang}')\">"; 
    $actions['inline hide-if-no-js'] .= __( 'Quick&nbsp;Edit' );
    $actions['inline hide-if-no-js'] .= '</a>';

    return $actions;    
}
?>