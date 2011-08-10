//
// Stand alone file selector for Mediashare
// (C) Jorn Wildt
//

/**
 * External interface functions
 */
function mediashareFindItem(targetId, mediashareURL)
{
  currentMediashareInput = document.getElementById(targetId);
  currentMediashareEditor = null;
  if (currentMediashareInput == null)
    alert("Unknown input element '" + targetId + "'");

  window.open(mediashareURL, "", "width=550,height=340,resizable");
}


function mediashareFindItemHtmlArea30(editor, mediashareURL)
{
    // Save editor for access in selector window
  currentMediashareEditor = editor;
  currentMediashareInput = null;

  window.open(mediashareURL, "", "width=550,height=340,resizable");
}


/**
 * Internal stuff
 */

// htmlArea 3.0 editor for access in selector window
var currentMediashareEditor = null;
var currentMediashareInput = null;

var mediashare = {}

mediashare.find = {}

mediashare.find.onFolderChanged = function(selectElement)
{
  var selectedValue = selectElement.value;
  var albumIdInput = document.getElementById('albumIdInput');
  albumIdInput.value = selectedValue;

  var commandInput = document.getElementById('commandInput');
  commandInput.value = "selectAlbum";

  var form = document.getElementById('selectorForm');
  form.submit();
}


mediashare.find.onItemChanged = function(selectElement)
{
  var selectedValue = selectElement.value;
  var mediaIdInput = document.getElementById('mediaIdInput');
  mediaIdInput.value = selectedValue;

  var commandInput = document.getElementById('commandInput');
  commandInput.value = "selectMedia";

  var form = document.getElementById('selectorForm');
  form.submit();
}


mediashare.find.handleCancel = function()
{
  var w = parent.window;
  window.close();
  w.focus();
}


// User clicks on "select item" button
mediashare.find.selectItem = function()
{
  if (window.opener.currentMediashareEditor != null)
  {
    var html = mediashare_paste_getHtml('html');

    window.opener.currentMediashareEditor.focusEditor();
    window.opener.currentMediashareEditor.insertHTML(html);
  }
  else
  {
    var html = mediashare_paste_getHtml('url');
    var currentInput = window.opener.currentMediashareInput;

    if (currentInput.tagName == 'INPUT')
    {
      // Simply overwrite value of input elements
      currentInput.value = html;
    }
    else if (currentInput.tagName == 'TEXTAREA')
    {
      // Try to paste into textarea - technique depends on environment
      if (typeof document.selection != "undefined")
      {
        // IE: Move focus to textarea (which fortunately keeps its current selection) and overwrite selection
        currentInput.focus();
        window.opener.document.selection.createRange().text = html;
      }
      else if (typeof currentInput.selectionStart != "undefined")
      {
        // Firefox: Get start and end points of selection and create new value based on old value
        var startPos = currentInput.selectionStart;
        var endPos = currentInput.selectionEnd;
        currentInput.value = currentInput.value.substring(0, startPos)
                                                     + html
                                                     + currentInput.value.substring(endPos, currentInput.value.length);
      } 
      else 
      {
        // Others: just append to the current value
        currentInput.value += html;
      }
    }
  }

  window.opener.focus();
  window.close();
}


function handleOnClickCancel()
{
  window.opener.focus();
  window.close();
}


/**
 * Mediashare item selector for pnForms
 */
mediashare.itemSelector = {};
mediashare.itemSelector.items = {};

mediashare.itemSelector.onLoad = function(baseId)
{
  var albumSelector = $(baseId+"_album");
  var selectedAlbumId = $F(albumSelector);
  var pars = "aid=" + selectedAlbumId;
  var url = document.location.pnbaseURL+"ajax.php?module=mediashare&func=getitems";
  
  new Ajax.Request(url, { method: "post", 
                          parameters: pars, 
                          onSuccess: function(response) { mediashare.itemSelector.gotItems(response,baseId,true); },
                          onFailure: mediashare.itemSelector.handleError});

  Event.observe('mediashare_upload_collapse', 'click', mediashare.itemSelector.uploadClick);
  $('mediashare_upload_collapse').addClassName('pn-toggle-link');
  mediashare.itemSelector.uploadClick();
}


mediashare.itemSelector.uploadClick = function()
{
  if ($('mediashare_upload_collapse').style.display != "none") {
      Element.removeClassName.delay(0.9, $('mediashare_upload_collapse'), 'pn-toggle-link-open');
  } else {
      $('mediashare_upload_collapse').addClassName('pn-toggle-link-open');
  }
  switchdisplaystate('mediashare_upload');
}


mediashare.itemSelector.albumChanged = function(albumSelector, baseId, mediadir)
{
  var selectedAlbumId = $F(albumSelector);
  var pars = "aid=" + selectedAlbumId;
  var url = document.location.pnbaseURL+"ajax.php?module=mediashare&func=getitems";
  
  $(baseId+'_img').src = 'images/ajax/rotating_arrow.gif';
  $(baseId+'_img').show();
  
  new Ajax.Request(url, { method: "post", 
                          parameters: pars, 
                          onSuccess: function(response) { mediashare.itemSelector.gotItems(response,baseId,false, mediadir); },
                          onFailure: mediashare.itemSelector.handleError});
}


mediashare.itemSelector.gotItems = function(response, baseId, updateListOnly, mediadir)
{
  var result = pndejsonize(response.responseText);

  mediashare.itemSelector.items[baseId] = result.mediaItems;

  if (!updateListOnly)
  {
    itemSelector = $(baseId+'_item');
    itemSelector.length = 0;

    for (i=0; i<result.mediaItems.length; ++i)
    {
      var item = result.mediaItems[i];
      itemSelector.options[i] = new Option(item.title, item.id, false);
    }

    if (result.mediaItems.length > 0)
    {
      if (result.mediaItems[0].isExternal)
        $(baseId+'_img').src = result.mediaItems[0].thumbnailRef;
      else
        $(baseId+'_img').src = mediadir + '/' + result.mediaItems[0].thumbnailRef;
      $(baseId+'_img').show();
    }
    else
    {
      $(baseId+'_img').hide();
    }
  }
}


mediashare.itemSelector.handleError = function(response)
{
  alert(response.responseText);
}


mediashare.itemSelector.itemChanged = function(itemSelector, baseId, mediadir)
{
  var selectedItemRef = $F(itemSelector);
  if (mediashare.itemSelector.items[baseId][itemSelector.selectedIndex].isExternal)
    $(baseId+'_img').src = mediashare.itemSelector.items[baseId][itemSelector.selectedIndex].thumbnailRef;
  else
    $(baseId+'_img').src = mediadir + '/' + mediashare.itemSelector.items[baseId][itemSelector.selectedIndex].thumbnailRef;
}
