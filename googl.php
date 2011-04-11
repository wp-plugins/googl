<?php
/*
Plugin Name: Goo.gl
Plugin URI: http://kovshenin.com/
Description: A simple Goo.gl URL shortener for WordPress.
Author: Konstantin Kovshenin
Version: 1.3
Author URI: http://kovshenin.com/
*/

function googl_shortlink($url, $post_id = false) {
	global $post;
	if (!$post_id && $post) $post_id = $post->ID;
	elseif ($post_id) $post = get_post($post_id);
	
	if ($post && $post->post_status != 'publish')
		return "";

	$shortlink = $url;
	
	if ((is_singular() || $post) && !is_front_page()) {
		$shortlink = get_post_meta($post_id, '_googl_shortlink', true);
		if ($shortlink)
			return $shortlink;
		
		$permalink = get_permalink($post_id);
		$shortlink = googl_shorten($permalink);
		
		if ($shortlink !== $url) {
			add_post_meta($post_id, '_googl_shortlink', $shortlink, true);
			return $shortlink;
		}
		else {
			return $url;
		}
	} elseif (is_front_page()) {
		$shortlink = (string) get_option('_googl_shortlink_home');
		if ($shortlink)
			return $shortlink;
			
		$googl_shortlink = googl_shorten(home_url('/'));
		if ($googl_shortlink !== $shortlink) {
			update_option('_googl_shortlink_home', $googl_shortlink);
			return $googl_shortlink;
		} else {
			return home_url('/');
		}
	}
}

function googl_shorten($url) {
	$http = new WP_Http();
	$headers = array('Content-Type' => 'application/json');
	$result = $http->request('https://www.googleapis.com/urlshortener/v1/url', array( 'method' => 'POST', 'body' => '{"longUrl": "' . $url . '"}', 'headers' => $headers));
	
	// Return the URL if the request got an error.
	if (is_wp_error($result)) {
		return $url;
	}

	$result = json_decode($result['body']);
	$shortlink = $result->id;
	if ($shortlink)
		return $shortlink;
	
	return $url;
}

function googl_post_columns($columns)
{
	$columns['shortlink'] = 'Shortlink';
	return $columns;
}

function googl_custom_columns($column)
{
	global $post;
	if ('shortlink' == $column)
	{
		$shorturl = wp_get_shortlink();
		$shorturl_caption = str_replace('http://', '', $shorturl);
		$shorturl_info = str_replace('goo.gl/', 'goo.gl/info/', $shorturl);		
		echo "<a href='{$shorturl}'>{$shorturl_caption}</a> (<a href='{$shorturl_info}'>info</a>)";
	}
}

function googl_save_post($post_ID, $post) {
	// Don't act on auto drafts.
	if ($post->post_status == 'auto-draft')
		return;
		
	delete_post_meta($post_ID, '_googl_shortlink');
}

add_filter('get_shortlink', 'googl_shortlink', 9, 2);
add_action('save_post', 'googl_save_post', 10, 2);
add_action('manage_posts_custom_column', 'googl_custom_columns');
add_filter('manage_edit-post_columns', 'googl_post_columns');
