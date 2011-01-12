<?php
/*
Plugin Name: Goo.gl
Plugin URI: http://kovshenin.com/
Description: A simple Goo.gl URL shortener for WordPress.
Author: Konstantin Kovshenin
Version: 1.0
Author URI: http://kovshenin.com/
*/

function my_shortlink($url, $post_id) {
	global $post;
	if ($post->post_status != 'publish')
		return "";

	$shortlink = get_post_meta($post_id, "_googl_shortlink", true);
	if ($shortlink)
		return $shortlink;
		
	$permalink = get_permalink($post_id);
		
	$http = new WP_Http();
	$headers = array("Content-Type" => "application/json");
	$result = $http->request('https://www.googleapis.com/urlshortener/v1/url', array( 'method' => 'POST', 'body' => '{"longUrl": "' . $permalink . '"}', 'headers' => $headers));
	$result = json_decode($result['body']);
	$shortlink = $result->id;
	
	if ($shortlink) {
		add_post_meta($post_id, "_googl_shortlink", $shortlink, true);
		return $shortlink;
	}
	else {
		return $url;
	}
}

add_filter('get_shortlink', 'my_shortlink', 9, 2);
