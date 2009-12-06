<?php
// $Id: mediashare.php,v 1.2 2006/03/16 20:14:48 jornlind Exp $
// =======================================================================
// Mediashare by Jorn Wildt (C) 2005.
// =======================================================================

require_once("modules/mediashare/common.php");

// Inform Zikula search framework of the functions this module uses
$search_modules[] = array('title'       => 'Mediashare files',
                          'func_search' => 'search_mediashare',
                          'func_opt'    => 'search_mediashare_opt');

  // "Print" search form in advanced search
function search_mediashare_opt()
{
    if (!SecurityUtil::checkPermission('mediashare::', '::', ACCESS_READ)) {
        return;
    }

    $dom = ZLanguage::getModuleDomain('mediashare');

    return "<table border=\"0\" width=\"100%\"><tr><td>"
         . "<input type=\"checkbox\" name=\"active_mediashare\" id=\"active_mediashare\" value=\"1\" checked>&nbsp;"
         . "<label for=\"active_mediashare\">" . __('Search Mediashare', $dom) . "</label></td></tr></table>";
}


  // Do the searching (or rather - let the API do it)
function search_mediashare()
{
    $query = FormUtil::getPassedValue('q');
    $match = FormUtil::getPassedValue('bool');
    $startnum = FormUtil::getPassedValue('mediashare_itemindex');
    $active_mediashare = FormUtil::getPassedValue('active_mediashare');
    $pageSize = 10;

    if (empty($active_mediashare)) {
      return;
    }

    if (!isset($startnum) || !is_numeric($startnum)) {
        $startnum = 0;
    }

    $render = & pnRender::getInstance('mediashare');
    $render->assign('pageSize', $pageSize);

    if (!empty($query)) {
        $result = pnModAPIFunc('mediashare', 'user', 'search',
                               array('query'      => $query,
                                     'match'      => $match,
                                     'pageSize'   => $pageSize,
                                     'itemIndex'  => $startnum));

        if ($result === false) {
            return false;
        }

        $render->assign('result', $result['result']);
        $render->assign('hitCount', $result['hitCount']);
    } else {
        $render->assign('result', array());
        $render->assign('hitCount', 0);
    }

    return $render->fetch('mediashare_search_result.html');
}
