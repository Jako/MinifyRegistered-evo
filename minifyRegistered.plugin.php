<?php 
/**
 * minifyRegistered
 *
 * @category 	plugin
 * @version 	0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author		Jako (thomas.jakobi@partout.info)
 *
 * @internal    @description: <strong>0.1</strong> collect the registered javascripts and css files and minify them by minify (http://code.google.com/p/minify/)
 * @internal    @plugin code: include(MODX_BASE_PATH.'assets/plugins/minifyregistered/minifyRegistered.plugin.php');
 * @internal	@events: OnLoadWebDocument, OnWebPagePrerender
 *
 * @internal    The Plugin needs a working installation of http://code.google.com/p/minify/ in the folder /min in the webroot.
 */

 
if (MODX_BASE_PATH == '') {
    die('<h1>ERROR:</h1><p>Please use the MODx Content Manager instead of accessing this file directly.</p>');
}

$e = &$modx->Event;
switch ($e->name) {
    case 'OnLoadWebDocument': {
            // Mask closing head and body tags - so MODX can't insert the registered blocks during 'OnParseDocument'
            $modx->documentContent = preg_replace("/(<\/head>)/i", '##/HEAD##', $modx->documentContent);
            $modx->documentContent = preg_replace("/(<\/body>)/i", '##/BODY##', $modx->documentContent);
            break;
        }
    case 'OnWebPagePrerender': {
            include (MODX_BASE_PATH.'assets/plugins/minifyregistered/includes/chunkie.class.inc.php');
            if (!function_exists('getTVDisplayFormat')) {
                include (MODX_BASE_PATH.'manager/includes/tmplvars.format.inc.php');
            }
            
            // collect the registered blocks and assign them to the right document part
            $registeredScripts = array();
            foreach ($modx->loadedjscripts as $scriptSrc=>$scriptParam) {
                $startup = ($scriptParam['startup']) ? 'head' : 'body';
                if (stripos($scriptSrc, '<') === FALSE) {
                    if (substr(trim($scriptSrc), -3) == '.js') {
                        if (trim(dirname(trim($scriptSrc)), '/') == 'assets/js') {
                            $registeredScripts[$startup.'_jsminjs'][$scriptParam['pos']] = str_replace('assets/js', '', $scriptSrc);
                        } else {
                            $registeredScripts[$startup.'_jsmin'][$scriptParam['pos']] = $scriptSrc;
                        }
                    } elseif (substr(trim($scriptSrc), -4) == '.css') {
                        $registeredScripts['head_cssmin'][$scriptParam['pos']] = $scriptSrc;
                    } else {
                        $registeredScripts[$startup][$scriptParam['pos']] = $scriptSrc;
                    }
                } else {
                    $registeredScripts[$startup][$scriptParam['pos']] = $scriptSrc;
                }
            }
            
            // prepare the output of the registered blocks
            $headScripts = $bodyScripts = '';
            if (count($registeredScripts['head_jsminjs'])) {
                $headScripts .= '<script src="/min/?b=assets/js&amp;f='.implode(',', $registeredScripts['head_jsminjs']).'" type="text/javascript"></script>'."\r\n";
            }
            if (count($registeredScripts['head_jsmin'])) {
                $headScripts .= '<script src="/min/?f='.implode(',', $registeredScripts['head_jsmin']).'" type="text/javascript"></script>'."\r\n";
            }
            if (count($registeredScripts['head_cssmin'])) {
                $headScripts .= '<link href="/min/?f='.implode(',', $registeredScripts['head_cssmin']).'" rel="stylesheet" type="text/css" />'."\r\n";
            }
            if (count($registeredScripts['head'])) {
                $headScripts .= "\r\n".implode("\r\n", $registeredScripts['head']);
            }
            if (count($registeredScripts['body_jsmin'])) {
                $bodyScripts .= '<script src="/min/?f='.implode(',', $registeredScripts['body_jsmin']).'" type="text/javascript"></script>'."\r\n";
            }
            if (count($registeredScripts['body'])) {
                $bodyScripts .= "\r\n".implode("\r\n", $registeredScripts['body']);
            }
            
            // parse the output of the registered blocks
            $tmpDocumentObject = array();
            foreach ($modx->placeholders as $key=>$value) {
                // add placeholder to the temporary documentObject
                if ($key != 'phx') {
                    $tmpDocumentObject[$key] = $value;
                }
            }
            foreach ($modx->documentObject as $key=>$value) {
                // check for template variables
                if (is_array($value)) {
                    $tmpDocumentObject[$key] = getTVDisplayFormat($value[0], $value[1], $value[2], $value[3], $value[4], $modx->documentObject['id']);
                } else {
                    $tmpDocumentObject[$key] = $value;
                }
            }
            
            $parser = new minifyChunkie('@CODE:'.$headScripts);
            foreach ($tmpDocumentObject as $key=>$value) {
                $parser->AddVar($key, $value);
            }
            $headScripts = $parser->Render();
            
            $parser = new minifyChunkie('@CODE:'.$bodyScripts);
            foreach ($tmpDocumentObject as $key=>$value) {
                $parser->AddVar($key, $value);
            }
            $bodyScripts = $parser->Render();
            
            $modx->documentOutput = str_replace('##/HEAD##', $headScripts."\r\n".'</head>', $modx->documentOutput);
            $modx->documentOutput = str_replace('##/BODY##', $bodyScripts."\r\n".'</body>', $modx->documentOutput);
        }
}
?>
