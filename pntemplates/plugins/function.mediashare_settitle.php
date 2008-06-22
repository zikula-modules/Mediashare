<?php

/** 
 *
 * Type: Function
 * Author: Jorn Lind-Nielsen
 *
 * Allows the user to place something in the <title> tag of the site.
 * This function places the passed title in a global variable that a Xantia theme
 * is expected to read (asuming it uses the standard <!--[title]--> functions) 
 * - so it only works with a Xantia theme.
 *@param params['title'] title to put in header.
 *@return nothing
 */
function smarty_function_mediashare_settitle($params, &$smarty)
{
  if (!array_key_exists('title', $params))
  {
    $smarty->trigger_error( "smarty_function_mediashare_settitle: missing parameter 'title'" );
    return false;
  }

  $GLOBALS['info']['title'] = $params['title'];

  return "";
}

?>
