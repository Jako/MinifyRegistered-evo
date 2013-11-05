//<?php
/**
 * minifyRegistered
 *
 * Collect the registered javascripts and css files and minify them by minify (https://github.com/mrclay/minify)
 *
 * @category	plugin
 * @version 	0.2.4
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Jako
 * @internal    @properties &groupJs=Group minified files in `groupFolder`:;list;yes,no;yes &groupFolder=Group files in this folder with `groupJs` enabled:;text;assets/js &minPath=Path to a working minify installation:;text;/min/ &excludeJs=Comma separated list of files (including pathnames) not to be minified:;text;
 * @internal    @events OnLoadWebDocument,OnWebPagePrerender
 * @internal    @modx_category Content
 * @internal    @installset base, sample
 */
require(MODX_BASE_PATH . 'assets/plugins/minifyregistered/minifyregistered.plugin.php');
