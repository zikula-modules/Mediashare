<?php
// $Id: common-edit.php 93 2009-12-07 22:43:20Z mateo $
//
// Mediashare by Jorn Lind-Nielsen (C) 2003.
//

class mediashare_contenttypesapi_mediaItemPlugin extends contentTypeBase
{
    var $mediaItemId;
    var $showAlbumLink = true;
    var $text;

    function getModule()
    {
        return 'mediashare';
    }

    function getName()
    {
        return 'mediaitem';
    }
   
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare item', $dom);
    }

    function getDescription()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Display a single Mediashare item with link to album.', $dom);
    }

    function loadData($data)
    {
        $this->mediaItemId = $data['mediaItemId'];
        $this->showAlbumLink = isset($data['showAlbumLink']) ? $data['showAlbumLink'] : true;
        $this->text = isset($data['text']) ? $data['text'] : '';
    }

    function display()
    {
        if (!empty($this->mediaItemId)) {
            return pnModFunc('mediashare', 'user', 'simpledisplay',
                             array('mid'            => $this->mediaItemId,
                                   'showAlbumLink'  => $this->showAlbumLink,
                                   'text'           => $this->text,
                                   'containerWidth' => $this->styleWidth));
        }
        return '';
    }

    function displayEditing()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        if (!empty($this->mediaItemId)) {
            return pnModFunc('mediashare', 'user', 'simpledisplay',
                             array('mid'            => $this->mediaItemId,
                                   'showAlbumLink'  => $this->showAlbumLink,
                                   'text'           => $this->text,
                                   'containerWidth' => $this->styleWidth));
        }

        return __('No media item selected', $dom);
    }

    function getDefaultData()
    {
        return array('mediaItemId' => null, 'showAlbumLink' => true, 'text' => '');
    }

    function startEditing(&$render)
    {
        array_push($render->plugins_dir, 'modules/mediashare/pntemplates/pnform');
    }
}

function mediashare_contenttypesapi_mediaitem($args)
{
    return new mediashare_contenttypesapi_mediaItemPlugin();
}
