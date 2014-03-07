minifyRegistered
================================================================================

Collect the registered javascripts and css files and minify them by minify
(https://github.com/mrclay/minify)
for the MODX Evolution content management framework

Features
--------------------------------------------------------------------------------

With this plugin all js and css files added by the MODX API functions `regClientStartupScript`, `regClientScript`, `regClientCSS`, `regClientStartupHTMLBlock` and `regClientHTMLBlock` are checked to minify them by minify

Installation
--------------------------------------------------------------------------------

1. Upload all files into the new folder *assets/plugins/minifyregistered*
2. Create a new plugin called minifyRegistered with the following code
```
include(MODX_BASE_PATH.'assets/plugins/minifyregistered/minifyregistered.plugin.php');
```
3. Check the event `OnLoadWebDocument` and `OnWebPagePrerender` in the plugin configuration (Note 1).

The Package could be installed directly by [Package Manager](https://github.com/Jako/PackageManager).

Parameters
--------------------------------------------------------------------------------

Optionally you can alter the plugin configuration with the following config 
string

```
&groupJs=Group minified files in `groupFolder`:;list;yes,no;yes 
&groupFolder=Group files in this folder with `groupJs` enabled:;text;assets/js 
&minPath=Path to a working minify installation:;text;/min/ 
&excludeJs=Comma separated list of files (including pathnames) not to be minified:;text;
```

Property | Description | Default
---- | ----------- | -------
groupJs | Group minified files in `groupFolder` (Note 2) | yes
groupFolder | Group files in this folder with `groupJs` enabled | `assets/js`
minPath | Path to a working minify installation | `/min/`
excludeJs | Comma separated list of files (including pathnames) not to be minified | -

Notes
--------------------------------------------------------------------------------

1. The plugin has to work before the Quick Manager+ Plugin (if you want to use Quick Manager+). Edit Plugin Execution Order and drag minifyRegistered plugin before Quick Manager+
2. Grouping all registered javascripts in `assets/js` could change the inclusion order of the registered javascripts.
3. Not minified files are included later than the grouped minified and minified files.
4. Registered chunks (i.e. javascript code) are included at the last position of head/body.
5. The order of inclusion is *external*, *grouped minified*, *minified*, *not minified* and direct code.
6. The Plugin needs a working installation of minify (https://github.com/mrclay/minify) in the folder `/min` in the webroot (the path could be changed in plugin configuration).
7. If you want to use the MODX cache folder for minify, you should insert the following line in `min/config.php` `$min_cachePath = dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME']))).'/assets/cache/minify';`

Limitations
--------------------------------------------------------------------------------

1. The media attribute of the link tag is not used
