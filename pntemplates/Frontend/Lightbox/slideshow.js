var MediashareSlideshow = Class.create();

MediashareSlideshow.prototype = 
{
  itemArray: [],
  
  currentItemIndex: 0,

  currentSpeed: 5,

  currentTimer: null,

  currentRunning: true,

  currentSize: "original",

  
  initialize: function()
  {
  },


  AddItem: function(id, originalUrl, previewUrl, mimeType, html, title)
  {
    this.itemArray.push({id: id, originalUrl: originalUrl, previewUrl: previewUrl, mimeType: mimeType, html: html, title: title});
  },


  Start: function(firstItemId)
  {
    var index = 0;
    for (var i=0; i<this.itemArray.length; ++i)
      if (this.itemArray[i].id == firstItemId)
        index = i;

    this.ShowItem(index);
    var t = this;
    this.currentTimer = setTimeout(function() { t.HandleNextTimeStep() }, this.currentSpeed*1000);
  },


  ShowItem: function(index)
  {
    var t = this;
    this.UpdateTitleState(false);//Element.hide('mediaTitleBox');
    new Effect.Fade('mediaItem', { duration: 0.4, to: 0.01, afterFinish: function() { t.ShowItem2(index); } });
  },


  ShowItem2: function(index)
  {
    var t = this;

    if (this.itemArray[index].mimeType.indexOf("image/") >= 0)
    {
      // Handle images as Lightbox does it - with a preloader object.

      var imgPreloader = new Image();
      
      imgPreloader.onload = function()
      {
        // Clear all sub childs
        while ($('mediaItem').childNodes.length > 0)
          $('mediaItem').removeChild($('mediaItem').childNodes[0]);

        // Add image element
        $('mediaItem').appendChild(this);

        new Effect.Appear('mediaItem', { duration: 0.9 } );
        t.UpdateDetails(index, imgPreloader.width, imgPreloader.height);
      }
      
      if (this.currentSize == 'original')
      {
        if (this.itemArray[index].originalUrl.indexOf("mediashare") == 0)
          imgPreloader.src = document.location.pnbaseURL + this.itemArray[index].originalUrl;
        else
          imgPreloader.src = this.itemArray[index].originalUrl;
      }
      else
      {
        if (this.itemArray[index].previewUrl.indexOf("mediashare") == 0)
          imgPreloader.src = document.location.pnbaseURL + this.itemArray[index].previewUrl;
        else
          imgPreloader.src = this.itemArray[index].previewUrl;
      }
    }
    else
    {
      // Everything else than images are handled plain and simple

      new Effect.Appear('mediaItem', { duration: 0.9 } );

      Element.setInnerHTML('mediaItem', this.itemArray[index].html);
      this.UpdateDetails(index, Element.getWidth('mediaItem'), 100);
    }

    this.currentItemIndex = index;
  },


  UpdateDetails: function(index, imgWidth, imgHeight)
  {
    var pageSize = getPageSize();
    var titleWidth = imgWidth * 80 / 100;
    var titleBox = $('mediaTitleBox');
  
    Element.setInnerHTML('mediaTitle', this.itemArray[index].title);
    Element.setWidth(titleBox, titleWidth);
    Element.setLeft(titleBox, pageSize.pageWidth/2 - titleWidth/2);
    
    Element.setInnerHTML('mediaCount', (index+1) + "/" + this.itemArray.length);

    this.UpdateTitleState(true);//new Effect.Appear(titleBox, { duration: 0.9, to: 0.6 } );
  },


  HandlePrevious: function()
  {
    this.StopSlideshow();
    this.Previous();
  },


  Previous: function()
  {
    this.currentItemIndex--;
    if (this.currentItemIndex < 0)
      this.currentItemIndex = this.itemArray.length-1;

    this.ShowItem(this.currentItemIndex);
  },


  HandleNext: function()
  {
    this.StopSlideshow();
    this.Next();
  },


  Next: function()
  {
    this.currentItemIndex++;
    if (this.currentItemIndex >= this.itemArray.length)
      this.currentItemIndex = 0;

    this.ShowItem(this.currentItemIndex);
  },


  HandleAdjustSpeed: function(increment)
  {
    this.currentSpeed += increment;
    Element.setInnerHTML('mediaTimeSpan', this.currentSpeed);
  },

  
  HandleSize: function()
  {
    if (this.currentSize == "original")
    {
      this.currentSize = "preview";
      Element.setInnerHTML('slideshowSizeAnchor', pnmlLarger);
    }
    else
    {
      this.currentSize = "original";
      Element.setInnerHTML('slideshowSizeAnchor', pnmlSmaller);
    }

    this.ShowItem(this.currentItemIndex);
  },


  HandleToggleTitle: function()
  {
    this.UpdateTitleState(null);
  },


  HandleNextTimeStep: function()
  {
    this.Next();
    var t = this;
    this.currentTimer = setTimeout(function() { t.HandleNextTimeStep() }, this.currentSpeed*1000);
  },

   
  HandleToggleStart: function()
  {
    if (this.currentRunning)
    {
      this.StopSlideshow();
    }
    else
    {
      this.StartSlideshow();
    }
  },

   
  StartSlideshow: function()
  {
    var t = this;
    this.currentTimer = setTimeout(function() { t.HandleNextTimeStep() }, this.currentSpeed*1000);
    $('slideshowStateImg').src = baseURL + "modules/mediashare/pntemplates/Frontend/Lightbox/images/pause.gif";
    $('slideshowStateImg').alt = pnmlStop;
    this.currentRunning = true;
  },

   
  StopSlideshow: function()
  {
    clearTimeout(this.currentTimer);
    $('slideshowStateImg').src = baseURL + "modules/mediashare/pntemplates/Frontend/Lightbox/images/play.gif";
    $('slideshowStateImg').alt = pnmlStart;
    this.currentRunning = false;
  },

   
  UpdateTitleState: function(doDisplay)
  {
    var checkbox = $('slideshowToggleTitle');
    if (!checkbox.checked || doDisplay == false)
    {
      new Effect.Fade('mediaTitleBox', { duration: 0.2, to: 0.01 } );
    }
    else if (checkbox.checked || doDisplay == true)
    {
      new Effect.Appear('mediaTitleBox', { duration: 0.2, to: 0.6 } );
    }
  }
}


