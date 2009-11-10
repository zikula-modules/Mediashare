<?php

function smarty_function_mediashare_templateSelector($params, &$smarty)
{
  if (!pnModAPILoad('mediashare', 'user'))
    return $smarty->trigger_error( mediashareErrorPage(__FILE__, __LINE__, 'Failed to load Mediashare user API') );

  $id = isset($params['id']) ? $params['id'] : 'album';
  $selectedTemplate = $smarty->get_template_vars($id);
  $name = isset($params['name']) ? $params['name'] : $id;

  $templates = pnModAPIFunc('mediashare', 'user', 'getAllTemplates');
  if ($templates === false)
    return $smarty->trigger_error( mediashareErrorAPIGet() );

  if (isset($params['onchange']) && $params['onchange'])
    $onChangeHtml = " onchange=\"$params[onchange]\"";
  else
    $onChangeHtml = '';

  if (isset($params['readonly']) && $params['readonly'])
    $readonlyHtml = " disabled=\"disabled\"";
  else
    $readonlyHtml = '';

  if (isset($params['id']) && $params['id'])
    $idHtml = " id=\"$id\"";
  else
    $idHtml = '';

  $html = "<select name=\"$name\"$onChangeHtml$idHtml$readonlyHtml>\n";

  foreach ($templates as $template)
  {
    $title = pnVarPrepForDisplay($template['title']);
    $value = $template['title'];

    $selectedHtml = (strcasecmp($value, $selectedTemplate)==0 ? ' selected="selected"' : '');

    $html .= "<option value=\"$value\"$selectedHtml>$title</option>\n";
  }

  $html .= "</select>";

  if (isset($params['assign']))
    $smarty->assign($params['assign'], $html);
  else
    return $html;
}

?>