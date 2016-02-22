## 0.1.0 First Release
#
<?php

if( function_exists('acf_add_options_page') ) {
	acf_add_options_page(array(
		'page_title' 	=> 'Admin Settings',
		'menu_title'	=> 'Admin Settings',
		'menu_slug' 	=> 'admin-general-settings',
		'capability'	=> 'manage_options',
		'redirect'		=> false
	));

	/*acf_add_options_sub_page(array(
		'page_title' 	=> 'General',
		'menu_title'	=> 'General',
		'parent_slug'	=> 'theme-general-settings',
	));*/
}

/**
 * Show/Hide row actions on edit screen post/page/cpt
*/
if ( !is_super_admin() ) add_filter( 'post_row_actions', 'apl_settings__remove_row_actions', 10, 1 );
function apl_settings__remove_row_actions( $actions ) {
  if ($row_actions__edit = get_field('post_row_actions', 'option')) :
  	//var_dump($row_actions__edit);
    foreach ( $row_actions__edit as $key=>$action ) {
      unset( $actions[$action] );
    }
  endif;
  return $actions;
}


/**
 * Set posts per page on edit post/page/cpt screen
*/
if ( !is_super_admin() ) add_action ('admin_init','apl_settings__set_posts_per_page', 199);
function apl_settings__set_posts_per_page () {

  $args = array (
      'public'    =>  true,
      '_builtin'  =>  false
  );

  $post_types = get_post_types($args, 'names', 'and');
  $post_types['post'] = 'post';
  $post_types['page'] = 'page';

  $posts_per_page = get_field( 'posts_per_page', 'option' );
  foreach ($post_types as $key => $post_types) {
    add_filter( 'get_user_option_edit_'.$post_types.'_per_page', create_function( '', 'return '.$posts_per_page.';' ), 10, 3 );
  }

}

/**
 * Remove the Help tab
 */
if ( !is_super_admin() && get_field('contextual_help', 'option') ) add_filter( 'contextual_help', 'apl_settings__remove_help_tabs', 999, 3 );
function apl_settings__remove_help_tabs($old_help, $screen_id, $screen){
    $screen->remove_help_tabs();
    return $old_help;
}

/**
 * Remove the Screen Option tab
 */
if ( !is_super_admin() && get_field('screen_options_show_screen', 'option') ) add_filter('screen_options_show_screen', '__return_false');


/**
 * Custom Admin Bar – Account Menu
 */
add_action( 'admin_bar_menu', 'apl_settings__admin_bar_my_custom_account_menu', 11 );
function apl_settings__admin_bar_my_custom_account_menu( $wp_admin_bar ) {

  $user_id = get_current_user_id();
  $current_user = wp_get_current_user();
  $profile_url = get_edit_profile_url( $user_id );

  $howdy = ( get_field('admin_bar__howdy', 'option') ) ? get_field('admin_bar__howdy', 'option') : '';

  if ( 0 != $user_id ) {
    /* Add the "My Account" menu */
    $avatar = get_avatar( $user_id, 28 );
    $howdy = sprintf( __($howdy.' %1$s'), $current_user->display_name );
    $class = empty( $avatar ) ? '' : 'with-avatar';

    $wp_admin_bar->add_menu( array(
      'id' => 'my-account',
      'parent' => 'top-secondary',
      'title' => $howdy . $avatar,
      'href' => $profile_url,
      'meta' => array(
      'class' => $class,
      ),
    ) );

  }
}


/**
 * Remove dashboard widgets
 *
 *  action: apl_settings__load_post_meta_boxes_dashboard()
 *  filter: apl_settings__acf_load_field_dashboard_widgets()
 *  action: apl_settings__remove_dashboard_widgets()
 */
