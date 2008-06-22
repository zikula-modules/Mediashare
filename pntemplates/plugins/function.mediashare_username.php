<?php

function smarty_function_mediashare_username($params, &$smarty)
{
  if (!isset($params['userId'])) 
    return $smarty->trigger_error('mediashare_username: userId parameter required');

  if (mediashareAccessUserRealName())
    $name = pnUserGetVar('name', $params['userId']);
  else
    $name = null;

  if (!$name)
    $name = pnUserGetVar('uname', $params['userId']);
  $name = pnVarPrepForDisplay($name);

  if (isset($params['assign']))
    $smarty->assign($params['assign'], $name);
  else
    return $name;
}

?>