<?php
namespace WPHelper;

use Puc_v4_Factory;

defined( 'ABSPATH' ) || die( 'No soup for you!' );

if( ! function_exists('get_plugin_data') ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! class_exists( 'WPHelper/PluginCore' ) ):
/**
 * PluginCore
 * 
 * Helper Class for creating WordPress Plugins
 * 
 * Defines PLUGIN_PATH, PLUGIN_URL (etc.) constants
 * (@see README.md)
 * 
 * @version 0.22
 */
class PluginCore {

	/**
	 * @var string Plugin filename
	 */
	private $plugin_file;

	/**
	 * @var string 
	 */
	private $title;

	/**
	 * @var string 
	 */
	private $slug;

	/**
	 * @var string 
	 */
	private $const;

	/**
	 * @var string 
	 */
	private $path;

	/**
	 * @var string 
	 */
	private $url;

	/**
	 * @var string 
	 */
	private $plugin_basename;

	/**
	 * @var callable 
	 */
	public $activate_cb;

	/**
	 * @var callable 
	 */
	public $deactivate_cb;

	/**
	 * @var callable 
	 */
	public $uninstall_cb;

	/**
	 * @var callable 
	 */
	public $upgrade_cb;

	/**
	 * @var array|callable
	 */
	public $action_links;

	/**
	 * @var AdminPage
	 */
	public $admin_page;

	/**
	 * @var \Puc_v4p10_Plugin_UpdateChecker
	 */
	private $update_checker;

	/**
	 * @var string Repo uri
	 */
	private $update_repo_uri;

	/**
	 * @var string Repo authentication key
	 */
	private $update_auth;
	
	/**
	 * @var string Repo branch
	 */
	private $update_branch;



	/**
	 * Static array of all PluginCore instances
	 * Used in PluginCore::get($slug)
	 * 
	 * @var array[PluginCore] Instances of PluginCore
	 */
	static $cores = [];

	/**
	 * Retrieve instance of PluginCore by plugin slug.
	 * 
	 * @param string $slug - Plugin slug
	 * @return PluginCore - Instance of specific plugin.
	 */
	static public function get( $slug ) {
		return self::$cores[ $slug ] ?? null;
	}

	function __construct( $plugin_file, $options = null ) {

		$this->plugin_file( $plugin_file );

		if ( is_array( $options ) && ! empty( $options ) ) {

			$options = (object) $options;

			$this->title( $options->title ?? null ); // fallback: get title from header plugin_data

			$this->slug( $options->slug ?? null ); // fallback: guess slug from plugin basename

			$this->const( $options->const ?? null ); // fallback: generate const from slug

			if ( isset( $options->activate_cb ) )
				$this->activate_cb( $options->activate_cb );

			if ( isset( $options->deactivate_cb ) )
				$this->deactivate_cb( $options->deactivate_cb );

			if ( isset( $options->uninstall_cb ) )
				$this->uninstall_cb( $options->uninstall_cb );

			if ( isset( $options->upgrade_cb ) )
				$this->upgrade_cb( $options->upgrade_cb );

			if ( isset( $options->action_links ) )
				$this->action_links( $options->action_links );

			if ( isset( $options->admin_page ) )
				$this->admin_page( $options->admin_page ); // creates AdminPage instance

			if ( isset( $options->update_checker ) )
				$this->update_checker( $options->update_checker );

		}
		
		$this->bootstrap();

	}

