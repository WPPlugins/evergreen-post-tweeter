<?php
require_once('evergreen.php');
require_once('ept-core.php');
require_once( 'Include/ept-oauth.php' );
require_once('ept-xml.php');
require_once( 'Include/ept-debug.php' );


function ept_admin() {
    //check permission
    if (current_user_can('manage_options')) 
    {
        $message = null;
        $message_updated = __("Evergreen Post Tweeter options have been updated!", 'Evergreen');
        $ept_after_update = get_option( 'ept_after_update' );
        if ( $ept_after_update ) {
            print('
                <div id="message" class="updated fade">
                    <p>' . __('Evergreen Post Tweeter Options Updated.', 'Evergreen') . '</p>
                </div>');
            update_option( 'ept_after_update', false );
        }
        $response = null;
        $save = true;
        $settings = ept_get_settings();

        //on authorize
        if (isset($_GET['EPT_oauth'])) {
            global $ept_oauth;

            $result = $ept_oauth->get_access_token($settings['oauth_request_token'], $settings['oauth_request_token_secret'], $_GET['oauth_verifier']);

            if ($result) {
                $settings['oauth_access_token'] = $result['oauth_token'];
                $settings['oauth_access_token_secret'] = $result['oauth_token_secret'];
                $settings['user_id'] = $result['user_id'];
                $settings['screen_name'] = $result['screen_name'];

                $result = $ept_oauth->get_user_info($result['screen_name'], $settings['oauth_access_token'], $settings['oauth_access_token_secret']);
                if ($result) {
                    $settings['profile_image_url'] = $result['profile_image_url'];
                    $settings['screen_name'] = $result['screen_name'];
                    if (isset($result['location'])) {
                        $settings['location'] = $result['location'];
                    } else {
                        $settings['location'] = false;
                    }
                }
                
                ept_save_settings($settings);
                echo '<script language="javascript">window.open ("' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=Evergreen","_self")</script>';

                die;
            }
        }
        //on deauthorize
        else if (isset($_GET['top']) && $_GET['top'] == 'deauthorize') {
            $settings = ept_get_settings();
            $settings['oauth_access_token'] = '';
            $settings['oauth_access_token_secret'] = '';
            $settings['user_id'] = '';
            $settings['tweet_queue'] = array();

            ept_save_settings($settings);
            echo '<script language="javascript">window.open ("' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=Evergreen","_self")</script>';
            die;
        }
         else if (isset($_GET['top']) && $_GET['top'] == 'reset') {
              print('
			<div id="message" class="updated fade">
				<p>' . __("All settings have been reset. Please update the settings for Evergreen Post Tweeter to start tweeting again.", 'Evergreen') . '</p>
			</div>');
         }
        //check if username and key provided if bitly selected
        if (isset($_POST['ept_opt_url_shortener'])) {
            if ($_POST['ept_opt_url_shortener'] == "bit.ly") {

                //check bitly username
                if (!isset($_POST['ept_opt_bitly_user'])) {
                    print('
			<div id="message" class="updated fade">
				<p>' . __('Please enter your bit.ly username.', 'Evergreen') . '</p>
			</div>');
                    $save = false;
                }
                //check bitly key
                elseif (!isset($_POST['ept_opt_bitly_key'])) {
                    print('
			<div id="message" class="updated fade">
				<p>' . __('Please enter your bit.ly API Key.', 'Evergreen') . '</p>
			</div>');
                    $save = false;
                }
                //if both the good to save
                else {
                    $save = true;
                }
            }
        }

        //if submit and if bitly selected its fields are filled then save
        if (isset($_POST['submit']) && $save) {
            $message = $message_updated;
            
			//
            if (isset($_POST['as_number_tweet'])) {
				if($_POST['as_number_tweet']>0 && $_POST['as_number_tweet']<=10){
					update_option('as_number_tweet', $_POST['as_number_tweet']);
				}elseif($_POST['as_number_tweet']>10){
					update_option('as_number_tweet', 10);
				}else{
					update_option('as_number_tweet', 1);
				}
            }
			if (isset($_POST['as_post_type'])) {
                update_option('as_post_type', $_POST['as_post_type']);
            }

            //set category to tweet
            if (isset($_POST['ept_cat_tweet'])) {
                update_option('ept_cat_tweet', implode(',', $_POST['ept_cat_tweet']));
            } else {
                update_option('ept_cat_tweet', '');
            }

            //set tag to tweet
            if (isset($_POST['ept_tag_tweet'])) {
                update_option('ept_tag_tweet', implode(',', $_POST['ept_tag_tweet']));
            } else {
                update_option('ept_tag_tweet', '');
            }
			
            //TOP admin URL (current url)
            if (isset($_POST['ept_opt_admin_url'])) {
                update_option('ept_opt_admin_url', $_POST['ept_opt_admin_url']);
            }
            
            //what to tweet 
            update_option('ept_opt_tweet_type', 'title');

            //additional data
            if (isset($_POST['ept_opt_add_text'])) {
                update_option('ept_opt_add_text', $_POST['ept_opt_add_text']);
            }

            //place of additional data
            if (isset($_POST['ept_opt_add_text_at'])) {
                update_option('ept_opt_add_text_at', $_POST['ept_opt_add_text_at']);
            }

            //include link
            if (isset($_POST['ept_opt_include_link'])) {
                update_option('ept_opt_include_link', $_POST['ept_opt_include_link']);
            }

            //fetch url from custom field?
            if (isset($_POST['ept_opt_custom_url_option'])) {
                update_option('ept_opt_custom_url_option', true);
            } else {

                update_option('ept_opt_custom_url_option', false);
            }

            //custom field to fetch URL from 
            // if (isset($_POST['ept_opt_custom_url_field'])) {
            //     update_option('ept_opt_custom_url_field', $_POST['ept_opt_custom_url_field']);
            // } else {

            //     update_option('ept_opt_custom_url_field', '');
            // }
            
            //use URL tracking?
            if (isset($_POST['ept_opt_use_url_tracking'])) {
                update_option('ept_opt_use_url_tracking', true);
            } else {
                update_option('ept_opt_use_url_tracking', false);
            }

            //use URL shortner?
            if (isset($_POST['ept_opt_use_url_shortner'])) {
                update_option('ept_opt_use_url_shortner', true);
            } else {
                update_option('ept_opt_use_url_shortner', false);
            }

            //url shortener to use
            if (isset($_POST['ept_opt_url_shortener'])) {
                update_option('ept_opt_url_shortener', $_POST['ept_opt_url_shortener']);
                if ($_POST['ept_opt_url_shortener'] == "bit.ly") {
                    if (isset($_POST['ept_opt_bitly_user'])) {
                        update_option('ept_opt_bitly_user', $_POST['ept_opt_bitly_user']);
                    }
                    if (isset($_POST['ept_opt_bitly_key'])) {
                        update_option('ept_opt_bitly_key', $_POST['ept_opt_bitly_key']);
                    }
                }
            }

            //hashtags option
            if (isset($_POST['ept_opt_custom_hashtag_option'])) {
                update_option('ept_opt_custom_hashtag_option', $_POST['ept_opt_custom_hashtag_option']);
            } else {
                update_option('ept_opt_custom_hashtag_option', "nohashtag");
            }

            //use inline hashtags
            if (isset($_POST['ept_opt_use_inline_hashtags'])) {
                update_option('ept_opt_use_inline_hashtags', true);
            } else {
                update_option('ept_opt_use_inline_hashtags', false);
            }

             //hashtag length
            if (isset($_POST['ept_opt_hashtag_length'])) {
                update_option('ept_opt_hashtag_length', $_POST['ept_opt_hashtag_length']);
            } else {
                update_option('ept_opt_hashtag_length', 0);
            }
            
            //custom field name to fetch hashtag from 
            if (isset($_POST['ept_opt_custom_hashtag_field'])) {
                update_option('ept_opt_custom_hashtag_field', $_POST['ept_opt_custom_hashtag_field']);
            } else {
                update_option('ept_opt_custom_hashtag_field', '');
            }

            //default hashtags for tweets
            if (isset($_POST['ept_opt_hashtags'])) {
                update_option('ept_opt_hashtags', $_POST['ept_opt_hashtags']);
            } else {
                update_option('ept_opt_hashtags', '');
            }

            //minimum post age to tweet
            if (isset($_POST['ept_opt_age_limit'])) {
                if (is_numeric($_POST['ept_opt_age_limit']) && $_POST['ept_opt_age_limit'] >= 0) {
                    update_option('ept_opt_age_limit', $_POST['ept_opt_age_limit']);
                } else {
                    update_option('ept_opt_age_limit', "0");
                }
            }

            //maximum post age to tweet
            if (isset($_POST['ept_opt_max_age_limit'])) {
                if (is_numeric($_POST['ept_opt_max_age_limit']) && $_POST['ept_opt_max_age_limit'] > 0) {
                    update_option('ept_opt_max_age_limit', $_POST['ept_opt_max_age_limit']);
                } else {
                    update_option('ept_opt_max_age_limit', "0");
                }
            }

            //use Pause Tweet?
            if (isset($_POST['ept_opt_use_pause_tweet'])) {
                update_option('ept_opt_use_pause_tweet', true);
            } else {

                update_option('ept_opt_use_pause_tweet', false);
            }

            //pause auto tweet - start time
            if (isset($_POST['ept_opt_start_pause_time'])) {
                if ($_POST['ept_opt_start_pause_time'] != "") {
                    update_option('ept_opt_start_pause_time', $_POST['ept_opt_start_pause_time']);
                } else {
                    update_option('ept_opt_start_pause_time', "22:00");
                }
            }

            //pause auto tweet - end time
            if (isset($_POST['ept_opt_end_pause_time'])) {
                if ($_POST['ept_opt_end_pause_time'] != "") {
                    update_option('ept_opt_end_pause_time', $_POST['ept_opt_end_pause_time']);
                } else {
                    update_option('ept_opt_end_pause_time', "05:00");
                }
            }

            // set schedule day - monday
            if (isset($_POST['ept_opt_schedule_day_mon'])) {
                update_option('ept_opt_schedule_day_mon', $_POST['ept_opt_schedule_day_mon']);
            } else {
                update_option('ept_opt_schedule_day_mon', "");
            }

            // set schedule day - tuesday
            if (isset($_POST['ept_opt_schedule_day_tue'])) {
                update_option('ept_opt_schedule_day_tue', $_POST['ept_opt_schedule_day_tue']);
            } else {
                update_option('ept_opt_schedule_day_tue', "");
            }

            // set schedule day - wednesday
            if (isset($_POST['ept_opt_schedule_day_wed'])) {
                update_option('ept_opt_schedule_day_wed', $_POST['ept_opt_schedule_day_wed']);
            } else {
                update_option('ept_opt_schedule_day_wed', "");
            }

            // set schedule day - thursday
            if (isset($_POST['ept_opt_schedule_day_thu'])) {
                update_option('ept_opt_schedule_day_thu', $_POST['ept_opt_schedule_day_thu']);
            } else {
                update_option('ept_opt_schedule_day_thu', "");
            }

            // set schedule day - friday
            if (isset($_POST['ept_opt_schedule_day_fri'])) {
                update_option('ept_opt_schedule_day_fri', $_POST['ept_opt_schedule_day_fri']);
            } else {
                update_option('ept_opt_schedule_day_fri', "");
            }

            // set schedule day - saturday
            if (isset($_POST['ept_opt_schedule_day_sat'])) {
                update_option('ept_opt_schedule_day_sat', $_POST['ept_opt_schedule_day_sat']);
            } else {
                update_option('ept_opt_schedule_day_sat', "");
            }

            // set schedule day - sunday
            if (isset($_POST['ept_opt_schedule_day_sun'])) {
                update_option('ept_opt_schedule_day_sun', $_POST['ept_opt_schedule_day_sun']);
            } else {
                update_option('ept_opt_schedule_day_sun', "");
            }

            // set schedule time - counter
            if (isset( $_POST['ept_opt_schedule_times_counter'] ) &&
                isset( $_POST['ept_opt_schedule_times_hour'] ) &&
                isset( $_POST['ept_opt_schedule_times_minute'] )
                ) {
                $ept_schedule_times_counter = $_POST['ept_opt_schedule_times_counter'];
                $ept_schedule_times_hour_array = $_POST['ept_opt_schedule_times_hour'];
                $ept_schedule_times_minute_array = $_POST['ept_opt_schedule_times_minute'];
                foreach ($ept_schedule_times_counter as $key => $value) {
                    $timestamp = strtotime($ept_schedule_times_hour_array[$key] . ':' . $ept_schedule_times_minute_array[$key]);
                    $ept_opt_schedule_times[$timestamp]['hour']    = $ept_schedule_times_hour_array[$key];
                    $ept_opt_schedule_times[$timestamp]['minute']  = $ept_schedule_times_minute_array[$key];
                }
                ksort($ept_opt_schedule_times);
                update_option('ept_opt_schedule_times_counter', $_POST['ept_opt_schedule_times_counter']);
                update_option('ept_opt_schedule_times', $ept_opt_schedule_times);
            } else {
                update_option('ept_opt_schedule_times_counter', array());
                update_option('ept_opt_schedule_times', array());
            }

            update_option( 'next_tweet_time', ept_determine_next_update() );
            // set schedule time - hour
            if (isset($_POST['ept_opt_schedule_times_hour'])) {
                update_option('ept_opt_schedule_times_hour', $_POST['ept_opt_schedule_times_hour']);
            } else {
                update_option('ept_opt_schedule_times_hour', array());
            }

            // set schedule time - minute
            if (isset($_POST['ept_opt_schedule_times_minute'])) {
                update_option('ept_opt_schedule_times_minute', $_POST['ept_opt_schedule_times_minute']);
            } else {
                update_option('ept_opt_schedule_times_minute', array());
            }
		
            //option to enable log
            if ( isset($_POST['ept_enable_log'])) {
                update_option('ept_enable_log', true);
                global $ept_debug; 												
                $ept_debug->enable( true ); 
            } else{
                update_option('ept_enable_log', false);
                global $ept_debug;
                $ept_debug->enable( false );	
            }
        
            //categories to omit from tweet
            if (isset($_POST['post_category'])) {
                update_option('ept_opt_omit_cats', implode(',', $_POST['post_category']));
            } else {
                update_option('ept_opt_omit_cats', '');
            }

            update_option( 'ept_after_update', true );

            echo '<script language="javascript">window.open ("' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=Evergreen","_self")</script>';
            die;

            //successful update message
        }
        //tweet now clicked
        elseif (isset($_POST['tweet'])) {
			update_option( 'ept_opt_last_update', current_time('timestamp', 0) );
            $tweet_msg = ept_opt_tweet_old_post();
            print('
			<div id="message" class="updated fade">
				<p>' . __($tweet_msg, 'Evergreen') . '</p>
			</div>');
        }
        elseif (isset($_POST['reset'])) {
           ept_reset_settings();
           echo '<script language="javascript">window.open ("' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=Evergreen&top=reset","_self")</script>';
                die;
        }

        $ept_url_shortener_disabled = '';
        if ( ! _isCurl() ) {
            print('                 
            <div id="message" class="updated fade red">
                <p>You cannot shorten URLs because you do not have CURL installed. Please install CURL on your machine (by following these <a href="http://www.tomjepson.co.uk/enabling-curl-in-php-php-ini-wamp-xamp-ubuntu/">instructions</a>) or ask your hosting provider to enable it.</p>
            </div>');
            $ept_url_shortener_disabled = 'disabled';
        }
	//set up data into fields from db
        global $wpdb;

		$admin_url = site_url('/wp-admin/options-general.php?page=Evergreen');        	

		//Current URL - updated query for those with caching plugins
		//$admin_url = $wpdb->get_var("select option_value from wp_options where option_name = 'ept_opt_admin_url';");
    	// $admin_url = get_option('ept_opt_admin_url');
        
        if (!isset($admin_url)) {
            $admin_url = ept_currentPageURL();
			update_option('ept_opt_admin_url', $admin_url);
        }
        
        //what to tweet?
        $tweet_type = get_option('ept_opt_tweet_type');
        if (!isset($tweet_type)) {
            $tweet_type = "title";
        }

        //additional text
        $additional_text = get_option('ept_opt_add_text');
        if (!isset($additional_text)) {
            $additional_text = "";
        }

        //position of additional text
        $additional_text_at = get_option('ept_opt_add_text_at');
        if (!isset($additional_text_at)) {
            $additional_text_at = "beginning";
        }

        //include link in tweet
        $include_link = get_option('ept_opt_include_link');
        if (!isset($include_link)) {
            $include_link = "yes";
        }

        //use custom field to fetch url
        $custom_url_option = get_option('ept_opt_custom_url_option');
        if (!isset($custom_url_option)) {
            $custom_url_option = "";
        } elseif ($custom_url_option)
            $custom_url_option = "checked";
        else
            $custom_url_option="";

        //custom field name for url
        // $custom_url_field = get_option('ept_opt_custom_url_field');
        // if (!isset($custom_url_field)) {
        //     $custom_url_field = "";
        // }

        //use url tracking?
        $use_url_tracking = get_option('ept_opt_use_url_tracking');
        if (!isset($use_url_tracking)) {
            $use_url_tracking = "";
        } elseif ($use_url_tracking)
            $use_url_tracking = "checked";
        else {
            $use_url_tracking="";
        }

        //use url shortner?
        $use_url_shortner = get_option('ept_opt_use_url_shortner');
        if (!isset($use_url_shortner)) {
            $use_url_shortner = "";
        } elseif ($use_url_shortner)
            $use_url_shortner = "checked";
        else
            $use_url_shortner="";

        //url shortner
        $url_shortener = get_option('ept_opt_url_shortener');
        if (!isset($url_shortener)) {
            $url_shortener = ept_opt_URL_SHORTENER;
        }

        //bitly key
        $bitly_api = get_option('ept_opt_bitly_key');
        if (!isset($bitly_api)) {
            $bitly_api = "";
        }

        //bitly username
        $bitly_username = get_option('ept_opt_bitly_user');
        if (!isset($bitly_username)) {
            $bitly_username = "";
        }

        //hashtag option
        $custom_hashtag_option = get_option('ept_opt_custom_hashtag_option');
        if (!isset($custom_hashtag_option)) {
            $custom_hashtag_option = "nohashtag";
        }

        //use inline hashtag
        $use_inline_hashtags = get_option('ept_opt_use_inline_hashtags');
        if (!isset($use_inline_hashtags)) {
            $use_inline_hashtags = "";
        } elseif ($use_inline_hashtags)
            $use_inline_hashtags = "checked";
        else
            $use_inline_hashtags="";

         //hashtag length
        $hashtag_length = get_option('ept_opt_hashtag_length');
        if (!isset($hashtag_length)) {
            $hashtag_length = "20";
        }
        
        //custom field 
        $custom_hashtag_field = get_option('ept_opt_custom_hashtag_field');
        if (!isset($custom_hashtag_field)) {
            $custom_hashtag_field = "";
        }

        //default hashtag
        $twitter_hashtags = get_option('ept_opt_hashtags');
        if (!isset($twitter_hashtags)) {
            $twitter_hashtags = ept_opt_HASHTAGS;
        }

        //min age limit
        $ageLimit = get_option('ept_opt_age_limit');
        if (!(isset($ageLimit) && is_numeric($ageLimit))) {
            $ageLimit = ept_opt_AGE_LIMIT;
        }

        //max age limit
        $maxAgeLimit = get_option('ept_opt_max_age_limit');
        if (!(isset($maxAgeLimit) && is_numeric($maxAgeLimit))) {
            $maxAgeLimit = ept_opt_MAX_AGE_LIMIT;
        }

        //use pause tweet?
        $use_pause_tweet = get_option('ept_opt_use_pause_tweet');
        if (!isset($use_pause_tweet)) {
            $use_pause_tweet = "";
        } elseif ($use_pause_tweet)
            $use_pause_tweet = "checked";
        else
            $use_pause_tweet="";

        //pause auto tweet - start time
        $ept_opt_start_pause_time = get_option('ept_opt_start_pause_time');
        if (!(isset($ept_opt_start_pause_time))) {
            $ept_opt_start_pause_time = "22:00";
        }
        
        //pause auto tweet - end time
        $ept_opt_end_pause_time = get_option('ept_opt_end_pause_time');
        if (!(isset($ept_opt_end_pause_time))) {
            $ept_opt_end_pause_time = "05:00";
        }

        //check enable log
        $ept_enable_log = get_option('ept_enable_log');
        if (!isset($ept_enable_log)) {
            $ept_enable_log = "";
        } elseif ($ept_enable_log)
            $ept_enable_log = "checked";
        else
            $ept_enable_log="";
        
        //set omitted categories
        $omitCats = get_option('ept_opt_omit_cats');
        if (!isset($omitCats)) {
            $omitCats = ept_opt_OMIT_CATS;
        }

        // get schedule day - monday
        $ept_opt_schedule_day_mon = get_option('ept_opt_schedule_day_mon');
        if (!(isset($ept_opt_schedule_day_mon))) {
            $ept_opt_schedule_day_mon = "";
        }

        // get schedule day - tuesday
        $ept_opt_schedule_day_tue = get_option('ept_opt_schedule_day_tue');
        if (!(isset($ept_opt_schedule_day_tue))) {
            $ept_opt_schedule_day_tue = "";
        }

        // get schedule day - wednesday
        $ept_opt_schedule_day_wed = get_option('ept_opt_schedule_day_wed');
        if (!(isset($ept_opt_schedule_day_wed))) {
            $ept_opt_schedule_day_wed = "";
        }

        // get schedule day - thursday
        $ept_opt_schedule_day_thu = get_option('ept_opt_schedule_day_thu');
        if (!(isset($ept_opt_schedule_day_thu))) {
            $ept_opt_schedule_day_thu = "";
        }

        // get schedule day - friday
        $ept_opt_schedule_day_fri = get_option('ept_opt_schedule_day_fri');
        if (!(isset($ept_opt_schedule_day_fri))) {
            $ept_opt_schedule_day_fri = "";
        }

        // get schedule day - saturday
        $ept_opt_schedule_day_sat = get_option('ept_opt_schedule_day_sat');
        if (!(isset($ept_opt_schedule_day_sat))) {
            $ept_opt_schedule_day_sat = "";
        }

        // get schedule day - sunday
        $ept_opt_schedule_day_sun = get_option('ept_opt_schedule_day_sun');
        if (!(isset($ept_opt_schedule_day_sun))) {
            $ept_opt_schedule_day_sun = "";
        }

        $ept_opt_schedule_times_counter = get_option( 'ept_opt_schedule_times_counter' );
        if (!(isset($ept_opt_schedule_times_counter)) || ! is_array($ept_opt_schedule_times_counter)) {
            $ept_opt_schedule_times_counter = array();
        }

        // $ept_opt_schedule_times_hour = get_option( 'ept_opt_schedule_times_hour' );
        // if (!(isset($ept_opt_schedule_times_hour)) || ! is_array($ept_opt_schedule_times_hour)) {
        //     $ept_opt_schedule_times_hour = array();
        // }

        // $ept_opt_schedule_times_minute = get_option( 'ept_opt_schedule_times_minute' );
        // if (!(isset($ept_opt_schedule_times_minute)) || ! is_array($ept_opt_schedule_times_minute)) {
        //     $ept_opt_schedule_times_minute = array();
        // }

        $ept_opt_schedule_times = get_option( 'ept_opt_schedule_times' );
        if (!(isset($ept_opt_schedule_times)) || ! is_array($ept_opt_schedule_times)) {
            $ept_opt_schedule_times = array();
        }

        foreach ($ept_opt_schedule_times as $key => $value) {
            // echo var_dump($value);
            $ept_opt_schedule_times_hour[] = $value['hour'];
            $ept_opt_schedule_times_minute[] = $value['minute'];
        }

        $x = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__));

        print('
			<div class="wrap">
				<h2>' . __('Evergreen Post Tweeter by ', 'Evergreen') . ' <a href="http://www.leavingworkbehind.com/">Tom Ewer</a></h2>
<h3>If you like this plugin, follow <a href="http://www.twitter.com/tomewer/">@tomewer</a> on Twitter!</h3>

<a href="https://twitter.com/tomewer" class="twitter-follow-button" data-show-count="true" data-size="large">Follow @tomewer</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<br /><br />

				<form id="ept_opt" name="ept_TweetOldPost" action="" method="post">
					<input type="hidden" name="ept_opt_action" value="ept_opt_update_settings" />
					<fieldset class="options">
						<div class="option">
							<label for="ept_opt_twitter_username">' . __('', 'Evergreen') . '</label>


<div id="profile-box">');
        if (!$settings["oauth_access_token"]) {

            echo '<a href="' . ept_get_auth_url() . '" class="auth-twitter">Sign in with Twitter</a>';
        } else {
            echo '<img class="avatar" src="' . $settings["profile_image_url"] . '" alt="" />
							<h4>' . $settings["screen_name"] . '</h4>';
            if ($settings["location"]) {
                echo '<h5>' . $settings["location"] . '</h5>';
            }
            echo '<p>

								You\'re Connected! <a href="' . $_SERVER["REQUEST_URI"] . '&top=deauthorize" onclick=\'return confirm("Are you sure you want to deauthorize your Twitter account?");\'>Click here to deauthorize</a>.<br />

							</p>
                            
							<div class="retweet-clear"></div>
					';
        }
		$as_number_tweet = get_option('as_number_tweet');
        $as_post_type = get_option('as_post_type');
        $ept_cat_tweet = get_option('ept_cat_tweet');
		$ept_tag_tweet = get_option('ept_tag_tweet');
		
        print('</div>
						</div>
                                               
						<div class="countdown_opt" style="width:100%;height:auto;overflow: hidden;float:none;border-bottom: dotted 3px #eeefff;"><br />
						<label style="margin-left:40px;"><strong>Next Tweet coming in:</strong></label>
						<div id="defaultCountdown" style="width:20%;margin-left:15%;margin-bottom:40px;"></div>
						</div>

						<div class="option" >
                            <label for="ept_enable_log">' . __('Enable Logging: ', 'Evergreen') . '</label>
                            <input type="checkbox" name="ept_enable_log" id="ept_enable_log" ' . $ept_enable_log . ' /> 
                                                        <strong>Yes, save a log of actions in log file.</strong>    
                                                       
                        </div>

						<div class="option" >
							<label for="ept_opt_add_text">' . __('Additional Text: ', 'Evergreen') . '</label>
							<input type="text" size="25" name="ept_opt_add_text" id="ept_opt_add_text" value="' . $additional_text . '" autocomplete="off" />
						</div>
						<div class="option" >
							<label for="ept_opt_add_text_at">' . __('Additional Text Location: ', 'Evergreen') . '</label>
							<select id="ept_opt_add_text_at" name="ept_opt_add_text_at" style="width:175px">
								<option value="beginning" ' . ept_opt_optionselected("beginning", $additional_text_at) . '>' . __(' Beginning of the tweet ', 'Evergreen') . '</option>
								<option value="end" ' . ept_opt_optionselected("end", $additional_text_at) . '>' . __(' End of the tweet ', 'Evergreen') . '</option>
							</select>
						</div>
                                                
						<div id="urloptions">

                        <div class="option">
                            <label for="ept_opt_use_url_tracking">' . __('Use URL tracking?: ', 'Evergreen') . '</label>
                            <input onchange="return showtracker()" type="checkbox" name="ept_opt_use_url_tracking" id="ept_opt_use_url_tracking" ' . $use_url_tracking . ' />
                        </div>

						<div class="option">
							<label for="ept_opt_use_url_shortner">' . __('Use URL shortener?: ', 'Evergreen') . '</label>
							<input ' . $ept_url_shortener_disabled . ' onchange="return showshortener()" type="checkbox" name="ept_opt_use_url_shortner" id="ept_opt_use_url_shortner" ' . $use_url_shortner . ' />
						</div>
						
						<div  id="urlshortener">
						<div class="option">
							<label for="ept_opt_url_shortener">' . __('URL Shortener Service', 'Evergreen') . ':</label>
							<select name="ept_opt_url_shortener" id="ept_opt_url_shortener" onchange="javascript:showURLAPI()" style="width:100px;">
									<option value="is.gd" ' . ept_opt_optionselected('is.gd', $url_shortener) . '>' . __('is.gd', 'Evergreen') . '</option>
									<option value="su.pr" ' . ept_opt_optionselected('su.pr', $url_shortener) . '>' . __('su.pr', 'Evergreen') . '</option>
									<option value="bit.ly" ' . ept_opt_optionselected('bit.ly', $url_shortener) . '>' . __('bit.ly', 'Evergreen') . '</option>
									<option value="tr.im" ' . ept_opt_optionselected('tr.im', $url_shortener) . '>' . __('tr.im', 'Evergreen') . '</option>
									<option value="3.ly" ' . ept_opt_optionselected('3.ly', $url_shortener) . '>' . __('3.ly', 'Evergreen') . '</option>
									<option value="u.nu" ' . ept_opt_optionselected('u.nu', $url_shortener) . '>' . __('u.nu', 'Evergreen') . '</option>
									<option value="1click.at" ' . ept_opt_optionselected('1click.at', $url_shortener) . '>' . __('1click.at', 'Evergreen') . '</option>
									<option value="tinyurl" ' . ept_opt_optionselected('tinyurl', $url_shortener) . '>' . __('tinyurl', 'Evergreen') . '</option>
							</select>
						</div>
						<div id="showDetail" style="display:none">
							<div class="option">
								<label for="ept_opt_bitly_user">' . __('bit.ly Username', 'Evergreen') . ':</label>
								<input type="text" size="25" name="ept_opt_bitly_user" id="ept_opt_bitly_user" value="' . $bitly_username . '" autocomplete="off" />
							</div>
							
							<div class="option">
								<label for="ept_opt_bitly_key">' . __('bit.ly API Key', 'Evergreen') . ':</label>
								<input type="text" size="25" name="ept_opt_bitly_key" id="ept_opt_bitly_key" value="' . $bitly_api . '" autocomplete="off" />
							</div>
						</div>
                    </div>
					</div>
						<div class="option" >
							<label for="ept_opt_age_limit">' . __('Minimum Age of Post: ', 'Evergreen') . '</label>
							<input type="text" id="ept_opt_age_limit" maxlength="5" value="' . $ageLimit . '" name="ept_opt_age_limit" /> Day / Days
							<strong>(0 will include today.)</strong>
                                                           
						</div>
						
						<div class="option" >
							<label for="ept_opt_max_age_limit">' . __('Maximum Age of Post: ', 'Evergreen') . '</label>
                                                        <input type="text" id="ept_opt_max_age_limit" maxlength="5" value="' . $maxAgeLimit . '" name="ept_opt_max_age_limit" /> Day / Days
                                                       <strong>(0 will include all posts.)</strong>
						</div>
                        
						<div class="option">
						<label class="ttip">Select Post Type: </label>


						<select name="as_post_type">
							<option value="post">Only Posts</option>
							<option value="page">Only Pages</option>
							<option value="all">Posts & Pages</option>
						</select> Currently Sharing:&nbsp;'.$as_post_type.'
						</div>

                        <div class="option">
                            <label class="ttip">Select Categories: </label>
                            <ul class="option-checkbox">
                                ');
                                echo ept_generate_categories_checkbox( $ept_cat_tweet );
                        print('
                            </ul>
                        </div>

                        <div class="option">
                            <label class="ttip">Select Tags: </label>
                            <ul class="option-checkbox">
                                ');
                                ept_dropdown_tag_cloud('number=0&order=asc', $ept_tag_tweet);
                        print('
                            </ul>
                        </div>

                        <div class="option">
                            <label for="ept_opt_schedule_tweet">' . __('Schedule Tweets: ', 'Evergreen') . '</label>
                            <ul id="schedule-days-table" class="option-schedule-days">
                                <li class="schedule-day-checkbox' . (1 == $ept_opt_schedule_day_mon ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_mon" value="1" id="day1" ' . checked( 1, $ept_opt_schedule_day_mon, false ) . ' />Monday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (2 == $ept_opt_schedule_day_tue ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_tue" value="2" id="day2" ' . checked( 2, $ept_opt_schedule_day_tue, false ) . ' />Tuesday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (3 == $ept_opt_schedule_day_wed ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_wed" value="3" id="day3" ' . checked( 3, $ept_opt_schedule_day_wed, false ) . ' />Wednesday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (4 == $ept_opt_schedule_day_thu ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_thu" value="4" id="day4" ' . checked( 4, $ept_opt_schedule_day_thu, false ) . ' />Thursday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (5 == $ept_opt_schedule_day_fri ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_fri" value="5" id="day5" ' . checked( 5, $ept_opt_schedule_day_fri, false ) . ' />Friday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (6 == $ept_opt_schedule_day_sat ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_sat" value="6" id="day6" ' . checked( 6, $ept_opt_schedule_day_sat, false ) . ' />Saturday</label>
                                </li>
                                <li class="schedule-day-checkbox' . (7 == $ept_opt_schedule_day_sun ? ' checked' : '') . '">
                                    <label><input type="checkbox" name="ept_opt_schedule_day_sun" value="7" id="day7" ' . checked( 7, $ept_opt_schedule_day_sun, false ) . ' />Sunday</label>
                                </li>
                            </ul>
                            <div style="clear: left;"></div>
                            <label></label>
                            <div>Tweet at these times on <span id="scheduled-days"></span></div>
                            <div style="clear: left;"></div>
                            <label></label>
                            <ol id="schedule-times" class="option-schedule-times">');
                                foreach ($ept_opt_schedule_times_counter as $key => $time) {
                                    print('
                                        <li>
                                            <input type="hidden" name="ept_opt_schedule_times_counter[]" />
                                            <select name="ept_opt_schedule_times_hour[]" style="width: 60px">
                                                ');
                                                for ($i=0; $i < 24; $i++) { 
                                                    if ($i < 10) 
                                                        $index = "0" . $i;
                                                    else
                                                        $index = "" . $i;
                                                    print('<option value="' . $index . '" ' . selected( $ept_opt_schedule_times_hour[$key], (String)$index ) . '>' . $index . '</option>');
                                                }
                                                print('
                                            </select>
                                            <select name="ept_opt_schedule_times_minute[]" style="width: 60px">
                                                ');
                                                for ($i=0; $i < 60; $i++) { 
                                                    if ($i <= 9) 
                                                        $index = "0" . $i;
                                                    else
                                                        $index = "" . $i;
                                                    print('<option value="' . $index . '" ' . selected( $ept_opt_schedule_times_minute[$key], (String)$index ) . '>' . $index . '</option>');
                                                }
                                                print('
                                            </select>
                                            <a class="remove-time tooltip" original-title="Remove posting time">×</a>
                                        </li>
                                    ');
                                }
                            print('
                            </ol>
                            <div style="clear: left;"></div>
                            <label></label>
                            <a id="add-time" class="button">Add Tweeting Time</a>
                        </div>
                                        
				    	<div class="option">
                            <label for="ept_opt_admin_url">' . __('Your Evergreen Plugin Admin URL', 'Evergreen') . ':</label>
                            <input type="text" style="width:500px" id="ept_opt_admin_url" value="' . $admin_url . '" name="ept_opt_admin_url" /><br /><strong>(Note: If the URL displayed in your browser\'s address bar does not match the current URL in the above text box, copy/paste the URL and click "Update Options.")</strong>  
                        </div>
					</fieldset>
					
                    	                  

                                                
						<p class="submit"><input type="submit" name="submit" onclick="javascript:return validate()" value="' . __('Update Evergreen Options', 'Evergreen') . '" />
						<input type="submit" name="tweet" value="' . __('Tweet Now!', 'Evergreen') . '" />
                                                <input type="submit" onclick=\'return confirm("This will reset all the setting, including your account, omitted categories and excluded posts. Are you sure you want to reset all the settings?");\' name="reset" value="' . __('Reset Settings', 'Evergreen') . '" /><br /><br /><strong>Note: Please remember to click "Update Settings" after making any changes.</strong>
					</p>
						
				</form><script language="javascript" type="text/javascript">
function showURLAPI()
{
	var urlShortener=document.getElementById("ept_opt_url_shortener").value;
	if(urlShortener=="bit.ly")
	{
		document.getElementById("showDetail").style.display="block";
		
	}
	else
	{
		document.getElementById("showDetail").style.display="none";
		
	}
	
}

function validate()
{

	if(document.getElementById("showDetail").style.display=="block" && document.getElementById("ept_opt_url_shortener").value=="bit.ly")
	{
		if(trim(document.getElementById("ept_opt_bitly_user").value)=="")
		{
			alert("Please enter bit.ly username.");
			document.getElementById("ept_opt_bitly_user").focus();
			return false;
		}

		if(trim(document.getElementById("ept_opt_bitly_key").value)=="")
		{
			alert("Please enter bit.ly API key.");
			document.getElementById("ept_opt_bitly_key").focus();
			return false;
		}
	}

        if(trim(document.getElementById("ept_opt_age_limit").value) != "" && !isNumber(trim(document.getElementById("ept_opt_age_limit").value)))
        {
            alert("Enter only numeric in Minimum age of post");
		document.getElementById("ept_opt_age_limit").focus();
		return false;
        }
 if(trim(document.getElementById("ept_opt_max_age_limit").value) != "" && !isNumber(trim(document.getElementById("ept_opt_max_age_limit").value)))
        {
            alert("Enter only numeric in Maximum age of post");
		document.getElementById("ept_opt_max_age_limit").focus();
		return false;
        }
	if(trim(document.getElementById("ept_opt_max_age_limit").value) != "" && trim(document.getElementById("ept_opt_max_age_limit").value) != 0)
	{
	if(eval(document.getElementById("ept_opt_age_limit").value) > eval(document.getElementById("ept_opt_max_age_limit").value))
	{
		alert("Post max age limit cannot be less than Post min age iimit");
		document.getElementById("ept_opt_age_limit").focus();
		return false;
	}
	}
}

function trim(stringToTrim) {
	return stringToTrim.replace(/^\s+|\s+$/g,"");
}

function isNumber(val)
{
    if(isNaN(val)){
        return false;
    }
    else{
        return true;
    }
}

function showshortener()
{
						

	if((document.getElementById("ept_opt_use_url_shortner").checked))
		{
			document.getElementById("urlshortener").style.display="block";
		}
		else
		{
			document.getElementById("urlshortener").style.display="none";
		}
}

function setFormAction()
{
    if(document.getElementById("ept_opt_admin_url").value == "")
    {
        document.getElementById("ept_opt_admin_url").value=location.href;
        document.getElementById("ept_opt").action=location.href;
    }
    else
    {
        document.getElementById("ept_opt").action=document.getElementById("ept_opt_admin_url").value;
    }
}

setFormAction();
showURLAPI();
showshortener();

</script>');
    } else {
        print('
			<div id="message" class="updated fade">
				<p>' . __('Oh no! Permission error, please contact your Web site administrator.', 'Evergreen') . '</p>
			</div>');
    }
}

function ept_opt_optionselected($opValue, $value) {
    if ($opValue == $value) {
        return 'selected="selected"';
    }
    return '';
}

function ept_opt_head_admin() {
    $home = get_settings('siteurl');
    $base = '/' . end(explode('/', str_replace(array('\\', '/ept-admin.php'), array('/', ''), __FILE__)));
    $stylesheet = $home . '/wp-content/plugins' . $base . '/css/evergreen-post-tweeter.css';
    echo('<link rel="stylesheet" href="' . $stylesheet . '" type="text/css" media="screen" />');
}

?>
