
/* Popup functions */

function popupMediaViewer(url)
{
  var imageWindow = window.open(url,'image','toolbar=0,location=0,directories=0,menuBar=0,scrollbars=0,resizable=1');
  imageWindow.focus();
}


function mediashareGreyboxOpen(width, height, url)
{
  GB_showCenter('blah', url, width, height);
}

/* Slideshow */

var slideshowTimerId = null;

function onchangeSlideshowIndex(selectElement, url, delay)
{
  id = selectElement.value;
  slideshowJumpToItem(id, url, delay, 'stopped');
}


function onchangeSlideshowDelay(selectElement, url, id)
{
  clearTimeout();
  delay = selectElement.value;
  slideshowJumpToItem(id, url, delay, 'running');
}


function slideshowJumpToItem(id, url, delay, mode)
{
  url = url.replace(/MEDIAID/, id);
  url = url.replace(/DELAY/, delay);
  url = url.replace(/MODE/, mode);
  window.location = url;
}


function slideshowStartup(url,delay)
{
  slideshowTimerId = setTimeout( function() { window.location = url; }, delay*1000 );
}


function slideshowStart(url,delay)
{
  slideshowTimerId = setTimeout( function() { window.location = url; }, delay*1000 );
  document.getElementById("slideshowStopButton").disabled = 0;
  document.getElementById("slideshowStartButton").disabled = 1;
}


function slideshowStop()
{
  clearTimeout(slideshowTimerId);
  document.getElementById("slideshowStopButton").disabled = 1;
  document.getElementById("slideshowStartButton").disabled = 0;
}
