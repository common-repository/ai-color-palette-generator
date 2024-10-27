<?php

namespace Red8;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AI_Color_Palette {

	private $primary_color;
	private $target_audience;
	private $key;
	public $palette = false;
	public $options = array();

	public function __construct( $key = '', $primary_color = '', $target_audience = '' ) {
		$this->primary_color   = $primary_color;
		$this->target_audience = $target_audience;
		$this->key             = $key;
		$this->load_options();
	}

	public function load_options() {
		$options       = get_option( 'red8_ai_color_palette_option' );
		$this->options = array(
			'palette'        => $options['generated_palettes'] ?? '',
			'replace_or_add' => $options['replace_or_add'] ?? '',
		);
	}


	public function add_color_palette( $theme_json ) {

		$current_color_palette = $theme_json->get_data()['settings']['color']['palette']['theme'];
		$new_colors            = $this->form_new_colors();
		if ( $this->options['replace_or_add'] === 'add' ) {
			$merged_colors = array_merge( $current_color_palette, $new_colors );
		} else {
			$merged_colors = $new_colors;
		}

		$new_data = array(
			'version'  => 2,
			'settings' => array(
				'color' => array(
					'palette' => $merged_colors,
				),
			),
		);

		return $theme_json->update_with( $new_data );
	}

	private function form_new_colors() {
		$new_colors = array();

		if ( $this->options['palette'] ) {
			foreach ( $this->options['palette'] as $color ) {
				$new_colors[] = array(
					'slug'  => sanitize_title( $color['name'] ),
					'color' => $color['color'],
					'name'  => $color['name'],
				);
			}
		}

		return $new_colors;
	}




	public function generate() {

		if ( ! $this->primary_color || ! $this->target_audience || ! $this->key ) {
			return false;
		}

		try {
			$client  = new Client();
			$headers = array(
				'Content-Type' => 'application/json',
				'key'          => $this->key,
			);
			$body    = '{
			  "primary_color": "' . $this->primary_color . '",
			  "target_audience": "' . $this->target_audience . '"
			}';

			$options = array(
				'headers' => $headers,
				'body'    => $body,
			);

			$response = $client->post( 'https://ai-api.red8interactive.com/wp-json/ai-color/v1/generate', $options );

			$this->palette = json_decode( $response->getBody()->getContents(), true );

			return $this->palette;
		} catch ( \Exception | GuzzleException $e ) {
			return false;
		}
	}

	public static function get_saved_palette() {

		$options = get_option( 'red8_ai_color_palette_option' );

		return $options['generated_palettes'] ?? array();


	}
}
