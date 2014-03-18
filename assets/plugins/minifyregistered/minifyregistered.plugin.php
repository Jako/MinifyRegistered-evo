<?php
/**
 * MinifyRegistered
 *
 * @category 	plugin
 * @version 	0.2.5
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    Description:
 *              <strong>0.2.5</strong> collect the registered javascripts and css files and minify them by minify (https://github.com/mrclay/minify)
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
$groupJs = (isset($groupJs) && $groupJs == 'no') ? false : true;
$groupFolder = (isset($groupFolder)) ? $groupFolder : 'assets/js';
$minPath = (isset($minPath)) ? $minPath : '/min/';
$excludeJs = (isset($excludeJs)) ? $excludeJs : '';

$excludeJs = ($excludeJs != '') ? explode(',', $excludeJs) : array();

$e = &$modx->Event;
switch ($e->name) {
	case 'OnLoadWebDocument': {
			// get output
			$output = &$modx->documentContent;
			// replace chunks
			foreach ($modx->chunkCache as $key => $value) {
				$output = str_replace('{{' . $key . '}}', $value, $output);
			}
			// generate marker at the end of the head and body
			$output = str_replace('</head>', '##MinifyRegisteredHead##' . "\n" . '</head>', $output);
			$output = str_replace('</body>', '##MinifyRegisteredBody##' . "\n" . '</body>', $output);

			break;
		}
	case 'OnWebPagePrerender' : {
			$registeredScripts = array();

			// get output and registered scripts
			$output = &$modx->documentOutput;
			$startupScripts = explode("\n", $modx->getRegisteredClientStartupScripts());
			$scripts = explode("\n", $modx->getRegisteredClientScripts());

			$conditional = FALSE;
			// startup scripts
			foreach ($startupScripts as $scriptSrc) {
				$tag = array();
				if (preg_match('/<!--\[if /', trim($scriptSrc), $tag) || $conditional) {
					// don't touch conditional css/scripts
					$registeredScripts['head'][] = $scriptSrc;
					$conditional = TRUE;
					if ($conditional && preg_match('/endif\]-->/', trim($scriptSrc), $tag)) {
						$conditional = FALSE;
					}
				} else {
					preg_match('/^<(script|link)[^>]+>/', trim($scriptSrc), $tag);
					$src = array();
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
						} elseif (substr(trim($src[2]), -4) == '.css' || (substr($src[2], 0, 4) !== 'http' && substr($src[2], 0, 2) !== '//')) {
							if (!in_array($src[2], $excludeJs)) {
								// minify internal css files
								$registeredScripts['head_cssmin'][] = $src[2];
							} else {
								// do not internal css files in excludeJs
								$registeredScripts['head_cssnomin'][] = $src[2];
							}
						} elseif (strpos($tag[0], 'rel="stylesheet"') !== FALSE) {
							// do not minify all other css files (i.e. Google font files)
							$registeredScripts['head_cssnomin'][] = $src[2];
						} else {
							// do not minify any other file
							$registeredScripts['head_nomin'][] = $src[2];
						}
					} else {
						// if there is no filename referenced in the registered line leave it alone
						$registeredScripts['head'][] = $scriptSrc;
					}
				}
			}

			$conditional = FALSE;
			// body scripts
			foreach ($scripts as $scriptSrc) {
				if (preg_match('/<!--\[if /', trim($scriptSrc), $tag) || $conditional) {
					// don't touch conditional css/scripts
					$registeredScripts['body'][] = $scriptSrc;
					$conditional = TRUE;
					if ($conditional && preg_match('/endif\]-->/', trim($scriptSrc), $tag)) {
						$conditional = FALSE;
					}
				} else {
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
							if (!in_array($src[2], $excludeJs)) {
								// minify internal css files
								$registeredScripts['body_cssmin'][] = $src[2];
							} else {
								// do not internal css files in excludeJs
								$registeredScripts['body_cssnomin'][] = $src[2];
							}
						} else {
							// do not minify any other file
							$registeredScripts['body_nomin'][] = $src[2];
						}
					} else {
						// if there is no filename referenced in the registered line leave it alone
						$registeredScripts['body'][] = $scriptSrc;
					}
				}
			}

			// prepare the output of the registered blocks
			if (count($registeredScripts['head_cssmin'])) {
				$minifiedScripts['head'] .= '<link href="' . $minPath . '?f=' . implode(',', $registeredScripts['head_cssmin']) . '" rel="stylesheet" type="text/css" />' . "\r\n";
			}
			if (count($registeredScripts['head_cssnomin'])) {
				$minifiedScripts['head'] .= '<link href="' . implode('" rel="stylesheet" type="text/css" />' . "\r\n" . '<link href="', $registeredScripts['head_cssnomin']) . '" rel="stylesheet" type="text/css" />' . "\r\n";
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
				$output = preg_replace('!(##MinifyRegisteredHead##.*)</head>!s', $minifiedScripts['head'] . '</head>', $output);
			}
			if (isset($minifiedScripts['body'])) {
				$output = preg_replace('!(##MinifyRegisteredBody##.*)</body>!s', $minifiedScripts['body'] . '</body>', $output);
			}
			break;
		}
}
?>
