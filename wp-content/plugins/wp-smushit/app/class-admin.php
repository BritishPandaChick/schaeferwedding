<?php
/**
 * Admin class.
 *
 * @package Smush\App
 */

namespace Smush\App;

use Smush\Core\Core;
use Smush\Core\Error_Handler;
use Smush\Core\Helper;
use Smush\Core\Next_Gen\Next_Gen_Manager;
use Smush\Core\Settings;
use Smush\Core\Stats\Global_Stats;
use Smush\Core\Membership\Membership;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Admin
 */
class Admin {
	private static $plugin_discount_percent = 80;
	private static $cdn_pop_locations = 123;
	private static $review_prompts_option_key = 'wp-smush-review_prompt_next_show';
	private static $review_prompts_min_images = 10;
	private static $review_prompts_optimized_images_threshold = 100;
	private static $review_prompts_optimization_failed_percent_threshold = 10;

	/**
	 * Plugin pages.
	 *
	 * @var array
	 */
	public $pages = array();

	/**
	 * AJAX module.
	 *
	 * @var Ajax
	 */
	public $ajax;

	/**
	 * Static instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * List of smush settings pages.
	 *
	 * @var array $plugin_pages
	 */
	public static $plugin_pages = array(
		'gallery_page_wp-smush-nextgen-bulk',
		'nextgen-gallery_page_wp-smush-nextgen-bulk', // Different since NextGen 3.3.6.
		'toplevel_page_smush',
		'toplevel_page_smush-network',
	);

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __call( $method_name, $arguments ) {
		_deprecated_function( esc_html( $method_name ), '3.24.0' );
	}

	/**
	 * Admin constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'network_admin_menu', array( $this, 'add_menu_pages' ) );

		add_action( 'admin_init', array( $this, 'smush_i18n' ) );
		// Add information to privacy policy page (only during creation).
		add_action( 'admin_init', array( $this, 'add_policy' ) );

		if ( wp_doing_ajax() ) {
			$this->ajax = new Ajax();
		}

		add_filter( 'plugin_action_links_' . WP_SMUSH_BASENAME, array( $this, 'dashboard_link' ) );
		add_filter( 'network_admin_plugin_action_links_' . WP_SMUSH_BASENAME, array( $this, 'dashboard_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );

		// Plugin conflict notice.
		add_action( 'admin_notices', array( $this, 'show_plugin_conflict_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_parallel_unavailability_notice' ) );
		add_action( 'smush_check_for_conflicts', array( $this, 'check_for_conflicts_cron' ) );
		add_action( 'activated_plugin', array( $this, 'check_for_conflicts_cron' ) );
		add_action( 'deactivated_plugin', array( $this, 'check_for_conflicts_cron' ) );

		// Deactivation survey.
		add_action( 'admin_footer-plugins.php', array( $this, 'load_deactivation_survey_modal' ) );

		add_action( 'all_admin_notices', array( $this, 'maybe_show_review_prompts' ) );
	}

	public static function get_cdn_pop_locations() {
		return self::$cdn_pop_locations;
	}

	/**
	 * Get review_prompts_option_key.
	 *
	 * @return mixed
	 */
	public static function get_review_prompts_option_key() {
		return self::$review_prompts_option_key;
	}

