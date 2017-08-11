<?php

require_once( 'Include/ept-oauth.php' );
require_once( 'Include/ept-itemshelper.php' );
global $ept_oauth;
$ept_oauth = new EPTOAuth;

ept_w3tc_cache_flush();

function ept_w3tc_cache_flush()
{
    if (defined('W3TC')) {
        $config = w3_instance('W3_Config');
        if ($config->get_boolean('pgcache.enabled'))
            w3tc_pgcache_flush();
        if ($config->get_boolean('dbcache.enabled'))
            w3tc_dbcache_flush();
        if ($config->get_boolean('minify.enabled'))
            w3tc_minify_flush();
        if ($config->get_boolean('objectcache.enabled'))
            w3tc_objectcache_flush();
        $cache = ' and W3TC Caches cleared';
    }
}

function _isCurl(){
    return function_exists('curl_version');
}

function ept_dropdown_tag_cloud( $args = '', $selected = '' ) {
    $defaults = array(
        'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
        'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
        'exclude' => '', 'include' => ''
    );
    $args = wp_parse_args( $args, $defaults );

    $tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'DESC')) ); // Always query top tags

    if ( empty($tags) )
        return;

    $return = ept_dropdown_generate_tag_cloud( $tags, $args, $selected ); // Here's where those top tags get sorted according to $args
    if ( is_wp_error( $return ) )
        return false;
    else
        echo apply_filters( 'ept_dropdown_tag_cloud', $return, $args );
}

function ept_dropdown_generate_tag_cloud( $tags, $args = '', $selected = '' ) {
    global $wp_rewrite;
    $defaults = array(
        'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
        'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC'
    );
    $args = wp_parse_args( $args, $defaults );
    extract($args);

    if ( !$tags )
        return;
    $counts = $tag_links = array();
    foreach ( (array) $tags as $tag ) {
        $counts[$tag->name] = $tag->count;
        $tag_links[$tag->name] = get_tag_link( $tag->term_id );
        if ( is_wp_error( $tag_links[$tag->name] ) )
            return $tag_links[$tag->name];
        $tag_ids[$tag->name] = $tag->term_id;
    }

    $min_count = min($counts);
    $spread = max($counts) - $min_count;
    if ( $spread <= 0 )
        $spread = 1;
    $font_spread = $largest - $smallest;
    if ( $font_spread <= 0 )
        $font_spread = 1;
    $font_step = $font_spread / $spread;

    // SQL cannot save you; this is a second (potentially different) sort on a subset of data.
    if ( 'name' == $orderby )
        uksort($counts, 'strnatcasecmp');
    else
        asort($counts);

    if ( 'DESC' == $order )
        $counts = array_reverse( $counts, true );

    $a = array();

    $rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

    foreach ( $counts as $tag => $count ) {
        $tag_id = $tag_ids[$tag];
        $tag_link = clean_url($tag_links[$tag]);
        $tag = str_replace(' ', '&nbsp;', wp_specialchars( $tag ));
        $is_checked = '';
        $checked_array = explode( ',', $selected );
        if (in_array($tag_id, $checked_array)) {
            $is_checked = 'checked="checked"';
        }
        $a[] = "\t<li><label><input value='$tag_id' type='checkbox' name='ept_tag_tweet[]' $is_checked> $tag</label></li>";
    }

    switch ( $format ) :
    case 'array' :
        $return =& $a;
        break;
    case 'list' :
        $return = "<ul class='wp-tag-cloud'>\n\t<li>";
        $return .= join("</li>\n\t<li>", $a);
        $return .= "</li>\n</ul>\n";
        break;
    default :
        $return = join("\n", $a);
        break;
    endswitch;

    return apply_filters( 'ept_dropdown_generate_tag_cloud', $return, $tags, $args );
}

function ept_generate_categories_checkbox( $selected = '' ) {
    $args = array(
        'orderby' => 'term_group',
        'hide_empty' => 0,
    );
    $categories = get_categories( $args );
    $categoriesHelper = new ItemsHelper($categories);

    return $categoriesHelper->htmlList($selected);
}

