<?php

class mediashare_contenttypesapi_albumPlugin extends contentTypeBase
{
    var $albumId;
    var $template;
    var $itemCount;

    function getModule()
    {
        return 'mediashare';
    }
    function getName()
    {
        return 'album';
    }
    function getTitle()
    {
        return __('Mediashare album thumbnails', $dom);
    }
    function getDescription()
    {
        return __('Insert a list of thumbnails from any Mediashare album.', $dom);
    }

    function loadData($data)
    {
        $this->albumId = $data['albumId'];
        $this->template = isset($data['template']) ? $data['template'] : null;
        $this->itemCount = isset($data['itemCount']) ? $data['itemCount'] : null;
    }

    function display()
    {
        $csssrc = ThemeUtil::getModuleStylesheet('mediashare');
        PageUtil::addVar('stylesheet', $csssrc);

        return pnModFunc('mediashare', 'user', 'simplethumbnails', array('aid' => $this->albumId, 'template' => $this->template, 'count' => $this->itemCount));
    }

    function displayEditing()
    {
        $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $this->albumId));
        return 'Mediashare album: ' . $album['title'];
    }

    function getDefaultData()
    {
        return array('albumId' => 1, 'template' => null, 'itemCount' => null);
    }

    function startEditing(&$render)
    {
        $render->assign('templates', array(array('text' => __('Default', $dom), 'value' => null), array('text' => __('Filmstrip', $dom), 'value' => 'filmstrip')));
        array_push($render->plugins_dir, 'modules/mediashare/pntemplates/pnform');
    }
}

function mediashare_contenttypesapi_album($args)
{
    return new mediashare_contenttypesapi_albumPlugin($args['data']);
}
