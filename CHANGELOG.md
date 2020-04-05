# WPHelper\PluginCore Changelog

## 0.13.1
- Update `composer.json` version.

## 0.13
- Fix `upgrade_cb_wrapper` function.

## 0.12
- Add upgrade_cb wrapper function that conducts sanity-checks before calling `upgrade_cb` callback provided.
- Add `plugin_basename()` getter/setter function and `plugin_basename` variable.
- Add changelog.

## 0.11
- Add `upgrade_cb` option - callable function to run on WordPress `upgrader_process_complete` hook.

## 0.10
- Fix undefined index PHP notices introduced in version 0.9

## 0.9
- Add automatic plugin update checker using `yahnis-elsts/plugin-update-checker` library.

## 0.8
- Fix wrong `plugin_basename` constant.

## 0.7
- Don't use `extarct` in constructor
- Add sanity checks and normalize getter/setter functions
- Add `file()` getter function.

## 0.6
- Add `path()`, `url()` getter/setter functions.
- Add `name()` getter function.

## 0.5
- Initial release.
- Defines `PLUGINNAME_URL`, `_PATH`, `_DIR`, `_BASENAME`, `_FILE` constants for plugin.
- Registers plugin activation, deactivation and uninstall hook if callbacks provided.
- Static function `PluginCore::get($slug)` will return instance of PluginCore registered with `$slug`. Thus PluginCore can be initiated without polluting global scope.