function ept_tweet_old_post() {
//check last tweet time against set interval and span
//also check if it isn't in pause time
// if (ept_opt_update_time() && ! ept_in_pause_time()) {
//     update_option('ept_opt_last_update', current_time('timestamp', 0));
   //  ept_opt_tweet_old_post();
// }

// if ( ept_opt_update_time() ) {
//     update_option('ept_opt_last_update', current_time('timestamp', 0));
//     ept_opt_tweet_old_post();
// }

    if ( ept_check_day_update() && ept_new_to_update() ) {
        update_option('ept_opt_last_update', current_time('timestamp', 0));
        ept_opt_tweet_old_post();
    }
    update_option( 'next_tweet_time', ept_determine_next_update() );
}

function ept_new_to_update()
{
    global $wpdb;
    $last = $wpdb->get_var("select SQL_NO_CACHE option_value from $wpdb->options where option_name = 'ept_opt_last_update';");

    $ept_opt_schedule_times_counter = get_option( 'ept_opt_schedule_times_counter' );
    if (!(isset($ept_opt_schedule_times_counter)) || ! is_array($ept_opt_schedule_times_counter)) {
        $ept_opt_schedule_times_counter = array();
    }

    $ept_opt_schedule_times = get_option( 'ept_opt_schedule_times' );
    if (!(isset($ept_opt_schedule_times)) || ! is_array($ept_opt_schedule_times)) {
        $ept_opt_schedule_times = array();
    }
    ksort( $ept_opt_schedule_times );

    // $ept_opt_schedule_times_hour = get_option( 'ept_opt_schedule_times_hour' );
    // if (!(isset($ept_opt_schedule_times_hour)) || ! is_array($ept_opt_schedule_times_hour)) {
    //     $ept_opt_schedule_times_hour = array();
    // }

    // $ept_opt_schedule_times_minute = get_option( 'ept_opt_schedule_times_minute' );
    // if (!(isset($ept_opt_schedule_times_minute)) || ! is_array($ept_opt_schedule_times_minute)) {
    //     $ept_opt_schedule_times_minute = array();
    // }

    $ret = false;
    if ( ! empty( $ept_opt_schedule_times ) ) {
        $last_element = array_pop( array_keys( $ept_opt_schedule_times ) );
    }
    $previous_time = null;

    foreach ( $ept_opt_schedule_times as $key => $time ) {
        $ept_time_start = strtotime($time['hour'] . ':' . $time['minute'], current_time('timestamp', 0));

        // echo var_dump( $last ) . "\n";
        // echo var_dump( $previous_time ) . "\n";
        // echo var_dump( $ept_time_start ) . "\n";

        if ( $key == $last_element ) {
            $midnight_time = strtotime("23:59:59", current_time('timestamp', 0));

            if ( 
                $ept_time_start < (Integer)current_time('timestamp', 0) && 
                (Integer)current_time('timestamp', 0) < $midnight_time &&
                ( ! ( $ept_time_start < (Integer)$last && (Integer)$last < $midnight_time ) )
                ) {
                $ret = true;
                break;
            }
        }

        if ( 
            $previous_time < (Integer)current_time('timestamp', 0) && 
            (Integer)current_time('timestamp', 0) < $ept_time_start &&
            ( ! ( $previous_time < (Integer)$last && (Integer)$last < $ept_time_start ) )
            ) {
            $ret = true;
            break;
        }

        $previous_time = $ept_time_start;
    }

    return $ret;
}

