<?php
// $Id: pnmediahandlerapi.php,v 1.10 2007/06/17 20:47:51 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


function mediashare_mediahandlerapi_getMediaHandlers($args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlersTable = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    // Get handlers


    $sql = "SELECT DISTINCT
            $handlersColumn[handler],
            $handlersColumn[title]
          FROM $handlersTable";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
        return mediashareErrorAPI(__FILE__, __LINE__, '"getMediaHandlers" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    $handlers = array();
    for (; !$result->EOF; $result->MoveNext()) {
        $handler = array('handler' => $result->fields[0], 'title' => $result->fields[1], 'mediaTypes' => array());

        $handlers[] = $handler;
    }

    $result->Close();

    // Get media types per handler


    for ($i = 0, $count = count($handlers); $i < $count; ++$i) {
        $handler = &$handlers[$i];

        $sql = "SELECT
              $handlersColumn[mimeType],
              $handlersColumn[fileType],
              $handlersColumn[foundMimeType],
              $handlersColumn[foundFileType]
            FROM $handlersTable
            WHERE $handlersColumn[handler] = '" . pnVarPrepForStore($handler['handler']) . "'";

        $result = $dbconn->execute($sql);

        if ($dbconn->errorNo() != 0)
            return mediashareErrorAPI(__FILE__, __LINE__, '"getMediaHandlers" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

        $types = array();
        for (; !$result->EOF; $result->MoveNext()) {
            $type = array('mimeType' => $result->fields[0], 'fileType' => $result->fields[1], 'foundMimeType' => $result->fields[2], 'foundFileType' => $result->fields[3]);

            $types[] = $type;
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
        if ($dotPos === false)
            $fileType = '';
        else
            $fileType = substr($filename, $dotPos + 1);
    } else
        $fileType = '';

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlersTable = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    $sql = "SELECT DISTINCT $handlersColumn[handler],
                          $handlersColumn[foundMimeType],
                          $handlersColumn[foundFileType]
          FROM $handlersTable
          WHERE    $handlersColumn[mimeType] = '" . pnVarPrepForStore($mimeType) . "'
                OR $handlersColumn[fileType] = '" . pnVarPrepForStore($fileType) . "'";

    $result = $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
        return mediashareErrorAPI(__FILE__, __LINE__, '"getHandlerInfo" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    if ($result->EOF)
        return mediashareErrorAPI(__FILE__, __LINE__, "Unable to locate media handler for '$filename' ($mimeType)");

    $handler = array('handlerName' => $result->fields[0], 'mimeType' => $result->fields[1], 'fileType' => $result->fields[2]);

    $result->close();

    return $handler;
}

function mediashare_mediahandlerapi_loadHandler($args)
{
    if (!empty($args['mimeType'])) {
        $handlerInfo = pnModAPIFunc('mediashare', 'mediahandler', 'getHandlerInfo', array('mimeType' => $args['mimeType']));
        if ($handlerInfo === false)
            return false;

        $handlerName = $handlerInfo['handlerName'];
    } else
        $handlerName = $args['handlerName'];

    $handlerApi = "media_$handlerName";

    if (!pnModAPILoad('mediashare', $handlerApi))
        return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$handlerApi' handler in mediashare_mediahandlerapi_loadHandler");

    $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');

    return $handler;
}

function mediashare_mediahandlerapi_scanMediaHandlers($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    // Check access
    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN))
        return mediashareErrorAPI(__FILE__, __LINE__, __('You do not have access to this feature', $dom));

    // Clear existing handler table


    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlerTable = $pntable['mediashare_mediahandlers'];
    $sql = "TRUNCATE TABLE $handlerTable";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
        return mediashareErrorAPI(__FILE__, __LINE__, '"scanMediaHandlers" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    // Scan for handlers
    if ($dh = opendir("modules/mediashare")) {
        while (($filename = readdir($dh)) !== false) {
            if (preg_match('/^pnmedia_([-a-zA-Z0-9_]+)api.php$/', $filename, $matches)) {
                $handlerName = $matches[1];
                $handlerApi = "media_$handlerName";

                // Force load - it is used during pninit
                if (!pnModAPILoad('mediashare', $handlerApi, true))
                    return mediashareErrorAPI(__FILE__, __LINE__, "Missing '$handlerApi' handler in scanMediaHandlers");

                $handler = pnModAPIFunc('mediashare', $handlerApi, 'buildHandler');

                if ($handler === false) {
                    closedir($dh);
                    return false;
                }

                $fileTypes = $handler->getMediaTypes();
                foreach ($fileTypes as $fileType) {
                    $fileType['handler'] = $handlerName;
                    $fileType['title'] = $handler->getTitle();

                    $ok = pnModAPIFunc('mediashare', 'mediahandler', 'addMediaHandler', $fileType);
                    if ($ok === false) {
                        closedir($dh);
                        return false;
                    }
                }
            }
        }

        closedir($dh);
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

    $handlersTable = $pntable['mediashare_mediahandlers'];
    $handlersColumn = $pntable['mediashare_mediahandlers_column'];

    $sql = "INSERT INTO $handlersTable (
            $handlersColumn[mimeType],
            $handlersColumn[fileType],
            $handlersColumn[foundMimeType],
            $handlersColumn[foundFileType],
            $handlersColumn[handler],
            $handlersColumn[title])
          VALUES (
            '" . pnVarPrepForStore($mimeType) . "',
            '" . pnVarPrepForStore($fileType) . "',
            '" . pnVarPrepForStore($foundMimeType) . "',
            '" . pnVarPrepForStore($foundFileType) . "',
            '" . pnVarPrepForStore($handler) . "',
            '" . pnVarPrepForStore($title) . "')";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0)
        return mediashareErrorAPI(__FILE__, __LINE__, '"addHandler" failed: ' . $dbconn->errorMsg() . " while executing: $sql");

    return true;
}

