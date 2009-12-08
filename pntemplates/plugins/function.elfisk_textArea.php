<?php
// $Id: pnexternal.php 96 2009-12-08 00:40:46Z mateo $

function smarty_function_elfisk_textArea($params, &$smarty)
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
    if (isset($params['text'])) {
        $text = htmlspecialchars($params['text']);
    } else if ($id != null) {
        $text = htmlspecialchars($smarty->get_template_vars($id));
    }

    $result = "<textarea{$idHtml}{$nameHtml}{$styleHtml}>$text</textarea>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