function ept_determine_next_update()
{
    $ept_opt_schedule_times_counter = get_option( 'ept_opt_schedule_times_counter' );
    if (!(isset($ept_opt_schedule_times_counter)) || ! is_array($ept_opt_schedule_times_counter)) {
        $ept_opt_schedule_times_counter = array();
    }

    // $ept_opt_schedule_times = get_option( 'ept_opt_schedule_times' );
    // if (!(isset($ept_opt_schedule_times)) || ! is_array($ept_opt_schedule_times)) {
    //     $ept_opt_schedule_times = array();
    // }

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
    ksort( $ept_opt_schedule_times );

    $ept_times = array();
    $last_element = array_pop( array_keys( $ept_opt_schedule_times ) );
    $first_element = array_shift( array_keys( $ept_opt_schedule_times ) );

    foreach ($ept_opt_schedule_times as $key => $time) {
        $ept_time_start = strtotime($time['hour'] . ':' . $time['minute'], current_time('timestamp', 0));

        if ( $key != $last_element ) {
            $remaining_time = $ept_time_start - current_time( 'timestamp', 0 );
        } else {
            if ( current_time('timestamp', 0) > $ept_time_start ) {
                $ept_time_tomorrow = strtotime(
                    $ept_opt_schedule_times[$first_element]['hour'] . ':' . $ept_opt_schedule_times[$first_element]['minute'], 
                    current_time( 'timestamp', 0 )
                );
                $ept_time_tomorrow += (24 * 60 *60);
                // echo var_dump();
                $remaining_time = $ept_time_tomorrow - current_time( 'timestamp', 0 );
            } else {
                $remaining_time = $ept_time_start - current_time( 'timestamp', 0 );
            }
        }
        if ( 0 < $remaining_time ) {
            $ept_times[] = $remaining_time;
        }
    }

    if ( empty($ept_times) ) {
        return 0;
    }

    return (Integer)(time() + $ept_times[min(array_keys($ept_times, min($ept_times)))]);
}

function ept_check_day_update()
{
    $current_day = date( 'N', current_time('timestamp', 0) );
    $ept_opt_schedule_day_mon = get_option('ept_opt_schedule_day_mon');
    $ept_opt_schedule_day_tue = get_option('ept_opt_schedule_day_tue');
    $ept_opt_schedule_day_wed = get_option('ept_opt_schedule_day_wed');
    $ept_opt_schedule_day_thu = get_option('ept_opt_schedule_day_thu');
    $ept_opt_schedule_day_fri = get_option('ept_opt_schedule_day_fri');
    $ept_opt_schedule_day_sat = get_option('ept_opt_schedule_day_sat');
    $ept_opt_schedule_day_sun = get_option('ept_opt_schedule_day_sun');
    $ret = false;

    if ($current_day == $ept_opt_schedule_day_mon) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_tue) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_wed) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_thu) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_fri) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_sat) {
        $ret = true;
    } else if ($current_day == $ept_opt_schedule_day_sun) {
        $ret = true;
    }
    
    return $ret;
}

function ept_currentPageURL() {
      if(!isset($_SERVER['REQUEST_URI'])){
        $serverrequri = $_SERVER['PHP_SELF'];
    }else{
        $serverrequri =    $_SERVER['REQUEST_URI'];
    }
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $protocol = ept_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
    
}

function ept_strleft($s1, $s2) {
    return substr($s1, 0, strpos($s1, $s2));
}

//get random post and tweet
function ept_opt_tweet_old_post() {
    return ept_generate_query();
}

