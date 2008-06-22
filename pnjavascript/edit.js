// =======================================================================
// Photoshare by Jorn Lind-Nielsen (C) 2002.
// ----------------------------------------------------------------------
// For POST-NUKE Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// =======================================================================

var currentImage   = null;  // Points to the selected image when only one image is selected
var selectedImages = {};    // Contains set of selected images
var imageCount     = 0;     // Number of selected images

var mediashare = {};

mediashare.key_escape = 27;
mediashare.key_enter = 13;
mediashare.key_up = 38;
mediashare.key_down = 40;

mediashare.button_left = 1;
mediashare.button_right = 2;


mediashare.isLeftButton = function(b)
{
  if (b == 0 || b == 1)
    return true;

  return false;
}


/* ======================================================================================================
  Event shortcuts
====================================================================================================== */

function Evt(evt) 
{
  evt = evt ? evt : window.event; 
	this.evt = evt; 
	this.source = evt.target ? evt.target : evt.srcElement;
	this.x = evt.pageX ? evt.pageX : evt.clientX;
	this.y = evt.pageY ? evt.pageY : evt.clientY;
	
	
	if (mediashare.isNetscape)
	{
	  this.button = e.which;
	  /*
	  if (this.button == 0)
	   this.button = mediashare.button_left;
	  */
	}
	else
	  this.button = evt.button;
	
	//this.docX = this.x + document.body.scrollLeft - document.body.clientLeft;
	//this.docY = this.y + document.body.scrollTop  - document.body.clientTop;
	//alert("ST:" + document.body.scrollTop);
	//alert("CT:"+document.body.clientTop);
}

Evt.prototype.toString = function () 
{
	return "Evt [ x = " + this.x + ", y = " + this.y + " ]";
};

Evt.prototype.consume = function () 
{
	if (this.evt.stopPropagation) 
	{
		this.evt.stopPropagation();
		this.evt.preventDefault();
	} 
	else if (this.evt.cancelBubble) 
	{
		this.evt.cancelBubble = true;
		this.evt.returnValue  = false;
	}
};

Evt.addEventListener = function (target,type,func,bubbles) 
{
	if (document.addEventListener) 
	{
		target.addEventListener(type,func,bubbles);
	} 
	else if (document.attachEvent) 
	{
		target.attachEvent("on"+type,func,bubbles);
	} 
	else 
	{
		target["on"+type] = func;
	}
};


Evt.removeEventListener = function (target,type,func,bubbles) 
{
	if (document.removeEventListener) 
	{
		target.removeEventListener(type,func,bubbles);
	} 
	else if (document.detachEvent) 
	{
		target.detachEvent("on"+type,func,bubbles);
	} 
	else 
	{
		target["on"+type] = null;
	}
};



// =======================================================================
// Event handlers
// =======================================================================

function toggleSelectImage(img)
{
  if (img.className == "photoshare-selected")
    unselectImage(img);
  else
    selectImage(img);
}


function unselectImage(img)
{
  img.className = "photoshare-unselected";
  --imageCount;
  selectedImages[img.id] = false;

  if (imageCount == 1)
    currentImage = img;
  else
    currentImage = null;
}


function selectImage(img)
{
  img.className = "photoshare-selected";
  ++imageCount;
  selectedImages[img.id] = true;

  if (imageCount == 1)
    currentImage = img;
  else
    currentImage = null;
}


function toggleSelectImageRange(img)
{
  var images = document.images;
  var inRange = false;
  var doSelect = (img.className == 'photoshare-selected' ? false : true);

  for (var i=images.length-1; i>=0; --i)
  {
    if (images[i].id == img.id)
      inRange = true;

    if (doSelect)
    {
      if (images[i].className == 'photoshare-selected')
        inRange = false;
      if (images[i].className == 'photoshare-unselected'  &&  inRange)
        selectImage(images[i]);
    }
    else
    {
      if (images[i].className == 'photoshare-unselected')
        inRange = false;
      if (images[i].className == 'photoshare-selected'  &&  inRange)
        unselectImage(images[i]);
    }
  }
}


function unselectAllImages()
{
  var images = document.images;

  for (var i=images.length-1; i>=0; --i)
  {
    if (images[i].className == 'photoshare-selected')
      unselectImage(images[i]);
  }
}


function handleOnMouseDownImage(imgElement, evt)
{
  evt = (evt ? evt : (event ? event : null));
  if (evt == null)
    return true;

  if (evt.ctrlKey)
  {
    toggleSelectImage(imgElement);
    return false;
  }
  else
  if (evt.shiftKey)
  {
    toggleSelectImageRange(imgElement);
    return false;
  }
  else
  {
    var selected = (imgElement.className == "photoshare-selected");
    unselectAllImages();
    if (!selected)
      selectImage(imgElement);
    return false;
  }

  return true;
}


function handleOnClickTarget(target, page)
{
  if (imageCount == 0)
  {
    alert(translations.selectImage);
  }
  else
  if (imageCount == 1)
  {
    if (currentImage == null)
      alert("Internal error: unexpected missing current image");

    var imageID  = currentImage.id;
    var position = target.id.substr(6);

    window.location = "index.php?module=photoshare&func=moveimage&iid=" + imageID + "&pos=" + position + "&page=" + page;
  }
  else
  {
    alert(translations.tooManyImages);
  }
}


