<?php   
/* 
Plugin Name: Evergreen Post Tweeter
Plugin URI: http://www.leavingworkbehind.com/evergreen-post-tweeter/
Description: Evergreen Post Tweeter enables you to automatically tweet out links to old posts based upon a tag or tags.
Author: Tom Ewer
Version: 1.8.8
Author URI: http://www.leavingworkbehind.com/about-me/
*/  



register_activation_hook( __FILE__, 'as_ept_install' );

function as_ept_install() {
	$admin_url = site_url('/wp-admin/options-general.php?page=Evergreen');
	add_option( 'as_number_tweet', '1', '', 'yes' ); 
	add_option( 'as_post_type', 'Post', '', 'yes' ); 
	add_option( 'next_tweet_time', '0', '', 'yes' ); 
	update_option( 'ept_opt_admin_url', $admin_url, '', 'yes' );
}

/* Display a notice that can be dismissed */

add_action('admin_notices', 'ept_admin_notice');

function ept_admin_notice() {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, 'ept_ignore_notice') ) {
        echo '<div class="updated"><p>'; 
        printf( __('Evergreen Post Tweeter has been upgraded with a brand new scheduling feature. You need to set a new schedule via the settings screen before tweets will be published. ') );
        echo "</p><p>";
        printf( __('<a class="button" href="%1$s">Go to EPT Settings Page</a> or <a class="button" href="%2$s">Hide Notice</a>'), site_url('/wp-admin/options-general.php?page=Evergreen'), '?ept_nag_ignore=0' );
        echo "</p></div>";
    }
}

add_action('admin_init', 'ept_nag_ignore');

function ept_nag_ignore() {
    global $current_user;
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['ept_nag_ignore']) && '0' == $_GET['ept_nag_ignore'] ) {
         add_user_meta($user_id, 'ept_ignore_notice', 'true', true);
         wp_redirect( site_url('/wp-admin/options-general.php?page=Evergreen'), '302' );
    }
}

add_action( 'admin_init', 'register_ept_settings' );
function register_ept_settings() {
	
	wp_register_style( 'as-countdown-style', plugins_url( 'countdown/jquery.countdown.css', __FILE__) );
	wp_enqueue_style( 'as-countdown-style' );

    /* Register our script. */
    wp_register_script( 'ept_plugin_script_countdown', plugins_url( 'countdown/jquery.countdown.min.js', __FILE__ ) );
    // don't finish
    // wp_register_script( 'ept_plugin_script_app', plugins_url( 'countdown/ept-app.js', __FILE__) );
}

function ept_admin_scripts() {
    /* Link our already registered script to a page */
    wp_enqueue_script( 'ept_plugin_script_countdown' );
    wp_enqueue_script( 'ept_plugin_script_app', plugins_url( 'countdown/ept-app.js', __FILE__), array( 'jquery', 'ept_plugin_script_countdown' ), 1, true );
    wp_enqueue_script( 'ept_plugin_application', plugins_url( 'js/application.js', __FILE__ ), array( 'jquery' ) );
    $next_tweet_time = get_option('next_tweet_time');
    wp_localize_script( 'ept_plugin_script_app', 'nextTweetTime', array('tweetTime' => $next_tweet_time) );
}

require_once('ept-admin.php');
require_once('ept-core.php');

define('ept_opt_1_HOUR', 60*60);
define('ept_opt_2_HOURS', 2*ept_opt_1_HOUR);
define('ept_opt_4_HOURS', 4*ept_opt_1_HOUR);
define('ept_opt_8_HOURS', 8*ept_opt_1_HOUR);
define('ept_opt_6_HOURS', 6*ept_opt_1_HOUR); 
define('ept_opt_12_HOURS', 12*ept_opt_1_HOUR); 
define('ept_opt_24_HOURS', 24*ept_opt_1_HOUR); 
define('ept_opt_48_HOURS', 48*ept_opt_1_HOUR); 
define('ept_opt_72_HOURS', 72*ept_opt_1_HOUR); 
define('ept_opt_168_HOURS', 168*ept_opt_1_HOUR); 
define('ept_opt_INTERVAL', 4);
define('ept_opt_INTERVAL_SLOP', 4);
define('ept_opt_AGE_LIMIT', 0); // 0 days
define('ept_opt_MAX_AGE_LIMIT', 0); // 0 days
define('ept_opt_OMIT_CATS', "");
define('ept_opt_TWEET_PREFIX',"");
define('ept_opt_ADD_DATA',"false");
define('ept_opt_URL_SHORTENER',"is.gd");
define('ept_opt_HASHTAGS',"");

$admin_url = site_url('/wp-admin/options-general.php?page=Evergreen');
define('ept_opt_admin_url',$admin_url);

global $ept_db_version;
$ept_db_version = "1.0";

function ept_admin_actions() {  
    $page_hook_suffix = add_submenu_page(
                            'options-general.php', 
                            "Evergreen Post Tweeter Settings", 
                            "Evergreen Post Tweeter", 
                            1, 
                            "Evergreen", 
                            "ept_admin"
                        );
    // add_action('admin_print_scripts-' . $page_hook_suffix, 'ept_admin_scripts');
}

add_action('admin_menu', 'ept_admin_actions');  
add_action('admin_head', 'ept_opt_head_admin');
add_action('init','ept_tweet_old_post');
add_action('admin_init','ept_authorize',1);
add_action( 'admin_enqueue_scripts', 'ept_enqueue' );

function ept_enqueue( $hook )
{
    if ( 'settings_page_Evergreen' != $hook ) {
        return;
    }

    ept_admin_scripts();
}
        
function ept_authorize()
{
     

    if ( isset( $_REQUEST['oauth_token'] ) ) {
	    $auth_url= str_replace('oauth_token', 'oauth_token1', ept_currentPageURL());
		$ept_url = get_option('ept_opt_admin_url') . substr($auth_url,strrpos($auth_url, "page=Evergreen") + strlen("page=Evergreen"));
        echo '<script language="javascript">window.open ("'.$ept_url.'","_self")</script>';
        
        die;
    }

}
        
add_filter('plugin_action_links', 'ept_plugin_action_links', 10, 2);

function ept_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        // The "page" query string value must be equal to the slug
        // of the Settings admin page we defined earlier, which in
        // this case equals "myplugin-settings".
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=Evergreen">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

/* Display a notice that can be dismissed */
add_action('admin_notices', 'ept_second_admin_notice');
function ept_second_admin_notice() {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, '_ept_lwb_plugin') ) {
        echo '<div class="updated"><p>'; 
        printf( __('Thank you for installing Evergreen Post Tweeter! It is the brainchild of Tom Ewer, the founder of') );
        printf( __(' <a href="%1$s">Leaving Work Behind</a> '), 'http://www.leavingworkbehind.com/?utm_source=plugins&utm_medium=banner&utm_campaign=plugins' );
        printf( __('-- a community for people who want to build successful online businesses.') );
        printf( __('<br/><br/><a class="button" href="%1$s">Dismiss</a> '), '?ept_lwb_dismiss=0' );
        echo "</p></div>";
    }
}

add_action('admin_init', 'ept_lwb_dismiss');
function ept_lwb_dismiss() {
    global $current_user;
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['ept_lwb_dismiss']) && '0' == $_GET['ept_lwb_dismiss'] ) {
        add_user_meta($user_id, '_ept_lwb_plugin', 'true', true);
    } else if ( isset($_GET['ept_lwb_dismiss']) && '1' == $_GET['ept_lwb_dismiss'] ) {
        delete_user_meta($user_id, '_ept_lwb_plugin');
    }
}
