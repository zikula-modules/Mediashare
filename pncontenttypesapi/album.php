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
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Mediashare album thumbnails', $dom);
    }

    function getDescription()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');
        return __('Insert a list of thumbnails from any Mediashare album.', $dom);
    }

    function loadData($data)
    {
        $this->albumId  = $data['albumId'];
        $this->template  = isset($data['template']) ? $data['template'] : null;
        $this->itemCount = isset($data['itemCount']) ? $data['itemCount'] : null;
    }

    function display()
    {
        $csssrc = ThemeUtil::getModuleStylesheet('mediashare');
        PageUtil::addVar('stylesheet', $csssrc);

        return pnModFunc('mediashare', 'user', 'simplethumbnails',
                         array('aid'      => $this->albumId,
                               'template' => $this->template,
                               'count'    => $this->itemCount));
    }

    function displayEditing()
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $album = pnModAPIFunc('mediashare', 'user', 'getAlbum', array('albumId' => $this->albumId));

        return __('Mediashare album: %s', DataUtil::formatForDisplay($album['title']), $dom);
    }

    function getDefaultData()
    {
        return array('albumId'   => 1,
                     'template'  => null,
                     'itemCount' => null);
    }

    function startEditing(&$render)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $templates   = array();
        $templates[] = array('text'  => __('Default', $dom),
                             'value' => null);
        $templates[] = array('text'  => __('Filmstrip', $dom),
                             'value' => 'filmstrip');

        $render->assign('templates', $templates);
        array_push($render->plugins_dir, 'modules/mediashare/pntemplates/pnform');
    }
}

function mediashare_contenttypesapi_album($args)
{
    return new mediashare_contenttypesapi_albumPlugin($args['data']);
}
