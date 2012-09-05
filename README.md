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
include(MODX_BASE_PATH.'assets/plugins/minifyregistered/minifyRegistered.plugin.php');
```
3. Check the events `OnLoadWebDocument` and `OnWebPagePrerender` in the plugin configuration (Note 1).

Parameters
--------------------------------------------------------------------------------

Optionally you can alter the plugin configuration with the following config 
string

```
&groupJs=Group minified files in 'assets/js':;list;yes,no;yes;
&excludeJs=Do not minify following files (comma separated):;text;
```

Property | Description | Default
---- | ----------- | -------
groupJs | All registered javascripts in 'assets/js' are grouped for minify (Note 2) | 1
excludeJs | Comma separated list of files not to be minified (Note 3) | -

Notes
--------------------------------------------------------------------------------
1. The plugin has to work before the Quick Manager Plugin (if you want to use Quick Manager). Edit Plugin Execution Order and drag minifyRegistered plugin before Quick Manager+
2. Grouping all registered javascripts in `assets/js` could change the inclusion order of the registered javascripts.
3. Not minified files are inserted at the last position of head/body.
4. The Plugin needs a working installation of minify (https://github.com/mrclay/minify) in the folder /min in the webroot.
5. Parsing of the registered blocks is done PHx/Chunkie and not by the MODX Parser. Maybe placeholders are not set inside these blocks.

Limitations
--------------------------------------------------------------------------------
1. the media attribute of the link tag is not used
2. to avoid the reordering of the javascript inclusion all snippet calls that are inserting scripts/css by MODX API funktions mentioned above (i.e. `AddHeaderfiles`) have to be called uncached.