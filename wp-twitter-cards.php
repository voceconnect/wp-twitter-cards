<?php

if ( !class_exists( 'WP_Twitter_Cards' ) ){

class WP_Twitter_Cards {

	static $card_types = array(
		'gallery' => 'Gallery',
		'photo' => 'Photo',
		'summary_large_image' => 'Summary Large Image',
		'summary' => 'Summary',
		'product' => 'Product'
	);

	static $post_types = array();

	public static function init(){
		if ( !class_exists( 'Voce_Meta_API' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Voce Post Meta plugin must be active for the Twitter Card integration to work correctly.', '' );
		if ( !class_exists( 'MultiPostThumbnails' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Multi Post Thumbnails plugin must be active for the Twitter Card integration to work correctly.', '' );


		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 11, 2 );
		add_action( 'wp_head', array( __CLASS__, 'render_card_meta' ) );
		add_action( 'admin_enqueue_scripts', function( $hook ) {
			if ( in_array( $hook, array('post-new.php', 'post.php') ) )
				wp_enqueue_script( 'twitter-card-metabox', plugins_url( '/metabox.js', __FILE__ ), array( 'jquery' ) );
		} );

		foreach( self::$post_types as $post_type => $card_types ){
			add_post_type_support( $post_type, $post_type . '_twitter_card' );

			add_metadata_group( $post_type . '_twitter_card', 'Twitter Card' );


			if( !apply_filters( 'twitter_card_title_setting_disabled', false ) ){
				add_metadata_field( $post_type . '_twitter_card', 'twitter_card_title', 'Card Title' );
			}

			add_metadata_field( $post_type . '_twitter_card', 'twitter_card_type', 'Card Type', 'dropdown', array(
				'options' => array_merge(
					array( '' => 'None' ),
					$card_types
				)
			) );

			if( !apply_filters( 'twitter_card_description_setting_disabled', false ) ){
				add_metadata_field( $post_type . '_twitter_card', 'twitter_card_description', 'Card Description', 'textarea' );
			}

			if( count( array_intersect( array( 'gallery', 'summary_large_image', 'product' ), array_keys( $card_types ) ) ) ){
				if ( !class_exists( 'MultiPostThumbnails' ) )
					return _doing_it_wrong( __FUNCTION__, 'The Multi Post Thumbnails plugin must be active for some twitter card types to work correctly.', '' );

				if( in_array( 'gallery', array_keys( $card_types ) ) ){

					for($i=1; $i <= 4; $i++){
						new MultiPostThumbnails( array(
							'label' => 'Twitter Card - Gallery Image #' . $i,
							'id' => 'twitter-card-gallery-image-' . $i,
							'post_type' => $post_type
						) );
					}
				}

				if( in_array( 'summary_large_image', array_keys( $card_types ) ) ){

					new MultiPostThumbnails( array(
						'label' => 'Twitter Card - Large Image',
						'id' => 'twitter-card-large-image',
						'post_type' => $post_type
					) );
				}

				if( in_array( 'product', array_keys( $card_types ) ) ){

					add_metadata_field( $post_type . '_twitter_card', 'twitter_card_label1', 'Card Label 1', 'text', array(
						'description' => 'ex. Price'
					) );
					add_metadata_field( $post_type . '_twitter_card', 'twitter_card_data1', 'Card Data 1', 'text', array(
						'description' => 'ex. $3.00'
					) );

					add_metadata_field( $post_type . '_twitter_card', 'twitter_card_label2', 'Card Label 2', 'text', array(
						'description' => 'ex. Color'
					) );
					add_metadata_field( $post_type . '_twitter_card', 'twitter_card_data2', 'Card Data 2', 'text', array(
						'description' => 'ex. Black'
					) );

					new MultiPostThumbnails( array(
						'label' => 'Twitter Card - Product Image',
						'id' => 'twitter-card-product-image',
						'post_type' => $post_type
					) );
				}
			}
		}
	}

	/**
	 * Adds support for the $post_type and for the $card_types provided.
	 * If no $card_types provided, uses self::$card_types by default
	 * @param string  $post_type
	 * @param boolean|array $card_types
	 */
	public static function add_post_type($post_type, $card_types = false){
		if( !isset( self::$post_types[$post_type] ) ){
			if( !$card_types )
				$card_types = self::$card_types;

			self::$post_types[$post_type] = $card_types;
		}
	}

	/**
	 * Gets the title to use for the Twitter Card by looking for the Twitter Card title setting,
	 * if that is either disabled or empty, it uses the post title.
	 * @return string
	 */
	static function get_the_title(){
		$title_setting_val = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_title' );
		return apply_filters( 'twitter_card_title_setting_disabled', false ) ? get_the_title() : ( !empty( $title_setting_val ) ? $title_setting_val : get_the_title() );
	}

	/**
	 * Gets the description to use for the Twitter Card by looking for the Twitter Card description setting,
	 * if that is either disabled or empty, it uses the post excerpt and if that is not explicitly set, sets it to a 40-word max trimmed post content
	 * @return string
	 */
	static function get_the_description(){
		$description_setting_val = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_description' );
		$description = ( has_excerpt() ) ? get_the_excerpt() : wp_trim_words( strip_shortcodes( strip_tags( get_post( get_queried_object_id() )->post_content ) ), 40, '...' );
		return apply_filters( 'twitter_card_description_setting_disabled', false ) ? $description : ( !empty( $description_setting_val ) ? $description_setting_val : $description );
	}

	/**
	 * Action to remove meta fields and meta boxes based on the Twitter Card type
	 * Only show fields that are relevent to the currently selected type.
	 * @param string $post_type
	 * @param WP_Post $post
	 */
	static function add_meta_boxes($post_type, $post){
		if( !post_type_supports( $post_type, $post_type . '_twitter_card' ) )
			return;

		$card_type = Voce_Meta_API::GetInstance()->get_meta_value( $post->ID, $post_type . '_twitter_card', 'twitter_card_type' );

		// foreach( array( 'normal', 'advanced', 'side' ) as $context ){
		// 	if( $card_type != 'gallery' ){
		// 		for($i=1; $i <= 4; $i++)
		// 			remove_meta_box( sprintf( '%s-twitter-card-gallery-image-%d', $post_type, $i ), $post_type, $context );
		// 	}
		// 	if( $card_type != 'summary_large_image' ){
		// 		remove_meta_box( sprintf( '%s-twitter-card-large-image', $post_type ), $post_type, $context );
		// 	}
		// 	if( $card_type != 'product' ){
		// 		remove_meta_box( sprintf( '%s-twitter-card-product-image', $post_type ), $post_type, $context );
		// 		for( $i=1; $i<=2; $i++ ){
		// 			remove_metadata_field( $post_type . '_twitter_card', 'twitter_card_label' . $i );
		// 			remove_metadata_field( $post_type . '_twitter_card', 'twitter_card_data' . $i );
		// 		}
		// 	}
		// 	if( $card_type == 'none' ){
		// 		remove_metadata_field( $post_type . '_twitter_card', 'twitter_card_title' );
		// 		remove_metadata_field( $post_type . '_twitter_card', 'twitter_card_description' );
		// 	}
		// }

	}

	/**
	 * Renders card meta fields in wp_head
	 * @return string
	 */
	static function render_card_meta(){
		if( !is_singular( array_keys( self::$post_types ) ) )
			return;

		if( !( $card_type = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_type' ) ) )
			return;

		$card_data = array(
			'card' => $card_type,
			'title' => self::get_the_title(),
			'description' => self::get_the_description()
		);

		switch( $card_type ){
			case 'gallery':

				$images = array();
				for($i=1; $i <= 4; $i++){
					if( MultiPostThumbnails::has_post_thumbnail( get_post_type(), 'twitter-card-gallery-image-' . $i, get_queried_object_id() ) ){
						$images['image' . ($i - 1)] = MultiPostThumbnails::get_post_thumbnail_url( get_post_type(), 'twitter-card-gallery-image-' . $i, get_queried_object_id(), 'medium' );
					}
				}

				if( !empty( $images ) )
					$card_data = array_merge( $card_data, $images );

			break;
			case 'summary_large_image':

				if( MultiPostThumbnails::has_post_thumbnail( get_post_type(), 'twitter-card-large-image', get_queried_object_id() ) )
					$card_data['image'] = MultiPostThumbnails::get_post_thumbnail_url( get_post_type(), 'twitter-card-large-image', get_queried_object_id(), 'large' );

			break;
			case 'product':
				if( MultiPostThumbnails::has_post_thumbnail( get_post_type(), 'twitter-card-product-image', get_queried_object_id() ) )
					$card_data['image'] = MultiPostThumbnails::get_post_thumbnail_url( get_post_type(), 'twitter-card-product-image', get_queried_object_id(), 'large' );

				$card_data['data1'] = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_data1' );
				$card_data['label1'] = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_label1' );
				$card_data['data2'] = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_data2' );
				$card_data['label2'] = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_label2' );
			break;
			case 'photo':
			case 'summary':

				if( has_post_thumbnail() ){
					$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
					if( $image )
						$card_data['image'] = $image[0];
				}

			break;
		}

		// Filter Twitter Card data so any values can be overridden externally from the plugin.
		$card_data = apply_filters( 'twitter_card_data', $card_data );

		// Gallery cards are not valid unless all four images are set
		// Photo cards are not valid unless there is a image set
		// If not valid, return empty.
		if( $card_type == 'gallery' ){
			for( $i=0; $i<4; $i++ ){
				if( !isset( $card_data['image' . $i] ) || empty( $card_data['image' . $i] ) )
					return;
			}
		} else if( $card_type == 'photo' ){
			if( !isset( $card_data['image'] ) || empty( $card_data['image'] ) )
				return;
		} else if( $card_type == 'product' ){
			foreach( array( 'image', 'data1', 'label1', 'data2', 'label2' ) as $required ){
				if( !isset( $card_data[$required] ) || empty( $card_data[$required] ) )
					return;
			}
		}


		foreach ( $card_data as $key => $value ) {
			if ( empty($value) )
				continue;

			printf( '<meta name="twitter:%s" content="%s" />' . PHP_EOL, esc_attr( $key ), esc_attr( $value ) );
		}
	}
}
add_action( 'init', array( 'WP_Twitter_Cards', 'init' ), 99 );

do_action( 'wp_load_dependency', 'multiple-post-thumbnails', 'multi-post-thumbnails.php' );
}