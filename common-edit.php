<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2003.
// =======================================================================

require_once ("modules/mediashare/common.php");

function mediashareUploadErrorMsg($error)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    switch ($error)
    {
        case 1:
            return __('Upload error - image size exceeds server upload limit', $dom);
        case 2:
            return __('Upload error - image size exceeds form upload limit', $dom);
        case 3:
            return __('Upload error - uploaded file was only partially uploaded', $dom);
        case 4:
            return __('Upload error - no file was uploaded', $dom);
        default:
            return __('Unknown upload error', $dom);
    }
}
