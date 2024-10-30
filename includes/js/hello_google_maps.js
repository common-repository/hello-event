// Interface function to the Google Maps API
// InitMap is called from the enqueue action when the google library is enqueued


function initMap() {
  var map = new google.maps.Map(document.getElementById('map'), {
    zoom: 16,
    center: {lat: lat, lng: lng}
  });
  if ((lat == 53) && (lng==10)) { // If in dummy location then geocode the real location
    console.log("We have to geocode...");
    var geocoder = new google.maps.Geocoder();
    geocodeAddress(geocoder, map);
    myGeocode(geocoder);
  }
  else { // We didn't have to geocode and now we can set the marker
    var marker = new google.maps.Marker({
      map: map,
      position: {lat: lat, lng: lng}
  });
  }
}

// Geocode and send the obtained lat/lng via Ajax to "set_geocode" that will update the event attributes in the database
function myGeocode(geocoder) {
  var address = loc;
  geocoder.geocode({'address': address}, function(results, status) {
    if (status === 'OK') {
      jQuery.ajax({
        url: adminAjax,
        type: "POST",
        data :{
          action: "set_geocode",
          post_id: post_id,
          lat: results[0].geometry.location['lat'],
          lng: results[0].geometry.location['lng'],
        }
        });
    } else {
      alert('Geocode was not successful for the following reason: ' + status);
    }
  });
}

function geocodeAddress(geocoder, resultsMap) {
  var address = loc;
  geocoder.geocode({'address': address}, function(results, status) {
    if (status === 'OK') {
      resultsMap.setCenter(results[0].geometry.location);
      var marker = new google.maps.Marker({
        map: resultsMap,
        position: results[0].geometry.location
      });
    } else {
      alert('Geocode was not successful for the following reason: ' + status);
    }
  });
}


