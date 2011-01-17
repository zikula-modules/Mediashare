<?php
// $Id: pnsource_zip.php 154 2009-12-18 00:42:55Z mateo $
//
// Mediashare by Jorn Lind-Nielsen (C)
//

require_once 'modules/mediashare/common-edit.php';
require_once 'modules/mediashare/pnincludes/elfisk_common.php';

function mediashare_source_youtube_view(& $args)
{
    $albumId = mediashareGetIntUrl('aid', $args, 0);

    if (isset($_POST['saveButton'])) {
        return mediashareSourceYoutubeSave($args);
    }

    if (isset($_POST['moreButton']) || isset($_POST['continueButton'])) {
        // After upload - update items and then continue to next page
        if (!mediashareSourceYoutubeUpdate()) {
            return false;
        }
    }

    if (isset($_POST['cancelButton']) || isset($_POST['continueButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'view', array('aid' => $albumId)));
    }

    if (isset($_POST['moreButton'])) {
        return pnRedirect(pnModURL('mediashare', 'edit', 'addmedia', array('aid' => $albumId, 'source' => 'youtube')));
    }

    // FIXME Required globals??
    pnModAPILoad('mediashare', 'edit');

    $uploadInfo = pnModAPIFunc('mediashare', 'source_zip', 'getUploadInfo');

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('imageNum', 1);
    $render->assign('uploadFields', array(1));
    $render->assign('post_max_size', $uploadInfo['post_max_size']);
    $render->assign('upload_max_filesize', $uploadInfo['upload_max_filesize']);

    return $render->fetch('mediashare_source_youtube_view.html');
}

function mediashareSourceYoutubeAdd($videoytcode, $args)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

		// Create tmp. file and copy image data into it
    $tmpdir = pnModGetVar('mediashare', 'tmpDirName');
    $tmpfilename = tempnam($tmpdir, 'YOUT');
    if (!($f = fopen($tmpfilename, 'wb'))) {
        @ unlink($tmpfilename);
        return false;
    }
    
		//text to save
		fwrite($f, $videoytcode);
    fclose($f);
		
		$ytvideoinfo = mediashareSourceYoutubeXmlInfo($videoytcode);

		$args['mimeType'] = 'video/youtubecode';

    $args['uploadFilename'] = $tmpfilename;
    $args['fileSize'] = 102;
    $args['filename'] = "youtubee.you";
    $args['keywords'] = null;
    $args['description'] = $ytvideoinfo['description'];
		$args['title'] = $ytvideoinfo['title'];
              

    // Create image 
    $result = pnModAPIFunc('mediashare', 'source_youtube', 'addMediaItem', $args);

    if ($result === false) {
        $status = array('ok' => false, 'message' => LogUtil::getErrorMessagesText());
    } else {
        $status = array('ok' => true, 'message' => $result['message'], 'mediaId' => $result['mediaId']);
    }
    

    return $status;
}



function mediashareSourceYoutubeSave(& $args)
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    $albumId = mediashareGetIntUrl('aid', $args, 0);

    // Check access
    if (!mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, '')) {
        return LogUtil::registerPermissionError();
    }

    // Get parent album information
    if (!($album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $albumId)))) {
        return false;
    }

    // Start fetching media items
    $imageNum = (int)FormUtil::getPassedValue('imagenum');
    $statusSet = array();

    $args['albumId'] = $albumId;

    for ($i = 1; $i <= $imageNum; ++$i)
    {
        $videoytcode = FormUtil::getPassedValue("videoytcode$i");
        $args['width'] = FormUtil::getPassedValue("width$i");
        $args['height'] = FormUtil::getPassedValue("height$i");

        $result = mediashareSourceYoutubeAdd($videoytcode, $args);

        if ($result === false) {
         $status = array('ok' => false, 'message' => LogUtil::getErrorMessagesText());
         } else {
						$status = array('ok' => true, 'message' => $result['message'], 'mediaId' => $result['mediaId']);
					}

						$statusSet = array_merge($statusSet, array($status));
					}
 

    // Quick count of uploaded images + getting IDs for further editing
    $editMediaIds = array();
    $acceptedImageNum = 0;
    foreach ($statusSet as $status) {
        if ($status['ok']) {
            ++$acceptedImageNum;
            $editMediaIds[] = $status['mediaId'];
        }
    }
    $album['imageCount'] += $acceptedImageNum; // Update for showing only

    if ($acceptedImageNum == 0) {
        $statusSet[] = array('ok' => false, 'message' => __('No media items', $dom));
    }

    if (($items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('mediaIdList' => $editMediaIds))) === false) {
        return false;
    }

    $render = & pnRender::getInstance('mediashare', false);

    $render->assign('statusSet', $statusSet);
    $render->assign('items', $items);

    return $render->fetch('mediashare_source_youtube_added.html');
}

// Second page in upload sequence - user has entered media titles and such like, and it needs to be updated
function mediashareSourceYoutubeUpdate()
{
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError();
    }

    $mediaIds = FormUtil::getPassedValue('mediaId');
    foreach ($mediaIds as $mediaId)
    {
        $mediaId = (int)$mediaId;

        $title       = FormUtil::getPassedValue("title-$mediaId");
        $keywords    = FormUtil::getPassedValue("keywords-$mediaId");
        $description = FormUtil::getPassedValue("description-$mediaId");

        // Check access
        if (!mediashareAccessItem($mediaId, mediashareAccessRequirementEditMedia, '')) {
            return LogUtil::registerPermissionError();
        }

        $args = array('mediaId'     => $mediaId,
                      'title'       => $title,
                      'keywords'    => $keywords,
                      'description' => $description);

        if (!pnModAPIFunc('mediashare', 'edit', 'updateItem', $args)) {
            return false;
        }
    }

    return true;
}


function mediashareSourceYoutubeXmlInfo($v) {

	$xml = 'http://gdata.youtube.com/feeds/api/videos/'.$v.'';
	$content = simplexml_load_file($xml);	
	
	$video['title'] = (string)$content->title;
	$video['description'] = (string)$content->content;

	$media = $content->children('http://search.yahoo.com/mrss/');
	$attr1 = $media->group->thumbnail[0]->attributes();
	$video['thumbnail']  = (string)$attr1['url']; // thumbnail url

	$yt   = $media->children('http://gdata.youtube.com/schemas/2007');
	$attr2 = $yt->duration->attributes();
	$length  = $attr2['seconds']; // in seconds
	$video_duration = round($length/60,2); // in minutes
	
	return $video;
}

