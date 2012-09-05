<?php
/**
 * minifyRegistered
 *
 * @category 	plugin
 * @version 	0.1.3
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    @description: <strong>0.1.2</strong> collect the registered
 *              javascripts and css files and minify them by minify
 *              (http://code.google.com/p/minify/)
 * @internal    @plugin code:
 *              include(MODX_BASE_PATH.'assets/plugins/minifyregistered/minifyRegistered.plugin.php');
 * @internal	@events: OnLoadWebDocument, OnWebPagePrerender
 * @internal	@configuration: &groupJs=Group minified files in 'assets/js':;list;yes,no;yes;&excludeJs=Do not minify following files (comma separated):;text;
 *
 * @internal    The Plugin needs a working installation of
 *              http://code.google.com/p/minify/ in the folder /min in the
 *              webroot.
 */

// Parameter
$groupJs = (isset($groupJs)) ? (bool) $groupJs : true;
$excludeJs = (isset($excludeJs)) ? explode(',', $excludeJs) : array();

if (MODX_BASE_PATH == '') {
	die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

$e = &$modx->Event;
switch ($e->name) {
	case 'OnLoadWebDocument' : {
			// Mask closing head and body tags - so MODX can't insert the registered blocks
			// during 'OnParseDocument'
			$modx->documentContent = preg_replace("/(<\/head>)/i", '##/HEAD##', $modx->documentContent);
			$modx->documentContent = preg_replace("/(<\/body>)/i", '##/BODY##', $modx->documentContent);
			break;
		}
	case 'OnWebPagePrerender' : {
			include (MODX_BASE_PATH . 'assets/plugins/minifyregistered/includes/chunkie.class.inc.php');
			if (!function_exists('getTVDisplayFormat')) {
				include (MODX_BASE_PATH . 'manager/includes/tmplvars.format.inc.php');
				include (MODX_BASE_PATH . 'manager/includes/tmplvars.commands.inc.php');
			}

			$registeredScripts = array();
			// Are there any scripts loaded by $modx->regClient... ?
			if (count($modx->loadedjscripts)) {
				// collect the registered blocks and assign them to the right document part
				foreach ($modx->loadedjscripts as $scriptSrc => $scriptParam) {
					$startup = ($scriptParam['startup']) ? 'head' : 'body';
					if (strpos($scriptSrc, '<') === FALSE) {
						// if there is no tag in the registered chunk
						if (substr(trim($scriptSrc), -3) == '.js') {
							// the registered chunk is a separate javascript
							if (substr($$scriptSrc, 0, 4) == 'http' || in_array($scriptSrc, $excludeJs)) {
								// do not minify scripts with an external url or scripts in excludeJs
								$registeredScripts[$startup . '_nomin'][$scriptParam['pos']] = $scriptSrc;
							} elseif ($groupJs && (trim(dirname(trim($scriptSrc)), '/') == 'assets/js')) {
								// group minify scripts in assets/js
								$registeredScripts[$startup . '_jsminjs'][$scriptParam['pos']] = str_replace('assets/js', '', $scriptSrc);
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
				$headScripts = $bodyScripts = '';
				if (count($registeredScripts['head_cssmin'])) {
					$headScripts .= '<link href="/min/?f=' . implode(',', $registeredScripts['head_cssmin']) . '" rel="stylesheet" type="text/css" />' . "\r\n";
				}
				if (count($registeredScripts['head_jsminjs'])) {
					$headScripts .= '<script src="/min/?b=assets/js&amp;f=' . implode(',', $registeredScripts['head_jsminjs']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head_jsmin'])) {
					$headScripts .= '<script src="/min/?f=' . implode(',', $registeredScripts['head_jsmin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head_nomin'])) {
					$headScripts .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['head_nomin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['head'])) {
					$headScripts .= implode("\r\n", $registeredScripts['head']) . "\r\n";
				}
				if (count($registeredScripts['body_jsminjs'])) {
					$bodyScripts .= '<script src="/min/?b=assets/js&amp;f=' . implode(',', $registeredScripts['body_jsminjs']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body_jsmin'])) {
					$bodyScripts .= '<script src="/min/?f=' . implode(',', $registeredScripts['body_jsmin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body_nomin'])) {
					$bodyScripts .= '<script src="' . implode('" type="text/javascript"></script>' . "\r\n" . '<script src="', $registeredScripts['body_nomin']) . '" type="text/javascript"></script>' . "\r\n";
				}
				if (count($registeredScripts['body'])) {
					$bodyScripts .= "\r\n" . implode("\r\n", $registeredScripts['body']);
				}
				// parse the output of the registered blocks
				$tmpDocumentObject = array();
				if (is_array($modx->placeholders)) {
					// add placeholder to the temporary documentObject
					$tmpDocumentObject = $modx->placeholders;
				}
				foreach ($modx->documentObject as $key => $value) {
					// check for template variables
					if (is_array($value)) {
						$tmpDocumentObject[$key] = getTVDisplayFormat($value[0], $value[1], $value[2], $value[3], $value[4], $modx->documentObject['id']);
					} else {
						$tmpDocumentObject[$key] = $value;
					}
				}
				$parser = new minifyChunkie('@CODE:' . $headScripts);
				foreach ($tmpDocumentObject as $key => $value) {
					$parser->AddVar($key, $value);
				}
				$headScripts = $parser->Render() . "\r\n";
				$parser = new minifyChunkie('@CODE:' . $bodyScripts);
				foreach ($tmpDocumentObject as $key => $value) {
					$parser->AddVar($key, $value);
				}
				$bodyScripts = $parser->Render() . "\r\n";
			}
			// insert the scripts at the end of the head and body
			$modx->documentOutput = str_replace('##/HEAD##', $headScripts . '</head>', $modx->documentOutput);
			$modx->documentOutput = str_replace('##/BODY##', $bodyScripts . '</body>', $modx->documentOutput);
		}
}
?>
