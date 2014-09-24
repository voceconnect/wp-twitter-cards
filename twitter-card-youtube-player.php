<?php

class Twitter_Card_Youtube_Player {

	const ENDPOINT = 'http://www.youtube.com/oembed';

	private $data;

	function __construct( $url ) {
		$this->data = $this->get_video_data( $url );
	}

	private function get_video_data( $video_url ) {
		$data = array();
		$args = array(
			'url' => $video_url,
			'format' => 'json'
		);
		$url = add_query_arg( $args, self::ENDPOINT );

		$request = wp_remote_get( $url );

		if (
			is_array($request)
			&& !empty($request['response']['code'])
			&& $request['response']['code'] === 200
			&& !empty($request['body'])
		) {
			$_data = json_decode($request['body']);
			if ( !is_null($_data) )
				$data = $_data;
		}

		return $data;
	}

	public function get_player_url() {
		if ( !empty($this->data->html) ) {
			$html = $this->data->html;
			$pattern = '/src="(.*?)"/';
			$matches = array();
			preg_match_all( $pattern, $html, $matches);
			if ( !empty($matches[1][0]) )
				$url = str_replace('http:', 'https:', $matches[1][0]);
				return $url;
		}
		return false;
	}

	public function get_player_width() {
		if ( !empty($this->data->width) )
			return intval($this->data->width);
		return false;
	}

	public function get_player_height() {
		if ( !empty($this->data->height) )
			return intval($this->data->height);
		return false;
	}

	public function get_player_image() {
		if ( !empty($this->data->thumbnail_url) )
			return $this->data->thumbnail_url;
		return false;
	}

}