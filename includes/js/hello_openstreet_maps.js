// Interface function to the OpenStreet Maps API

var map;
var zoom = 17;
var ajaxRequest;
var plotlist;
var plotlayers=[];

function showmap() {
	// create the tile layer with correct attribution
	var osmUrl='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 6, maxZoom: 20, attribution: osmAttrib});
  
  if ((lat == 53) && (lng==10)) { // If in dummy location then geocode the real location
    console.log("We have to geocode...address "+loc);
    geocodeAddress(loc);
  }

	// start the map at our location
	map.setView(new L.LatLng(lat, lng),zoom);
	map.addLayer(osm);

  // Put a market with the address in popup  
  L.marker([lat, lng]).addTo(map).bindPopup(loc);
  
	// askForPlots();
	// map.on('moveend', onMapMove);
}

function initmap() {
	// set up the map
	map = new L.Map('open-map', {scrollWheelZoom: false});
  
  // set up AJAX request
  ajaxRequest=getXmlHttpObject();
  if (ajaxRequest==null) {
  	alert ("This browser does not support HTTP Request");
  	return;
  }
  showmap();
}

function getXmlHttpObject() {
	if (window.XMLHttpRequest) { return new XMLHttpRequest(); }
	if (window.ActiveXObject)  { return new ActiveXObject("Microsoft.XMLHTTP"); }
	return null;
}

function askForPlots() {
  // Not used
  return;
	// request the marker info with AJAX for the current bounds
	var bounds=map.getBounds();
	var minll=bounds.getSouthWest();
	var maxll=bounds.getNorthEast();
	var msg='leaflet/findbybbox.cgi?format=leaflet&bbox='+minll.lng+','+minll.lat+','+maxll.lng+','+maxll.lat;
	ajaxRequest.onreadystatechange = stateChanged;
	ajaxRequest.open('GET', msg, true);
	ajaxRequest.send(null);
}

function onMapMove(e) { 
  //askForPlots();
}

function stateChanged() {
	// if AJAX returned a list of markers, add them to the map
	if (ajaxRequest.readyState==4) {
		//use the info here that was returned
		if (ajaxRequest.status==200) {
			plotlist=eval("(" + ajaxRequest.responseText + ")");
			removeMarkers();
			for (i=0;i<plotlist.length;i++) {
				var plotll = new L.LatLng(plotlist[i].lat,plotlist[i].lon, true);
				var plotmark = new L.Marker(plotll);
				plotmark.data=plotlist[i];
				map.addLayer(plotmark);
				plotmark.bindPopup("<h3>"+plotlist[i].name+"</h3>"+plotlist[i].details);
				plotlayers.push(plotmark);
			}
		}
	}
}

function removeMarkers() {
	for (i=0;i<plotlayers.length;i++) {
		map.removeLayer(plotlayers[i]);
	}
	plotlayers=[];
}



function geocodeAddress(address) {
  var url = "https://nominatim.openstreetmap.org/search?q=";
  var addressx = address.replace(/ /g, "+");
  url = url + addressx  + "&format=json&polygon=1&addressdetails=1";
  //alert("URL="+url);
  jQuery.getJSON( url, '', ).done(function(data){
    //alert("DATA="+data);
    if (data.length > 0) {
      lat = data[0]['lat'];
      lng = data[0]['lon'];
    	showmap();
      jQuery.ajax({
        url: adminAjax,
        type: "POST",
        data :{
          action: "set_geocode",
          post_id: post_id,
          lat: lat,
          lng: lng,
        }
        });
    }
    else {
      alert('Impossible to geocode the address: ' + address);
    }    
  })
}


(function($){
  initmap();
})(jQuery)