add_action( 'wp_dashboard_setup' , 'apl_settings__load_post_meta_boxes_dashboard' , 10000 );
function apl_settings__load_post_meta_boxes_dashboard() {
  global $current_screen;
  //$capability = $this->get_plugin_cap();

  //if( !empty( $current_screen ) && $current_screen->id == 'dashboard' && current_user_can( $capability ) ) {
  if( !empty( $current_screen ) && $current_screen->id == 'dashboard' ) {
    global $wp_meta_boxes;

    $post_type = 'dashboard';
    $metaboxes = $wp_meta_boxes[$post_type];

    $update = array();

    if( !empty( $metaboxes ) ) {
      foreach( $metaboxes as $context => $meta_box ) {
        foreach( $meta_box as $priority => $box ) {
          if( is_array( $box ) ) {
            foreach( $box as $metabox_id => $b ) {
              $update["metaboxes"][$post_type][$context][$priority][$b["id"]] = strip_tags( $b["title"] );
            }
          }
        }
      }
    }

    if( !empty( $update ) ) {
      update_option( "apl__regist_dashboard_metabox" , $update );
    }

  }

}


add_filter('acf/load_field/name=remove_dashboard_meta', 'apl_settings__acf_load_field_dashboard_widgets');
function apl_settings__acf_load_field_dashboard_widgets( $field ) {

  // Welcome widget
  $field['choices']['welcome'] = 'Welcome';

  // All registered widgets
  $dashboard_metaboxes = get_option( 'apl__regist_dashboard_metabox' );

  foreach( $dashboard_metaboxes['metaboxes']['dashboard'] as $context => $meta_box ) {
    foreach( $meta_box as $priority => $box ) {
      if( is_array( $box ) ) {
        foreach( $box as $metabox_id => $b ) {
          $field['choices'][$metabox_id.';'.$priority.';'.$context] = $b;
        }
      }
    }
  }

  return $field;

}

add_action('wp_dashboard_setup', 'apl_settings__remove_dashboard_widgets', 20000);
function apl_settings__remove_dashboard_widgets() {

  $widgets = get_field( 'remove_dashboard_meta' , 'option' );

  if ( !empty( $widgets ) ) :

    foreach ($widgets as $key => $widget) {

      if ( $widget == 'welcome' ) {
        remove_action('welcome_panel', 'wp_welcome_panel');
      } else {
        $meta_box = explode (';', $widget);
        //[0] = metabox id
        //[1] = priority
        //[2] = context
        remove_meta_box($meta_box[0], 'dashboard', $meta_box[2]);
      }
    }
  endif;
}
/*
 */

/**
* Remove the WordPress Logo from the Toolbar
*/
if ( !is_super_admin() ) add_action( 'admin_bar_menu', 'apl_settings__remove_wp_logo', 999 );
function apl_settings__remove_wp_logo( $wp_admin_bar ) {
	if ( get_field( 'remove_wp_logo', 'option' ) ) $wp_admin_bar->remove_node( 'wp-logo' );
  if ( get_field( 'remove_view_item', 'option' ) ) $wp_admin_bar->remove_node( 'view' );
}


/**
* Remove Header Meta
*/
remove_action( 'wp_head', 'feed_links_extra', 3 ); // Display the links to the extra feeds such as category feeds
remove_action( 'wp_head', 'feed_links', 2 ); // Display the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'rsd_link' ); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action( 'wp_head', 'wlwmanifest_link' ); // Display the link to the Windows Live Writer manifest file.
remove_action( 'wp_head', 'index_rel_link' ); // index link
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); // prev link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); // start link
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 ); // Display relational links for the posts adjacent to the current post.
remove_action( 'wp_head', 'wp_generator' ); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action( 'wp_head', 'wp_shortlink_wp_head'); // Remove the shortlink
remove_action( 'wp_head', 'print_emoji_detection_script', 7 ); // Remove emoji detection script
remove_action( 'wp_print_styles', 'print_emoji_styles' ); // Remove emoji detection script
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' ); // Remove emoji detection script from admin
remove_action( 'admin_print_styles', 'print_emoji_styles' ); // Remove emoji detection script from admin

/**
* Remove admin bar from front-end only
*/
add_filter( 'show_admin_bar', 'apl_settings__hide_admin_bar_from_front_end' );
function apl_settings__hide_admin_bar_from_front_end(){
 if (is_blog_admin()) {
   return true;
 }
 remove_action( 'wp_head', '_admin_bar_bump_cb' );
 return false;
}
