<?php
/* Define the custom box */
$option_page = null;

add_action('admin_menu', 'test_add_option_page');

function test_add_option_page() {
  global $option_page;

  $option_page = add_menu_page('Ceceppa ML Options', __('Ceceppa Test', 'ceceppaml'), 'administrator', 'ceceppaml-test-page', 'cml_test_option');
  add_meta_box("yourplugin_helloworld", __('Say Hello', 'yourplugin'), "yourplugin_helloworld_meta_box", "yourplugin");
  
  myplugin_add_custom_box();
}

add_action( 'add_meta_boxes', 'myplugin_add_custom_box' );

/* Adds a box to the main column on the Post and Page edit screens */
function myplugin_add_custom_box() {
  global $option_page;

  $screens = array( 'post', 'page' );
    foreach ($screens as $screen) {
        add_meta_box(
            'myplugin_sectionid',
            __( 'My Post Section Title', 'myplugin_textdomain' ),
            'myplugin_inner_custom_box',
            $option_page
        );
    }
}

/* Prints the box content */
function myplugin_inner_custom_box( $post ) {

  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_noncename' );

  // The actual fields for data entry
  // Use get_post_meta to retrieve an existing value from the database and use the value for the form
  $value = get_post_meta( $post->ID, '_my_meta_value_key', true );
  echo '<label for="myplugin_new_field">';
       _e("Description for this field", 'myplugin_textdomain' );
  echo '</label> ';
  echo '<input type="text" id="myplugin_new_field" name="myplugin_new_field" value="'.esc_attr($value).'" size="25" />';
}

/* When the post is saved, saves our custom data */
function myplugin_save_postdata( $post_id ) {

  // First we need to check if the current user is authorised to do this action. 
  if ( 'page' == $_POST['post_type'] ) {
    if ( ! current_user_can( 'edit_page', $post_id ) )
        return;
  } else {
    if ( ! current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // Secondly we need to check if the user intended to change this value.
  if ( ! isset( $_POST['myplugin_noncename'] ) || ! wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename( __FILE__ ) ) )
      return;

  // Thirdly we can save the value to the database

  //if saving in a custom table, get post_ID
  $post_ID = $_POST['post_ID'];
  //sanitize user input
  $mydata = sanitize_text_field( $_POST['myplugin_new_field'] );

  // Do something with $mydata 
  // either using 
  add_post_meta($post_ID, '_my_meta_value_key', $mydata, true) or
    update_post_meta($post_ID, '_my_meta_value_key', $mydata);
  // or a custom table (see Further Reading section below)
}

function cml_test_option() {
  global $option_page;

  add_meta_box(
      'myplugin_sectionid',
      __( 'My Post Section Title', 'myplugin_textdomain' ),
      'myplugin_inner_custom_box',
      $option_page, 'normal', 'high'
  );
  do_meta_boxes($option_page,'normal',null);

  do_meta_boxes('yourplugin','advanced',null);
}

function yourplugin_helloworld_meta_box(){
?>
Hello, world!
<?php
}

?>