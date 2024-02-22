# WPHelper\PluginCore Changelog

## 0.29
Release Date: Feb 22, 2024

### Fixed
- Check if constants are defined before defining constants.

## 0.28
Release Date: Jan 22, 2024

### Added
- Public method `version()` returns plugin version from plugin header.

### Changed
- Getter method `path()` accepts optional relative path parameter. Does not affect setter functionality.
- Getter method `url()` accepts optional relative path parameter. Does not affect setter functionality.

## 0.27
Release Date: Oct 3, 2023

### Added
- Support Plugin Update Checker v5 as well as v4. Create class alias `WPHelper\PucFactory` for `YahnisElsts\PluginUpdateChecker\v5\PucFactory` if available. Fallback to `Puc_v4_Factory`.

## 0.26
Release Date: Jun 9, 2023

### Fixed
- Fix passing PluginCore instance to AdminPage constructor in `admin_page()` method.

## 0.25
Release Date: Feb 3, 2023

### Added
- Property `token` and method `token`. Lowercase underscore token to be used as variable name.

## 0.24
Release Date: Jan 13, 2023

### Added
- Static `get_by_file` method. Get PluginCore instance by plugin filename.

## 0.23
Release Date: Aug 8, 2022

### Fixed
- Minor fixes.

## 0.22
Release Date: Aug 8, 2022

### Removed
- Remove all dependencies. No require.

## 0.21
Release Date: Aug 5, 2022

### Added

- Add `action_links` option. Accepts standard `plugin_action_links_` callback filter function. Alternatively accepts array of links. links can be HTML tag strings (`'<a href="/link">Link</a>'`) or arrays with keys `href` and `text`. Special use case `'href' => 'menu_page'` available for quick Settings link generation.

### Changed
- Plugin updater - prefer plugin header `Update URI` for plugin update checker, if no URI provided in options.
- Validate class `WPHelper\AdminPage` exists - required for `admin_page` option/settings.
- Significant code cleanup, notes, doc blocks and reorganizing of PluginCore class.

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
- Don't use `extract` in constructor
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
