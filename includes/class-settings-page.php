<?php
namespace Red8;

class Settings_Page {

	private $settings_page = 'red8_ai_color_palette_settings';

	public function __construct() {
		// Hook to add the menu item
		add_action( 'admin_menu', array( $this, 'add_menu' ) );

		// Hook to initialize settings
		add_action( 'admin_init', array( $this, 'initialize_settings' ) );
	}

	// Add a menu item under Appearance
	public function add_menu() {
		$page = add_theme_page(
			'AI Color Palette',
			'AI Color Palette',
			'manage_options',
			'ai-color-palette-generator',
			array( $this, 'render_page' )
		);

		wp_register_script( 'ai-color-palette-generator', RED8_PATH . '/js/ai-color-palette-generator.js', array(), 1, array() );

		add_action(
			'admin_print_scripts-' . $page,
			function () {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'ai-color-palette-generator' );
			}
		);
	}

	// Callback function to render the settings page
	public function render_page() {
		settings_errors( $this->settings_page );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form id="red8_ai_color_form" method="post" action="options.php">
				<?php
				settings_fields( 'red8_ai_color_palette_options' );

				do_settings_sections( '$this->settings_page' );
				?>

				<div class="" style="display: inline-block">
					<?php
						submit_button( 'Generate Colors Now' );
					?>
				</div>

				<div class="loader" style="display: none;    height: 1.9rem;    position: relative;    top: 0.8rem; left: 0.3rem">
					<img class="" decoding="async" src="/wp-includes/images/spinner-2x.gif" alt="spinner-2x.gif">
				</div>

			</form>
		</div>
		<?php
	}

	// Initialize settings
	public function initialize_settings() {
		// Register a setting and its sanitization callback
		register_setting(
			'red8_ai_color_palette_options',
			'red8_ai_color_palette_option',
			array( $this, 'sanitize_callback' )
		);

		add_settings_section(
			'red8_ai_color_palette_section',
			'',
			array( $this, 'section_callback' ),
			'$this->settings_page'
		);

		add_settings_field(
			'red8_ai_api_key',
			'OpenAI API Key',
			array( $this, 'api_key_field' ),
			'$this->settings_page',
			'red8_ai_color_palette_section'
		);

		add_settings_field(
			'red8_ai_primary_color_field',
			'Primary Color',
			array( $this, 'primary_color_field' ),
			'$this->settings_page',
			'red8_ai_color_palette_section'
		);

		add_settings_field(
			'red8_ai_target_audience_text_field',
			'Target Audience',
			array( $this, 'target_audience_text_field' ),
			'$this->settings_page',
			'red8_ai_color_palette_section'
		);

		add_settings_field(
			'red8_ai_replace_current_color_field',
			'Replace or add colors to current theme palette?',
			array( $this, 'replace_current_color_field' ),
			'$this->settings_page',
			'red8_ai_color_palette_section'
		);

		add_settings_field(
			'red8_ai_generated_palette_field',
			'',
			array( $this, 'generated_palette_field' ),
			'$this->settings_page',
			'red8_ai_color_palette_section'
		);
	}

	// Sanitization callback for the option
	public function sanitize_callback( $input ) {

		$output = array();
		foreach ( $input as $field => $value ) {
			$output[ $field ] = sanitize_text_field( $value );
		}

		$primary_color   = $output['primary_color'];
		$target_audience = $output['target_audience'];
		$api_key         = $output['api_key'];

		Key_Expiration_Handler::update_key_timestamp($api_key);

		$output['generated_palettes'] = AI_Color_Palette::get_saved_palette();


		if ( ! $primary_color ) {
			add_settings_error(
				$this->settings_page,
				'error',
				'Primary color can not be empty'
			);
		} elseif ( ! $target_audience ) {
			add_settings_error(
				$this->settings_page,
				'error',
				'Target audience can not be empty'
			);
		} elseif ( ! $api_key ) {
			add_settings_error(
				$this->settings_page,
				'error',
				'You need an OpenAI API Key'
			);
		} else {

			$color_palette = new AI_Color_Palette( $api_key, $primary_color, $target_audience );
			$color_palette->generate();

			if ( $color_palette->palette ) {
				$output['generated_palettes'] = $color_palette->palette;
			} else {

				$output['generated_palettes'] =array();
				add_settings_error(
					$this->settings_page,
					'error',
					'Could not generate new colors, please try again later'
				);
			}
		}


		return ( $output );
	}

	public function section_callback() {
		?>
		<p>Create a 60/30/10 website color palette with seven entries based on a primary color and target audience.</p>
		<p><em><small>To keep your OpenAI key secure it will not be stored on our server. It will be held within the
		              plugin for 10 days. After 10 days your key will be deleted. If you need to reuse the plugin after
		              10 days, you will need to add your key again.</small></em></p>

		<?php
	}

	public function target_audience_text_field() {
		$options = get_option( 'red8_ai_color_palette_option' );
		$value   = $options['target_audience'] ?? '';

		?>
		<textarea
			name="red8_ai_color_palette_option[target_audience]"
			placeholder="describe the website's primary audience and what they are seeking..."
			id="" cols="30" rows="8"><?php echo esc_attr( $value ); ?></textarea>
		<?php
	}

	public function api_key_field() {
		$options = get_option( 'red8_ai_color_palette_option' );
		$value   = $options['api_key'] ?? '';

		?>
		<input	name="red8_ai_color_palette_option[api_key]"
					onclick="reveal_password(this)"
					type="password"
					value="<?php echo esc_attr( $value ); ?>"
					style="min-width:320px;"
		/>

		<p><a href="#" class="red8_ai_key_toggle_instructions">Instructions</a></p>

		<div class="red8_ai_key_instructions" style="background-color:white; border-radius: 4px; margin-top: 0.5rem; margin-bottom: 0.5rem ; padding: 1rem; width: auto;display: none">
			<p>
				To obtain an OpenAI API key, follow these simple steps:
			</p>
			<ol>
				<li> Visit the <a href="https://openai.com/" target="_blank">OpenAI website</a> and either login or create a new account.</li>
				<li> Select the API Section.</li>
				<li> Select API Keys from the menu on the left.</li>
				<li> Click on ‘Create new secret key.’</li>
				<li> Your API key will be displayed. Copy this key.</li>
				<li> Return to our plugin and paste your API key in the designated field.</li>
				<li> Click ‘Save’ to store your API key securely in the plugin. Your plugin is now ready to use!</li>
			</ol>
			<em>	 Note: Keep your API key confidential. Do not share it publicly or with unauthorized individuals to ensure security</em>

		</div>

		<?php
	}

	public function primary_color_field() {
		$options = get_option( 'red8_ai_color_palette_option' );
		$value   = $options['primary_color'] ?? '';

		?>


		<input type="text"
				value="<?php echo esc_attr( $value ); ?>"
				name="red8_ai_color_palette_option[primary_color]"
				class="red8_color_field"
				data-default-color="#440000"
		/>
		<?php
	}
	public function replace_current_color_field() {
		$options = get_option( 'red8_ai_color_palette_option' );
		$value   = $options['replace_or_add'] ?? 'replace';

		?>
		<label for="red8_ai_color_palette_option_replace">
			<input
				type="radio"
				name="red8_ai_color_palette_option[replace_or_add]"
				id="red8_ai_color_palette_option_replace"
				value="replace"
			<?php echo $value === 'replace' ? 'checked=checked' : ''; ?>"
															/>
															Replace
		</label>
		<br><br>
		<label for="red8_ai_color_palette_option_add">
			<input
				type="radio"
				name="red8_ai_color_palette_option[replace_or_add]"
				id="red8_ai_color_palette_option_add"
				value="add"
				<?php echo $value === 'add' ? 'checked=checked' : ''; ?>"
				/>
			Add
		</label>



		<?php
	}

	public function generated_palette_field() {
		$options = get_option( 'red8_ai_color_palette_option' );
		$palette = $options['generated_palettes'] ?? array();

		?>
		<style type="text/css">
			.red8_color_palette {
				display: flex;
				flex-wrap: wrap;
			}

			.red8_color_palette > div {
				margin: 10px;
				text-align: center;
			}

			.red8_color_palette span.color {
				display: block;
				width: 60px;
				height: 60px;
				border-radius: 50%;
				margin: 0 auto 5px;
			}

			.red8_color_palette b {
				display: block;
				font-size: 10px;
				font-weight: bold;
			}


		</style>

		<div class="red8_color_palette">
		<?php
		if ( $palette ) {
			foreach ( $palette as $color ) {
				?>
				<div>
					<span class="color" style="background-color: <?php echo esc_html( $color['color'] ); ?>;"></span>
					<b><?php echo esc_html( $color['name'] ); ?></b>
				</div>
				<?php
			}
		}

		?>
		</div>
		<?php
	}


}



