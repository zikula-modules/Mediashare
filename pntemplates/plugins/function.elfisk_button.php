<?php

function smarty_function_elfisk_button($params, &$smarty)
{
    if (!isset($params['name'])) {
        $smarty->trigger_error('button: name parameter required');
        return false;
    }

    $name = $params['name'];
    $text = $params['text'];

    $onclickHtml = '';
    if (isset($params['confirmMessage'])) {
        $msg = eval("return $params[confirmMessage];") . '?';
        $onclickHtml = " onclick=\"return confirm('$msg')\"";
    } else if (isset($params['onclick'])) {
        $onclickHtml = " onclick=\"return $params[onclick]\"";
    }

    echo "<input type=\"submit\" name=\"$name\" value=\"$text\"$onclickHtml/>";
}
