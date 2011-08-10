/**
 * Some utility functions
 */
function getPositionOfElement(element)
{
  var pos = { top: 0, left: 0 };

  do 
  {
    pos.top  += element.offsetTop;
    pos.left += element.offsetLeft;

    element = element.offsetParent;
  }
  while (element != null  &&  element != element.offsetParent);

  return pos;
}


function getPositionOfEvent(evt)
{
  if (evt.pageX)
    return { 
             left: evt.pageX, 
             top: evt.pageY 
           };
  else if (evt.clientX)
    return { 
             left: evt.clientX + document.body.scrollLeft - document.body.clientLeft, 
             top:  evt.clientY + document.body.scrollTop  - document.body.clientTop
           };

  alert("Unable to get position of event");
  return { left: 0, top: 0 };
};


/**
 * PS Menu navigation handling
 */
var psmenu =
{
  closeDelay: 800,
  currentMenuDivElement: null,
  currentCancelCount: 0,
  currentListener: null
};


/**
 * Open/close menu
 */
psmenu.openMenu = function(evt, listener, menuDivElement, pos)
{
  if (psmenu.isOpen())
  {
    var doSkip = psmenu.currentMenuDivElement == menuDivElement;

    psmenu.closeCurrentMenu();

    if (doSkip)
      return;
  }

  menuDivElement.style.visibility = "visible";
  menuDivElement.style.left = "0px";
  menuDivElement.style.top = "0px";
  psmenu.currentMenuDivElement = menuDivElement;
  psmenu.currentListener = listener;

  document.onclick = function() { psmenu.closeMenu(menuDivElement); };
  if (typeof event != "undefined")
    event.cancelBubble = true;
  else
    evt.stopPropagation();
}


psmenu.closeMenu = function(menuDivElement)
{
  psmenu.cancelDelayedCloseMenu();

  menuDivElement.style.visibility = "hidden";
  psmenu.currentMenuDivElement = null;

  psmenu.currentListener.menuClosed();
}


psmenu.closeCurrentMenu = function()
{
  clearTimeout();
  if (psmenu.currentMenuDivElement != null)
    psmenu.closeMenu(psmenu.currentMenuDivElement);
}


psmenu.delayedCloseMenu = function(menuDivElement)
{
  psmenu.currentMenuDivElement = menuDivElement;
  ++psmenu.currentCancelCount;
  setTimeout( "if (psmenu.currentCancelCount==" + psmenu.currentCancelCount + ") psmenu.closeCurrentMenu();", psmenu.closeDelay );
}


psmenu.cancelDelayedCloseMenu = function()
{
  psmenu.currentMenuDivElement = null;
  clearTimeout();
}


psmenu.isOpen = function()
{
  return psmenu.currentMenuDivElement != null;
}


/**
 * Event handlers
 */
psmenu.onMouseOutDiv = function(menuDivElement)
{
  psmenu.delayedCloseMenu(menuDivElement);
}


psmenu.onMouseOver = function(rowElement)
{
  rowElement.className = "psmenu-menuItemOn";
  psmenu.cancelDelayedCloseMenu();
}


psmenu.onMouseOut = function(rowElement)
{
  rowElement.className = "psmenu-menuItem";
}


psmenu.onMouseDown = function(rowElement)
{
  var menuDivElement = psmenu.getParentDivElement(rowElement);

  psmenu.currentListener.itemSelected(menuDivElement.id, rowElement.rowIndex);

  psmenu.closeMenu(menuDivElement);
}


psmenu.onClick = function(linkElement, index)
{
  var menuDivElement = psmenu.getParentDivElement(linkElement);

  psmenu.currentListener.itemSelected(menuDivElement.id, index);

  psmenu.closeMenu(menuDivElement);
}


/**
 * DOM navigation
 */
psmenu.getParentDivElement = function(rowElement)
{
  var element = rowElement.parentNode;
  while (element.tagName != "DIV")
    element = element.parentNode;

  return element;
}
