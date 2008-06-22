<?php

function smarty_function_mediashare_mediaUrl($params, &$smarty)
{
  pnModLoad('mediashare', 'user');

  $result = pnModAPIFunc('mediashare', 'user', 'getMediaUrl', $params);

  if (array_key_exists('assign', $params))
    $smarty->assign($params['assign'], $result);
  else
    return $result;
}

?>