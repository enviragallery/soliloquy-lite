<?php
/**
 * Admin Common Class.
 *
 * @since 2.5.0
 * @package SoliloquyWP Lite
 * @author SoliloquyWP Team <support@soliloquywp.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Soliloquy Admin Common
 *
 * @since 2.5.0
 */
class Soliloquy_Common_Admin_Lite {

	/**
	 * Holds the class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Path to the file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $file = __FILE__;

	/**
	 * Holds the base class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $base;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load the base class object.
		$this->base = Soliloquy_Lite::get_instance();

		// Check for upgrading sliders.
		add_action( 'admin_notices', array( $this, 'legacy_upgrade' ) );
		add_action( 'admin_notices', array( $this, 'legacy_upgrade_success' ) );

		// Delete any slider association on attachment deletion. Also delete any extra cropped images.
		add_action( 'delete_attachment', array( $this, 'delete_slider_association' ) );
		add_action( 'delete_attachment', array( $this, 'delete_cropped_image' ) );

		// Ensure slider display is correct when trashing/untrashing sliders.
		add_action( 'wp_trash_post', array( $this, 'trash_slider' ) );
		add_action( 'untrash_post', array( $this, 'untrash_slider' ) );

	}

	/**
	 * Performs a legacy upgrade for sliders from v1 to v2.
	 *
	 * @since 1.0.0
	 */
	public function legacy_upgrade() {

		// If the option exists for upgrading, do nothing.
		$upgrade = get_option( 'soliloquy_upgrade' );
		if ( $upgrade ) {
			return;
		}

		// If the option exists for already checking for sliders from previous versions, bail.
		$has_sliders = get_option( 'soliloquy_lite_upgrade' );
		if ( $has_sliders ) {
			return;
		}

		// If we have no sliders, only run this check once. Set option to prevent again.
		$sliders = get_posts(
			array(
				'post_type'      => 'soliloquy',
				'posts_per_page' => -1,
			)
		);
		if ( ! $sliders ) {
			update_option( 'soliloquy_lite_upgrade', true );
			return;
		}

		?>
		<div class="error">
			<?php /* translators: %s: url */ ?>
			<p><?php printf( esc_html__( 'Soliloquy Lite is now rocking v2! <strong>You need to upgrade your legacy v1 sliders to v2.</strong> <a href="%s">Click here to begin the upgrade process.</a>', 'soliloquy' ), esc_url( add_query_arg( 'page', 'soliloquy-lite-settings', admin_url( 'edit.php?post_type=soliloquy' ) ) ) ); ?></p>
		</div>
		<?php

	}

	/**
	 * Outputs the legacy upgrade notice message for folks who have just upgraded.
	 *
	 * @since 1.0.0
	 */
	public function legacy_upgrade_success() {

		// If the parameter is not set, do nothing.
		if ( empty( $_GET['soliloquy-upgraded'] ) ) {
			return;
		}

		?>
		<div class="updated">
			<p><strong><?php esc_html_e( 'Congratulations! You have upgraded your sliders successfully!', 'soliloquy' ); ?></strong></p>
		</div>
		<?php

	}

	/**
	 * Deletes the Soliloquy slider association for the image being deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attach_id The attachment ID being deleted.
	 */
	public function delete_slider_association( $attach_id ) {

		$has_slider = get_post_meta( $attach_id, '_sol_has_slider', true );

		// Only proceed if the image is attached to any Soliloquy sliders.
		if ( ! empty( $has_slider ) ) {
			foreach ( (array) $has_slider as $post_id ) {
				// Remove the in_slider association.
				$in_slider = get_post_meta( $post_id, '_sol_in_slider', true );
				if ( ! empty( $in_slider ) ) {
					$key = array_search( $attach_id, (array) $in_slider, true );
					if ( false !== $key ) {
						unset( $in_slider[ $key ] );
					}
				}

				update_post_meta( $post_id, '_sol_in_slider', $in_slider );

				// Remove the image from the slider altogether.
				$slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
				if ( ! empty( $slider_data['slider'] ) ) {
					unset( $slider_data['slider'][ $attach_id ] );
				}

				// Update the post meta for the slider.
				update_post_meta( $post_id, '_sol_slider_data', $slider_data );

				// Flush necessary slider caches.
				Soliloquy_Common_Lite::get_instance()->flush_slider_caches( $post_id, ( ! empty( $slider_data['config']['slug'] ) ? $slider_data['config']['slug'] : '' ) );
			}
		}

	}