	/**
	 * Bootstrap
	 * 
	 * Setup url, path, plugin_basename variables
	 * Add PluginCore instance to static $cores
	 * Define plugin constants (_PATH, _URL, _BASENAME, _FILE etc.)
	 * Register activation, deactivation, uninstall, upgrade hooks.
	 * Init PUC update checker.
	 * 
	 * @todo set plugin_dir_path, plugin_basename as accessible public variables (available thru methods atm)
	 */
	private function bootstrap() {

		// validate basic variables (in case no options array were given)
		$this->title();
		$this->slug();
		$this->const();

		// set variables
		$this->path();
		$this->url();
		$this->plugin_basename();

		/**
		 * Add this PluginCore instance to static list of PluginCore instances (key = slug).
		 * @see static function get()
		 */
		self::$cores[ $this->slug ] = $this;

		// define constants
		define( $this->const . '_PATH', $this->path );
		define( $this->const . '_DIR', $this->path );

		define( $this->const . '_URL', $this->url );
		define( $this->const . '_BASENAME', $this->plugin_basename );

		define( $this->const . '_PLUGIN_FILE',  $this->plugin_file );
		define( $this->const . '_FILE',  $this->plugin_file );

		$this->register_hooks();

		$this->add_plugin_action_links();

		if ( $this->update_checker === true ) {
			$this->build_update_checker();
		}
	}

	private function register_hooks() {

		if ( ! empty( $this->activate_cb ) ) // && is_callable() ?
			register_activation_hook( $this->plugin_file, $this->activate_cb );
		
		if ( ! empty( $this->deactivate_cb ) )
			register_deactivation_hook( $this->plugin_file, $this->deactivate_cb );

		if ( ! empty( $this->uninstall_cb ) )
			register_uninstall_hook( $this->plugin_file, $this->uninstall_cb );

		if ( ! empty( $this->upgrade_cb ) )
			add_action( 'upgrader_process_complete', [ $this, 'upgrade_cb_wrapper' ], 10, 2 );
	}

	/**
	 * Getter/Setter - title
	 * Plugin title.
	 * If none provided - plugin header Title will be used.
	 * 
	 * @param  string|null $title
	 * @return string      $this->title
	 */
	public function title( $title = null ) {
		return $this->title ??= esc_html( $title ) ?: $this->plugin_data()['Title'];
	}

	/**
	 * Wrapper function for $this->title()
	 * 
	 * @deprecated
	 */
	public function name( $title = null ) {
		_doing_it_wrong( __METHOD__, 'Use PluginCore::title instead.', '0.21' );
		return $this->title( $title );
	}

	/**
	 * Getter/Setter - slug
	 * Plugin slug.
	 * If none provided - plugin file basename will be used
	 * 
	 * @param  string|null $slug
	 * @return string      $this->slug
	 */
	public function slug( $slug = null ) {
		return $this->slug ??= $slug ?: basename( $this->plugin_file, '.php' );
	}

	/**
	 * Setter - plugin_file (also Getter - kinda)
	 * Plugin file fully qualified path.
	 * 
	 * @param  string $plugin_file - Path to plugin file
	 * @return string $this->plugin_file
	 */
	public function plugin_file( $plugin_file ) {
		return $this->plugin_file ??= $plugin_file;
	}

	/**
	 * GETTER function. NOT a wrapper
	 * Might have to rethink this
	 * used by test-plugin update_checker
	 * 
	 * @todo revisit this
	 */
	public function file() {
		return $this->plugin_file;
	}


	/**
	 * Getter/Setter - plugin data array
	 */
	public function plugin_data() {
		return $this->plugin_data ??= get_plugin_data( $this->plugin_file, false);
	}

	/**
	 * Getter/Setter - const
	 * Prefix of plugin specific defines (PLUGIN_NAME_PATH etc.)
	 * If not provided - plugin slug will be uppercase.
	 * 
	 * @param  string|null $const (string should be uppercase)
	 * @return string      $this->const
	 */
	public function const( $const = null ) {
		return $this->const ??= $const ?: str_replace( '-', '_' , strtoupper( $this->slug() ) );
	}

	/**
	 * Getter/setter
	 */
	public function path() {
		return $this->path ??= plugin_dir_path( $this->plugin_file );
	}

	/**
	 * Getter/Setter
	 */
	public function url() {
		return $this->url ??= plugin_dir_url( $this->plugin_file );
	}

	/**
	 * Getter/Setter
	 */
	public function plugin_basename() {
		return $this->plugin_basename ??= plugin_basename( $this->plugin_file );
	}