	/**
	 * Load translation files.
	 */
	public function smush_i18n() {
		load_plugin_textdomain(
			'wp-smushit',
			false,
			dirname( WP_SMUSH_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register JS and CSS.
	 */
	private function register_scripts() {
		global $wp_version;
		/**
		 * Queue clipboard.js from your plugin if WP's version is below 5.2.0
		 * since it's only included from 5.2.0 on.
		 *
		 * Use 'clipboard' as the handle so it matches WordPress' handle for the script.
		 *
		 * @since 3.8.0
		 */
		if ( version_compare( $wp_version, '5.2', '<' ) ) {
			wp_register_script( 'clipboard', WP_SMUSH_URL . 'app/assets/js/smush-clipboard.min.js', array(), WP_SMUSH_VERSION, true );
		}

		/**
		 * Share UI JS.
		 *
		 * @since 3.8.0 added 'clipboard' dependency.
		 */
		wp_register_script( 'smush-sui', WP_SMUSH_URL . 'app/assets/js/smush-sui.min.js', array( 'jquery', 'clipboard' ), WP_SHARED_UI_VERSION, true );

		// Main JS.
		wp_register_script( 'smush-admin', WP_SMUSH_URL . 'app/assets/js/smush-admin.min.js', array( 'jquery', 'smush-sui', 'underscore', 'wp-color-picker' ), WP_SMUSH_VERSION, true );

		// JS that can be used on all pages in the WP backend.
		wp_register_script( 'smush-admin-common', WP_SMUSH_URL . 'app/assets/js/smush-admin-common.min.js', array( 'jquery' ), WP_SMUSH_VERSION, true );

		// Main CSS.
		wp_register_style( 'smush-admin', WP_SMUSH_URL . 'app/assets/css/smush-admin.min.css', array(), WP_SMUSH_VERSION );

		// Styles that can be used on all pages in the WP backend.
		wp_register_style( 'smush-admin-common', WP_SMUSH_URL . 'app/assets/css/smush-global.min.css', array(), WP_SMUSH_VERSION );

		// Dismiss update info.
		WP_Smush::get_instance()->core()->mod->smush->dismiss_update_info();
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'smush-global', WP_SMUSH_URL . 'app/assets/js/smush-global.min.js', array(), WP_SMUSH_VERSION, true );
		wp_localize_script(
			'smush-global',
			'smush_global',
			array(
				'nonce' => wp_create_nonce( 'wp-smush-ajax' ),
			)
		);

		wp_localize_script(
			'smush-global',
			'wp_smush_mixpanel',
			array(
				'opt_in' => Settings::get_instance()->get( 'usage' ),
			)
		);

		$current_page   = '';
		$current_screen = '';

		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			$current_page   = ! empty( $current_screen ) ? $current_screen->base : $current_page;
		}

		if ( 'plugins' === $current_page || 'plugins-network' === $current_page ) {
			$this->register_scripts();
			wp_enqueue_script( 'smush-sui' );
			wp_enqueue_style( 'smush-admin' );
			return;
		}

		if ( ! in_array( $current_page, Core::$external_pages, true ) && false === strpos( $current_page, 'page_smush' ) ) {
			return;
		}

		// Allows to disable enqueuing smush files on a particular page.
		if ( ! apply_filters( 'wp_smush_enqueue', true ) ) {
			return;
		}

		$this->register_scripts();

		// Load on all Smush page only.
		if ( isset( $current_screen->id ) && ( in_array( $current_screen->id, self::$plugin_pages, true ) || false !== strpos( $current_screen->id, 'page_smush' ) ) ) {
			// Smush admin (smush-admin) includes the Shared UI.
			wp_enqueue_style( 'smush-admin' );
			wp_enqueue_script( 'smush-wpmudev-sui' );
		}

		if ( ! in_array( $current_page, array( 'post', 'post-new', 'page', 'edit-page' ), true ) ) {
			// Skip these pages where the script isn't used.
			wp_enqueue_script( 'smush-admin' );
		} else {
			// Otherwise, load only the common JS code.
			wp_enqueue_script( 'smush-admin-common' );
		}

		// We need it on media pages and Smush pages.
		wp_enqueue_style( 'smush-admin-common' );

		// Localize translatable strings for js.
		WP_Smush::get_instance()->core()->localize();
	}

	/**
	 * Adds a Smush pro settings link on plugin page.
	 *
	 * @param array $links  Current links.
	 *
	 * @return array|string
	 */
	public function dashboard_link( $links ) {
		// Upgrade link.
		$upgrade_url = add_query_arg(
			array(
				'utm_source'   => 'smush',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'wp-smush-pro/wp-smush.php' !== WP_SMUSH_BASENAME ? 'smush_pluginlist_upgrade' : 'smush_pluginlist_renew',
			),
			esc_url( 'https://wpmudev.com/project/wp-smush-pro/' )
		);

		$using_free_version = 'wp-smush-pro/wp-smush.php' !== WP_SMUSH_BASENAME;
		if ( $using_free_version ) {
			$label = __( 'Upgrade to Smush Pro', 'wp-smushit' );
			$text = __( 'Get Smush Pro', 'wp-smushit' );
		} else {
			$label = __( 'Renew Membership', 'wp-smushit' );
			$text  = __( 'Renew Membership', 'wp-smushit' );
		}

		if ( isset( $text ) ) {
			$links['smush_upgrade'] = '<a id="smush-pluginlist-upgrade-link" href="' . esc_url( $upgrade_url ) . '" aria-label="' . esc_attr( $label ) . '" target="_blank" style="color: #8D00B1;">' . esc_html( $text ) . '</a>';
		}

		// Documentation link.
		$docs_link           = Helper::get_utm_link(
			array( 'utm_campaign' => 'smush_pluginlist_docs' ),
			'https://wpmudev.com/docs/wpmu-dev-plugins/smush/'
		);
		$links['smush_docs'] = '<a href="' . esc_url( $docs_link ) . '" aria-label="' . esc_attr( __( 'View Smush Documentation', 'wp-smushit' ) ) . '" target="_blank">' . esc_html__( 'Docs', 'wp-smushit' ) . '</a>';

		// Dashboard link.
		$dashboard_page           = is_multisite() && is_network_admin() ? network_admin_url( 'admin.php?page=smush' ) : menu_page_url( 'smush', false );
		$links['smush_dashboard'] = '<a href="' . esc_url( $dashboard_page ) . '" aria-label="' . esc_attr( __( 'Go to Smush Dashboard', 'wp-smushit' ) ) . '">' . esc_html__( 'Dashboard', 'wp-smushit' ) . '</a>';

		$access = get_site_option( 'wp-smush-networkwide' );
		if ( ! is_network_admin() && is_plugin_active_for_network( WP_SMUSH_BASENAME ) && ! $access ) {
			// Remove settings link for subsites if Subsite Controls is not set on network permissions tab.
			unset( $links['smush_dashboard'] );
		}

		return array_reverse( $links );
	}

	/**
	 * Add additional links next to the plugin version.
	 *
	 * @since 3.5.0
	 *
	 * @param array  $links  Links array.
	 * @param string $file   Plugin basename.
	 *
	 * @return array
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( ! defined( 'WP_SMUSH_BASENAME' ) || WP_SMUSH_BASENAME !== $file ) {
			return $links;
		}

		if ( 'wp-smush-pro/wp-smush.php' !== WP_SMUSH_BASENAME ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post" target="_blank" title="' . esc_attr__( 'Rate Smush', 'wp-smushit' ) . '">' . esc_html__( 'Rate Smush', 'wp-smushit' ) . '</a>';
			$links[] = '<a href="https://wordpress.org/support/plugin/wp-smushit/" target="_blank" title="' . esc_attr__( 'Support', 'wp-smushit' ) . '">' . esc_html__( 'Support', 'wp-smushit' ) . '</a>';
		} else {
			if ( isset( $links[2] ) && false !== strpos( $links[2], 'project/wp-smush-pro' ) ) {
				$links[2] = sprintf(
					'<a href="https://wpmudev.com/project/wp-smush-pro/" target="_blank">%s</a>',
					__( 'View details', 'wp-smushit' )
				);
			}

			$links[] = '<a href="https://wpmudev.com/get-support/" target="_blank" title="' . esc_attr__( 'Premium Support', 'wp-smushit' ) . '">' . esc_html__( 'Premium Support', 'wp-smushit' ) . '</a>';
		}

		$roadmap_link = Helper::get_utm_link(
			array(
				'utm_campaign' => 'smush_pluginlist_roadmap',
			),
			'https://wpmudev.com/roadmap/'
		);
		$links[]      = '<a href="' . esc_url( $roadmap_link ) . '" target="_blank" title="' . esc_attr__( 'Roadmap', 'wp-smushit' ) . '">' . esc_html__( 'Roadmap', 'wp-smushit' ) . '</a>';

		$links[] = '<a class="wp-smush-review" href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Rate our plugin', 'wp-smushit' ) . '">
					<span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
					</a>';

		echo '<style>.wp-smush-review span,.wp-smush-review span:hover{color:#ffb900}.wp-smush-review span:hover~span{color:#888}</style>';

		return $links;
	}

	/**
	 * Add menu pages.
	 */
	public function add_menu_pages() {
		$title = 'wp-smush-pro/wp-smush.php' === WP_SMUSH_BASENAME ? esc_html__( 'Smush Pro', 'wp-smushit' ) : esc_html__( 'Smush', 'wp-smushit' );
		if ( Settings::can_access( false, true ) ) {
			$this->pages['smush']     = new Pages\Dashboard( 'smush', $title );
			$this->pages['dashboard'] = new Pages\Dashboard( 'smush', __( 'Dashboard', 'wp-smushit' ), 'smush' );

			if ( Abstract_Page::should_render( 'bulk' ) ) {
				$this->pages['bulk'] = new Pages\Bulk( 'smush-bulk', __( 'Bulk Smush', 'wp-smushit' ), 'smush' );
			}

			if ( Abstract_Page::should_render( Settings::get_lazy_preload_module_name() ) ) {
				$pro_feature_ripple_effect   = Abstract_Page::should_show_new_feature_hotspot() ? '<span class="smush-new-feature-dot"></span>' : '';
				$this->pages['lazy-preload'] = new Pages\Lazy_Preload( 'smush-lazy-preload', __( 'Lazy Load & Preload', 'wp-smushit' ) . $pro_feature_ripple_effect, 'smush' );
			}

			if ( Abstract_Page::should_render( 'cdn' ) ) {
				$this->pages['cdn'] = new Pages\CDN( 'smush-cdn', __( 'CDN', 'wp-smushit' ), 'smush' );
			}

			if ( Abstract_Page::should_render( 'next-gen' ) ) {
				$this->pages['next-gen'] = new Pages\Next_Gen( 'smush-next-gen', __( 'Next-Gen Formats', 'wp-smushit' ), 'smush' );
			}

			if ( Abstract_Page::should_render( 'integrations' ) ) {
				$this->pages['integrations'] = new Pages\Integrations( 'smush-integrations', __( 'Integrations', 'wp-smushit' ), 'smush' );
			}

			if ( ! is_multisite() || is_network_admin() ) {
				$this->pages['settings'] = new Pages\Settings( 'smush-settings', __( 'Settings', 'wp-smushit' ), 'smush' );
			}

			$this->register_free_menus();
		}
	}

	protected function register_free_menus() {
		$this->pages['upsell'] = new Pages\Upgrade( 'smush_submenu_upsell', __( 'Get Smush Pro', 'wp-smushit' ), 'smush', true );
	}

	/**
	 * Add Smush Policy to "Privacy Policy" page during creation.
	 *
	 * @since 2.3.0
	 */
	public function add_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<h3>' . __( 'Plugin: Smush', 'wp-smushit' ) . '</h3>';
		$content .=
			'<p>' . __( 'Note: Smush does not interact with end users on your website. The only input option Smush has is to a newsletter subscription for site admins only. If you would like to notify your users of this in your privacy policy, you can use the information below.', 'wp-smushit' ) . '</p>';
		$content .=
			'<p>' . __( 'Smush sends images to the WPMU DEV servers to optimize them for web use. This includes the transfer of EXIF data. The EXIF data will either be stripped or returned as it is. It is not stored on the WPMU DEV servers.', 'wp-smushit' ) . '</p>';
		$content .=
			'<p>' . sprintf( /* translators: %1$s - opening <a>, %2$s - closing </a> */
				__( "Smush uses the Stackpath Content Delivery Network (CDN). Stackpath may store web log information of site visitors, including IPs, UA, referrer, Location and ISP info of site visitors for 7 days. Files and images served by the CDN may be stored and served from countries other than your own. Stackpath's privacy policy can be found %1\$shere%2\$s.", 'wp-smushit' ),
				'<a href="https://www.stackpath.com/legal/privacy-statement/" target="_blank">',
				'</a>'
			) . '</p>';

		if ( strpos( WP_SMUSH_DIR, 'wp-smushit' ) !== false ) {
			// Only for wordpress.org members.
			$content .=
				'<p>' . __( 'Smush uses a third-party email service (Drip) to send informational emails to the site administrator. The administrator\'s email address is sent to Drip and a cookie is set by the service. Only administrator information is collected by Drip.', 'wp-smushit' ) . '</p>';
		}

		wp_add_privacy_policy_content(
			__( 'WP Smush', 'wp-smushit' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Check for plugin conflicts cron.
	 *
	 * @since 3.6.0
	 *
	 * @param string $deactivated  Holds the slug of activated/deactivated plugin.
	 */
	public function check_for_conflicts_cron( $deactivated = '' ) {
		$conflicting_plugins = array(
			'autoptimize/autoptimize.php',
			'ewww-image-optimizer/ewww-image-optimizer.php',
			'imagify/imagify.php',
			'resmushit-image-optimizer/resmushit.php',
			'shortpixel-image-optimiser/wp-shortpixel.php',
			'tiny-compress-images/tiny-compress-images.php',
			'wp-rocket/wp-rocket.php',
			'optimole-wp/optimole-wp.php',
			// lazy load plugins.
			'rocket-lazy-load/rocket-lazy-load.php',
			'a3-lazy-load/a3-lazy-load.php',
			'jetpack/jetpack.php',
			'sg-cachepress/sg-cachepress.php',
			'w3-total-cache/w3-total-cache.php',
			'wp-fastest-cache/wpFastestCache.php',
			'wp-optimize/wp-optimize.php',
			'nitropack/main.php',
		);

		$plugins = get_plugins();

		$active_plugins = array();
		foreach ( $conflicting_plugins as $plugin ) {
			if ( ! array_key_exists( $plugin, $plugins ) ) {
				continue;
			}

			if ( ! is_plugin_active( $plugin ) ) {
				continue;
			}

			// Deactivation of the plugin in process.
			if ( doing_action( 'deactivated_plugin' ) && $deactivated === $plugin ) {
				continue;
			}

			$active_plugins[] = $plugins[ $plugin ]['Name'];
		}

		set_transient( 'wp-smush-conflict_check', $active_plugins, 3600 );
	}

	/**
	 * Display plugin incompatibility notice.
	 *
	 * @since 3.6.0
	 */
	public function show_plugin_conflict_notice() {
		// Do not show on lazy load module, there we show an inline notice.
		$is_lazy_preload_page = false !== strpos( get_current_screen()->id, 'page_smush-lazy-preload' );
		if ( $is_lazy_preload_page ) {
			return;
		}

		$dismissed = $this->is_notice_dismissed( 'plugin-conflict' );
		if ( $dismissed ) {
			return;
		}

		$conflict_check = get_transient( 'wp-smush-conflict_check' );

		// Have never checked before.
		if ( false === $conflict_check ) {
			wp_schedule_single_event( time(), 'smush_check_for_conflicts' );
			return;
		}

		// No conflicting plugins detected.
		if ( isset( $conflict_check ) && is_array( $conflict_check ) && empty( $conflict_check ) ) {
			return;
		}

		array_walk(
			$conflict_check,
			function ( &$item ) {
				$item = '<strong>' . $item . '</strong>';
			}
		);
		?>
		<div class="notice notice-info is-dismissible smush-dismissible-notice"
			 id="smush-conflict-notice"
			 data-key="plugin-conflict">

			<p><?php esc_html_e( 'You have multiple WordPress image optimization plugins installed. This may cause unpredictable behavior while optimizing your images, inaccurate reporting, or images to not display. For best results use only one image optimizer plugin at a time. These plugins may cause issues with Smush:', 'wp-smushit' ); ?></p>
			<p>
				<?php echo wp_kses_post( join( '<br>', $conflict_check ) ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Manage Plugins', 'wp-smushit' ); ?>
				</a>
				<a href="#"
				   style="margin-left: 15px"
				   id="smush-dismiss-conflict-notice" class="smush-dismiss-notice-button">

					<?php esc_html_e( 'Dismiss', 'wp-smushit' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Prints the content for pending images for the Bulk Smush section.
	 *
	 * @param int $remaining_count
	 * @param int $reoptimize_count
	 * @param int $optimize_count
	 *
	 * @since 3.7.2
	 */
	public function print_pending_bulk_smush_content( $remaining_count, $reoptimize_count, $optimize_count ) {
		$optimize_message = '';
		if ( 0 < $optimize_count ) {
			$optimize_message = sprintf(
				/* translators: 1. opening strong tag, 2: unsmushed images count,3. closing strong tag. */
				esc_html( _n( '%1$s%2$d attachment%3$s that needs smushing', '%1$s%2$d attachments%3$s that need smushing', $optimize_count, 'wp-smushit' ) ),
				'<strong>',
				absint( $optimize_count ),
				'</strong>'
			);
		}

		$reoptimize_message = '';
		if ( 0 < $reoptimize_count ) {
			$reoptimize_message = sprintf(
				/* translators: 1. opening strong tag, 2: re-smush images count,3. closing strong tag. */
				esc_html( _n( '%1$s%2$d attachment%3$s that needs re-smushing', '%1$s%2$d attachments%3$s that need re-smushing', $reoptimize_count, 'wp-smushit' ) ),
				'<strong>',
				esc_html( $reoptimize_count ),
				'</strong>'
			);
		}

		$bulk_limit_free_message = $this->generate_bulk_limit_message_for_free( $remaining_count );

		$image_count_description = sprintf(
			/* translators: 1. username, 2. unsmushed images message, 3. 'and' text for when having both unsmushed and re-smush images, 4. re-smush images message. */
			__( '%1$s, you have %2$s%3$s%4$s! %5$s', 'wp-smushit' ),
			esc_html( Helper::get_user_name() ),
			$optimize_message,
			( $optimize_message && $reoptimize_message ? esc_html__( ' and ', 'wp-smushit' ) : '' ),
			$reoptimize_message,
			$bulk_limit_free_message
		);
		?>
		<span id="wp-smush-bulk-image-count"><?php echo esc_html( $remaining_count ); ?></span>
		<p id="wp-smush-bulk-image-count-description">
			<?php echo wp_kses_post( $image_count_description ); ?>
		</p>
		<?php
	}

	public function get_global_stats_with_bulk_smush_content() {
		$core             = WP_Smush::get_instance()->core();
		$stats            = $core->get_global_stats();
		$global_stats     = Global_Stats::get();
		$remaining_count  = $global_stats->get_remaining_count();
		$optimize_count   = $global_stats->get_optimize_list()->get_count();
		$reoptimize_count = $global_stats->get_redo_count();

		$stats['errors']  = Error_Handler::get_last_errors();

		if ( $remaining_count > 0 ) {
			ob_start();
			WP_Smush::get_instance()->admin()->print_pending_bulk_smush_content(
				$remaining_count,
				$reoptimize_count,
				$optimize_count
			);
			$content          = ob_get_clean();
			$stats['content'] = $content;
		}

		return $stats;
	}

	public function get_global_stats_with_bulk_smush_content_and_notice() {
		$stats = $this->get_global_stats_with_bulk_smush_content();
		$remaining_count  = Global_Stats::get()->get_remaining_count();
		if ( $remaining_count < 1 ) {
			$stats['notice']     = esc_html__( 'Yay! All images are optimized as per your current settings.', 'wp-smushit' );
			$stats['noticeType'] = 'success';
		} else {
			$stats['noticeType'] = 'warning';
			$stats['notice']     = sprintf(
				/* translators: %1$d - number of images, %2$s - opening a tag, %3$s - closing a tag */
				esc_html__( 'Image check complete, you have %1$d images that need smushing. %2$sBulk smush now!%3$s', 'wp-smushit' ),
				$remaining_count,
				'<a href="#" class="wp-smush-trigger-bulk">',
				'</a>'
			);
		}

		return $stats;
	}

	private function generate_bulk_limit_message_for_free( $remaining_count ) {
		if ( ! Settings::get_instance()->should_enforce_bulk_limit() || $remaining_count < Core::get_bulk_pause_limit() ) {
			return '';
		}

		$upgrade_url = add_query_arg(
			array(
				'utm_source'   => 'smush',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'smush_bulk_smush_pre_smush_50_limit',
			),
			'https://wpmudev.com/project/wp-smush-pro/'
		);
		return sprintf(
			/* translators: 1: max free bulk limit, 2: opening a tag, 3: closing a tag. */
			esc_html__( 'Free users can only Bulk Smush %1$d images at one time. Skip limits, save time. Bulk Smush unlimited images — %2$sGet Smush Pro%3$s', 'wp-smushit' ),
			Core::get_bulk_pause_limit(),
			'<a class="smush-upsell-link" target="_blank" href="' . $upgrade_url . '">',
			'</a>'
		);
	}

	public function is_notice_dismissed( $notice ) {
		$dismissed_notices = get_option( 'wp-smush-dismissed-notices', array() );

		return ! empty( $dismissed_notices[ $notice ] );
	}

	public function show_parallel_unavailability_notice() {
		$smush                     = WP_Smush::get_instance()->core()->mod->smush;
		$curl_multi_exec_available = $smush->curl_multi_exec_available();
		$is_current_user_not_admin = ! current_user_can( 'manage_options' );
		$is_not_bulk_smush_page    = false === strpos( get_current_screen()->id, 'page_smush-bulk' );
		$notice_hidden             = $this->is_notice_dismissed( 'curl-multi-unavailable' );

		if (
			$curl_multi_exec_available ||
			$is_current_user_not_admin ||
			$is_not_bulk_smush_page ||
			$notice_hidden
		) {
			return;
		}

		$notice_text = sprintf(
			/* translators: %s: <strong>curl_multi_exec()</strong> */
			esc_html__( 'Smush was unable to activate parallel processing on your site as your web hosting provider has disabled the %s function on your server. We highly recommend contacting your hosting provider to enable that function to optimize images on your site faster.', 'wp-smushit' ),
			'<strong>curl_multi_exec()</strong>'
		);

		?>
		<div class="notice notice-warning is-dismissible smush-dismissible-notice"
			 id="smush-parallel-unavailability-notice"
			 data-key="curl-multi-unavailable">

			<strong style="font-size: 15px;line-height: 30px;margin: 8px 0 0 2px;display: inline-block;">
				<?php esc_html_e( 'Smush images faster with parallel image optimization', 'wp-smushit' ); ?>
			</strong>
			<br/>
			<p style="margin-bottom: 13px;margin-top: 0;">
				<?php echo wp_kses_post( $notice_text ); ?><br/>

				<a style="margin-top: 5px;display: inline-block;" href="#" class="smush-dismiss-notice-button">
					<?php esc_html_e( 'Dismiss', 'wp-smushit' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function get_plugin_discount() {
		return self::$plugin_discount_percent . '%';
	}

	public function load_deactivation_survey_modal() {
		$deactivation_survey_template_file = WP_SMUSH_DIR . 'app/modals/deactivation-survey.php';
		if ( ! file_exists( $deactivation_survey_template_file ) ) {
			return;
		}

		ob_start();
		include $deactivation_survey_template_file;
		// Everything escaped in all template files.
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the notice for reminding later.
	 */
	public function maybe_show_review_prompts() {
		$current_screen = get_current_screen();
		$is_smush_page  = strpos( $current_screen->id, 'page_smush' ) !== false || strpos( $current_screen->id, 'smush_page' ) !== false;
		if ( ! $is_smush_page ) {
			return false;
		}

		if ( ! $this->should_show_review_prompts() ) {
			return;
		}

		?>
		<style>
			#smush-review-prompts-notice{min-width:320px;padding:6px 20px 6px 12px;display:flex;gap:15px;align-items:center;}#smush-review-prompts-notice #smush-review-prompts-actions{margin-left:2px;margin-top:6px;display:flex;gap:10px;align-items:center;}#smush-review-prompts-notice h3{font-size:15px;margin:0 0 5px 0;}#smush-review-prompts-notice a{text-decoration:none;}#smush-review-prompts-notice a#smush-review-prompts-already-did{border:none;}#smush-review-prompts-notice p{letter-spacing:-0.25px;}#smush-review-prompts-notice .notice-dismiss{display: none;}@media screen and (min-width:1792px) {#smush-review-prompts-notice[data-notice-type="smushed_hundred_images"] .smush-review-prompts-notice-logo img{max-width:95px}}@media screen and (max-width: 600px) {#smush-review-prompts-notice{ display:block; }#smush-review-prompts-notice img{display:none;}#smush-review-prompts-notice .button{font-size:12px;min-height: 32px;padding: 0 10px;}}@media screen and (max-width:860px) { #smush-review-prompts-notice p br{ display:none; } }
		</style>
		<?php

		$next_review_prompt = get_option( self::$review_prompts_option_key, array() );
		if ( ! empty( $next_review_prompt['type'] ) ) {
			return 'all_optimized' === $next_review_prompt['type'] ? $this->get_all_optimized_images_notice() : $this->get_remind_later_notice();
		}

		$global_stats = Global_Stats::get();

		$optimized_count = $global_stats->get_total_optimizable_items_count() - $global_stats->get_remaining_count();
		if ( $optimized_count >= self::$review_prompts_optimized_images_threshold ) {
			return $this->get_optimized_images_notice( $optimized_count );
		}

		// Store the prompt state to continue showing the notice when new images are uploaded on small sites.
		update_option(
			self::$review_prompts_option_key,
			array(
				'time' => time() - 1,
				'type' => 'all_optimized',
			)
		);

		return $this->get_all_optimized_images_notice();
	}

	/**
	 * Check if the review prompts should be shown.
	 *
	 * @return bool
	 */
	protected function should_show_review_prompts() {
		$membership = Membership::get_instance();
		if ( $membership->is_api_hub_access_required() ) {
			return false;
		}

		if ( $this->is_notice_dismissed( 'review-prompts' ) ) {
			return false;
		}
		$global_stats = Global_Stats::get();

		$image_count = $global_stats->get_image_attachment_count();
		if ( $image_count < self::$review_prompts_min_images ) {
			return false;
		}

		$percent_failed = $global_stats->get_optimization_failed_percent();
		if ( $percent_failed >= self::$review_prompts_optimization_failed_percent_threshold ) {
			return false;
		}

		$next_review_prompt = get_option( self::$review_prompts_option_key, array() );
		$current_time       = isset( $_GET['smush-current-time'] ) ? (int) $_GET['smush-current-time'] : time();
		if ( ! empty( $next_review_prompt['time'] ) ) {
			return $current_time >= (int) $next_review_prompt['time'];
		}

		$percent_optimized   = $global_stats->get_percent_optimized();
		$all_image_optimized = $percent_optimized >= 100;
		if ( $all_image_optimized ) {
			return true;
		}

		$optimized_count = $global_stats->get_total_optimizable_items_count() - $global_stats->get_remaining_count();

		return $optimized_count >= self::$review_prompts_optimized_images_threshold;
	}

	/**
	 * Get the notice for optimized images.
	 *
	 * @param int $optimized_count Number of optimized images.
	 * @return void
	 */
	private function get_optimized_images_notice( $optimized_count ) {
		?>
		<div id="smush-review-prompts-notice" class="notice notice-info is-dismissible" data-notice-type="smushed_hundred_images">
			<div class="smush-review-prompts-notice-logo">
				<img
					style="margin-top:-2px;margin-bottom:-3px"
					src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon.png' ); ?>"
					srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon@2x.png' ); ?> 2x"
					alt="<?php esc_html_e( 'Smush review prompts icon', 'wp-smushit' ); ?>"
				>
			</div>
			<div class="smush-review-prompts-notice-message">
				<h3>
				<?php
					/* translators: %d: optimized images count */
					printf( esc_html__( 'You’ve optimized %d images! 🎉', 'wp-smushit' ), (int) $optimized_count );
				?>
				</h3>
				<p><?php esc_html_e( 'Seeing faster speeds? We’d really appreciate a quick review. It keeps us growing and helps more WordPress users discover Smush.', 'wp-smushit' ); ?></p>
				<div id="smush-review-prompts-actions">
					<a target="_blank" href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post"
					class="button button-small button-primary"><?php esc_html_e( 'Rate Smush', 'wp-smushit' ); ?></a>
					<span id="smush-review-prompts-remind-later" class="button button-small" style="background-color: transparent;"><?php esc_html_e( 'Remind me later', 'wp-smushit' ); ?></span>
					<span id="smush-review-prompts-already-did" class="button button-small" style="box-shadow:unset!important;background-color: transparent;" href="#"><?php esc_html_e( 'I already did', 'wp-smushit' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the notice for all optimized images.
	 *
	 * @return void
	 */
	private function get_all_optimized_images_notice() {
		?>
		<div id="smush-review-prompts-notice"
			class="notice notice-info is-dismissible"
			style="padding-top:12px;padding-bottom:12px;"
			data-notice-type="all_images_optimized">
			<div class="smush-review-prompts-notice-logo">
				<img
					style="margin-top:-2px;margin-bottom:-3px"
					src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon.png' ); ?>"
					srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon@2x.png' ); ?> 2x"
					alt="<?php esc_html_e( 'Smush review prompts icon', 'wp-smushit' ); ?>"
				>
			</div>
			<div class="smush-review-prompts-notice-message">
				<h3><?php esc_html_e( '100% of your images are now optimized! 🎉', 'wp-smushit' ); ?></h3>
				<p>
					<?php
					printf(
						/* translators: 1: <br>, 2: Open the link <a>, 3: Close the link </a> */
						esc_html__( 'Your site’s faster and lighter than ever. Plus, Smush will keep every new image optimized, free for life. %1$sHappy with the results? Share the love with a 5-star review on %2$sWordPress.org%3$s.', 'wp-smushit' ),
						'<br>',
						'<a href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post" target="_blank">',
						'</a>'
					);
					?>
				</p>
				<div id="smush-review-prompts-actions">
					<a target="_blank" href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post"
					class="button button-small button-primary"><?php esc_html_e( 'Rate Smush', 'wp-smushit' ); ?></a>
					<span id="smush-review-prompts-remind-later" class="button button-small" style="background-color: transparent;"><?php esc_html_e( 'Remind me later', 'wp-smushit' ); ?></span>
					<span id="smush-review-prompts-already-did" class="button button-small" style="box-shadow:unset!important;background-color: transparent;"><?php esc_html_e( 'I already did', 'wp-smushit' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the notice for reminding later.
	 *
	 * @return void
	 */
	private function get_remind_later_notice() {
		?>
		<div id="smush-review-prompts-notice"
			class="notice notice-info is-dismissible"
			style="padding-top:8px;padding-bottom:10px;"
			data-notice-type="seven_days">
			<div class="smush-review-prompts-notice-logo">
				<img
					style="margin-top:-2px;margin-bottom:-3px"
					src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon.png' ); ?>"
					srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/notices/review-prompts-icon@2x.png' ); ?> 2x"
					alt="<?php esc_html_e( 'Smush review prompts icon', 'wp-smushit' ); ?>"
				>
			</div>
			<div class="smush-review-prompts-notice-message">
				<h3><?php esc_html_e( 'Thanks for choosing Smush! 💙', 'wp-smushit' ); ?></h3>
				<p><?php esc_html_e( 'If your site’s feeling faster, we’d be so grateful for a quick 5-star review. It really helps us out!', 'wp-smushit' ); ?></p>
				<div id="smush-review-prompts-actions">
					<a target="_blank" href="https://wordpress.org/support/plugin/wp-smushit/reviews/?filter=5#new-post"
					class="button button-small button-primary"><?php esc_html_e( 'Rate Smush', 'wp-smushit' ); ?></a>
					<span id="smush-review-prompts-remind-later" class="button button-small" style="background-color: transparent;"><?php esc_html_e( 'Remind me later', 'wp-smushit' ); ?></span>
					<span id="smush-review-prompts-already-did" class="button button-small" style="box-shadow:unset!important;background-color: transparent;"><?php esc_html_e( 'I already did', 'wp-smushit' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}
}
