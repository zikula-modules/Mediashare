<?php

function smarty_function_mediashare_breadcrumb($params, &$smarty)
{
  if (!isset($params['albumId'])) 
    return $smarty->trigger_error('mediashare_breadcrumb: albumId parameter required');

  $albumId = (int)$params['albumId'];

  $mode = array_key_exists('mode',$params) ? $params['mode'] : 'view';

  $breadcrumb = pnModAPIFunc('mediashare', 'user', 'getAlbumBreadcrumb',
                              array('albumId' => $params['albumId']));
  if ($breadcrumb === false)
    return $smarty->trigger_error(mediashareErrorAPIGet());

  $urlType = $mode == 'edit' ? 'edit' : 'user';
  $url = pnModUrl('mediashare', $urlType, 'view', array('aid' => 0));
  $result = "<span class=\"mediashare-breadcrumb\">";
  $first = true;
  foreach ($breadcrumb as $album)
  {
    $url = pnVarPrepForDisplay(pnModUrl('mediashare', $urlType, 'view', array('aid' => $album['id'])));
    $result .= ($first ? '' : ' :: ')
             . "<a href=\"$url\">$album[title]</a>";
    $first = false;
  }

  $result .= "</span>";

  if (array_key_exists('assign', $params))
    $smarty->assign($params['assign'], $result);
  else
    return $result;
}

?>