	/**
	 * Setter - Activation callback
	 * Callback runs on 'register_activation_hook'
	 * PluginCore does not validate. Authors must ensure valid callback.
	 * 
	 * @param callable $activate_cb - Activation callback
	 * 
	 * @access private
	 */
	private function activate_cb( $activate_cb ) {
		$this->activate_cb = $activate_cb;
	}

	/**
	 * Setter - Deactivation callback
	 * Callback runs on 'register_deactivation_hook'
	 * PluginCore does not validate. Authors must ensure valid callback.
	 * 
	 * @param callable $deactivate_cb - Deactivation callback.
	 * 
	 * @access private
	 */
	private function deactivate_cb( $deactivate_cb ) {
		$this->deactivate_cb = $deactivate_cb;
	}

	/**
	 * Setter - Uninstall callback
	 * Callback runs on 'register_uninstall_hook'
	 * PluginCore does not validate. Authors must ensure valid callback.
	 * 
	 * @param callable $uninstall_cb - Uninstall callback.
	 * 
	 * @access private
	 */
	private function uninstall_cb( $uninstall_cb ) {
		$this->uninstall_cb = $uninstall_cb;
	}

	/**
	 * Setter - Upgrade callback
	 * Callback runs on 'upgrader_process_complete' hook - only for our plugin.
	 * Runs inside wrapper function that ensures our plugin was updated. 
	 * (@see upgrade_cb_wrapper() below)
	 * 
	 * PluginCore does not validate. Authors must ensure valid callback.
	 * 
	 * @param callable $upgrade_cb - Upgrade callback.
	 * 
	 * @access private
	 */
	private function upgrade_cb( $upgrade_cb ) {
		$this->upgrade_cb = $upgrade_cb;
	}

	/**
	 * Setter - Plugin action links
	 * 
	 * Add links to plugin action links on Plugins page.
	 * Accepts callable hooked to 'plugin_action_links_{$plugin}'
	 * Alternatively accepts array of key => string/HTML tag (eg. [ 'settings' => '<a href="foo" />' ] )
	 * Alternatively accepts array of key => [ 'text' => 'My Link', 'href' => 'foo' ]
	 * Special case: Settings Page
	 * [ 'settings' => [ 'href' => 'menu_page', 'text' => 'Settings' ] ] will generate link to plugin menu page url (@see menu_page_url() )
	 * (@see add_plugin_action_links() below)
	 * 
	 * @since 0.21
	 * 
	 * @param callable|array $action_links - filter function or custom action links array
	 * 
	 * @todo perhaps have separate action_links_array + action_links_cb variables
	 */
	private function action_links( $action_links ) {
		$this->action_links = $action_links;
	}

	/**
	 * Getter/Setter - AdminPage
	 * 
	 * Construct AdminPage instance for plugin. 
	 * 
	 * @param array $admin_page - AdminPage settings array
	 * 
	 * @return AdminPage
	 */
	public function admin_page( $admin_page ) {

		if ( ! class_exists( 'WPHelper\AdminPage' ) )
			return;

		// validate
		$admin_page['slug'] ??= $this->slug();
		$admin_page['title'] ??= $this->title();

		$this->admin_page = new AdminPage( $admin_page );

		// validate for older versions of AdminPage
		if ( method_exists( $this->admin_page, 'plugin_core' ) ) {
			$this->admin_page->plugin_core( $this ); // back-reference
		}

		return $this->admin_page;
	}

