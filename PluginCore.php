<?php
namespace WPHelper;

use Puc_v4_Factory;

use function add_action;
use function get_plugin_data;
use function register_activation_hook;
use function register_deactivation_hook;
use function register_uninstall_hook;

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
 * @version 0.20
 * 
 * @todo plugin_action_links - on Plugins page
 */
class PluginCore {

	/**
	 * @var string 
	 */
	private $title;

	/**
	 * @var string 
	 */
	private $slug;

	/**
	 * @var string Plugin filename
	 */
	private $plugin_file;

	/**
	 * @var string 
	 */
	private $const;

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

			if ( isset( $options->title ) ) {
				$this->title( $options->title );
			}else{
				$this->title(); // get title from header plugin_data
			}

			if ( isset( $options->slug ) ) {
				$this->slug( $options->slug );
			}else{
				$this->slug(); // guess slug from plugin basename
			}
			
			if ( isset( $options->const ) ) {
				$this->const( $options->const );
			}else{
				$this->const();
			}

			if ( isset( $options->admin_page ) ) {
				$this->admin_page( $options->admin_page );
			}

			if ( isset( $options->activate_cb ) )
				$this->activate_cb( $options->activate_cb );

			if ( isset( $options->deactivate_cb ) )
				$this->deactivate_cb( $options->deactivate_cb );

			if ( isset( $options->uninstall_cb ) )
				$this->uninstall_cb( $options->uninstall_cb );

			if ( isset( $options->upgrade_cb ) )
				$this->upgrade_cb( $options->upgrade_cb );

			if ( isset( $options->update_checker ) ) {
				$this->update_checker( $options->update_checker );
			}
		}
		
		$this->setup();

	}

	/**
	 * Define plugin constants (_PATH, _URL, _BASENAME, _FILE etc.)
	 * Register activation, deactivation, uninstall, upgrade hooks.
	 * Init PUC update checker.
	 * Add PluginCore instance to static $cores
	 * 
	 * @todo rename method bootstrap()
	 * @todo set plugin_dir_path, plugin_basename as accessible public variables (available thru methods atm)
	 */
	function setup() {

		// init path and url
		// redundant init() and path() are getter/setter methods.
		$this->path();
		$this->url();

		define( $this->const() . '_PATH', $this->path() );
		define( $this->const() . '_DIR', $this->path() );

		define( $this->const() . '_URL', $this->url() );
		define( $this->const() . '_BASENAME', $this->plugin_basename() );

		define( $this->const() . '_PLUGIN_FILE',  $this->plugin_file );
		define( $this->const() . '_FILE',  $this->plugin_file );

		$this->register_hooks();

		self::$cores[ $this->slug() ] = $this; // using slug() method


		if ( $this->update_checker == true ) {
			// run early - before Puc_v4p8_Scheduler->maybeCheckForUpdates() [admin_init 10]
			// hooking on admin_init does not work with wp_plugin_updates as it requires user logged-in
			// add_action( 'admin_init', [$this, 'init_update_checker'], 9 );
			$this->init_update_checker();
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
		
		if ( ! empty( $title ) ) {
			$title = esc_html( $title );
			$this->title = $title;
		}	

		if ( empty( $this->title ) ) {
			$this->plugin_data();
			$this->title = $this->plugin_data['Title'];
		}

		return $this->title;
	}

	/**
	 * Wrapper function for $this->title()
	 */
	public function name( $title = null ) {
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
		// doing it this way means slug can only be set once.
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
		$this->plugin_file = $plugin_file;
		return $this->plugin_file;
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


	public function plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->plugin_file, false);
		}
		return $this->plugin_data;
	}

	/**
	 * Getter/Setter - const
	 * Prefix of plugin specific defines (PLUGIN_NAME_PATH etc.)
	 * If not provided - plugin slug will be uppercased.
	 * 
	 * @param  string|null $const (string should be uppercased)
	 * @return string      $this->const
	 */
	public function const( $const = null ) {
		
		// if $const provided - use that
		if ( ! empty( $const ) ) {
			$this->const = $const;
		}

		// if no $const provided - generate from slug()
		if ( empty( $this->const ) ) {
			$this->const = str_replace( '-', '_' , strtoupper( $this->slug() ) ); // using slug() getter/setter
		}
		
		return $this->const;
	}

	public function admin_page( $admin_page ) {
		if ( empty( $admin_page['slug'] ) ) {
			$admin_page['slug'] = $this->slug();
		}
		if ( empty( $admin_page['title'] ) ) {
			$admin_page['title'] = $this->title();
		}

		$this->admin_page = new AdminPage( $admin_page );

		if ( method_exists( $this->admin_page, 'plugin_core' ) ) {
			$this->admin_page->plugin_core( $this );
		}

		return $this->admin_page;
	}


	public function path() {
		if ( empty( $this->path ) )
			$this->path = plugin_dir_path( $this->plugin_file );
		return $this->path;
	}

	public function url() {
		if ( empty( $this->url ) )
			$this->url = plugin_dir_url( $this->plugin_file );
		return $this->url;
	}

	public function plugin_basename() {
		if ( empty( $this->plugin_basename ) )
			$this->plugin_basename = plugin_basename( $this->plugin_file );
		return $this->plugin_basename;
	}

	private function activate_cb( $activate_cb ) {
		// test is_callable() ? or is it too soon?
		$this->activate_cb = $activate_cb;
	}

	private function deactivate_cb( $deactivate_cb ) {
		// test is_callable() ? or is it too soon?
		$this->deactivate_cb = $deactivate_cb;
	}

	private function uninstall_cb( $uninstall_cb ) {
		// test is_callable() ? or is it too soon?
		$this->uninstall_cb = $uninstall_cb;
	}

	private function upgrade_cb( $upgrade_cb ) {
		// test is_callable() ? or is it too soon?
		$this->upgrade_cb = $upgrade_cb;
	}

	private function update_checker( $update_checker ) {
		// Puc_v4_Factory::buildUpdateChecker
		if ( empty( $update_checker ) ) {
			$this->update_checker = false;
		}else{
			if ( is_bool( $update_checker ) ) {
				$this->update_checker = $update_checker;
			}

			if ( is_string( $update_checker ) ) {
				$this->update_checker = true;
				$this->update_repo_uri = $update_checker;
			}

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
		}
	}

	public function init_update_checker() {
	
		if ( ! class_exists('Puc_v4_Factory') )
			return;

		if ( ! isset( $this->update_repo_uri ) ) {
			$this->plugin_data();
			
			if ( isset( $this->plugin_data['PluginURI'] ) )
				$this->update_repo_uri = $this->plugin_data['PluginURI'];
			else
				return;
		}
		// wp_dump($this);
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

}
endif;