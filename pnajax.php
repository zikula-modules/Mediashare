<?php
/**
 * Mediashare AJAX handler
 *
 * @copyright (C) 2007, Jorn Wildt
 * @link http://www.elfisk.dk
 * @version $Id$
 * @license See license.txt
 */

function mediashare_ajax_getitems($args)
{
    $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => FormUtil::getPassedValue('aid')));

    $mediaItems = array();

    foreach ($items as $item) {
        $mediaItems[] = array('id' => $item['id'], 'isExternal' => $item['mediaHandler'] == 'extapp', 'thumbnailRef' => $item['thumbnailRef'], 'previewRef' => $item['previewRef'], 'title' => $item['title']);
    }

    return array('mediaItems' => $mediaItems);
}