function ept_generate_query($can_requery = true)
{
    global $wpdb;
    $rtrn_msg="";
    $omitCats = get_option('ept_opt_omit_cats');
    $maxAgeLimit = get_option('ept_opt_max_age_limit');
    $ageLimit = get_option('ept_opt_age_limit');
    $exposts = get_option('ept_opt_excluded_post');
    $exposts = preg_replace('/,,+/', ',', $exposts);

    $ept_opt_tweeted_posts = array();
    $ept_opt_tweeted_posts = get_option('ept_opt_tweeted_posts');
    
    if(!$ept_opt_tweeted_posts)
        $ept_opt_tweeted_posts = array();
        
    if($ept_opt_tweeted_posts != null)
        $already_tweeted = implode(",", $ept_opt_tweeted_posts);
    else
        $already_tweeted="";
    
    if (substr($exposts, 0, 1) == ",") {
        $exposts = substr($exposts, 1, strlen($exposts));
    }
    if (substr($exposts, -1, 1) == ",") {
        $exposts = substr($exposts, 0, strlen($exposts) - 1);
    }

    if (!(isset($ageLimit) && is_numeric($ageLimit))) {
        $ageLimit = ept_opt_AGE_LIMIT;
    }

    if (!(isset($maxAgeLimit) && is_numeric($maxAgeLimit))) {
        $maxAgeLimit = ept_opt_MAX_AGE_LIMIT;
    }
    if (!isset($omitCats)) {
        $omitCats = ept_opt_OMIT_CATS;
    }
$as_post_type = get_option('as_post_type');
$ept_tag_tweet = get_option('ept_tag_tweet');
$ept_cat_tweet = get_option('ept_cat_tweet');
$as_number_tweet = get_option('as_number_tweet');
if($as_number_tweet<=0){$as_number_tweet = 1;}
if($as_number_tweet>10){$as_number_tweet = 10;}

//trying to fix multiposts
//if($last<1){$as_number_tweet = 0;}
if($as_post_type!='all'){
    $pt = "post_type = '$as_post_type' AND";
}
    $sql = "SELECT ID
            FROM $wpdb->posts
            WHERE $pt post_status = 'publish' ";
    
    if(is_numeric($ageLimit))
    {
        if($ageLimit > 0)
                $sql = $sql . " AND post_date <= curdate( ) - INTERVAL " . $ageLimit . " day";
    }
    
    if ($maxAgeLimit != 0) {
        $sql = $sql . " AND post_date >= curdate( ) - INTERVAL " . $maxAgeLimit . " day";
    }

    if (isset($exposts)) {
        if (trim($exposts) != '') {
            $sql = $sql . " AND ID Not IN (" . $exposts . ") ";
        }
    }

    if (isset($already_tweeted)) {
    if(trim($already_tweeted) !="")
    {
        $sql = $sql . " AND ID Not IN (" . $already_tweeted . ") ";
    }
    }
    if ($omitCats != '') {
        $sql = $sql . " AND NOT (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omitCats . ")))";
    }
    if ($ept_tag_tweet != '' && $ept_cat_tweet != '') {
        $sql = $sql . " AND ( (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'post_tag' AND tt.term_id IN (" . $ept_tag_tweet . ")))";
        $sql = $sql . " OR (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $ept_cat_tweet . "))) )";
    } else if ($ept_tag_tweet != '' && $ept_cat_tweet == '') {
        $sql = $sql . " AND (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'post_tag' AND tt.term_id IN (" . $ept_tag_tweet . ")))";
    } else if ($ept_tag_tweet == '' && $ept_cat_tweet != '') {
        $sql = $sql . " AND (ID IN (SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $ept_cat_tweet . ")))";
    }
    $sql = $sql . "
            ORDER BY RAND() 
            LIMIT $as_number_tweet ";
            
    $oldest_post = $wpdb->get_results($sql);
    
    if($oldest_post == null)
    {
        if($can_requery)
        {
            $ept_opt_tweeted_posts=array();
            update_option('ept_opt_tweeted_posts', $ept_opt_tweeted_posts);
           return ept_generate_query(false);
        }
        else
        {
           return "No post found to tweet. Please check your settings and try again."; 
        }
    }



    if(isset($oldest_post)){
         $ret = '';
         foreach($oldest_post as $k=>$odp){
                    array_push($ept_opt_tweeted_posts, $odp->ID);
                $ret .= ept_opt_tweet_post($odp->ID).'<br/>';
        }
                update_option('ept_opt_tweeted_posts', $ept_opt_tweeted_posts);

        ept_w3tc_cache_flush();

        update_option( 'next_tweet_time', ept_determine_next_update() );

        return $ret;
     }
     return $rtrn_msg;
   }

