<?php
/**
 * Header Template
 *
 * @since 2.5.0
 * @package SoliloquyWP Lite
 * @author SoliloquyWP Team <support@soliloquywp.com>
 */

$base = Soliloquy_Lite::get_instance(); ?>
<div id="soliloquy-header">

	<div id="soliloquy-logo"><img src="<?php echo esc_url( plugins_url( 'assets/images/soliloquy-logo.png', $base->file ) ); ?>" alt="<?php esc_html_e( 'Soliloquy', 'soliloquy' ); ?>"></div>

</div>
