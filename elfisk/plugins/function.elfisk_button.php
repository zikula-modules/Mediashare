<?php

function smarty_function_elfisk_button($params, &$smarty) 
{
  if (!isset($params['name'])) 
  {
    $smarty->trigger_error('button: name parameter required');
    return false;
  }

  $name = $params['name'];
  $text = $params['text'];

  $onclickHtml = '';
  if (array_key_exists('confirmMessage', $params))
  {
    $msg = eval("return $params[confirmMessage];") . '?';
    $onclickHtml = " onclick=\"return confirm('$msg')\"";
  }
  else if (array_key_exists('onclick', $params))
  {
    $onclickHtml = " onclick=\"return $params[onclick]\"";
  }

  $text = eval("return $text;");

  echo "<input type=\"submit\" name=\"$name\" value=\"$text\"$onclickHtml/>";
}

?>