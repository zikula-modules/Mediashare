<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// =======================================================================

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/elfisk/elfisk_common.php';

function mediashare_import_main($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    $render = & pnRender::getInstance('mediashare', false);

    return $render->fetch('mediashare_import_main.html');
}

// =======================================================================
// Import Photoshare
// =======================================================================
function mediashare_import_photoshare($args)
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (isset($_POST['cancelButton'])) {
        return pnRedirect(pnModURL('mediashare', 'import', 'main'));
    }
    if (isset($_POST['importButton'])) {
        return mediashareImportPhotoshare();
    }

    $render = & pnRender::getInstance('mediashare', false);

    return $render->fetch('mediashare_import_photoshare.html');
}

function mediashareImportPhotoshare()
{
    $ok = pnModAPIFunc('mediashare', 'import', 'photoshare');
    if ($ok === false) {
        return false;
    }

    return pnRedirect(pnModURL('mediashare', 'import', 'main'));
}
