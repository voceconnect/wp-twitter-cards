<?php

if(defined('ABSPATH') && !has_action('init', array( 'WP_Twitter_Cards', 'init' ))) {
	add_action( 'init', array( 'WP_Twitter_Cards', 'init' ), 99 );
}
