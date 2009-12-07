<?php

function smarty_function_mediashare_templateSelector($params, &$smarty)
{
    $id               = isset($params['id']) ? $params['id'] : 'album';
    $selectedTemplate = $smarty->get_template_vars($id);
    $name             = isset($params['name']) ? $params['name'] : $id;

    $templates = pnModAPIFunc('mediashare', 'user', 'getAllTemplates');
    if ($templates === false) {
        $smarty->trigger_error(LogUtil::getErrorMessagesText());
        return false;
    }

    if (isset($params['onchange']) && $params['onchange']) {
        $onChangeHtml = " onchange=\"$params[onchange]\"";
    } else {
        $onChangeHtml = '';
    }

    if (isset($params['readonly']) && $params['readonly']) {
        $readonlyHtml = " disabled=\"disabled\"";
    } else {
        $readonlyHtml = '';
    }

    if (isset($params['id']) && $params['id']) {
        $idHtml = " id=\"$id\"";
    } else {
        $idHtml = '';
    }

    $html = "<select name=\"$name\"$onChangeHtml$idHtml$readonlyHtml>\n";

    foreach ($templates as $template)
    {
        $title = DataUtil::formatForDisplay($template['title']);
        $value = $template['title'];

        $selectedHtml = (strcasecmp($value, $selectedTemplate)==0 ? ' selected="selected"' : '');

        $html .= "<option value=\"$value\"$selectedHtml>$title</option>\n";
    }

    $html .= "</select>";

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $html);
    }

    return $html;
}