// getPageSize()
// Returns object with page width, height and window width, height
// Core code from - quirksmode.org
// Edit for Firefox by pHaez
// Copied from Lightbox project
function getPageSize()
{
  var xScroll, yScroll;
  
  if (window.innerHeight && window.scrollMaxY) {  
    xScroll = document.body.scrollWidth;
    yScroll = window.innerHeight + window.scrollMaxY;
  } else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
    xScroll = document.body.scrollWidth;
    yScroll = document.body.scrollHeight;
  } else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
    xScroll = document.body.offsetWidth;
    yScroll = document.body.offsetHeight;
  }
  
  var windowWidth, windowHeight;
  if (self.innerHeight) { // all except Explorer
    windowWidth = self.innerWidth;
    windowHeight = self.innerHeight;
  } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
    windowWidth = document.documentElement.clientWidth;
    windowHeight = document.documentElement.clientHeight;
  } else if (document.body) { // other Explorers
    windowWidth = document.body.clientWidth;
    windowHeight = document.body.clientHeight;
  } 
  
  // for small pages with total height less then height of the viewport
  if(yScroll < windowHeight){
    pageHeight = windowHeight;
  } else { 
    pageHeight = yScroll;
  }

  // for small pages with total width less then width of the viewport
  if(xScroll < windowWidth){  
    pageWidth = windowWidth;
  } else {
    pageWidth = xScroll;
  }

  arrayPageSize = { pageWidth: pageWidth, pageHeight: pageHeight, windowWidth: windowWidth, windowHeight: windowHeight }; 
  return arrayPageSize;
}


// -----------------------------------------------------------------------------------

//
//  Additional methods for Element added by SU, Couloir
//  - further additions by Lokesh Dhakar (huddletogether.com)
//
Object.extend(Element, {
  getWidth: function(element) {
      element = $(element);
      return element.offsetWidth; 
  },
  setWidth: function(element,w) {
      element = $(element);
      element.style.width = w +"px";
  },
  setHeight: function(element,h) {
      element = $(element);
      element.style.height = h +"px";
  },
  setTop: function(element,t) {
      element = $(element);
      element.style.top = t +"px";
  },
  setLeft: function(element,t) {
      element = $(element);
      element.style.left = t +"px";
  },
  getLeft: function(element) {
      element = $(element);
      return element.offsetLeft;
  },
  setSrc: function(element,src) {
      element = $(element);
      element.src = src; 
  },
  setHref: function(element,href) {
      element = $(element);
      element.href = href; 
  },
  setInnerHTML: function(element,content) {
    element = $(element);
    element.innerHTML = content;
  }
});


