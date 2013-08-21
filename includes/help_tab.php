<?php
if( !isset( $_GET['page'] ) ) return;

$tab = isset( $_GET['tab'] ) ? intval( $_GET['tab'] ) : 0;

switch( $_GET['page'] ) {
  case 'ceceppaml-language-page':
    if( $tab == 0 ) :
      $title = __( 'Add new language', 'ceceppaml' );
      $content  = __( 'Choose the language that you want to add.', 'ceceppaml' ) . '<br />' . __( 'Customize the name of the language.', 'ceceppaml' ) . '<br />';
      $content .= __( '<b>"Post notice"</b> and <b>"Page notice"</b> are displayed when the post/page is available in the visitor\'s page.', 'ceceppaml' );
      $content .= __( 'Customize them if you want, or leave it blank (no message will be shown)', 'ceceppaml' );
      $content .= __( 'Save changes', 'ceceppaml' );
    else:
      $title = __( 'Language files', 'ceceppaml' );
      $content  = __( 'Download wordpress language file for standard themes and admin interfaces', 'ceceppaml' ) . '<br />';
      $content .= __( 'If download fails, means that wordpress interfaces isn\'t available in that language, anyhow the plugin will works correctly', 'ceceppaml' ) . " ;)";
      $content .= '<br />';
    endif;

    break;
  case 'ceceppaml-flags-page':
    $title = __( 'Show flags', 'ceceppaml' );
    $content = __( 'Show flags on your site withouth use the widget', 'ceceppaml' );
    
    break;
  case 'ceceppaml-translations-plugins-themes':
    $title = __( 'Translate theme', 'ceceppaml' );
    $content = __( 'Works only if has been localized using the the GNU gettext framework.', 'ceceppaml' );
    break;
  default:
    return;
}
$screen = get_current_screen();

// Add my_help_tab if current screen is My Admin Page
$screen->add_help_tab( array(
  'id'	=> 'test_help_tab',
  'title'	=> $title,
  'content'	=> '<p>' . $content . '</p>',
) );
?>