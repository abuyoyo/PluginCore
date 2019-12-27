<?php
/**
 * PluginCore
 * 
 * Helper Class for creating WordPress Plugins
 * 
 * Defines PLUGIN_PATH, PLUGIN_URL (etc.) constants
 * (@see README.md)
 * 
 * @version 0.7
 * 
 * @todo add admin menu page option
 * @todo plugin_action_links - on Plugins page
 * 
 */

namespace WPHelper;

class PluginCore{

	private $title; // these should be public essentially - func? var?

	private $slug;

	private $plugin_file;

	private $const;

	public $activate_cb;

	public $deactivate_cb;

	public $uninstall_cb;

	/**
	 * Static array of all PluginCore instances
	 * Used in PluginCore::get($slug)
	 */
	static $cores = [];

	static public function get($slug){
		return self::$cores[$slug] ? self::$cores[$slug] : null;
	}

	function __construct( $plugin_file, $options = null ){

		$this->plugin_file( $plugin_file );

		if ( is_array($options) && ! empty($options) ){

			$options = (object) $options;

			if ( isset( $options->title ) ){
				$this->title( $options->title );
			}else{
				$this->title(); // get title from header plugin_data
			}

			if ( isset( $options->slug ) ){
				$this->slug( $options->slug );
			}else{
				$this->slug(); // guess slug from plugin basename
			}
			
			// if ( ! isset( $options->const ) )
			// 	$options->const = str_replace( '-', '_' , strtoupper( $options->slug ) );
			
			if ( isset( $options->const ) ){
				$this->const( $options->const );
			}else{
				$this->const();
			}
				

			if ( isset( $options->activate_cb ) )
				$this->activate_cb( $options->activate_cb );

			if ( isset( $options->deactivate_cb ) )
				$this->deactivate_cb( $options->deactivate_cb );

			if ( isset( $options->uninstall_cb ) )
				$this->uninstall_cb( $options->uninstall_cb );

			if ( isset( $options->admin_menu_page ) ){
				// new WPHelper/AdminMenuPage()
			}

			if ( isset( $options->update_checker ) ){
				// Puc_v4_Factory::buildUpdateChecker
			}
		}
		
		$this->setup();

	}

	/**
	 * @todo set plugin_dir_path, plugin_basename as accessible public variables
	 */
	function setup(){

		// init path and url
		$this->path();
		$this->url();

		define( $this->const . '_PATH', $this->path() );
		define( $this->const . '_DIR', $this->path() );

		define( $this->const . '_URL', $this->url() );
		define( $this->const . '_BASENAME', $this->url() );

		define( $this->const . '_PLUGIN_FILE',  $this->plugin_file );
		define( $this->const . '_FILE',  $this->plugin_file );

		$this->register_hooks();

		self::$cores[$this->slug()] = $this; // using slug() method
	}

	private function register_hooks(){

		if ( ! empty( $this->activate_cb ) ) // && is_callable() ?
			register_activation_hook( $this->plugin_file, $this->activate_cb );
		
		if ( ! empty( $this->deactivate_cb ) )
			register_deactivation_hook( $this->plugin_file, $this->deactivate_cb );

		if ( ! empty( $this->uninstall_cb ) )
			register_uninstall_hook( $this->plugin_file, $this->uninstall_cb );
	}

	public function title( $title=null ){
		
		if ( ! empty( $title ) ){
			$title = esc_html( $title );
			$this->title = $title;
		}	

		if ( empty( $this->title ) ){
			// get title from header plugin_data()
		}

		return $this->title;
	}

	/**
	 * Wrapper function for $this->title()
	 */
	public function name( $title=null ){
		return $this->title( $title );
	}

	public function slug( $slug=null ){
		if ( ! empty( $slug ) )
			$this->slug = $slug;

		if ( empty( $this->slug ) ){
			// get slug from plugin-file basename
		}

		return $this->slug;
	}

	public function plugin_file( $plugin_file ){
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
	public function file(){
		return $this->plugin_file;
	}

	public function const( $const=null ){
		if ( ! empty( $const ) ){
			$this->const = $const;
		}

		if ( empty( $this->const ) ){
			$this->const = str_replace( '-', '_' , strtoupper( $this->slug() ) ); // using slug() method
		}
		
		return $this->const;
	}


	public function path(){
		if ( empty( $this->path ) )
			$this->path = plugin_dir_path( $this->plugin_file );
		return $this->path;
	}

	public function url(){
		if ( empty( $this->url ) )
			$this->url = plugin_dir_url( $this->plugin_file );
		return $this->url;
	}

	private function activate_cb( $activate_cb ){
		// test is_callable() ? or is it too soon?
		$this->activate_cb = $activate_cb;
	}

	private function deactivate_cb( $deactivate_cb ){
		// test is_callable() ? or is it too soon?
		$this->deactivate_cb = $deactivate_cb;
	}

	private function uninstall_cb( $uninstall_cb ){
		// test is_callable() ? or is it too soon?
		$this->uninstall_cb = $uninstall_cb;
	}


}