<?php

namespace Red8;
class Key_Expiration_Handler {
	private $key_life_in_days = 10;

	public function __construct() {

		$this->schedule_expired_key_cron();
	}

	/**
	 * Update key timestamp only if it's a different key
	 * @return void
	 */
	public static function update_key_timestamp( $new_key ) {

		$current_options = get_option( 'red8_ai_color_palette_option' );
		$current_key_option  = $current_options['api_key'] ?? '';
		if ( $current_key_option !== $new_key ) {
			update_option( "red8_ai_color_palette_settings_key_saved_date", time() );
		}
	}

	public function schedule_expired_key_cron() {

		add_filter( 'cron_schedules', array( $this, 'add_time_interval' ) );
		add_action( 'red8_schedule_expired_key_hook', array( $this, 'check_expired_key' ) );
		if ( ! wp_next_scheduled( 'red8_schedule_expired_key_hook' ) ) {
			wp_schedule_event( time() + 1, 'every_two_hours', 'red8_schedule_expired_key_hook' );
		}
	}

	public function add_time_interval( $schedules ) {
		$schedules['every_two_hours'] = array(
			'interval' => 60*60*2,
			'display'  => esc_html__( 'Every two Hours' ),
		);

		return $schedules;
	}

	public function check_expired_key() {

		$saved_key_timestamp     = get_option( "red8_ai_color_palette_settings_key_saved_date", false );

		$time_difference         = time() - $saved_key_timestamp;
		$time_difference_in_days = $time_difference / ( 60 * 60 * 24 );
		if ( $saved_key_timestamp and intval( $time_difference_in_days ) >= $this->key_life_in_days ) {

			$current_options = get_option( 'red8_ai_color_palette_option' );
			$current_options['api_key']='';

			update_option("red8_ai_color_palette_option",$current_options);

		}
	}
}
