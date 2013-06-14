<?php

class WP_Twitter_Cards {

	static $card_types = array(
		'gallery' => 'Gallery',
		'photo' => 'Photo',
		'summary_large_image' => 'Summary Large Image',
		'summary' => 'Summary'
	);

	static $post_types = array();

	public static function init(){
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 11, 2 );
		add_action( 'wp_head', array( __CLASS__, 'render_card_meta' ) );

		foreach( self::$post_types as $post_type => $card_types ){
			add_post_type_support( $post_type, $post_type . '_twitter_card' );

			add_metadata_group( $post_type . '_twitter_card', 'Twitter Card' );

			add_metadata_field( $post_type . '_twitter_card', 'twitter_card_type', 'Card Type', 'dropdown', array(
				'options' => array_merge(
					array( '' => 'None' ),
					$card_types
				)
			) );

			if( in_array( 'gallery', array_keys( $card_types ) ) ){
				for($i=1; $i <= 4; $i++){
					new MultiPostThumbnails( array(
						'label' => 'Twitter Gallery Image #' . $i,
						'id' => 'twitter-gallery-image-' . $i,
						'post_type' => $post_type
					) );
				}
			}

			if( in_array( 'summary_large_image', array_keys( $card_types ) ) ){
				new MultiPostThumbnails( array(
					'label' => 'Twitter Large Image',
					'id' => 'twitter-large-image',
					'post_type' => $post_type
				) );
			}
		}
	}

	public static function add_post_type($post_type, $card_types = false){
		if( !isset( self::$post_types[$post_type] ) ){
			if( !$card_types )
				$card_types = self::$card_types;

			self::$post_types[$post_type] = $card_types;
		}
	}

	public static function add_meta_boxes($post_type, $post){
		error_log($post_type);
		if( !post_type_supports( $post_type, $post_type . '_twitter_card' ) )
			return;

		$card_type = Voce_Meta_API::GetInstance()->get_meta_value( $post->ID, $post_type . '_twitter_card', 'twitter_card_type' );
		if( $card_type != 'gallery' ){
			for($i=1; $i <= 4; $i++){
				remove_meta_box( sprintf( '%s-twitter-gallery-image-%d', $post_type, $i ), $post_type, 'side' );
			}
		}
		if( $card_type != 'summary_large_image' ){
			remove_meta_box( sprintf( '%s-twitter-large-image', $post_type ), $post_type, 'side' );
		}
	}

	public static function render_card_meta(){
		if( !is_singular( array_keys( self::$post_types ) ) )
			return;

		if( !( $card_type = Voce_Meta_API::GetInstance()->get_meta_value( get_queried_object_id(), get_post_type() . '_twitter_card', 'twitter_card_type' ) ) )
			return;

		$card_data = array(
			'card' => $card_type,
			'title' => get_the_title(),
			'description' => ( has_excerpt() ) ? get_the_excerpt() : wp_trim_words( strip_shortcodes( strip_tags( get_post( get_queried_object_id() )->post_content ) ), 40, '...' )
		);

		switch( $card_type ){
			case 'gallery':
				$images = array();
				for($i=1; $i <= 4; $i++){
					if( $image_id = MultiPostThumbnails::get_post_thumbnail_id( get_post_type(), 'twitter-gallery-image-' . $i, get_queried_object_id() ) ){
						if( $image = wp_get_attachment_image_src( $image_id, 'medium' ) ){
							$images['image' . ($i - 1)] = $image[0];
						}
					}
				}
				if( !empty( $images ) )
					$card_data = array_merge( $card_data, $images );
			break;
			case 'summary_large_image':
				if( MultiPostThumbnails::has_post_thumbnail( get_post_type(), 'twitter-large-image', get_queried_object_id() ) ){
					$image = wp_get_attachment_image_src( MultiPostThumbnails::get_post_thumbnail_id( get_post_type(), 'twitter-large-image', get_queried_object_id() ), 'medium' );
					if( $image )
						$card_data['image'] = $image[0];
				}
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

		$card_data = apply_filters( 'twitter_card_data', $card_data );

		foreach ( $card_data as $key => $value ) {
			if ( empty($value) )
				continue;

		if( $card_type == 'gallery' ){
			for( $i=0; $i<4; $i++ ){
				if( !isset( $card_data['image' . $i] ) || empty( $card_data['image' . $i] ) ){
					return;
				}
			}
		} else if( $card_type == 'photo' ){
			if( !isset( $card_type['image'] ) || empty( $card_type['image'] ) )
				return;
		}

			printf( '<meta name="twitter:%s" content="%s" />' . PHP_EOL, esc_attr( $key ), esc_attr( $value ) );
		}
	}
}
add_action( 'init', array( 'WP_Twitter_Cards', 'init' ), 99 );