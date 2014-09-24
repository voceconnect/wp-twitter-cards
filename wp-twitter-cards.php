<?php

if ( !class_exists( 'WP_Twitter_Cards' ) ){

class WP_Twitter_Cards {

	static $card_types = array(
		'gallery' => 'Gallery',
		'photo' => 'Photo',
		'summary_large_image' => 'Summary Large Image',
		'summary' => 'Summary',
		'product' => 'Product',
		'player' => 'Player'
	);

	static $post_types = array();

	public static function init(){
		if ( !class_exists( 'Voce_Meta_API' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Voce Post Meta plugin must be active for the Twitter Card integration to work correctly.', '' );
		if ( !class_exists( 'MultiPostThumbnails' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Multi Post Thumbnails plugin must be active for the Twitter Card integration to work correctly.', '' );

		add_action( 'wp_head', array( __CLASS__, 'render_card_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		self::add_post_meta();
	}

	public static function admin_enqueue_scripts( $hook ) {
		if ( in_array( $hook, array('post-new.php', 'post.php') ) ) {
			wp_enqueue_script( 'twitter-card-metabox', plugins_url( '/metabox.js', __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'twitter-card-metabox', 'twitterCardOptions', array(
				'postType' => get_post_type()
			) );
		}
	}

	private static function add_post_meta() {
		foreach( self::$post_types as $post_type => $card_types ){
			$group = $post_type . '_twitter_card';
			$card_type_keys = array_keys($card_types);

			add_post_type_support( $post_type, $group );

			add_metadata_group( $group, 'Twitter Card' );

			add_metadata_field( $group, 'twitter_card_type', 'Card Type', 'dropdown', array(
				'options' => array_merge(
					array( '' => 'None' ),
					$card_types
				)
			) );

			if( !apply_filters( 'twitter_card_title_setting_disabled', false ) ){
				add_metadata_field( $group, 'twitter_card_title', 'Card Title' );
			}

			if( !apply_filters( 'twitter_card_description_setting_disabled', false ) ){
				add_metadata_field( $group, 'twitter_card_description', 'Card Description', 'textarea' );
			}

			if( in_array( 'gallery', $card_type_keys ) ){

				for($i=1; $i <= 4; $i++){
					new MultiPostThumbnails( array(
						'label' => 'Twitter Card - Gallery Image #' . $i,
						'id' => 'twitter-card-gallery-image-' . $i,
						'post_type' => $post_type
					) );
				}
			}

			if( in_array( 'summary_large_image', $card_type_keys ) ){

				new MultiPostThumbnails( array(
					'label' => 'Twitter Card - Large Image',
					'id' => 'twitter-card-large-image',
					'post_type' => $post_type
				) );
			}

			if( in_array( 'product', $card_type_keys ) ){

				add_metadata_field( $group, 'twitter_card_label1', 'Card Label 1', 'text', array(
					'description' => 'ex. Price'
				) );
				add_metadata_field( $group, 'twitter_card_data1', 'Card Data 1', 'text', array(
					'description' => 'ex. $3.00'
				) );

				add_metadata_field( $group, 'twitter_card_label2', 'Card Label 2', 'text', array(
					'description' => 'ex. Color'
				) );
				add_metadata_field( $group, 'twitter_card_data2', 'Card Data 2', 'text', array(
					'description' => 'ex. Black'
				) );

				new MultiPostThumbnails( array(
					'label' => 'Twitter Card - Product Image',
					'id' => 'twitter-card-product-image',
					'post_type' => $post_type
				) );
			}

			if ( in_array( 'player', $card_type_keys ) ) {
				add_metadata_field( $group, 'twitter_card_player_url', 'Player URL', 'text', array(
					'description' => 'URL to iframe player. This must be a HTTPS URL. Required for player card to work.',
					'sanitize_callbacks' => array( function($field, $old_value, $new_value, $post_id) {
						return esc_url_raw( $new_value, array('https') );
					} )
				) );
				add_metadata_field( $group, 'twitter_card_player_width', 'Player Width', 'numeric', array(
					'description' => 'Width of iframe specified in player field in pixels. Required for player card to work.'
				) );
				add_metadata_field( $group, 'twitter_card_player_height', 'Player Height', 'numeric', array(
					'description' => 'Height of iframe specified in player field in pixels. Required for player card to work.'
				) );
				add_metadata_field( $group, 'twitter_card_player_stream', 'Player Stream', 'text', array(
					'description' => 'Optional URL to raw stream. If provided, the stream must be delivered in the MPEG-4 container format',
					'sanitize_callbacks' => array( function($field, $old_value, $new_value, $post_id) {
						return esc_url_raw( $new_value );
					} )
				) );
				add_metadata_field( $group, 'twitter_card_stream_content_type', 'Stream Content Type', 'text', array(
					'description' => 'The MIME type/subtype combination that describes the content contained in the stream field. Required if stream is set.'
				) );

				new MultiPostThumbnails( array(
					'label' => 'Twitter Card - Player Image',
					'id' => 'twitter-card-player-image',
					'post_type' => $post_type
				) );
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
		$title_setting_val = get_vpm_value( get_post_type() . '_twitter_card', 'twitter_card_title', get_queried_object_id() );
		return apply_filters( 'twitter_card_title_setting_disabled', false ) ? get_the_title() : ( !empty( $title_setting_val ) ? $title_setting_val : get_the_title() );
	}

	/**
	 * Gets the description to use for the Twitter Card by looking for the Twitter Card description setting,
	 * if that is either disabled or empty, it uses the post excerpt and if that is not explicitly set, sets it to a 40-word max trimmed post content
	 * @return string
	 */
	static function get_the_description(){
		$description_setting_val = get_vpm_value( get_post_type() . '_twitter_card', 'twitter_card_description', get_queried_object_id() );
		$description = ( has_excerpt() ) ? get_the_excerpt() : wp_trim_words( strip_shortcodes( strip_tags( get_post( get_queried_object_id() )->post_content ) ), 40, '...' );
		return apply_filters( 'twitter_card_description_setting_disabled', false ) ? $description : ( !empty( $description_setting_val ) ? $description_setting_val : $description );
	}

	/**
	 * Renders card meta fields in wp_head
	 * @return string
	 */
	static function render_card_meta(){
		if( !is_singular( array_keys( self::$post_types ) ) )
			return;

		$vpm_group = get_post_type() . '_twitter_card';

		if( !( $card_type = get_vpm_value( $vpm_group, 'twitter_card_type', get_queried_object_id() ) ) )
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

				$card_data['data1'] = get_vpm_value( $vpm_group, 'twitter_card_data1', get_queried_object_id() );
				$card_data['label1'] = get_vpm_value( $vpm_group, 'twitter_card_label1', get_queried_object_id() );
				$card_data['data2'] = get_vpm_value( $vpm_group, 'twitter_card_data2', get_queried_object_id() );
				$card_data['label2'] = get_vpm_value( $vpm_group, 'twitter_card_label2', get_queried_object_id() );
			break;
			case 'photo':
			case 'summary':

				if( has_post_thumbnail() ){
					$image = wp_get_attachment_image_src( get_post_thumbnail_id(), 'medium' );
					if( $image )
						$card_data['image'] = $image[0];
				}

			break;
			case 'player':
				if( MultiPostThumbnails::has_post_thumbnail( get_post_type(), 'twitter-card-player-image', get_queried_object_id() ) )
					$card_data['image'] = MultiPostThumbnails::get_post_thumbnail_url( get_post_type(), 'twitter-card-player-image', get_queried_object_id(), 'large' );

				$card_data['player'] = get_vpm_value( $vpm_group, 'twitter_card_player_url', get_queried_object_id() );
				$card_data['player:width'] = get_vpm_value( $vpm_group, 'twitter_card_player_width', get_queried_object_id() );
				$card_data['player:height'] = get_vpm_value( $vpm_group, 'twitter_card_player_height', get_queried_object_id() );
				$card_data['player:stream'] = get_vpm_value( $vpm_group, 'twitter_card_player_stream', get_queried_object_id() );
				$card_data['player:stream:content_type'] = get_vpm_value( $vpm_group, 'twitter_card_stream_content_type', get_queried_object_id() );

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
		} else if ( $card_type == 'player' ) {
			foreach ( array( 'title', 'description', 'image', 'player', 'player:width', 'player:height' ) as $required ) {
				if( !isset( $card_data[$required] ) || empty( $card_data[$required] ) )
					return;
			}
			if ( !empty($card_data['player:stream']) && empty($card_data['player:stream:content_type']) )
				return;
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