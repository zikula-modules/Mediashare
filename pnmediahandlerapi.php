<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

function mediashare_mediahandlerapi_getMediaHandlers($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $pntable = &pnDBGetTables();

    // Get handlers
    if (!($result = DBUtil::selectFieldArray('mediashare_mediahandlers', 'handler', '', '', true, 'title'))) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.getMediaHandlers', 'Could not load the handlers.'), $dom));
    }

    $handlers = array();
    foreach ($result as $title => $handler)
    {
        $handlers[] = array('handler' => $handler,
                            'title'   => $title,
                            'mediaTypes' => array());
    }

    $handlersTable  = $pntable['mediashare_mediahandlers']; 
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    // Get media types per handler
    foreach (array_keys($handlers) as $k)
    {
        $handler = DataUtil::formatForStore($handlers[$k]['handler']);

        $sql = "SELECT $handlersColumn[mimeType],
                       $handlersColumn[fileType],
                       $handlersColumn[foundMimeType],
                       $handlersColumn[foundFileType]
                  FROM $handlersTable
                 WHERE $handlersColumn[handler] = '$handler'";

        $result = DBUtil::executeSQL($sql);

        if ($result === false) {
            return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.getMediaHandlers', "Could not load the types for the handler '$handler'."), $dom));
        }

        $colArray = array('mimeType', 'fileType', 'foundMimeType', 'foundFileType');

        $handlers[$k]['mediaTypes'] = DBUtil::marshallObjects($result, $colArray);
    }

    return $handlers;
}

function mediashare_mediahandlerapi_getHandlerInfo($args)
{
    $mimeType = strtolower($args['mimeType']);
    $filename = strtolower($args['filename']);

    if (!empty($filename)) {
        $dotPos = strpos($filename, '.');
        if ($dotPos === false) {
            $fileType = '';
        } else {
            $fileType = substr($filename, $dotPos + 1);
        }
    } else {
        $fileType = '';
    }

    $pntable = &pnDBGetTables();

    $handlersTable  = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    $sql = "SELECT DISTINCT $handlersColumn[handler],
                            $handlersColumn[foundMimeType],
                            $handlersColumn[foundFileType]
                       FROM $handlersTable
                      WHERE $handlersColumn[mimeType] = '" . DataUtil::formatForStore($mimeType) . "'
                         OR $handlersColumn[fileType] = '" . DataUtil::formatForStore($fileType) . "'";

    $result = DBUtil::executeSQL($sql);

    $errormsg = __f('Unable to locate media handler for \'%1$s\' (%2$s)', array($filename, $mimeType), $dom);

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.getHandlerInfo', $errormsg), $dom));
    }

    if (!$result) {
        return LogUtil::registerError($errormsg);
    }

    $colArray = array('handlerName', 'mimeType', 'fileType');
    $handler  = DBUtil::marshallObjects($result, $colArray);

    return $handler[0];
}

function mediashare_mediahandlerapi_loadHandler($args)
{
    if (!empty($args['mimeType'])) {
        if (!($handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo', array('mimeType' => $args['mimeType'])))) {
            return false;
        }
        $handlerName = $handlerInfo['handlerName'];
    } else {
        $handlerName = $args['handlerName'];
    }

    return pnModAPIFunc('mediashare', "media_{$handlerName}", 'buildHandler');
}

function mediashare_mediahandlerapi_scanMediaHandlers($args)
{
    // Check access
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');
    
    // Clear existing handler table
    if (!DBUtil::truncateTable('mediashare_mediahandlers')) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.scanMediaHandlers', __f("Could not clear the '%s' table.", 'mediahandlers', $dom)), $dom));
    }

    // Scan for handlers APIs
    $files = FileUtil::getFiles('modules/mediashare', false, true, 'php', 'f');
    foreach ($files as $file)
    {
        if (preg_match('/^pnmedia_([-a-zA-Z0-9_]+)api.php$/', $file, $matches)) {
            $handlerName = $matches[1];
            $handlerApi  = "media_$handlerName";

            // Force load - it is used during pninit
            pnModAPILoad('mediashare', $handlerApi, true);

            if (!($handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler'))) {
                return false;
            }

            $fileTypes = $handler->getMediaTypes();
            foreach ($fileTypes as $fileType)
            {
                $fileType['handler'] = $handlerName;
                $fileType['title']   = $handler->getTitle();

                if (!pnModAPIFunc('mediashare', 'mediahandler', 'addMediaHandler', $fileType)) {
                    return false;
                }
            }
        }
    }

    return true;
}

function mediashare_mediahandlerapi_addMediaHandler($args)
{
    $handler = array(
        'mimeType'      => strtolower($args['mimeType']),
        'fileType'      => strtolower($args['fileType']),
        'foundMimeType' => strtolower($args['foundMimeType']),
        'foundFileType' => strtolower($args['foundFileType']),
        'handler'       => $args['handler'],
        'title'         => $args['title']
    );

    $result = DBUtil::insertObject($handler, 'mediashare_mediahandlers', 'id');

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('mediahandlerapi.addHandler', 'Could not add a handler.'), $dom));
    }

    return true;
}
