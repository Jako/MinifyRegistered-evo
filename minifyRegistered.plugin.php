<?php
/**
 * MinifyRegistered
 *
 * @category 	plugin
 * @version 	0.2.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    Description: 
 *              <strong>0.2.1</strong> collect the registered javascripts and css files and minify them by minify (https://github.com/mrclay/minify)
 * @internal    Plugin code:
 *              include(MODX_BASE_PATH.'assets/plugins/minifyregistered/minifyRegistered.plugin.php');
 * @internal	Events: 
 *              OnWebPagePrerender
 * @internal	Configuration: 
 *              &groupJs=Group minified files in `groupFolder`:;list;yes,no;yes &groupFolder=Group files in this folder with `groupJs` enabled:;text;assets/js &minPath=Path to a working minify installation:;text;/min/ &excludeJs=Comma separated list of files (including pathnames) not to be minified:;text;
 *
 * @internal    The Plugin needs a working installation of
 *              https://github.com/mrclay/minify in the folder /min or in the
 *              webroot (the path could be changed in plugin configuration).
 */
$groupJs = (isset($groupJs)) ? (bool) $groupJs : true;
$groupFolder = (isset($groupFolder)) ? $groupFolder : 'assets/js';
$minPath = (isset($minPath)) ? (bool) $minPath : '/min/';
$excludeJs = (isset($excludeJs)) ? $excludeJs : '';

$excludeJs = ($excludeJs != '') ? explode(',', $excludeJs) : array();

$e = &$modx->Event;
switch ($e->name) {
	case 'OnWebPagePrerender' : {
			$registeredScripts = array();

			// get output and registered scripts
			$output = &$modx->documentOutput;
			$clientStartupScripts = $modx->getRegisteredClientStartupScripts();
			$clientScripts = $modx->getRegisteredClientScripts();

			// remove inserted registered scripts
			if ($clientStartupScripts) {
				$output = str_replace($clientStartupScripts . "\n", '', $output);
			}
			if ($clientScripts) {
				$output = str_replace($clientScripts . "\n", '', $output);
			}
			// if minified scripts not cached, collect them
				$startupScripts = explode("\n", $clientStartupScripts);
				$scripts = explode("\n", $clientScripts);

				// startup scripts
				foreach ($startupScripts as $scriptSrc) {
					preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag);
					if (preg_match('/(src|href)=\"([^\"]+)/', $tag[0], $src)) {
						// if there is a filename referenced in the registered line
						if (substr(trim($src[2]), -3) == '.js') {
							// the registered chunk is a separate javascript
							if (substr($src[2], 0, 4) == 'http' || substr($src[2], 0, 2) == '//') {
								// do not minify scripts with an external url
								$registeredScripts['head_external'][] = $src[2];
							} elseif (in_array($src[2], $excludeJs)) {
								// do not minify scripts in excludeJs
								$registeredScripts['head_nomin'][] = $src[2];
							} elseif ($groupJs && (trim(dirname(trim($src[2])), '/') == $groupFolder)) {
								// group minify scripts in assets/js
								$registeredScripts['head_jsmingroup'][] = trim(str_replace($groupFolder, '', $src[2]), '/');
							} else {
								// minify scripts
								$registeredScripts['head_jsmin'][] = $src[2];
							}
						} elseif (substr(trim($src[2]), -4) == '.css') {
							// minify css
							$registeredScripts['head_cssmin'][] = $src[2];
						} else {
							// do not minify any other file
							$registeredScripts['head_nomin'][] = $src[2];
						}
					} else {
						// if there is no filename referenced in the registered line leave it alone
						$registeredScripts['head'][] = $scriptSrc;
					}
				}

				// body scripts
				foreach ($scripts as $scriptSrc) {
					preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag);
					if (preg_match('/(src|href)=\"([^\"]+)/', $tag[0], $src)) {
						// if there is a filename referenced in the registered line
						if (substr(trim($src[2]), -3) == '.js') {
							// the registered chunk is a separate javascript
							if (substr($src[2], 0, 4) == 'http' || substr($src[2], 0, 2) == '//') {
								// do not minify scripts with an external url
								$registeredScripts['body_external'][] = $src[2];
							} elseif (in_array($src[2], $excludeJs)) {
								// do not minify scripts in excludeJs
								$registeredScripts['body_nomin'][] = $src[2];
							} elseif ($groupJs && (trim(dirname(trim($src[2])), '/') == $groupFolder)) {
								// group minify scripts in assets/js
								$registeredScripts['body_jsmingroup'][] = trim(str_replace($groupFolder, '', $src[2]), '/');
							} else {
								// minify scripts
								$registeredScripts['body_jsmin'][] = $src[2];
							}
						} elseif (substr(trim($src[2]), -4) == '.css') {
							// minify css
							$registeredScripts['body_cssmin'][] = $src[2];
						} else {
							// do not minify any other file
							$registeredScripts['body_nomin'][] = $src[2];
						}
					} else {
						// if there is no filename referenced in the registered line leave it alone
						$registeredScripts['body'][] = $scriptSrc;
					}
				}

				// prepare the output of the registered blocks
				if (count($registeredScripts['head_cssmin'])) {
					$minifiedScripts['head'] .= '<link href="' . $minPath . '?f=' . implode(',', $registeredScripts['head_cssmin']) . '" rel="stylesheet" type="text/css" />' . "\r\n";
				}
				if (count($registeredScripts['head_external'])) {
					$minifiedScripts['head'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['head_external']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head_jsmingroup'])) {
					$minifiedScripts['head'] .= '<script src="' . $minPath . '?b=' . $groupFolder . '&amp;f=' . implode(',', $registeredScripts['head_jsmingroup']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head_jsmin'])) {
					$minifiedScripts['head'] .= '<script src="' . $minPath . '?f=' . implode(',', $registeredScripts['head_jsmin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head_nomin'])) {
					$minifiedScripts['head'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['head_nomin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head'])) {
					$minifiedScripts['head'] .= implode("\r\n", $registeredScripts['head']) . "\r\n";
				}
				if (count($registeredScripts['body_external'])) {
					$minifiedScripts['body'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['body_external']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body_jsmingroup'])) {
					$minifiedScripts['body'] .= '<script src="' . $minPath . '?b=' . $groupFolder . '&amp;f=' . implode(',', $registeredScripts['body_jsmingroup']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body_jsmin'])) {
					$minifiedScripts['body'] .= '<script src="' . $minPath . '?f=' . implode(',', $registeredScripts['body_jsmin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body_nomin'])) {
					$minifiedScripts['body'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['body_nomin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body'])) {
					$minifiedScripts['body'] .= "\r\n" . implode("\r\n", $registeredScripts['body']);
				}

			// insert minified scripts
			if (isset($minifiedScripts['head'])) {
				$output = str_replace('</head>', $minifiedScripts['head'] . '</head>', $output);
			}
			if (isset($minifiedScripts['body'])) {
				$output = str_replace('</body>', $minifiedScripts['body'] . '</body>', $output);
			}
			break;
		}
}
?>
