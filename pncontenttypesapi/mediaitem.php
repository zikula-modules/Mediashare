<?php

class mediashare_contenttypesapi_mediaItemPlugin extends contentTypeBase
{
  var $mediaItemId;
  var $showAlbumLink = true;

  function getModule() { return 'mediashare'; }
  function getName() { return 'mediaitem'; }
  function getTitle() { return _MEDIASHARE_CONTENTENTTYPE_MEDIAITEMTITLE; }
  function getDescription() { return _MEDIASHARE_CONTENTENTTYPE_MEDIAITEMDESCR; }


  function loadData($data)
  {
    $this->mediaItemId = $data['mediaItemId'];
    $this->showAlbumLink = isset($data['showAlbumLink']) ? $data['showAlbumLink'] : true;
  }

  
  function display()
  {
    if (!empty($this->mediaItemId))
      return pnModFunc('mediashare', 'user', 'simpledisplay', 
                       array('mid' => $this->mediaItemId,
                             'showAlbumLink' => $this->showAlbumLink,
                             'containerWidth' => $this->styleWidth));
    return '';
  }

  
  function displayEditing()
  {
    if (!empty($this->mediaItemId))
    {
      return pnModFunc('mediashare', 'user', 'simpledisplay', 
                       array('mid' => $this->mediaItemId,
                             'showAlbumLink' => $this->showAlbumLink,
                             'containerWidth' => $this->styleWidth));
    }
    return _MEDIASHARE_CONTENTENTTYPE_NOMEDIA;
  }

  
  function getDefaultData()
  { 
    return array('mediaItemId' => null,
                 'showAlbumLink' => true);
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
