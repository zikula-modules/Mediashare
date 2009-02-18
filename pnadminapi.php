<?php
// $Id: pnadminapi.php,v 1.4 2007/06/17 20:47:51 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once("modules/mediashare/common-edit.php");


/**
 * get available admin panel links
 *
 * @return array array of admin links
 */
function mediashare_adminapi_getlinks()
{
    $links = array();
    if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('mediashare', 'user',   'view'),    'text' => _MSBROWSE);
        $links[] = array('url' => pnModURL('mediashare', 'admin',  'main'),    'text' => _MSGENERAL);
        $links[] = array('url' => pnModURL('mediashare', 'admin',  'plugins'), 'text' => _MSPLUGINS);
        $links[] = array('url' => pnModURL('mediashare', 'import', 'main'),    'text' => _MSIMPORT);
        $links[] = array('url' => pnModURL('mediashare', 'admin',   'recalc'), 'text' => _MSREC_RECALCULATE);
    }
    return $links;
}

// =======================================================================
// Scan for all media
// =======================================================================

function mediashare_adminapi_scanAllPlugins($args)
{
  // Force load - it is used during pninit
  if (!pnModAPILoad('mediashare', 'mediahandler', true))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare mediahandler API');

  $ok = pnModAPIFunc('mediashare', 'mediahandler', 'scanMediaHandlers');
  if ($ok === false)
    return false;

  // Force load - it is used during pninit
  if (!pnModAPILoad('mediashare', 'sources', true))
    return mediashareErrorAPI(__FILE__, __LINE__, 'Failed to load Mediashare sources API');

  $ok = pnModAPIFunc('mediashare', 'sources', 'scanSources');
  if ($ok === false)
    return false;

  return true;
}


// =======================================================================
// Set plugins
// =======================================================================

function mediashare_adminapi_setTemplateGlobally($args)
{
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $albumsTable = $pntable['mediashare_albums'];
  $albumsColumn = &$pntable['mediashare_albums_column'];

  $template = pnVarPrepForStore($args['template']);

  $sql = "UPDATE $albumsTable 
          SET $albumsColumn[template] = '$template'";

  $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
    return mediashareErrorAPI(__FILE__, __LINE__, 'Set template failed: ' . $dbconn->errorMsg() . " while executing: $sql");

  return true;
}

?>