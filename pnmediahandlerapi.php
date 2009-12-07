<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

function mediashare_mediahandlerapi_getMediaHandlers($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Get handlers
    $result = DBUtil::selectFieldArray('mediashare_mediahandlers', 'handler', '', '', true, 'title');

    if ($result === false) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('mediahandlerapi.getMediaHandlers', 'Could not load the handlers.'), $dom));
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
    for ($i = 0, $count = count($handlers); $i < $count; ++$i)
    {
        $handler = &$handlers[$i];

        $sql = "SELECT $handlersColumn[mimeType],
                       $handlersColumn[fileType],
                       $handlersColumn[foundMimeType],
                       $handlersColumn[foundFileType]
                  FROM $handlersTable
                 WHERE $handlersColumn[handler] = '" . DataUtil::formatForStore($handler['handler']) . "'";

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0) {
            return LogUtil::registerError(__f('Error in %1$s: %2$%', array('mediahandlerapi.getMediaHandlers', "Could not load the types for the handler $handler[handler]."), $dom));
        }

        $types = array();
        for (; !$result->EOF; $result->MoveNext()) {
            $types[] = array('mimeType' => $result->fields[0],
                             'fileType' => $result->fields[1],
                             'foundMimeType' => $result->fields[2],
                             'foundFileType' => $result->fields[3]);
        }

        $result->Close();

        $handler['mediaTypes'] = $types;
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

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlersTable  = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    $sql = "SELECT DISTINCT $handlersColumn[handler],
                            $handlersColumn[foundMimeType],
                            $handlersColumn[foundFileType]
                       FROM $handlersTable
                      WHERE $handlersColumn[mimeType] = '" . DataUtil::formatForStore($mimeType) . "'
                         OR $handlersColumn[fileType] = '" . DataUtil::formatForStore($fileType) . "'";

    $result = $dbconn->execute($sql);

    $errormsg = __('Unable to locate media handler for \'%1$s\' (%2$s)', array($filename, $mimeType), $dom);
    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('mediahandlerapi.getHandlerInfo', $errormsg), $dom));
    }

    if ($result->EOF) {
        return LogUtil::registerError($errormsg);
    }

    $handler = array('handlerName' => $result->fields[0],
                     'mimeType' => $result->fields[1],
                     'fileType' => $result->fields[2]);

    $result->close();

    return $handler;
}

function mediashare_mediahandlerapi_loadHandler($args)
{
    if (!empty($args['mimeType'])) {
        $handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo', array('mimeType' => $args['mimeType']));
        if ($handlerInfo === false) {
            return false;
        }
        $handlerName = $handlerInfo['handlerName'];
    } else {
        $handlerName = $args['handlerName'];
    }

    $handler = pnModAPIFunc('mediashare', "media_{$handlerName}", 'buildHandler');

    return $handler;
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
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('mediahandlerapi.scanMediaHandlers', __f("Could not clear the '%s' table.", 'mediahandlers', $dom)), $dom));
    }

    // Scan for handlers APIs
    $files = FileUtil::getFiles('modules/mediashare', false, true, 'php', 'f');
    foreach ($files as $file)
    {
        if (preg_match('/^pnmedia_([-a-zA-Z0-9_]+)api.php$/', $file, $matches)) {
            $handlerName = $matches[1];
            $handlerApi = "media_$handlerName";

            // Force load - it is used during pninit
            pnModAPILoad('mediashare', $handlerApi, true);

            $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');

            if ($handler === false) {
                return false;
            }

            $fileTypes = $handler->getMediaTypes();
            foreach ($fileTypes as $fileType)
            {
                $fileType['handler'] = $handlerName;
                $fileType['title'] = $handler->getTitle();

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
    $title = $args['title'];
    $handler = $args['handler'];
    $mimeType = strtolower($args['mimeType']);
    $fileType = strtolower($args['fileType']);
    $foundMimeType = strtolower($args['foundMimeType']);
    $foundFileType = strtolower($args['foundFileType']);

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlersTable  = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    $sql = "INSERT INTO $handlersTable (
            $handlersColumn[mimeType],
            $handlersColumn[fileType],
            $handlersColumn[foundMimeType],
            $handlersColumn[foundFileType],
            $handlersColumn[handler],
            $handlersColumn[title])
          VALUES (
            '" . DataUtil::formatForStore($mimeType) . "',
            '" . DataUtil::formatForStore($fileType) . "',
            '" . DataUtil::formatForStore($foundMimeType) . "',
            '" . DataUtil::formatForStore($foundFileType) . "',
            '" . DataUtil::formatForStore($handler) . "',
            '" . DataUtil::formatForStore($title) . "')";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('mediahandlerapi.addHandler', 'Could not add a handler.'), $dom));
    }

    return true;
}
