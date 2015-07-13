<?php

interface Twitter_Card_Player {

	public static function is_valid_url( $url );

	public function get_player_url();

	public function get_player_width();

	public function get_player_height();

	public function get_player_image();

}