<?php

function smarty_function_elfisk_setInitialFocus($params, &$smarty)
{
    $dom = ZLanguage::getModuleDomain('mediashare');

    if (!isset($params['inputId'])) {
        $smarty->trigger_error(__('Missing [%1$s] in \'%2$s\'', array('inputId', 'elfisk_setInitialFocus'), $dom));
        return false;
    }

    $doSelect = (isset($params['doSelect']) ? $params['doSelect'] : true);
    $id = $params['inputId'];

    if ($doSelect) {
        $selectHtml = 'inp.select();';
    } else {
        $selectHtml = '';
    }

    $html = '<script type="text/javascript">'
           .'var bodyElement = document.getElementsByTagName("body")[0];'
           .'var f = function() {'
           .'  var inp = document.getElementById("'.$id.'");'
           .'  if (inp != null) {'
           .'    inp.focus();'
           .'    '.$selectHtml
           .'  }'
           .'};'
           .'var oldF = window.onload;'
           .'window.onload = function() { f(); if (oldF) oldF(); };'
           .'</script>';

    return $html;
}
