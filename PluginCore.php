<?php
/**
 * PluginCore
 * 
 * Helper Class for creating WordPress Plugins
 * 
 * Defines PLUGIN_PATH, PLUGIN_URL (etc.) constants
 * (@see README.md)
 * 
 * @version 0.5
 * 
 * @todo add admin menu page option
 * @todo plugin_action_links - on Plugins page
 * 
 */

namespace WPHelper;

class PluginCore{

	private $title;

	private $slug;

	private $plugin_file;

	private $const;

	static $cores = [];

	static public function get($slug){
		return self::$cores[$slug] ? self::$cores[$slug] : null;
	}

	function __construct( $plugin_file, $args = null ){

		$this->plugin_file( $plugin_file );

		if ( $args ){
			extract( $args );

			if ( $title )
				$this->title( esc_html($title) );

			if ( $slug )
				$this->slug( $slug );

			if ( ! $const )
				$const = str_replace( '-', '_' , strtoupper( $slug ) );
			
			if ( $const )
				$this->const( $const );

			if ( $activate_cb )
				$this->activate_cb( $activate_cb );

			if ( $deactivate_cb )
				$this->deactivate_cb( $deactivate_cb );

			if ( $uninstall_cb )
				$this->uninstall_cb( $uninstall_cb );
		}
		
		$this->setup();

	}

	/**
	 * @todo set plugin_dir_path, plugin_basename as accessible public variables
	 */
	function setup(){

		define( $this->const . '_PATH', plugin_dir_path( $this->plugin_file ) );
		define( $this->const . '_DIR', plugin_dir_path( $this->plugin_file ) );

		define( $this->const . '_URL', plugin_dir_url( $this->plugin_file ) );
		define( $this->const . '_BASENAME', plugin_basename( $this->plugin_file ) );

		define( $this->const . '_PLUGIN_FILE',  $this->plugin_file );
		define( $this->const . '_FILE',  $this->plugin_file );

		$this->register_hooks();

		self::$cores[$this->slug] = $this;
	}

	function register_hooks(){

		if ( $this->activate_cb )
			register_activation_hook( $this->plugin_file, $this->activate_cb );
		
		if ( $this->deactivate_cb )
			register_deactivation_hook( $this->plugin_file, $this->deactivate_cb );

		if ( $this->uninstall_cb )
			register_uninstall_hook( $this->plugin_file, $this->uninstall_cb );
	}

	function title($title=null){
		if ($title)
			$this->title = $title;
		return $this->title;
	}

	function slug($slug){
		$this->slug = $slug;
		return $this->slug;
	}

	function plugin_file($plugin_file){
		$this->plugin_file = $plugin_file;
		return $this->plugin_file;
	}

	function const($const){
		$this->const = $const;
		return $this->const;
	}

	function activate_cb($activate_cb){
		$this->activate_cb = $activate_cb;
	}

	function deactivate_cb($deactivate_cb){
		$this->deactivate_cb = $deactivate_cb;
	}

	function uninstall_cb($uninstall_cb){
		$this->uninstall_cb = $uninstall_cb;
	}


}