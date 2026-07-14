<?php

/**
 * Renders plugin meta box.
 *
 * @var array $event Event data.
 * @var \Cartellone\Data $evdata Data instance.
 */
?>
<table class="form-table">
	<tr>
		<th scope="row">
			<label for="cartellone_data"><?php esc_html_e( 'Date', 'cartellone' ); ?></label>
		</th>
		<td>
			<input type="text" name="cartellone_data" class="cartellone_data" value="<?php echo esc_attr( ! empty( $event['data'] ) ? date_i18n( 'd/m/Y', (int) $event['data'] ) : '' ); ?>" size="12" autocomplete="off">
			<label for="cartellone_ora"><?php esc_html_e( 'Time', 'cartellone' ); ?></label>
			<input type="text" name="cartellone_ora" class="cartellone_ora" value="<?php echo esc_attr( $event['ora'] ?? '' ); ?>" size="12">
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="cartellone_produzione"><?php esc_html_e( 'Production', 'cartellone' ); ?></label>
		</th>
		<td>
			<input type="text" name="cartellone_produzione" value="<?php echo esc_attr( $event['produzione'] ?? '' ); ?>" class="regular-text">
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="cartellone_protagonisti"><?php esc_html_e( 'Cast', 'cartellone' ); ?></label>
		</th>
		<td>
			<input type="text" name="cartellone_protagonisti" value="<?php echo esc_attr( $event['protagonisti'] ?? '' ); ?>" class="regular-text">
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="cartellone_credits"><?php esc_html_e( 'Credits', 'cartellone' ); ?></label>
		</th>
		<td>
			<textarea name="cartellone_credits" rows="3" cols="50" class="large-text"><?php echo esc_textarea( $event['credits'] ?? '' ); ?></textarea>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label for="cartellone_vivaticket"><?php esc_html_e( 'VivaTicket link', 'cartellone' ); ?></label>
		</th>
		<td>
			<input type="url" name="cartellone_vivaticket" value="<?php echo esc_attr( $event['vivaticket'] ?? '' ); ?>" class="regular-text">
		</td>
	</tr>
</table>

<script>
jQuery(function ($) {
	$('.cartellone_data').datepicker({
		minDate: 1,
		maxDate: '+2Y',
		dateFormat: 'dd/mm/yy',
		regional: 'it'
	});

	var tpdata = {
		showPeriodLabels: false,
		hours: {
			starts: 7,
			ends: 22
		},
		minutes: {
			starts: 0,
			ends: 45,
			interval: 15,
			manual: []
		},
		hourText: '<?php echo esc_js( __( 'Hours', 'cartellone' ) ); ?>',
		minuteText: '<?php echo esc_js( __( 'Minutes', 'cartellone' ) ); ?>',
		amPmText: ['AM', 'PM']
	};

	$('.cartellone_ora').timepicker(tpdata);
});
</script>
