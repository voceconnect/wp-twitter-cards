WP Twitter Cards
=================
Contributors: voceplatforms, kevinlangleyjr  
Tags: twitter, cards, meta, helper  
Requires at least: 3.0  
Tested up to: 3.6  
Stable tag: 1.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Description
Helper class to generate meta tags for twitter card support.

## Installation

### As standard plugin:
> See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### As theme or plugin dependency:
> After dropping the plugin into the containing theme or plugin, add the following:
```php
if( ! class_exists( 'WP_Twitter_Cards' ) ) {
    require_once( $path_to_wp_twitter_cards . '/wp-twitter-cards.php' );
}
```

## Usage
On the WordPress `init` hook:  
`WP_Twitter_Cards::add_post_type( 'post-type-here' );`

## Changelog

**1.0**
* Initial release