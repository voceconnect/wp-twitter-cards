<?php

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
		do_action( 'wp_load_dependency', 'multiple-post-thumbnails', 'multi-post-thumbnails.php' );
		
		if ( !class_exists( 'Voce_Meta_API' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Voce Post Meta plugin must be active for the Twitter Card integration to work correctly.', '' );
		if ( !class_exists( 'MultiPostThumbnails' ) )
			return _doing_it_wrong( __FUNCTION__, 'The Multi Post Thumbnails plugin must be active for the Twitter Card integration to work correctly.', '' );

		add_action( 'wp_head', array( __CLASS__, 'render_card_meta' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_filter( 'meta_type_mapping', array( __CLASS__, 'meta_field_mapping' ) );

		self::add_post_meta();
	}

	public static function meta_field_mapping( $mapping ) {
		$mapping['non_editable'] = array(
			'class' => 'Voce_Meta_Field',
			'args' => array(
				'display_callbacks' => array( function( $field, $value, $post_id ) {
					echo '<p>';
					voce_field_label_display( $field );
					printf( '<span id="%s">%s</span>', esc_attr( $field->get_input_id() ), $value );
					echo '</p>';
				} ),
				'sanitize_callbacks' => array( function( $field, $old_value, $new_value, $post_id ) {
					return $old_value;
				} )
			)
		);
		return $mapping;
	}

	public static function admin_enqueue_scripts( $hook ) {
		if ( in_array( $hook, array('post-new.php', 'post.php') ) ) {
			$this_plugin_directory_uri = apply_filters( 'twitter_card_install_directory', plugins_url( '', __FILE__ ), __FILE__ );
			wp_enqueue_script( 'twitter-card-metabox', sprintf( '%s/%s', untrailingslashit( $this_plugin_directory_uri ), 'metabox.js' ), array( 'jquery' ) );
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
				add_metadata_field( $group, 'twitter_card_video_url', 'Video URL', 'text', array(
					'description' => 'URL of the video to display on the card.',
					'sanitize_callbacks' => array( array( __CLASS__, 'handle_video_url' ) )
				) );
				add_metadata_field( $group, 'twitter_card_player_url', 'Player URL', 'non_editable' );
				add_metadata_field( $group, 'twitter_card_player_width', 'Player Width', 'non_editable' );
				add_metadata_field( $group, 'twitter_card_player_height', 'Player Height', 'non_editable' );
				add_metadata_field( $group, 'twitter_card_player_image', 'Player Image', 'non_editable' );
			}
		}
	}

	private static function get_card_players() {
		return array( 'Twitter_Card_Youtube_Player' );
	}

	public static function handle_video_url( $field, $old_value, $new_value, $post_id ) {
		$updated = false;

		foreach ( self::get_card_players() as $player_class ) {

			if (
				class_exists($player_class)
				&& in_array('Twitter_Card_Player', class_implements($player_class))
				&& $player_class::is_valid_url($new_value)
			) {

				$player = new $player_class( $new_value );

				if ( $player instanceof $player_class ) {

					$player_url = $player->get_player_url();
					$player_width = $player->get_player_width();
					$player_height = $player->get_player_height();
					$player_image = $player->get_player_image();

					$player_data = array(
						'url' => strpos($player_url, 'https://') === 0 ? $player_url : false,
						'width' => intval($player_width),
						'height' => intval($player_height),
						'image' => $player_image
					);

					foreach ( $player_data as $key => $value ) {
						$meta_key = sprintf( '%s_twitter_card_twitter_card_player_%s', get_post_type(), $key );
						if ( $value )
							update_post_meta( $post_id, $meta_key, $value );
						else
							delete_post_meta( $post_id, $meta_key );
					}

					$updated = true;
					break;
				}
			}
		}

		if ( !$updated ) {
			foreach ( array( 'url', 'width', 'height', 'image' ) as $key )
				delete_post_meta( $post_id, get_post_type() . '_twitter_card_twitter_card_player_' . $key );
		}

		return $new_value;
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

				$card_data['player'] = get_vpm_value( $vpm_group, 'twitter_card_player_url', get_queried_object_id() );
				$card_data['player:width'] = get_vpm_value( $vpm_group, 'twitter_card_player_width', get_queried_object_id() );
				$card_data['player:height'] = get_vpm_value( $vpm_group, 'twitter_card_player_height', get_queried_object_id() );
				$card_data['image'] = get_vpm_value( $vpm_group, 'twitter_card_player_image', get_queried_object_id() );

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