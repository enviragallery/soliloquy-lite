<?php
/**
 * Gutenberg Class.
 *
 * @since 2.5.7
 * @package SoliloquyWP
 * @author SoliloquyWP Team <support@soliloquywp.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Soliloquy Gutenberg Class.
 *
 * @since 2.5.7
 */
class Soliloquy_Gutenberg {

	/**
	 * Holds Class Instance
	 *
	 * @var Soliloquy_Gutenberg
	 */
	public static $instance = null;

	/**
	 * Holds Main instance.
	 *
	 * @var Soliloquy
	 */
	public $base = null;

	/**
	 * Class Constructor
	 *
	 * @since 2.5.7
	 */
	public function __construct() {

		$this->base = Soliloquy_Lite::get_instance();
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ), 10 );
	}

	/**
	 * Add Editor Assets.
	 *
	 * @since 2.5.7
	 *
	 * @return void
	 */
	public function editor_assets() {

		wp_enqueue_script(
			'soliloquy_block_js',
			plugins_url( 'assets/js/soliloquy-gutenberg.js', $this->base->file ), // Block.build.js: we register the block here and built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor' ), // dependencies, defined above.
			$this->base->version,
			true // Enqueue the script in the footer.
		);

		// Styles.
		// wp_enqueue_style(
		// 'envira_gutenberg-block-editor-css', // Handle.
		// plugins_url( 'assets/css/blocks.editor.build.css', ENVIRA_FILE ), // Block editor CSS.
		// array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
		// ENVIRA_VERSION
		// );
	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 2.5.7
	 *
	 * @return object The Soliloquy_Common_Admin object.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Gutenberg ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

$soliloquy_gutenberg = Soliloquy_Gutenberg::get_instance();
