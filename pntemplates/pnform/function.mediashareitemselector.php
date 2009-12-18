<?php
/**
 * Mediashare plugin
 *
 * @copyright (C) 2007, Jorn Wildt
 * @link http://www.elfisk.dk
 * @version $Id$
 * @license See license.txt
 */

require_once 'modules/mediashare/common.php';

class mediashareItemSelector extends pnFormPlugin
{
    var $inputName;
    var $dataField;
    var $dataBased;
    var $group;
    var $selectedItemId;
    var $enableUpload;
    var $enableAddAlbum;
    var $mandatory;
    var $isValid = true;
    var $errorMessage;
    var $myLabel;

    function getFilename()
    {
        return __FILE__;
    }

    function create(&$render, $params)
    {
        $this->inputName      = $this->id;
        $this->dataBased      = isset($params['dataBased']) ? $params['dataBased'] : true;
        $this->dataField      = isset($params['dataField']) ? $params['dataField'] : $this->id;
        $this->mandatory      = isset($params['mandatory']) ? $params['mandatory'] : false;
        $this->enableUpload   = isset($params['enableUpload']) ? $params['enableUpload'] : true;
        $this->enableAddAlbum = isset($params['enableAddAlbum']) ? $params['enableAddAlbum'] : true;
    }

    function load(&$render, $params)
    {
        $this->loadValue($render, $render->get_template_vars());
    }

    function initialize(&$render)
    {
        $render->pnFormAddValidator($this);
    }

    function render(&$render)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        PageUtil::AddVar('javascript', 'javascript/ajax/prototype.js');
        PageUtil::AddVar('javascript', 'javascript/ajax/scriptaculous.js');
        PageUtil::AddVar('javascript', 'javascript/ajax/pnajax.js');
        PageUtil::AddVar('javascript', 'javascript/ajax/lightbox.js');
        PageUtil::AddVar('stylesheet', 'javascript/ajax/lightbox/lightbox.css');
        PageUtil::AddVar('javascript', 'modules/mediashare/pnjavascript/finditem.js');
        PageUtil::AddVar('stylesheet', ThemeUtil::getModuleStylesheet('mediashare'));

        $thumbnailSize = (int)pnModGetVar('mediashare', 'thumbnailSize') + 10;
        $html = "<div class=\"mediashareItemSelector\">\n<table><tr>\n";

        if (!($albums = pnModAPIFunc('mediashare', 'user', 'getAllAlbums', array('albumId' => 1)))) {
            return false;
        }

        if ($this->selectedItemId != null) {
            $mediaItem = pnModAPIFunc('mediashare', 'user', 'getMediaItem', array('mediaId' => $this->selectedItemId));
            if ($mediaItem === false) {
                return false;
            }
            $selectedMediaId = $mediaItem['id'];
            $selectedAlbumId = $mediaItem['parentAlbumId'];
        } else {
            $mediaItem = null;
            $selectedMediaId = null;
            $selectedAlbumId = (count($albums) > 0 ? $albums[0]['id'] : null);
        }

        $imgSrc = null;
        $itemOptionsHtml = '';

