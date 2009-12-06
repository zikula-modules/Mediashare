<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

function mediashare_import_main($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;

    return $render->fetch('mediashare_import_main.html');
}

// =======================================================================
// Import Photoshare
// =======================================================================


function mediashare_import_photoshare($args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return mediashareErrorAPI(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (isset($_POST['importButton'])) {
        return mediashareImportPhotoshare();
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'import', 'main'));
        return true;
    }

    $render = & pnRender::getInstance('mediashare');
    $render->caching = false;

    return $render->fetch('mediashare_import_photoshare.html');
}

function mediashareImportPhotoshare()
{
    if (!pnModAPILoad('mediashare', 'import')) {
        return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare import API');
    }

    $ok = pnModAPIFunc('mediashare', 'import', 'photoshare');
    if ($ok === false) {
        return mediashareErrorAPIGet();
    }

    pnRedirect(pnModURL('mediashare', 'import', 'main'));
    return true;
}

