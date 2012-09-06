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
			$output = str_replace($clientStartupScripts . "\n", '', $output);
			$output = str_replace($clientScripts . "\n", '', $output);

			// Are there any scripts loaded by $modx->regClient... ?
			if (count($modx->loadedjscripts)) {
				// collect the registered blocks and assign them to the right document part
				foreach ($modx->loadedjscripts as $scriptSrc => $scriptParam) {
					$startup = ($scriptParam['startup']) ? 'head' : 'body';
					if (strpos($scriptSrc, '<') === FALSE) {
						// if there is no tag in the registered chunk (just a filename)
						if (substr(trim($scriptSrc), -3) == '.js') {
							// the registered chunk is a separate javascript
							if (substr($scriptSrc, 0, 4) == 'http' || substr($scriptSrc, 0, 2) == '//') {
								// do not minify scripts with an external url
								$registeredScripts[$startup . '_external'][$scriptParam['pos']] = $scriptSrc;
							} elseif (in_array($scriptSrc, $excludeJs)) {
								// do not minify scripts in excludeJs
								$registeredScripts[$startup . '_nomin'][$scriptParam['pos']] = $scriptSrc;
							} elseif ($groupJs && (trim(dirname(trim($scriptSrc)), '/') == $groupFolder)) {
								// group minify scripts in assets/js
								$registeredScripts[$startup . '_jsmingroup'][$scriptParam['pos']] = trim(str_replace($groupFolder, '', $scriptSrc), '/');
							} else {
								// minify scripts
								$registeredScripts[$startup . '_jsmin'][$scriptParam['pos']] = $scriptSrc;
							}
						} elseif (substr(trim($scriptSrc), -4) == '.css') {
							// minify css
							$registeredScripts['head_cssmin'][$scriptParam['pos']] = $scriptSrc;
						} else {
							// do not minify any other file
							$registeredScripts[$startup . '_nomin'][$scriptParam['pos']] = $scriptSrc;
						}
					} else {
						// if there is any tag in the registered chunk leave it alone
						$registeredScripts[$startup][$scriptParam['pos']] = $scriptSrc;
					}
				}

				// prepare the output of the registered blocks
				if (count($registeredScripts['head_cssmin'])) {
					$minifiedScripts['head'] .= '<link href="' . $minPath . '?f=' . implode(',', $registeredScripts['head_cssmin']) . '" rel="stylesheet" type="text/css" />' . "\r\n";
				}
				if (count($registeredScripts['head_external'])) {
					$minifiedScripts['head'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['body_nomin']) . '" type="text/javascript"></script>' . "\r\n";
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
					$minifiedScripts['body'] .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['body_nomin']) . '" type="text/javascript"></script>' . "\r\n";
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
