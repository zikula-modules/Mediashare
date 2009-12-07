<?php

function smarty_function_mediashare_tab($params, &$smarty)
{
    $content = $params['content'];
    $class   = $params['selected'] ? 'link selected' : 'link';

    $result = "<li class=\"{$class}\">{$content}</li><li>&nbsp;</li>";

    if (array_key_exists('assign', $params)) {
        $smarty->assign($params['assign'], $result);
    }

    return $result;
}