function handleOnClickCommand(command, requireSelectedImages)
{
    // Check if any image is selected
  if (imageCount == 0  &&  requireSelectedImages)
  {
    alert(translations.selectImage);
    return;
  }

    // Confirm delete
  if (command == "delete")
    if (!confirm(translations.confirmDelete))
      return;

    // Build image id list

    // Iterate through image set and create comma-sep. list in string
  var idString = "";
  for (var i in selectedImages)
  {
    if (selectedImages[i])
    {
      if (idString == "")
        idString += i;
      else
        idString += "," + i;
    }
  }

    // Put image id list and command into form and submit it

  var commandForm = document.forms["commandForm"];
  commandForm.command.value = command;
  commandForm.imageids.value = idString;

  commandForm.submit();
}


/*=============================================================================
  Context dependent menus
=============================================================================*/

var contextmenu =
{
  album:
  {
    currentElement: null,
    currentId:      null,
    actionURLs:     null
  },
  media:
  {
    currentElement: null,
    currentId:      null,
    actionURLs:     null
  }
};


contextmenu.album.onClick = function(element, albumId, evt, menuId)
{
  contextmenu.urlIdParam = 'aid';
  return contextmenu.onClick(element, albumId, evt, menuId, contextmenu.album);
}

contextmenu.media.onClick = function(element, mediaId, evt, menuId)
{
  contextmenu.urlIdParam = 'mid';
  return contextmenu.onClick(element, mediaId, evt, menuId, contextmenu.media);
}

contextmenu.onClick = function(element, itemId, evt, menuId, context)
{
  evt = (evt ? evt : (event ? event : null));
  if (evt == null)
    return true;

  var imgElement = document.getElementById(itemId);

  var menuDivElement = document.getElementById(menuId);

  var pos = getPositionOfElement(element);

  psmenu.openMenu(evt, context, menuDivElement, pos);

  context.currentId = itemId;
  context.currentElement = imgElement;

  return false;
}


//-[ Listener methods for psmenu ]---------------------------------------------

function addUrlParam(url, param, value)
{
  var pos = url.indexOf("?");
  if (pos < 0)
  {
    pos = url.lastIndexOf(".");
    url = url.substr(0,pos) + "-" + param + "-" + value + url.substr(pos);
  }
  else
    url += "&" + param + "=" + value;

  return url;
}


contextmenu.album.itemSelected = function(menuId, itemIndex)
{
  return contextmenu.itemSelected(menuId, itemIndex, contextmenu.album)
}

contextmenu.media.itemSelected = function(menuId, itemIndex)
{
  return contextmenu.itemSelected(menuId, itemIndex, contextmenu.media)
}

contextmenu.itemSelected = function(menuId, itemIndex, context)
{
  var id = context.currentId.substring(6);
  var url = context.actionURLs[itemIndex].replace(/&amp;/g,"&");
  url = addUrlParam(url, contextmenu.urlIdParam, id);
  window.location = url;
}


contextmenu.album.menuClosed = function()
{
  return contextmenu.menuClosed(contextmenu.album);
}

contextmenu.media.menuClosed = function()
{
  return contextmenu.menuClosed(contextmenu.media);
}

contextmenu.menuClosed = function(context)
{
  context.currentElement = null;
  context.currentId = null;
}


/* ======================================================================================================
  Dragging utilities
====================================================================================================== */
mediashare.drag = {};

mediashare.drag.disableTextSelection = function()
{
	// Ensure cursor movement with mouse down do not function as "select text".
	
	mediashare.oldOndrag = document.body.ondrag;
	mediashare.oldOnselectstart = document.body.onselectstart;
	
	document.body.ondrag = function () { return false; };
	document.body.onselectstart = function () { return false; };
}


mediashare.drag.enableTextSelection = function()
{
 	document.body.ondrag = mediashare.oldOndrag;
 	document.body.onselectstart = mediashare.oldOnselectstart;
}


// =======================================================================
// Arranging items
// =======================================================================

mediashare.drag.dragPress = function(evt)
{
	evt = new Evt(evt);

  if (!mediashare.isLeftButton(evt.button))
	  return;

  evt.consume();

	mediashare.drag.startItem = evt.source;

	Evt.addEventListener(document, "mousemove", mediashare.drag.dragMove, false);
	Evt.addEventListener(document, "mouseup", mediashare.drag.dragRelease, false);
	
	mediashare.drag.disableTextSelection();
}


mediashare.drag.dragMove = function(evt) 
{
	var evt = new Evt(evt);

  //mediashare.drag.startItem.style.position = 'absolute';

	evt.consume();
}


mediashare.drag.dragRelease = function(evt) 
{
	evt = new Evt(evt);
  var rowEnd = evt.source.parentNode.parentNode;

  if (rowEnd.className == "mr")
  {
    var rowStart = mediashare.drag.startItem.parentNode.parentNode;
    var table = rowStart.parentNode;

    if (rowStart.rowIndex < rowEnd.rowIndex)
      table.insertBefore(rowStart.cloneNode(true), rowEnd.nextSibling);
    else
      table.insertBefore(rowStart.cloneNode(true), rowEnd);
    table.removeChild(rowStart);
  }

  // Set event handlers back to normal

  mediashare.drag.enableTextSelection();

	Evt.removeEventListener(document, "mousemove", mediashare.drag.dragMove, false);
 	Evt.removeEventListener(document, "mouseup", mediashare.drag.dragRelease, false);

  mediashare.drag.startItem.style.position = 'static';
}


mediashare.drag.submit = function()
{
  var table = document.getElementById("mediashare-arrange-table");
  var seq = "";

  for (var i=0,cou=table.rows.length; i<cou; ++i)
  {
    var img = table.rows[i].cells[0].childNodes[0];
    var id = parseInt(img.id.substring(6));

    if (seq != "")
      seq += ",";
    seq += id;
  }

  var input = document.getElementById("mediashare-arrange-seqinput");
  input.value = seq;
}