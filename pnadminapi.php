<?php
// $Id$
//
// Mediashare by Jorn Lind-Nielsen (C)
//

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
        $links[] = array('url' => pnModURL('mediashare', 'user', 'view'),     'text' => __('Browse', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'plugins'), 'text' => __('Plugins', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'recalc'),  'text' => __('Regenerate', $dom));
        $links[] = array('url' => pnModURL('mediashare', 'admin', 'main'),    'text' => __('Settings', $dom));
    }

    return $links;
}

/**
 * Scan for all media
 */
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

/**
 * Set plugins
 */
function mediashare_adminapi_setTemplateGlobally($args)
{
    $new = array('template' => DataUtil::formatForStore($args['template']));

    if (!DBUtil::updateObject($new, 'mediashare_albums', '1=1', 'id')) {
        return LogUtil::registerError(__f('Error in %1$s: %2$s.', array('adminapi.setTemplateGlobally', 'Could not set the template.'), $dom));
    }

    return true;
}
