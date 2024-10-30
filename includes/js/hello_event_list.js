// Divs with class "flexFont" will set the internal font size to 5% of the div's width (in px)
// The internal elements can have classes to scale up and down using thingls like 0.6em
flexFont = function () {
  var divs = document.getElementsByClassName("flexFont");
  for(var i = 0; i < divs.length; i++) {
    var relFontsize = divs[i].offsetWidth*0.06;
    relFontsize = Math.min(relFontsize, 24);
    relLineheight = relFontsize*1.3;
    divs[i].style.fontSize = relFontsize+'px';
    divs[i].style.lineHeight = relLineheight+'px';
  }
};

window.onload = function(event) {
    flexFont();
};
window.onresize = function(event) {
    flexFont();
};

(function($){
  $(document).ready(function() {
    if ($( ".event-tabs" ).length) {
      $( ".event-tabs" ).tabs({
        active: 0
      });
    }
    
    $('#myTabs a').click(function (e) {
      e.preventDefault()
      $(this).tab('show')
    })
    
  })
})(jQuery)

