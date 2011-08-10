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


/**
 * Event shortcuts
 */
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
  } else {
    this.button = evt.button;
  }
  
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


/**
 * Context dependent menus
 */
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
  
  element.parentNode.appendChild(menuDivElement); 

  psmenu.openMenu(evt, context, menuDivElement, pos);

  context.currentId = itemId;
  context.currentElement = imgElement;

  return false;
}


/**
 * Listener methods for psmenu
 */
function addUrlParam(url, param, value)
{
  return url.replace(/XXX/, value);
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

function selectMedia(mark) 
{
  var checkboxes = document.getElementsByName('mediaId[]'); 
  for (var i=checkboxes.length-1; i>=0; --i) 
  {
    checkboxes[i].checked = mark;
  }
}

/**
 * Dragging utilities
 */
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


/**
 * Arranging items
 */
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