	/**
	 * Setter
	 * 
	 * Setup info used by Puc_v4_Factory
	 * 
	 * set $update_checker (bool)
	 * set $update_repo_uri (string)
	 * set $update_auth (optional)
	 * set $update_branch (optional)
	 * 
	 * @param bool|string|array $update_checker
	 */
	private function update_checker( $update_checker ) {

		if ( empty( $update_checker ) ) {
			$this->update_checker = false;
		}

		if ( is_bool( $update_checker ) ) {
			$this->update_checker = $update_checker;
		}

		// option 'update_checker' accepts string - repo uri
		if ( is_string( $update_checker ) ) {
			$this->update_checker = true;
			$this->update_repo_uri = $update_checker;
		}

		// option 'update_checker' accepts array: ['uri'=> , 'auth'=>, 'branch'=> ]
		if ( is_array( $update_checker ) ) {
			$this->update_checker = true;

			if ( isset( $update_checker['uri'] ) ) {
				$this->update_repo_uri = $update_checker['uri'];
			}
			if ( isset( $update_checker['auth'] ) ) {
				$this->update_auth = $update_checker['auth'];
			}
			if ( isset( $update_checker['branch'] ) ) {
				$this->update_branch = $update_checker['branch'];
			}
		}

		// Use plugin header 'UpdateURI' or fallback to 'PluginURI'
		// call plugin_data() to init var plugin_data
		$this->update_repo_uri ??= $this->plugin_data()['UpdateURI'] ?: $this->plugin_data['PluginURI'] ?: null;

		// validate
		// If no repo uri - update checker is disabled.
		if ( empty( $this->update_repo_uri ) ) {
			$this->update_checker = false;
		}
		
	}

	/**
	 * Init Puc update checker instance
	 * 
	 * @uses Puc_v4_Factory::buildUpdateChecker
	 */
	private function build_update_checker() {
	
		if ( ! class_exists('Puc_v4_Factory') )
			return;

		$update_checker = Puc_v4_Factory::buildUpdateChecker(
			$this->update_repo_uri,
			$this->plugin_file,
			$this->slug() // using slug()
		);

		//Optional: If you're using a private repository, specify the access token like this:
		if ( isset( $this->update_auth ) )
			$update_checker->setAuthentication( $this->update_auth );

		//Optional: Set the branch that contains the stable release.
		if ( isset( $this->update_branch ) )
			$update_checker->setBranch( $this->update_branch );

	}

	/**
	 * upgrade_cb_wrapper
	 * 
	 * This function only called if upgrade_cb is set (@see register_hooks())
	 * This function called on upgrader_process_complete
	 * sanity-checks if our plugin was upgraded
	 * if so - calls upgrade_cb provided by our plugin
	 */
	public function upgrade_cb_wrapper( $upgrader_object, $options ) {
		if(
			$options['action'] == 'update'  // has upgrade taken place
			&&
			$options['type'] == 'plugin' // is it a plugin upgrade
			&&
			(
				(
					isset( $options['plugins'] ) // is list of plugins upgraded
					&&
					in_array( $this->plugin_basename(), $options['plugins']) // is our plugin in that list
				)
				||
				( // single plugin updated
					isset( $options['plugin'] )
					&&
					$this->plugin_basename() == $options['plugin']
				)
			)
		) {
			call_user_func( $this->upgrade_cb, $upgrader_object, $options );
		}
	}

	/**
	 * Add plugin_action_links
	 * 
	 * Parse action_links (callable or array).
	 * Generate callback if action_links provided as array.
	 * Add callback to 'plugin_action_links_{$plugin}' hook.
	 * 
	 * @since 0.21
	 * 
	 * @access private
	 */
	private function add_plugin_action_links() {
		if ( empty( $this->action_links ) )
			return;

		if ( is_callable( $this->action_links ) ) { // default - pass a filter method
			$action_links_cb =  $this->action_links;
		} else if ( is_array( $this->action_links ) ) { // array of links - PluginCore will do the heavy lifting
			$action_links_cb = function( $links ) {
				foreach( $this->action_links as $key => $link ) {
					if ( is_string( $link ) ) { // we assume a straight HTML tag string
						$links[ $key ] = $link; // just print it
					} else if ( is_array( $link ) ) { // accepts ['href'=>'/my-href', 'text'=>'My Action Link']
						$links[ $key ] = sprintf(
							'<a href="%s">%s</a>',
							$link['href'] == 'menu_page' // reserved parameter value
								? esc_url( menu_page_url( $this->slug, false ) )
								: $link['href'],
							$link['text'],
						);
					}
				}
				return $links;
			};
		}

		add_filter( 'plugin_action_links_' . $this->plugin_basename(), $action_links_cb );
	}

}
endif;