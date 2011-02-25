<?php
/*
Plugin Name: Goo.gl
Plugin URI: http://kovshenin.com/
Description: A simple Goo.gl URL shortener for WordPress.
Author: Konstantin Kovshenin
Version: 1.2
Author URI: http://kovshenin.com/
*/

function googl_shortlink($url, $post_id) {
	global $post;
	if (!$post_id && $post) $post_id = $post->ID;
	
	if ($post->post_status != 'publish')
		return "";

	$shortlink = get_post_meta($post_id, '_googl_shortlink', true);
	if ($shortlink)
		return $shortlink;
	
	$permalink = get_permalink($post_id);

	$http = new WP_Http();
	$headers = array('Content-Type' => 'application/json');
	$result = $http->request('https://www.googleapis.com/urlshortener/v1/url', array( 'method' => 'POST', 'body' => '{"longUrl": "' . $permalink . '"}', 'headers' => $headers));
	
	// Return the URL if the request got an error.
	if (is_wp_error($result)) {
		return $url;
	}

	$result = json_decode($result['body']);
	$shortlink = $result->id;
	
	if ($shortlink) {
		add_post_meta($post_id, '_googl_shortlink', $shortlink, true);
		return $shortlink;
	}
	else {
		return $url;
	}
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

add_filter('get_shortlink', 'googl_shortlink', 9, 2);
add_action('manage_posts_custom_column', 'googl_custom_columns');
add_filter('manage_edit-post_columns', 'googl_post_columns');
