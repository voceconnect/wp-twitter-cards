<?php

class WP_Twitter_cards_Tests extends WP_UnitTestCase {
 
    function test_wp_dependency_loader_loaded(){
    	$this->assertTrue( class_exists( 'WP_Dependency_Loader' ) );

    }

    function test_multi_post_thumbnails_loaded(){

    	$this->assertTrue( class_exists( 'MultiPostThumbnails' ) );
    }

     function test_voce_meta_api_loaded(){

    	$this->assertTrue( class_exists( 'Voce_Meta_API' ) );
    }
    


} 