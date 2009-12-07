<?php

function smarty_function_elfisk_upload($params, &$smarty)
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
    if (array_key_exists('text', $params)) {
        $text = htmlspecialchars($params['text']);
    }

    $result = "<input type=\"file\"{$idHtml}{$nameHtml}{$styleHtml} value=\"$text\"/>";

    if (array_key_exists('assign', $params)) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
