<?php

function smarty_function_elfisk_intInput($params, &$smarty)
{
    $id = null;
    $idHtml = '';
    if (array_key_exists('id', $params)) {
        $id = $params['id'];
        $idHtml = " id=\"$id\"";
    }

    $nameHtml = '';
    if (array_key_exists('name', $params)) {
        $nameHtml = " name=\"$params[name]\"";
    } else if (array_key_exists('id', $params)) {
        $nameHtml = " name=\"$params[id]\"";
    }

    $styleHtml = elfisk_getStyleHtml($params);

    $text = '';
    if (array_key_exists('intValue', $params)) {
        $text = $params['intValue'];
    } else if ($id != null) {
        $text = $smarty->get_template_vars($id);
    }

    $result = "<input{$idHtml}{$nameHtml}{$styleHtml} class=\"int\" value=\"$text\"/>";

    if (array_key_exists('assign', $params))
        $smarty->assign($params['assign'], $result);
    else
        return $result;
}

