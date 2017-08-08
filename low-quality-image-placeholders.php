<?php
/*
Plugin Name: Low-Quality Image Placeholders (LQIP)
Plugin URI: https://github.com/doctorbin/low-quality-image-placeholders
Description: How to easily generate Low-Quality Image Placeholders (LQIP) in WordPress. Requires the Regenerate thumbnails Plugin to be installed and active.
Version: 1.0.1
Author: Dr. Bin
Author URI: https://www.facebook.com/binbinbeobeo
License: GPL3
*/

add_action( 'init', 'lqip_plugin_setup' );
if ( ! function_exists( 'lqip_plugin_setup' ) ) {
	function lqip_plugin_setup() {

		add_action( 'admin_init', 'lqip_plugin_activate' );
		function lqip_plugin_activate() {
			if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'regenerate-thumbnails/regenerate-thumbnails.php' ) ) {
				add_action( 'admin_notices', 'lqip_plugin_notice' );

				deactivate_plugins( plugin_basename( __FILE__ ) );

				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
			if ( is_plugin_active( 'regenerate-thumbnails/regenerate-thumbnails.php' ) && ! get_option( 'lqip_notice_option' ) ) {
				add_action( 'admin_notices', 'lqip_running_notice' );
				add_option( 'lqip_notice_option', 'true' );
			}
		}

		function lqip_plugin_notice() { ?>
            <div class="error"><p>Sorry, but this plugin requires the <a
                            href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">Regenerate
                        Thumbnails</a> plugin to be installed and active.</p></div>
		<?php }

		function lqip_running_notice() { ?>
            <div class="notice notice-success is-dismissible"><p>After that you will have to run <a
                            href="<?php echo home_url(); ?>/wp-admin/tools.php?page=regenerate-thumbnails">Regenerate
                        Thumbnail Tool</a> to resize all existing images, (LQIP) plugin will work correctly.</p>
            </div>
		<?php }

		/**
		 * Get size information for all currently-registered image sizes.
		 *
		 * @global $_wp_additional_image_sizes
		 * @uses   get_intermediate_image_sizes()
		 * @return array $sizes Data for all currently-registered image sizes.
		 *
		 * https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
		 */
		function get_image_sizes() {
			global $_wp_additional_image_sizes;

			$sizes = array();

			foreach ( get_intermediate_image_sizes() as $_size ) {
				if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
					$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
					$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height']
					);
				}
			}

			return $sizes;
		}

		$lqip_img_sizes = get_image_sizes();

		foreach ( $lqip_img_sizes as $v => $k ) {
			$width   = $k['width'] / 10;
			$height  = $k['height'] / 10;
			$id_size = explode( '_', $v );
			if ( $id_size[0] != 'lqip' ) {
				add_image_size( 'lqip_' . $v, $width, $height, true );
				add_filter( 'post_thumbnail_html', 'modify_post_thumbnail_html', 99, 5 );
			}
		}

		function modify_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
			$id       = get_post_thumbnail_id(); // gets the id of the current post_thumbnail (in the loop)
			$data_src = wp_get_attachment_image_src( $id, $size ); // gets the image url specific to the passed in size (aka. custom image size)
			if ( is_array( $size ) ) {
				$src = wp_get_attachment_image_src( $id, array( $size[0] / 10, $size[1] / 10 ) );
			} else {
				$src = wp_get_attachment_image_src( $id, 'lqip_' . $size ); // gets the LQIP image url specific to the passed in size (aka. custom image size)
				if ( ! $src ) {
					$src = $data_src;
				}
			}
			$alt   = get_the_title( $id ); // gets the post thumbnail title
			$class = $attr['class'];

			$html = '<img src="' . $src[0] . '" alt="" data-src="' . $data_src[0] . '" alt="' . $alt . '" class="' . $class . $size . ' lqip_img" width="' . $data_src[1] . '" height="' . $data_src[2] . '"/>';

			return $html;
		}
	}
}

// add the filter
add_filter( 'image_send_to_editor', 'filter_image_send_to_editor', 10, 8 );

add_action( 'wp_footer', 'lqip_bin_wp_footer' );

function lqip_bin_wp_footer() {
	?>
    <script>
        // Script from https://varvy.com/pagespeed/defer-images.html
        function init() {
            var imgDefer = document.getElementsByClassName('lqip_img');
            for (var i = 0; i < imgDefer.length; i++) {
                if (imgDefer[i].getAttribute('data-src')) {
                    imgDefer[i].setAttribute('src', imgDefer[i].getAttribute('data-src'));
                }
            }
        }
        window.onload = init;
    </script>
	<?php
}
