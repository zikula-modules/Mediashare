<?php
// $Id: pnimport.php,v 1.2 2006/03/12 11:57:48 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================


require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';
require_once 'modules/mediashare/elfisk/elfiskRender.class.php';

function mediashare_import_main($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN)) {
        return mediashareErrorPage(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    $render = new elfiskRender('mediashare');
    $render->caching = false;

    return $render->fetch('mediashare_import_main.html');
}

// =======================================================================
// Import Photoshare
// =======================================================================


function mediashare_import_photoshare($args)
{
    $dom = ZLanguage::getModuleDomain('Mediashare');
    if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_ADMIN)) {
        return mediashareErrorAPI(__FILE__, __LINE__, __('You do not have access to this feature', $dom));
    }

    if (isset($_POST['importButton'])) {
        return mediashareImportPhotoshare();
    }

    if (isset($_POST['cancelButton'])) {
        pnRedirect(pnModURL('mediashare', 'import', 'main'));
        return true;
    }

    $render = new elfiskRender('mediashare');
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

