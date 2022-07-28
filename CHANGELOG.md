# WPHelper\PluginCore Changelog

## 0.20
Release Date: Jul 29, 2022

### Changed

- Update `composer.json` dependencies - `abuyoyo/adminmenupage ~0.20`.

## 0.19
Release Date: Jul 27, 2022

### Changed

- Update `composer.json` dependencies.
- Require PHP >= 7.4

## 0.18
Release Date: May 22, 2022

### Changed

- Class `PluginCore` is pluggable.
- Prevent direct PHP script execution if not accessed within the WordPress environment.

### Fixed

- Include `plugin.php` if function `get_plugin_data` does not exist. This could case critical failure.

## 0.17
Release Date: Feb 7, 2021

### Added

- Pass instance of `PluginCore` to `AdminPage` if current version supports it (used in Plugin Info Metabox generation).

## 0.16

### Fixed

- Upgrade callback `upgrade_cb` will execute when only single plugin is updated.

## 0.15

### Changed

- Use `new WPHelper\AdminPage()` (WPHelper\AdminMenuPage >= 0.12) instead of deprecated `AdminMenuPage`.
- Do not hook `Puc_v4_Factory::buildUpdateChecker` on `admin_init`. Run in plugin's global scope.

## 0.14

### Added

- Add `admin_page` option to create a WPHelper\AdminMenuPage instance.
- Add `plugin_data` variable with WordPress core `get_plugin_data()` object. Use header data if no slug or title provided.

### Fixed
- Fix PHP defines when `const` not provided.

## 0.13.3
- Fix `upgrade_cb` function handling.

## 0.13.2
- Fix `upgrade_cb_wrapper` function.

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
