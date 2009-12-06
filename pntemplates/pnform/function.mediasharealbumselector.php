<?php
/**
 * Mediashare plugin
 *
 * @copyright (C) 2007, Jorn Wildt
 * @link http://www.elfisk.dk
 * @version $Id$
 * @license See license.txt
 */

require_once 'system/pnForm/plugins/function.pnformdropdownlist.php';

class mediashareAlbumSelector extends pnFormDropdownList
{
  var $onlyMine = 0; 
  var $access = 0xFF;
  
  function getFilename()
  {
    return __FILE__;
  }

  function load(&$render, &$params)
  {
    $this->update(false);
    parent::load($render, $params);
  }

  function update($force)
  {
    if ($force  ||  count($this->items) == 0)
    {
      $albums = pnModAPIFunc('mediashare', 'user', 'getAllAlbums',
                             array('albumId'        => 1, // Always get all from top
                                   //'excludeAlbumId' => $excludeAlbumId,
                                   'access'         => $this->access,
                                   'onlyMine'       => $this->onlyMine));
      if ($albums === false) {
        pn_exit(LogUtil::getErrorMessagesText());
      }

      foreach ($albums as $album) {
        $this->addItem($album['title'], $album['id']);
      }
    }
  }
}

function smarty_function_mediasharealbumselector($params, &$render)
{
  return $render->pnFormRegisterPlugin('mediashareAlbumSelector', $params);
}
