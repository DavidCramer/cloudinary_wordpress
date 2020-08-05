<?php
/**
 * Upgrades from a Legecy version of Cloudinary.
 *
 * @package Cloudinary
 */

namespace Cloudinary\Media;

use Cloudinary\Sync;

/**
 * Class Filter.
 *
 * Handles filtering of HTML content.
 */
class Upgrade {

	/**
	 * Holds the Media instance.
	 *
	 * @since   0.1
	 *
	 * @var     \Cloudinary\Media Instance of the plugin.
	 */
	private $media;

	/**
	 * Holds the Sync instance.
	 *
	 * @since   0.1
	 *
	 * @var     \Cloudinary\Sync Instance of the plugin.
	 */
	private $sync;

	/**
	 * Filter constructor.
	 *
	 * @param \Cloudinary\Media $media The plugin.
	 */
	public function __construct( \Cloudinary\Media $media ) {
		$this->media = $media;
		$this->sync  = $media->plugin->components['sync'];
		$this->setup_hooks();
	}

	/**
	 * Convert an image post that was created from Cloudinary v1.
	 *
	 * @param int $attachment_id The attachment ID to convert.
	 *
	 * @return string Cloudinary ID
	 */
	public function convert_cloudinary_version( $attachment_id ) {

		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		if ( wp_http_validate_url( $file ) ) {
			// Version 1 upgrade.
			$path                  = wp_parse_url( $file, PHP_URL_PATH );
			$media                 = $this->media;
			$parts                 = explode( '/', ltrim( $path, '/' ) );
			$cloud_name            = null;
			$asset_version         = 1;
			$asset_transformations = array();
			$id_parts              = array();
			foreach ( $parts as $val ) {
				if ( empty( $val ) ) {
					continue;
				}
				if ( is_null( $cloud_name ) ) {
					// Cloudname will always be the first item.
					$cloud_name = md5( $val );
					continue;
				}
				if ( in_array( $val, [ 'image', 'video', 'upload' ], true ) ) {
					continue;
				}
				$transformation_maybe = $media->get_transformations_from_string( $val );
				if ( ! empty( $transformation_maybe ) ) {
					$asset_transformations = $transformation_maybe;
					continue;
				}
				if ( substr( $val, 0, 1 ) === 'v' && is_numeric( substr( $val, 1 ) ) ) {
					$asset_version = substr( $val, 1 );
					continue;
				}

				$id_parts[] = $val;
			}
			// Build public_id.
			$parts     = array_filter( $id_parts );
			$public_id = implode( '/', $parts );
			// Remove extension.
			$path      = pathinfo( $public_id );
			$public_id = str_replace( $path['basename'], $path['filename'], $public_id );
			$this->media->update_post_meta( $attachment_id, Sync::META_KEYS['public_id'], $public_id );
			$this->media->update_post_meta( $attachment_id, Sync::META_KEYS['version'], $asset_version );
			if ( ! empty( $asset_transformations ) ) {
				$this->media->update_post_meta( $attachment_id, Sync::META_KEYS['transformation'], $asset_transformations );
			}
			$this->sync->set_signature_item( $attachment_id, 'cloud_name', $cloud_name );
		} else {
			// v2 upgrade.
			$public_id = $this->media->get_public_id( $attachment_id );
		}
		$this->media->update_post_meta( $attachment_id, Sync::META_KEYS['plugin_version'], $this->media->plugin->version );
		$this->sync->set_signature_item( $attachment_id, 'upgrade' );
		$this->sync->set_signature_item( $attachment_id, 'public_id' );

		return $public_id;
	}


	/**
	 * Setup hooks for the filters.
	 */
	public function setup_hooks() {

		// Add a redirection to the new plugin settings, from the old plugin.
		if ( is_admin() ) {
			add_action( 'admin_menu', function () {
				global $plugin_page;
				if ( ! empty( $plugin_page ) && false !== strpos( $plugin_page, 'cloudinary-image-management-and-manipulation-in-the-cloud-cdn' ) ) {
					wp_safe_redirect( admin_url( '?page=cloudinary' ) );
					die;
				}
			} );
		}
	}
}
