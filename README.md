WP Twitter Cards
=================
Contributors: voceplatforms, kevinlangleyjr  
Tags: twitter, cards, meta, helper  
Requires at least: 3.0  
Tested up to: 4.0  
Stable tag: 1.1
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

## Description
Helper class to generate meta tags for twitter card support. When support is added to a post type, a meta box is added on the post edit view to configure the twitter card properties.

Requires both Voce Post Meta and Multi Post Thumbnails WordPress plugins.
https://github.com/voceconnect/voce-post-meta
https://github.com/voceconnect/multi-post-thumbnails

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
In the post edit view configure the Twitter Card metabox to enable the tag output.

## Changelog
**1.1**
* Adding support for player card

**1.0**
* Initial release