	/**
	 * Removes any extra cropped images when an attachment is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return null        Return early if the appropriate metadata cannot be retrieved.
	 */
	public function delete_cropped_image( $post_id ) {

		// Get attachment image metadata.
		$metadata = wp_get_attachment_metadata( $post_id );

		// Return if no metadata is found.
		if ( ! $metadata ) {
			return;
		}

		// Return if we don't have the proper metadata.
		if ( ! isset( $metadata['file'] ) || ! isset( $metadata['image_meta']['resized_images'] ) ) {
			return;
		}

		// Grab the necessary info to removed the cropped images.
		$wp_upload_dir  = wp_upload_dir();
		$pathinfo       = pathinfo( $metadata['file'] );
		$resized_images = $metadata['image_meta']['resized_images'];

		// Loop through and deleted and resized/cropped images.
		foreach ( $resized_images as $dims ) {
			// Get the resized images filename and delete the image.
			$file = $wp_upload_dir['basedir'] . '/' . $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '-' . $dims . '.' . $pathinfo['extension'];

			// Delete the resized image.
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

	}

	/**
	 * Trash a slider when the slider post type is trashed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id   The post ID being trashed.
	 * @return null Return early if no slider is found.
	 */
	public function trash_slider( $id ) {

		$slider = get_post( $id );

		// Flush necessary slider caches to ensure trashed sliders are not showing.
		Soliloquy_Common_Lite::get_instance()->flush_slider_caches( $id );

		// Return early if not an Soliloquy slider.
		if ( 'soliloquyv2' !== $slider->post_type ) {
			return;
		}

		// Set the slider status to inactive.
		$slider_data = get_post_meta( $id, '_sol_slider_data', true );
		if ( empty( $slider_data ) ) {
			return;
		}

		$slider_data['status'] = 'inactive';
		update_post_meta( $id, '_sol_slider_data', $slider_data );

	}

	/**
	 * Untrash a slider when the slider post type is untrashed.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $id   The post ID being untrashed.
	 * @return void
	 */
	public function untrash_slider( $id ) {

		$slider = get_post( $id );

		// Flush necessary slider caches to ensure untrashed sliders are showing.
		Soliloquy_Common_Lite::get_instance()->flush_slider_caches( $id );

		// Return early if not an Soliloquy slider.
		if ( 'soliloquyv2' !== $slider->post_type ) {
			return;
		}

		// Set the slider status to inactive.
		$slider_data = get_post_meta( $id, '_sol_slider_data', true );
		if ( empty( $slider_data ) ) {
			return;
		}

		if ( isset( $slider_data['status'] ) ) {
			unset( $slider_data['status'] );
		}

		update_post_meta( $id, '_sol_slider_data', $slider_data );

	}
	/**
	 * Called whenever an upgrade button / link is displayed in Lite, this function will
	 * check if there's a shareasale ID specified.
	 *
	 * There are three ways to specify an ID, ordered by highest to lowest priority
	 * - add_filter( 'soliloquy_shareasale_id', function() { return 1234; } );
	 * - define( 'SOLILOQUY_SHAREASALE_ID', 1234 );
	 * - get_option( 'soliloquy_shareasale_id' ); (with the option being in the wp_options table)
	 *
	 * If an ID is present, returns the ShareASale link with the affiliate ID, and tells
	 * ShareASale to then redirect to soliloquywp.com/lite
	 *
	 * If no ID is present, just returns the soliloquywp.com/lite URL with UTM tracking.
	 *
	 * @since 2.5.0
	 */
	public function get_upgrade_link() {

		if ( class_exists( 'Soliloquy' ) ) {
			// User is using Soliloquy, so just take them to the Pricing page.
			// Note: On the Addons screen, if the user has a license, we won't hit this function,
			// as the API will tell us the direct URL to send the user to based on their license key,
			// so they see pro-rata pricing.
			return 'https://soliloquywp.com/pricing/?utm_source=proplugin&utm_medium=link&utm_campaign=WordPress';
		}

		// Check if there's a constant.
		$shareasale_id = '';
		if ( defined( 'SOLILOQUY_SHAREASALE_ID' ) ) {
			$shareasale_id = SOLILOQUY_SHAREASALE_ID;
		}

		// If there's no constant, check if there's an option.
		if ( empty( $shareasale_id ) ) {
			$shareasale_id = get_option( 'soliloquy_shareasale_id', '' );
		}

		// Whether we have an ID or not, filter the ID.
		$shareasale_id = apply_filters( 'soliloquy_shareasale_id', $shareasale_id );

		// If at this point we still don't have an ID, we really don't have one!
		// Just return the standard upgrade URL.
		if ( empty( $shareasale_id ) ) {
			return 'https://soliloquywp.com/lite/?utm_source=liteplugin&utm_medium=link&utm_campaign=WordPress';
		}

		// If here, we have a ShareASale ID
		// Return ShareASale URL with redirect.
		return 'http://www.shareasale.com/r.cfm?u=' . $shareasale_id . '&b=380096&m=40286&afftrack=&urllink=soliloquywp%2Ecom%2Flite%2F';

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return object The Soliloquy_Common_Admin_Lite object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Common_Admin_Lite ) ) {
			self::$instance = new Soliloquy_Common_Admin_Lite();
		}

		return self::$instance;

	}

}

// Load the common admin class.
$soliloquy_common_admin_lite = Soliloquy_Common_Admin_Lite::get_instance();
