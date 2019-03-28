<?php
/*
 * Plugin Name: Auto Load Next Post: Divi Support
 * Plugin URI:  https://github.com/autoloadnextpost/alnp-divi-support
 * Description: Provides theme support for Divi by Elegant Themes
 * Author: Auto Load Next Post
 * Author URI: https://autoloadnextpost.com
 * Version: 1.0.0
 * Developer: Sébastien Dumont
 * Developer URI: https://sebastiendumont.com
 * Text Domain: alnp-divi-support
 * Domain Path: /languages/
 *
 * Copyright: © 2019 Sébastien Dumont
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   Auto Load Next Post: Divi Support
 * @author    Auto Load Next Post
 * @copyright Copyright © 2019, Auto Load Next Post
 * @license   GNU General Public License v3.0 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'ALNP_Divi_Support' ) ) {
	class ALNP_Divi_Support {

		/**
		 * @var ALNP_Divi_Support - the single instance of the class.
		 *
		 * @access protected
		 * @static
		 * @since  1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Plugin Version
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 */
		public static $version = '1.0.0';

		/**
		 * Required Auto Load Next Post Version
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 */
		public static $required_alnp = '1.5.0';

		/**
		 * Main ALNP_Divi_Support Instance.
		 *
		 * Ensures only one instance of ALNP_Divi_Support is loaded or can be loaded.
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 * @see    ALNP_Divi_Support()
		 * @return ALNP_Divi_Support - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		} // END instance()

		/**
		 * Cloning is forbidden.
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'alnp-divi-support' ), self::$version );
		} // END __clone()

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'alnp-divi-support' ), self::$version );
		} // END __wakeup()

		/**
		 * ALNP_Divi_Support Constructor.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return ALNP_Divi_Support
		 */
		public function __construct() {
			$this->init_hooks();
		} // END __construct()

		/**
		 * Initialize hooks.
		 *
		 * @access private
		 * @since  1.0.0
		 */
		private function init_hooks() {
			// Check that the required version of Auto Load Next Post is installed.
			add_action( 'auto_load_next_post_loaded', array( $this, 'check_required_version' ) );

			// Load textdomain.
			add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );

			// Add theme support and preset the theme selectors and if the JavaScript should load in the footer.
			add_action( 'after_setup_theme', array( $this, 'add_theme_support' ) );

			// Register support once plugin is activated.
			register_activation_hook( __FILE__, array( $this, 'update_alnp_settings' ) );

			// This removes the default post navigation in the repeater template.
			remove_action( 'alnp_load_after_content', 'auto_load_next_post_navigation', 1, 10 );

			// Before Content
			add_action( 'alnp_load_before_content', array( $this, 'wrapper_start' ), 10 );
			add_action( 'alnp_load_before_content', array( $this, 'post_heading_start' ), 15 );
			add_action( 'alnp_load_before_content', array( $this, 'post_meta' ), 20 );
			add_action( 'alnp_load_before_content', array( $this, 'featured_image' ), 25 );
			add_action( 'alnp_load_before_content', array( $this, 'post_heading_end' ), 30 );

			// Before Content - Post Format
			add_action( 'alnp_load_before_content_post_format_audio', array( $this, 'audio_content' ), 10 );
			add_action( 'alnp_load_before_content_post_format_quote', array( $this, 'quote_content' ), 10 );
			add_action( 'alnp_load_before_content_post_format_link', array( $this, 'link_content' ), 10 );

			// Before Content - Post Type
			add_action( 'alnp_load_before_content_post_type_single', array( $this, 'the_content' ), 10 );

			// After Content
			add_action( 'alnp_load_after_content', array( $this, 'post_meta_wrapper_start' ), 10 );
			add_action( 'alnp_load_after_content', array( $this, 'comments' ), 15 );
			add_action( 'alnp_load_after_content', array( $this, 'post_meta_wrapper_end' ), 20 );
			add_action( 'alnp_load_after_content', array( $this, 'wrapper_end' ), 25 );

			// Add Post Navigation to Single Posts.
			add_action( 'et_after_post', array( $this, 'alnp_compatible_post_nav' ), 10 );
		} // END init_hooks()

		/**
		 * Checks if the required Auto Load Next Post is installed.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return bool
		 */
		public function check_required_version() {
			if ( ! defined( 'AUTO_LOAD_NEXT_POST_VERSION' ) || version_compare( AUTO_LOAD_NEXT_POST_VERSION, $this->required_alnp, '<' ) ) {
				add_action( 'admin_notices', array( $this, 'alnp_not_installed' ) );
				return false;
			}
		} // END check_required_version()

		/**
		 * Required version of Auto Load Next Post is Not Installed Notice.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function alnp_not_installed() {
			echo '<div class="error"><p>' . sprintf( __( 'Auto Load Next Post: Divi Support requires $%1s v%2$s or higher to be installed.', 'alnp-divi-support' ), '<a href="https://autoloadnextpost.com/" target="_blank">Auto Load Next Post</a>', $this->required_alnp ) . '</p></div>';
		} // END alnp_not_installed()

		/**
		 * These settings will be applied once the plugin is activated.
		 * 
		 * @access public
		 * @since  1.0.0
		 */
		public function add_theme_support() {
			add_theme_support( 'auto-load-next-post', array(
				'content_container'    => 'div#left-area',
				'title_selector'       => 'h1.entry-title',
				'navigation_container' => 'nav.post-navigation',
				'comments_container'   => '#comment-wrap',
				'load_js_in_footer'    => 'no',
				'lock_js_in_footer'    => 'no',
				'plugin_support'       => 'yes',
			) );
		} // END add_theme_support()

		/**
		 * The start of the article.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content - 10 (outputs opening article for the content)
		 * @return void
		 */
		public function wrapper_start() {
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>
			<?php
		} // END wrapper_start()

		/**
		 * The end of the article.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_after_content - 25 (outputs closing article for the content)
		 * @return void
		 */
		public function wrapper_end() {
			?>
			</article> <!-- .et_pb_post -->
			<?php
		} // END wrapper_end()

		/**
		 * Wraps the start of the post heading.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content - 15
		 * @return void
		 */
		public function post_heading_start() {
			?>
			<div class="et_post_meta_wrapper">
				<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php
		} // END post_heading_start()

		/**
		 * The post meta.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content - 20
		 * @return void
		 */
		public function post_meta() {
			et_divi_post_meta();
		} // END post_meta()

		/**
		 * Displays a featured image, video or gallery before the content.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content - 25
		 * @return void
		 */
		public function featured_image() {
			$thumb = '';

			$width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

			$height = (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
			$classtext = 'et_featured_image';
			$titletext = get_the_title();
			$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
			$thumb = $thumbnail["thumb"];

			$post_format = et_pb_post_format();

			if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) {
				printf(
					'<div class="et_main_video_container">
						%1$s
					</div>',
					et_core_esc_previously( $first_video )
				);
			} else if ( ! in_array( $post_format, array( 'gallery', 'link', 'quote' ) ) && 'on' === et_get_option( 'divi_thumbnails', 'on' ) && '' !== $thumb ) {
				print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height );
			} else if ( 'gallery' === $post_format ) {
				et_pb_gallery_images();
			}
		} // END featured_image()

		/**
		 * Displays an audio player for posts with the post format set to Audio.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content_post_format_audio - 10
		 * @return void
		 */
		public function audio_content() {
			$text_color_class = et_divi_get_post_text_color();

			$inline_style = et_divi_get_post_bg_inline_style();
			$audio_player = et_pb_get_audio_player();

			if ( $audio_player ) {
				printf(
					'<div class="et_audio_content%1$s"%2$s>
						%3$s
					</div>',
					esc_attr( $text_color_class ),
					et_core_esc_previously( $inline_style ),
					et_core_esc_previously( $audio_player )
				);
			}
		} // END audio_content()

		/**
		 * Displays a quote for posts with the post format set to Quote.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content_post_format_quote - 10
		 * @return void
		 */
		public function quote_content() {
			printf(
				'<div class="et_quote_content%2$s"%3$s>
					%1$s
				</div> <!-- .et_quote_content -->',
				et_core_esc_previously( et_get_blockquote_in_content() ),
				esc_attr( $text_color_class ),
				et_core_esc_previously( $inline_style )
			);
		} // END quote_content()

		/**
		 * Displays a link for posts with the post format set to Link.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content_post_format_link - 10
		 * @return void
		 */
		public function link_content() {
			printf(
				'<div class="et_link_content%3$s"%4$s>
					<a href="%1$s" class="et_link_main_url">%2$s</a>
				</div> <!-- .et_link_content -->',
				esc_url( et_get_link_url() ),
				esc_html( et_get_link_url() ),
				esc_attr( $text_color_class ),
				et_core_esc_previously( $inline_style )
			);
		} // END link_content()

		/**
		 * Wraps the end of the post heading.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content - 30
		 * @return void
		 */
		public function post_heading_end() {
			?>
			</div> <!-- .et_post_meta_wrapper -->
			<?php
		} // END post_heading_end()

		/**
		 * Displays the post content.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_before_content_post_type_single - 10
		 * @return void
		 */
		public function the_content() {
			?>
			<div class="entry-content">
			<?php
				do_action( 'et_before_content' );

				the_content();

				wp_link_pages( array( 'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'alnp-divi-support' ), 'after' => '</div>' ) );
			?>
			</div> <!-- .entry-content -->
			<?php
		} // END the_content()

		/**
		 * The start of the post meta wrapper.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_after_content - 10
		 * @return void
		 */
		public function post_meta_wrapper_start() {
			?>
			<div class="et_post_meta_wrapper">
			<?php
			if ( et_get_option('divi_468_enable') === 'on' ) {
				echo '<div class="et-single-post-ad">';

				if ( et_get_option('divi_468_adsense') !== '' ) echo et_core_intentionally_unescaped( et_core_fix_unclosed_html_tags( et_get_option('divi_468_adsense') ), 'html' );
				else { ?>
					<a href="<?php echo esc_url(et_get_option('divi_468_url')); ?>"><img src="<?php echo esc_attr(et_get_option('divi_468_image')); ?>" alt="468" class="foursixeight" /></a>
				<?php
				}
				echo '</div> <!-- .et-single-post-ad -->';
			}
		} // END post_meta_wrapper_start()

		/**
		 * Diplays the post comments.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_after_content - 15
		 * @return void
		 */
		public function comments() {
			/**
			 * Fires after the post content on single posts.
			 */
			do_action( 'et_after_post' );

			if ( ( comments_open() || get_comments_number() ) && 'on' === et_get_option( 'divi_show_postcomments', 'on' ) ) {
				comments_template( '', true );
			}
		} // END comments()

		/**
		 * The end of the post meta wrapper.
		 *
		 * @access public
		 * @since  1.0.0
		 * @hooked alnp_load_after_content - 20
		 * @return void
		 */
		public function post_meta_wrapper_end() {
			?>
			</div> <!-- .et_post_meta_wrapper -->
			<?php
		} // END post_meta_wrapper_end()

		/**
		 * Adds a post navigation to single posts.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function alnp_compatible_post_nav() {
			if ( is_single() ) {
				the_post_navigation( array(
					'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'alnp-divi-support' ) . '</span> ' .
					'<span class="screen-reader-text">' . __( 'Next post:', 'alnp-divi-support' ) . '</span> ' .
					'<span class="post-title">%title</span>',
					'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'alnp-divi-support' ) . '</span> ' .
					'<span class="screen-reader-text">' . __( 'Previous post:', 'alnp-divi-support' ) . '</span> ' .
					'<span class="post-title">%title</span>',
				) );
			}
		} // END alnp_compatible_post_nav()

		/**
		 * Updates the theme selectors and any additionl supported feature.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function update_alnp_settings( $stylesheet = '', $old_theme = false ) {
			$theme_support = get_theme_support( 'auto-load-next-post' );

			if ( is_array( $theme_support ) ) {
				// Preferred implementation, where theme provides an array of options
				if ( isset( $theme_support[0] ) && is_array( $theme_support[0] ) ) {
					foreach( $theme_support[0] as $key => $value ) {
						if ( ! empty( $value ) ) update_option( 'auto_load_next_post_' . $key, $value );
					}
				}
			}
		} // END update_alnp_settings()

		/**
		 * Make the plugin translation ready.
		 *
		 * Translations should be added in the WordPress language directory:
		 *  - WP_LANG_DIR/plugins/alnp-divi-support-LOCALE.mo
		 *
		 * @access public
		 * @since  1.0.0
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'alnp-divi-support', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END load_plugin_textdomain()

	} // END class

} // END if class exists

return ALNP_Divi_Support::instance();
