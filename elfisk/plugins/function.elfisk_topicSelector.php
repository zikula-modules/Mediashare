<?php

function smarty_function_elfisk_topicSelector($params, &$smarty) 
{
  $id = null;
  $idHtml = '';
  if (array_key_exists('id', $params))
  {
    $id = $params['id'];
    $idHtml = " id=\"$id\"";
  }
  
  $nameHtml = '';
  if (array_key_exists('name', $params))
  {
    $nameHtml = " name=\"$params[name]\"";
  }
  else if (array_key_exists('id', $params))
  {
    $nameHtml = " name=\"$params[id]\"";
  }

  $topics = smarty_function_elfisk_topicSelectorGetTopics();

  if ($topics === false)
  {
    $smarty->trigger_error('Failed to fetch topics');
    return false;
  }

  $selectedValue = null;
  if (array_key_exists('selectedValue', $params))
  {
    $selectedValue = htmlspecialchars($params['selectedValue']);
  }
  else if ($id != null)
  {
    $selectedValue = $smarty->get_template_vars($id);
  }


  $html = "<select{$nameHtml}{$idHtml}>
           <option value=\"0\">" . _ELFISKNOTOPIC . "</option>\n";

  foreach ($topics as $topic)
  {
    if ($topic['id'] == $selectedValue)
      $selected = ' selected="1"';
    else
      $selected = '';

    $html .= "<option value=\"$topic[id]\"$selected>" . htmlspecialchars($topic['title']) . "</option>\n";
  }

  $html .= "</select>\n";

  return $html;
}


function smarty_function_elfisk_topicSelectorGetTopics()
{
  pnModDBInfoLoad('Topics');

  list($dbconn) = pnDBGetConn();
  $pntable =& pnDBGetTables();

  $topicsTable  = $pntable['topics'];
  $topicsColumn = $pntable['topics_column'];

  $sql = "SELECT   $topicsColumn[tid],
                   $topicsColumn[topicname]
          FROM     $topicsTable
          ORDER BY $topicsColumn[topicname]";

  $result = $dbconn->execute($sql);

  if ($dbconn->errorNo() != 0)
  {
    echo 'Elfisk "GetTopics" failed: ' . $dbconn->errorMsg() . " while executing: $sql";
    return null;
  }

  $topics = array();

  for (; !$result->EOF; $result->MoveNext())
  {
    $topics[] = array('id'    => intval($result->fields[0]),
                      'title' => $result->fields[1]);
  }

  $result->Close();

  return $topics;
}

?>