        if ($selectedAlbumId != null)
        {
            $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $selectedAlbumId));
            foreach ($items as $item)
            {
                if ($selectedMediaId == null) {
                    $selectedMediaId = $item['id'];
                }

                if ($selectedMediaId == $item['id']) {
                    $imgSrc = pnModAPIFunc('mediashare', 'user', 'getRelativeMediadir') . $item['thumbnailRef'];
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }

                $itemOptionsHtml .= "<option value=\"$item[id]\"$selected>" . DataUtil::formatForDisplay($item['title']) . "</option>\n";
            }
        }

        $html .= "<td class=\"img\" style=\"height: {$thumbnailSize}px; width: {$thumbnailSize}px;\">\n";

        if ($imgSrc != null) {
            $imgStyle = '';
        } else {
            $imgStyle = ' style="display: none"';
        }
        $html .= "<img id=\"{$this->id}_img\" src=\"$imgSrc\" alt=\"\"$imgStyle/><br/>\n";

        $html .= "</td>\n";
        $html .= "<td class=\"selector\">\n";

        $html .= "<label for=\"{$this->id}_album\">" . __('Album', $dom) . "</label><br/>";
        $html .= "<select id=\"{$this->id}_album\" name=\"{$this->inputName}_album\" onchange=\"mediashare.itemSelector.albumChanged(this,'{$this->id}')\">\n";

        foreach ($albums as $album)
        {
            $title = '';
            for ($i=1,$cou=(int)$album['nestedSetLevel']; $i<$cou; ++$i) {
                $title .= '+ ';
            }
            if ($selectedAlbumId == $album['id']) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $html .= "<option value=\"$album[id]\"$selected>" . $title . DataUtil::formatForDisplay($album['title']) . "</option>\n";
        }
        $html .= "</select><br/>\n";

        $html .= "<label for=\"{$this->id}_item\">" . __('Media item', $dom) . "</label><br/> ";
        $html .= "<select id=\"{$this->id}_item\" name=\"$this->inputName\" onchange=\"mediashare.itemSelector.itemChanged(this,'{$this->id}')\">\n";

        if ($selectedAlbumId != null) {
            $items = pnModAPIFunc('mediashare', 'user', 'getMediaItems', array('albumId' => $selectedAlbumId));
            $html .= $itemOptionsHtml;
        }
        $html .= "</select>";

        $html .= "</td></tr>\n";

        if ($this->enableUpload) {
            $html .= "<tr><td colspan=\"2\"><a href=\"javascript:void(0)\" id=\"mediashare_upload_collapse\">" . __('Upload', $dom). "</a><div id=\"mediashare_upload\">\n";

            $html .= __('Select an item or upload a file directly', $dom) . '<br/>';
            $html .= "<label for=\"{$this->id}_upload\">" . __('Upload', $dom) . "</label>\n";
            $html .= "<input type=\"file\" id=\"{$this->id}_upload\" name=\"{$this->inputName}_upload\" class=\"file\"/>\n";

            if ($this->enableAddAlbum)
            {
                $html .= '<br/>' . __('Optional name of a new album to place the upload in', $dom) . '<br/>';
                $html .= "<label for=\"{$this->id}_newalbum\">" . __('Album', $dom) . "</label>\n";
                $html .= "<input type=\"text\" id=\"{$this->id}_newalbum\" name=\"{$this->inputName}_newalbum\"/>\n";
            }

            $html .= "</div></td></tr>\n";
        }

        $html .= "</table>\n";
        $html .= "<script type=\"text/javascript\">Event.observe(window,'load',function(){mediashare.itemSelector.onLoad('{$this->id}');});\n</script>\n";
        $html .= "</div>\n";

        return $html;
    }

    function decode(&$render)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        $this->clearValidation($render);

        $value    = FormUtil::getPassedValue($this->inputName, null, 'POST');
        $albumId  = FormUtil::getPassedValue("{$this->inputName}_album", null, 'POST');
        $newAlbum = FormUtil::getPassedValue("{$this->inputName}_newalbum", null, 'POST');

        if (!empty($newAlbum)) {
            if (mediashareAccessAlbum($albumId, mediashareAccessRequirementAddAlbum, '')) {
                $newAlbumID = pnModAPIFunc('mediashare', 'edit', 'addAlbum',
                                           array('title'         => $newAlbum,
                                                 'keywords'      => '',
                                                 'summary'       => '',
                                                 'description'   => '',
                                                 'template'      => null,
                                                 'parentAlbumId' => $albumId) );
                if ($newAlbumID === false) {
                    $this->setError(LogUtil::getErrorMessagesText());
                } else {
                    $albumId = $newAlbumID;
                }
            } else {
                $this->setError(__('You do not have access to this feature', $dom));
            }
        }

        $file = (isset($_FILES["{$this->inputName}_upload"]) ? $_FILES["{$this->inputName}_upload"] : null);

        if (!empty($file) && $file['error'] == 0) {
            if (mediashareAccessAlbum($albumId, mediashareAccessRequirementAddMedia, '')) {
                $result = pnModAPIFunc('mediashare', 'source_browser', 'addMediaItem',
                                       array('albumId'        => $albumId,
                                             'uploadFilename' => $file['tmp_name'],
                                             'fileSize'       => $file['size'],
                                             'filename'       => $file['name'],
                                             'mimeType'       => $file['type'],
                                             'title'          => null,
                                             'keywords'       => null,
                                             'description'    => null,
                                             'width'          => 0,
                                             'height'         => 0));
                if ($result === false) {
                    $this->setError(LogUtil::getErrorMessagesText());
                } else {
                    $value = $result['mediaId'];
                }
            } else {
                $this->setError(__('You do not have access to this feature', $dom));
            }
        }

        $this->selectedItemId = $value;
    }

    function validate(&$render)
    {
        $dom = ZLanguage::getModuleDomain('mediashare');

        if ($this->mandatory && empty($this->selectedItemId)) {
            $this->setError(__('A selection here is mandatory.', $dom));
        }
    }

    function setError($msg)
    {
        $this->isValid = false;
        $this->errorMessage = $msg;
    }

    function clearValidation(&$render)
    {
        $this->isValid = true;
        $this->errorMessage = null;
    }

    function saveValue(&$render, &$data)
    {
        if ($this->dataBased)
        {
            if ($this->group == null) {
                $data[$this->dataField] = $this->selectedItemId;
            } else {
                if (!isset($data[$this->group])) {
                    $data[$this->group] = array();
                }
                $data[$this->group][$this->dataField] = $this->selectedItemId;
            }
        }
    }

    function loadValue(&$render, &$values)
    {
        if ($this->dataBased)
        {
            $value = null;

            if ($this->group == null) {
                if ($this->dataField != null && isset($values[$this->dataField])) { 
                    $value = $values[$this->dataField];
                }
            } else {
                if (isset($values[$this->group])) {
                    $data = $values[$this->group];
                    if (isset($data[$this->dataField])) {
                        $value = $data[$this->dataField];
                    }
                }
            }

            $this->selectedItemId = $value;
        }
    }
}

function smarty_function_mediashareitemselector($params, &$render)
{
    return $render->pnFormRegisterPlugin('mediashareItemSelector', $params);
}