//tweet for the passed random post
function ept_opt_tweet_post($oldest_post) {
    global $wpdb;
    $post = get_post($oldest_post);
    $content = "";
    $to_short_url = true;
    $shorturl = "";
    $tweet_type = get_option('ept_opt_tweet_type');
    $additional_text = get_option('ept_opt_add_text');
    $additional_text_at = get_option('ept_opt_add_text_at');
    $include_link = get_option('ept_opt_include_link');
    $custom_hashtag_option = get_option('ept_opt_custom_hashtag_option');
    $custom_hashtag_field = get_option('ept_opt_custom_hashtag_field');
    $twitter_hashtags = get_option('ept_opt_hashtags');
    $url_shortener = get_option('ept_opt_url_shortener');
    $use_url_tracking = get_option('ept_opt_use_url_tracking');
    $to_short_url = get_option('ept_opt_use_url_shortner');
    $use_inline_hashtags = get_option('ept_opt_use_inline_hashtags');
    $hashtag_length = get_option('ept_opt_hashtag_length');

    if ($include_link != "false") {
        $permalink = get_permalink($oldest_post);

        // if ($custom_url_option) {
        //     $custom_url_field = get_option('ept_opt_custom_url_field');
        //     if (trim($custom_url_field) != "") {
        //         $permalink = trim(get_post_meta($post->ID, $custom_url_field, true));
        //     }
        // }

        if ($use_url_tracking) {
            $permalink .= '?utm_source=twitter&utm_medium=evergreen_post_tweeter&utm_campaign=website';
        }

        if ($to_short_url) {

            if ($url_shortener == "bit.ly") {
                $bitly_key = get_option('ept_opt_bitly_key');
                $bitly_user = get_option('ept_opt_bitly_user');
                $shorturl = ept_shorten_url($permalink, $url_shortener, $bitly_key, $bitly_user);
            } else {
                $shorturl = ept_shorten_url($permalink, $url_shortener);
            }
        } else {
            $shorturl = $permalink;
        }
    }

    if ($tweet_type == "title" || $tweet_type == "titlenbody") {
        $title = stripslashes($post->post_title);
        $title = strip_tags($title);
        $title = preg_replace('/\s\s+/', ' ', $title);
    } else {
        $title = "";
    }

    if ($tweet_type == "body" || $tweet_type == "titlenbody") {
        $body = stripslashes($post->post_content);
        $body = strip_tags($body);
        $body = preg_replace('/\s\s+/', ' ', $body);
    } else {
        $body = "";
    }

    if ($tweet_type == "titlenbody") {
        if ($title == null) {
            $content = $body;
        } elseif ($body == null) {
            $content = $title;
        } else {
            $content = $title . " - " . $body;
        }
    } elseif ($tweet_type == "title") {
        $content = $title;
    } elseif ($tweet_type == "body") {
        $content = $body;
    }

    if ($additional_text != "") {
        if ($additional_text_at == "end") {
            $content = $content . " - " . $additional_text;
        } elseif ($additional_text_at == "beginning") {
            $content = $additional_text . " " . $content;
        }
    }

    $hashtags = "";
    $newcontent = "";
    if ($custom_hashtag_option != "nohashtag") {

        if ($custom_hashtag_option == "common") {
//common hashtag
            $hashtags = $twitter_hashtags;
        }
//post custom field hashtag
        elseif ($custom_hashtag_option == "custom") {
            if (trim($custom_hashtag_field) != "") {
                $hashtags = trim(get_post_meta($post->ID, $custom_hashtag_field, true));
            }
        } elseif ($custom_hashtag_option == "categories") {
            $post_categories = get_the_category($post->ID);
            if ($post_categories) {
                foreach ($post_categories as $category) {
                    $tagname = str_replace(".", "", str_replace(" ", "", $category->cat_name));
                    if ($use_inline_hashtags) {
                        if (strrpos($content, $tagname) === false) {
                            $hashtags = $hashtags . "#" . $tagname . " ";
                        } else {
                            $newcontent = preg_replace('/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1);
                        }
                    } else {
                        $hashtags = $hashtags . "#" . $tagname . " ";
                    }
                }
            }
        } elseif ($custom_hashtag_option == "tags") {
            $post_tags = get_the_tags($post->ID);
            if ($post_tags) {
                foreach ($post_tags as $tag) {
                    $tagname = str_replace(".", "", str_replace(" ", "", $tag->name));
                    if ($use_inline_hashtags) {
                        if (strrpos($content, $tagname) === false) {
                            $hashtags = $hashtags . "#" . $tagname . " ";
                        } else {
                            $newcontent = preg_replace('/\b' . $tagname . '\b/i', "#" . $tagname, $content, 1);
                        }
                    } else {
                        $hashtags = $hashtags . "#" . $tagname . " ";
                    }
                }
            }
        }

        if ($newcontent != "")
            $content = $newcontent;
    }



    if ($include_link != "false") {
        if (!is_numeric($shorturl) && (strncmp($shorturl, "http", strlen("http")) == 0)) {
            
        } else {
            return "OOPS!!! problem with your URL shortning service. Some signs of error " . $shorturl . ".";
        }
    }

    $message = ept_set_tweet_length($content, $shorturl, $hashtags, $hashtag_length);
    $status = urlencode(stripslashes(urldecode($message)));
    if ($status) {
        $poststatus = ept_update_status($message);
        if ($poststatus == true)
        {
            return "Success! Your tweet has been published.";
        }
        else {
            return "Oh no! Something went wrong. Please try again.";
        }
    }
    return "Oh no! Looks like there are problems. Please email tom@leavingworkbehind.com.";
}

//send request to passed url and return the response
function ept_send_request($url, $method='GET', $data='', $auth_user='', $auth_pass='') {
    $ch = curl_init($url);
    if (strtoupper($method) == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if ($auth_user != '' && $auth_pass != '') {
        curl_setopt($ch, CURLOPT_USERPWD, "{$auth_user}:{$auth_pass}");
    }
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode != 200) {
        return $httpcode;
    }

    return $response;
}


/* returns a result form url */
function ept_curl_get_result($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

function ept_get_bitly_short_url($url,$login,$appkey,$format='txt') {
  $connectURL = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format;
  return ept_curl_get_result($connectURL);
}

//Shorten long URLs with is.gd or bit.ly.
function ept_shorten_url($the_url, $shortener='is.gd', $api_key='', $user='') {

    if (($shortener == "bit.ly") && isset($api_key) && isset($user)) {
            $response = ept_get_bitly_short_url($the_url, $user, $api_key);
    } elseif ($shortener == "su.pr") {
        $url = "http://su.pr/api/simpleshorten?url={$the_url}";
        $response = ept_send_request($url, 'GET');
    } elseif ($shortener == "tr.im") {
        $url = "http://api.tr.im/api/trim_simple?url={$the_url}";
        $response = ept_send_request($url, 'GET');
    } elseif ($shortener == "3.ly") {
        $url = "http://3.ly/?api=em5893833&u={$the_url}";
        $response = ept_send_request($url, 'GET');
    } elseif ($shortener == "tinyurl") {
        $url = "http://tinyurl.com/api-create.php?url={$the_url}";
        $response = ept_send_request($url, 'GET');
    } elseif ($shortener == "u.nu") {
        $url = "http://u.nu/unu-api-simple?url={$the_url}";
        $response = ept_send_request($url, 'GET');
    } elseif ($shortener == "1click.at") {
        $url = "http://1click.at/api.php?action=shorturl&url={$the_url}&format=simple";
        $response = ept_send_request($url, 'GET');
    } else {
        $url = "http://is.gd/api.php?longurl={$the_url}";
        $response = ept_send_request($url, 'GET');
    }

    return $response;
}

//Shrink a tweet and accompanying URL down to 140 chars.
function ept_set_tweet_length($message, $url, $twitter_hashtags="", $hashtag_length=0) {

    $tags = $twitter_hashtags;
    $message_length = strlen($message);
    $url_length = preg_match("@^https?://@", $url) ? 23 : 22;
    //$cur_length = strlen($tags);
    if ($hashtag_length == 0)
        $hashtag_length = strlen($tags);

    if ($twitter_hashtags != "") {
        if (strlen($tags) > $hashtag_length) {
            $tags = substr($tags, 0, $hashtag_length);
            $tags = substr($tags, 0, strrpos($tags, ' '));
        }
        $hashtag_length = strlen($tags);
    }

    if ($message_length + $url_length + $hashtag_length > 140) {


        $shorten_message_to = 140 - $url_length - $hashtag_length;
        $shorten_message_to = $shorten_message_to - 4;
//$message = $message." ";
        if (strlen($message) > $shorten_message_to) {
            $message = substr($message, 0, $shorten_message_to);
            $message = substr($message, 0, strrpos($message, ' '));
        }
        $message = $message . "...";
    }
    return $message . " " . $url . " " . $tags;
}

//check time and update the last tweet time
function ept_opt_update_time() {
    return ept_to_update();
}

ept_w3tc_cache_flush();

function ept_to_update() {
    global $wpdb;
    //have to use normal query to prevent the caching plug-in from caching the last update time
    $last  = $wpdb->get_var("select SQL_NO_CACHE option_value from $wpdb->options where option_name = 'ept_opt_last_update';");
    $interval = get_option('ept_opt_interval');
    $slop = get_option('ept_opt_interval_slop');

    if (!(isset($interval))) {
        $interval = ept_opt_INTERVAL;
    }
    else if(!(is_numeric($interval)))
    {
        $interval = ept_opt_INTERVAL;
    }
    
    if (!(isset($slop))) {
        $slop = ept_opt_INTERVAL_SLOP;
    }
    else if(!(is_numeric($slop)))
    {
        $slop = ept_opt_INTERVAL_SLOP;
    }
    
    $passed = current_time('timestamp', 0) - $last;

    $interval = $interval * 60 * 60;
    $slop = $slop * 60 * 60;
    if (false === $last) {
        $ret = 1;
    } else if (is_numeric($last)) {
        $ret = (time() - $last) > $interval;
    }

    return $ret;        
}

function ept_in_pause_time()
{
    $ept_opt_use_pause_tweet = get_option('ept_opt_use_pause_tweet');

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

    if (! $ept_opt_use_pause_tweet) {
        return false;
    }

    if (strtotime($ept_opt_start_pause_time) > strtotime($ept_opt_end_pause_time))
        if (strtotime($ept_opt_start_pause_time) < current_time('timestamp', 0) || 
            current_time('timestamp', 0) < strtotime($ept_opt_end_pause_time)) {
            $ret = true;
        } else {
            $ret = false;
        }
    else {
        if (strtotime($ept_opt_start_pause_time) < current_time('timestamp', 0) && 
            current_time('timestamp', 0) < strtotime($ept_opt_end_pause_time)) {
            $ret = true;
        } else {
            $ret = false;
        }
    }

    return $ret;
}

function ept_get_auth_url() {
    global $ept_oauth;
    $settings = ept_get_settings();

    $token = $ept_oauth->get_request_token();
    if ($token) {
        $settings['oauth_request_token'] = $token['oauth_token'];
        $settings['oauth_request_token_secret'] = $token['oauth_token_secret'];

        ept_save_settings($settings);

        return $ept_oauth->get_auth_url($token['oauth_token']);
    }
}

function ept_update_status($new_status) {
    global $ept_oauth;
    $settings = ept_get_settings();

    if (isset($settings['oauth_access_token']) && isset($settings['oauth_access_token_secret'])) {
        return $ept_oauth->update_status($settings['oauth_access_token'], $settings['oauth_access_token_secret'], $new_status);
    }

    return false;
}

function ept_get_settings() {
    global $ept_defaults;

    $settings = $ept_defaults;

    $wordpress_settings = get_option('ept_settings');
    if ($wordpress_settings) {
        foreach ($wordpress_settings as $key => $value) {
            $settings[$key] = $value;
        }
    }

    return $settings;
}

function ept_save_settings($settings) {
    update_option('ept_settings', $settings);
}

function ept_reset_settings()
{
    delete_option('ept_settings');
    update_option('ept_enable_log','');
    update_option('ept_opt_add_text','');
    update_option('ept_opt_add_text_at','beginning');
    update_option('ept_opt_bitly_key','');
    update_option('ept_opt_bitly_user','');
    update_option('ept_opt_custom_hashtag_field','');
    update_option('ept_opt_custom_hashtag_option','nohashtag');
    update_option('ept_opt_custom_url_field','');
    update_option('ept_opt_custom_url_option','');
    update_option('ept_opt_excluded_post','');
    update_option('ept_opt_hashtags','');
    update_option('ept_opt_hashtag_length','20');
    update_option('ept_opt_include_link','yes');
    delete_option('ept_opt_last_update');
    update_option('ept_opt_age_limit', 0);
    update_option('ept_opt_max_age_limit', 0);
    update_option('ept_opt_omit_cats','');
    update_option('ept_opt_tweet_type','title');
    delete_option('ept_opt_tweeted_posts');
    update_option('ept_opt_url_shortener','is.gd');
    update_option('ept_opt_use_inline_hashtags','');
    update_option('ept_opt_use_url_shortner','');
    update_option('ept_opt_use_pause_tweet','');
    update_option('ept_cat_tweet', '');
    update_option('ept_tag_tweet', '');
}

?>
