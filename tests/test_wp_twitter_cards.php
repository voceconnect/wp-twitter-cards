<?php

class WP_Twitter_cards_Tests extends WP_UnitTestCase {
	
    /**
	 * Ensure that WP_Dependency_Loader is loaded
	 */	

    function test_wp_dependency_loader_loaded(){
    	$this->assertTrue( class_exists( 'WP_Dependency_Loader' ) );
    }

    /**
	 * Ensure that MultiPostThumbnails is loaded
	 */	    

    function test_multi_post_thumbnails_loaded(){
    	$this->assertTrue( class_exists( 'MultiPostThumbnails' ) );
    }

    /**
	 * Ensure that Voce_Meta_API is loaded
	 */	    

    function test_voce_meta_api_loaded(){
    	$this->assertTrue( class_exists( 'Voce_Meta_API' ) );
    }

    /**
     * Ensure that the plugin has been installed and activated.
     */
    function test_plugin_activated() {

        $this->assertTrue( is_plugin_active( 'wp-twitter-cards/wp-twitter-cards.php' ) );

    }

    /**
	 * Ensure that main hooks are set for "manage_options" (administrator) users
	 *
	 * NOTE: after this test, the hooks will be set!
	 */

	function test_hooks_for_privileged_user() {
		set_current_screen( 'post-new.php' );
		$user = $this->factory->user->create_and_get( array(
			'role' => 'administrator'
		) );
		$this->assertTrue( $user->has_cap( 'manage_options' ), 'Administrator role user does not have "manage_options" capability.' );
		wp_set_current_user( $user->ID );
		$this->assertEquals( $user->ID, get_current_user_id(), "User {$user->ID} is not current user." );
		$hooks = array(
			'add_meta_boxes'	=> 'add_meta_boxes',
			'wp_head'         	=> 'render_card_meta',
		);
		foreach ( $hooks as $hook => $callback ) {
			$priority = has_action( $hook, array( 'WP_Twitter_Cards', $callback ) );
			$this->assertGreaterThan( 0, $priority, "WP_Twitter_Cards::{$callback} not attached to {$hook}." );
		}

	}

    /**
     * Ensure that the metabox is loaded
     *
     * @global array $wp_meta_boxes
     */

    function test_meta_box_loaded(){
		WP_Twitter_Cards::add_post_type( 'post' );
		set_current_screen( 'post-new.php' );
		global $wp_meta_boxes;
		if ( is_array ( $wp_meta_boxes ) ) {
			$this->assertArrayHasKey( 'page_twitter_card', $wp_meta_boxes['page']['normal']['default'] );
		} else {
			$this->assertFalse( true, '$wp_meta_boxes is not an array' );
		}

    }

    


} 