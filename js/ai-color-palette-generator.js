jQuery(
	function ($) {

		$( '.red8_color_field' ).wpColorPicker();

		$( "#red8_ai_color_form" ).on(
			"submit",
			function () {

				$( this ).find( ".loader" ).css( "display","inline-block" );

			}
		)

		$( ".red8_ai_key_toggle_instructions" ).click(
			function (e) {
				e.preventDefault();
				$( ".red8_ai_key_instructions" ).slideToggle().css("display","inline-block");
			}
		)
	}
)

const reveal_password = (field) => {
	jQuery( field ).attr( "type","text" );
}
