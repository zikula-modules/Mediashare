<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once ("modules/mediashare/common-edit.php");

/**
 * get available admin panel links
 *
 * @return array array of admin links
 */
function mediashare_adminapi_getlinks()
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    $links = array();

    if (SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('mediashare', 'user', 'view'), 'text' => __('Browse', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'plugins'), 'text' => __('Plugins', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'import', 'main'), 'text' => __('Import', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'recalc'), 'text' => __('Regenerate', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'main'), 'text' => __('Settings', $dom));
    }
    return $links;
}

// =======================================================================
// Scan for all media
// =======================================================================
function mediashare_adminapi_scanAllPlugins($args)
{
    // Force load - it is used during pninit
    pnModAPILoad('mediashare', 'mediahandler', true);

    if (!pnModAPIFunc('mediashare', 'mediahandler', 'scanMediaHandlers')) {
        return false;
    }

    // Force load - it is used during pninit
    pnModAPILoad('mediashare', 'sources', true);

    return pnModAPIFunc('mediashare', 'sources', 'scanSources');
}

// =======================================================================
// Set plugins
// =======================================================================
function mediashare_adminapi_setTemplateGlobally($args)
{
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $albumsTable = $pntable['mediashare_albums'];
    $albumsColumn = &$pntable['mediashare_albums_column'];

    $template = DataUtil::formatForStore($args['template']);

    $sql = "UPDATE $albumsTable
          SET $albumsColumn[template] = '$template'";

    $dbconn->execute($sql);

    if ($dbconn->errorNo() != 0) {
        return LogUtil::registerError(__f('Error in %1$s: %2$%', array('adminapi.setTemplateGlobally', 'Could not set the template.'), $dom));
    }

    return true;
}
