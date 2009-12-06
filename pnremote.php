<?php
// $Id$
// =======================================================================
// Mediashare by Jorn Lind-Nielsen (C) 2005.
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================


require_once ("modules/mediashare/common.php");

/* DEBUG
  $f = fopen('c:/tmp/gallery.txt', 'a');
  ob_start();
  echo "GET: "; var_dump($_GET);
  echo "POST: "; var_dump($_POST);
  fwrite($f, ob_get_contents() . "\n");
  ob_end_clean();
  fclose($f);
//*/

// http://www.pn760.dk/user.php?uname=$USERNAME$&pass=$PASSWORD$&module=NS-User&op=login


function mediashare_remote_main()
{
    $cmd = str_replace('-', '', $_POST['cmd']);

    ob_start();
    pnModFunc('mediashare', 'remote', $cmd);
    $txt = "\n" . ob_get_clean();

    /* DEBUG
  $f = fopen('c:/tmp/gallery.txt', 'a');
  fwrite($f, $txt);
  fclose($f);
//*/

    echo $txt;

    return true;
}

function mediashare_remote_login()
{
    if (pnUserLogIn($_POST['uname'], $_POST['password'])) {
        echo "__#GR2PROTO__
status=0
status_text=ok
server_version=2.15";
    } else {
        echo "__#GR2PROTO__
status=202
status_text=no access
server_version=2.15";
    }

    return true;
}

function mediashare_remote_fetchalbums()
{
    return mediashare_remote_fetchalbumsprune();
}

function mediashare_remote_fetchalbumsprune()
{
    $albums = pnModAPIFunc('mediashare', 'user', 'getAllAlbums', array('access' => mediashareAccessRequirementEditAccess, 'albumId' => 0));
    if ($albums === false)
        return mediashareErrorAPIRemote();

    $thumbnailSize = (int) pnModGetVar('mediashare', 'thumbnailSize');
    $previewSize = (int) pnModGetVar('mediashare', 'previewSize');

    echo "__#GR2PROTO__
status=0
status_text=ok";

    for ($i = 1, $cou = count($albums); $i <= $cou; ++$i) {
        $album = &$albums[$i - 1];

        echo "
album.name.$i=$album[id]
album.title.$i=" . mediashareRemoteEscape($album[title]) . "
album.summary.$i=" . mediashareRemoteEscape($album[summary] . ' ' . $album[description]) . "
album.parent.$i=$album[parentAlbumId]
album.resize_size.$i=$previewSize
album.thumb_size.$i=$thumbnailSize
album.max_size.$i=999999
album.perms.add.$i=true
album.perms.write.$i=true
album.perms.del_item.$i=true
album.perms.del_alb.$i=true
album.perms.create_sub.$i=true";
    }

    echo "
album_count=" . count($albums) . "
can_create_root=no";

    return true;
}

function mediashare_remote_albumproperties()
{
    $albumId = $_POST['set_albumName'];

    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementViewSomething)) {
        return LogUtil::registerPermissionError();
    }

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false) {
        return mediashareErrorAPIRemote();
    }

    $size = (int) pnModGetVar('mediashare', 'previewSize');

    echo "__#GR2PROTO__
status=0
status_text=ok
auto_resize=$size
max_size=999999
add_to_beginning=no";

    return true;
}

function mediashare_remote_fetchalbumimages()
{
    $albumId = $_POST['set_albumName'];

    $images = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('access' => mediashareAccessRequirementView, 'albumId' => $albumId));
    if ($images === false)
        return mediashareErrorAPIRemote();

    $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId));
    if ($album === false)
        return mediashareErrorAPIRemote();

    $baseurl = pnGetBaseURL() . pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir');

    echo "__#GR2PROTO__
status=0
status_text=ok
album.caption=$album[title]";

    for ($i = 1, $cou = count($images); $i <= $cou; ++$i) {
        $image = &$images[$i - 1];

        echo "
image.name.$i=$image[originalRef]
image.raw_width.$i=$image[originalWidth]
image.raw_height.$i=$image[originalHeight]
image.raw_filesize.$i=$image[originalBytes]
image.resizedName.$i=$image[previewRef]
image.resized_width.$i=$image[previewWidth]
image.resized_height.$i=$image[previewHeight]
image.thumbName.$i=$image[thumbnailRef]
image.thumb_width.$i=$image[thumbnailWidth]
image.thumb_height.$i=$image[thumbnailHeight]
image.caption.$i=" . mediashareRemoteEscape($image[title]) . "
image.title.$i=" . mediashareRemoteEscape($image[title]) . "
image.clicks.$i=0
image.hidden.$i=no";
    }

    echo "
image_count=" . count($images) . "
baseurl=$baseurl
";

    return true;
}

function mediashare_remote_additem()
{
    $title = $_POST['caption'];
    $albumId = $_POST['set_albumName'];
    $uploadInfo = $_FILES['userfile'];

    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem', array(
        'albumId' => $albumId,
        'uploadFilename' => $uploadInfo['tmp_name'],
        'fileSize' => $uploadInfo['size'],
        'filename' => $uploadInfo['name'],
        'mimeType' => $uploadInfo['type'],
        'title' => $title,
        'keywords' => null,
        'description' => null,
        'width' => $width,
        'height' => $height));

    if ($result === false) {
        return mediashareErrorAPIRemote();
    }

    echo "__#GR2PROTO__
status=0
status_text=ok
item_name=$result[mediaId]";

    return true;
}

function mediashare_remote_newalbum()
{
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddAlbum, '')) {
        return LogUtil::registerPermissionError();
    }

    $newAlbumID = pnModAPIFunc('mediashare', 'edit', 'addAlbum', array('title' => $_POST['newAlbumTitle'], 'keywords' => '', 'summary' => '', 'description' => $_POST['newAlbumDesc'], 'template' => null, 'parentAlbumId' => $_POST['set_albumName']));
    if ($newAlbumID === false) {
        return mediashareErrorAPIRemote();
    }

    echo "__#GR2PROTO__
status=0
status_text=ok
album_name=$newAlbumID";

    return true;
}

function mediashare_remote_movealbum()
{
    $albumId    = $_POST['set_albumName'];
    $dstAlbumId = $_POST['set_destalbumName'];

    // Access control built into API funcion
    $ok = pnModAPIFunc('mediashare', 'edit', 'moveAlbum', array('albumId' => $albumId, 'dstAlbumId' => $dstAlbumId));
    if ($ok === false) {
        return mediashareErrorAPIRemote();
    }

    echo "__#GR2PROTO__
status=0
status_text=ok";

    return true;
}

function mediashare_remote_incrementviewcount()
{
    // Ignore
    echo "__#GR2PROTO__
status=0
status_text=ok";

    return true;
}

function mediashare_remote_noop()
{
    echo "__#GR2PROTO__
status=0
status_text=ok";

    return true;
}

function mediashareRemoteEscape($txt)
{
    $txt = str_replace("\r", '', $txt);
    $txt = str_replace("\n", ' ', $txt);
    return $txt;
}

function mediashareErrorPageRemote($file, $line, $msg)
{
    echo "__#GR2PROTO__
status=1
status_text=$file($line): $msg";

    return true;
}

function mediashareErrorAPIRemote()
{
    $msg = LogUtil::getErrorMessagesText();

    echo "__#GR2PROTO__
status=1
status_text=$msg";

    return true;
}
