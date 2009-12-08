<?php

function smarty_function_elfisk_intInput($params, &$smarty)
{
    $id = null;
    $idHtml = '';
    if (isset($params['id'])) {
        $id = $params['id'];
        $idHtml = " id=\"$id\"";
    }

    $nameHtml = '';
    if (isset($params['name'])) {
        $nameHtml = " name=\"$params[name]\"";
    } else if (isset($params['id'])) {
        $nameHtml = " name=\"$params[id]\"";
    }

    $styleHtml = elfisk_getStyleHtml($params);

    $text = '';
    if (isset($params['intValue'])) {
        $text = $params['intValue'];
    } else if ($id != null) {
        $text = $smarty->get_template_vars($id);
    }

    $result = "<input{$idHtml}{$nameHtml}{$styleHtml} class=\"int\" value=\"$text\"/>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
