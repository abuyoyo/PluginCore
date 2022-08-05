# WPHelper \ PluginCore

> Helper class for registering WordPress plugins.

Plugin Boilerplates and boilerplate generator are a hassle. The file structure they impose is way too cumbersome (and redundant) to push into every single plugin. WPHelper\PluginCore replaces boilerplates with one simple class (usually hidden away somewhere in your ``vendor/`` dir).  

[WPHelper\AdminMenuPage](https://github.com/abuyoyo/AdminMenuPage) can be used to register and generate admin menus if your plugin requires that.

## Requirements
* PHP >= 7.4
* [Composer](https://getcomposer.org/)
* [WordPress](https://wordpress.org)

## Installation

Install with [Composer](https://getcomposer.org/) or just drop PluginCore.php into your plugin folder and require it.

```PHP
// Require the Composer autoloader anywhere in your code.
require __DIR__ . '/vendor/autoload.php';

```

OR

```PHP
// Require the class file directly from your plugin.
require_once __DIR__ . 'PluginCore.php';

```


WPHelper\PluginCore uses [PSR-4](https://www.php-fig.org/psr/psr-4/) to autoload.

## Basic Usage

WpHelper/PluginCore replaces the many plugin core skeleton generators out there. Just add these lines of code at the top of your plugin file and you're good to go.

WpHelper/PluginCore will define %PLUGIN%_BASENAME, %PLUGIN%_PATH, %PLUGIN%_URL, %PLUGIN%_FILE constants available to your code.

```PHP
/*
 * Plugin Name: My Awesome Plugin
 * Description: Plugin's description.
 * Version:  1.0.0
 */

// Import PluginCore.
use WPHelper\PluginCore;

// Register the plugin
$args = [
    'title' => 'My Awesome Plugin', // Optional - will fallback to plugin header Plugin Name.
    'slug' => 'my-awesome-plugin', // Optional - will generate slug based on plugin header Plugin Name
    'const' => 'MYPLUGIN' // Optional - slug used to define constants: MYPLUGIN_DIR, MYPLUGIN_URL etc. (if not provided will use 'slug' in ALLCAPS)
    'activate_cb' => 'activate_callback' // Optional - Provide a callable function to run on activation
    'deactivate_cb' => 'deactivate_callback' // Optional - Provide a callable function to run on deactivation
    'uninstall_cb' => 'uninstall_callback' // Optional - (@todo) Consider using uninstall.php and not this plugin. This plugin can run in the global scope and cause problems
];

// Setup plugin constants and activation/deactivation hooks
new PluginCore( __FILE__, $args );

// Start writing your code here..
include '/foo.php';
add_action( 'plugins_loaded' function() {
    // whatever..
});
```

### Constants

WPHelper\PluginCore defines constants for use in your code. Where ``__FILE__`` is the filename provided to the class and ``%PLUGIN%`` is the  ``'const'`` option.
Like so:

```PHP
define( '%PLUGIN%_URL', plugin_dir_url( __FILE__ ) );
define( '%PLUGIN%_FILE', __FILE__ );
```

These are the constants defined by WPHelper\PluginCore. There are some redundancies to account for different conventions.

* %PLUGIN%_PATH: ``plugin_dir_path( __FILE__ ) )``
* %PLUGIN%_DIR: ``plugin_dir_path( __FILE__ ) )``
* %PLUGIN%_URL:  ``plugin_dir_url( __FILE__ ) )``
* %PLUGIN%_BASENAME: ``plugin_basename( __FILE__ ) )``
* %PLUGIN%_FILE: ``__FILE__``
* %PLUGIN%_PLUGIN_FILE: ``__FILE__``

### Get Instance

All PluginCore instances can be referenced anywhere in your code using static method `get()` and the plugin slug. Available on `plugins_loaded` hook or later.
```PHP
PluginCore::get('my-awesome-plugin'); // returns PluginCore instance constructed with slug 'my-awesome-plugin'
```
