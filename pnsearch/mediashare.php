<?php
// $Id: mediashare.php,v 1.2 2006/03/16 20:14:48 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

require_once("modules/mediashare/common.php");

$currentlang = pnUserGetLang();
include("modules/mediashare/pnlang/$currentlang/userapi.php");

  // Inform PostNuke search framework of the functions this module uses
$search_modules[] = array( 'title'       => 'Mediashare files',
                           'func_search' => 'search_mediashare',
                           'func_opt'    => 'search_mediashare_opt'
                         );

  // "Print" search form in advanced search
function search_mediashare_opt()
{
  global $bgcolor2;

  if (!pnSecAuthAction(0, 'mediashare::', '::', ACCESS_READ))
    return;

  return   "<table border=\"0\" width=\"100%\"><tr bgcolor=\"$bgcolor2\"><td>"
         . "<input type=\"checkbox\" name=\"active_mediashare\" id=\"active_mediashare\" value=\"1\" checked>&nbsp;"
         . "<label for=\"active_mediashare\">" . _SEARCH_MEDIASHARE . "</label></td></tr></table>";
}


  // Do the searching (or rather - let the API do it)
function search_mediashare()
{
  list($query,
       $match,
       $startnum,
       $active_mediashare) = pnVarCleanFromInput('q',
                                                 'bool',
                                                 'mediashare_itemindex',
                                                 'active_mediashare');

  $pageSize = 10;

  if (empty($active_mediashare))
    return;

  if (!isset($startnum) || !is_numeric($startnum))
    $startnum = 0;

  $render = new pnRender('mediashare');
  $render->assign('pageSize', $pageSize);

  if (!empty($query))
  {
    if (!pnModAPILoad('mediashare', 'user'))
      return mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API');

    $result = pnModAPIFunc('mediashare', 'user', 'search',
                           array('query'      => $query,
                                 'match'      => $match,
                                 'pageSize'   => $pageSize,
                                 'itemIndex'  => $startnum));
    if ($result === false)
      return mediashareErrorAPIGet();

    $render->assign('result', $result['result']);
    $render->assign('hitCount', $result['hitCount']);
  }
  else
  {
    $render->assign('result', array());
    $render->assign('hitCount', 0);
  }

  return $render->fetch('mediashare_search_result.html');
}




?>