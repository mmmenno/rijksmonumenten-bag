<?php

if(isset($_GET['gemeente'])){
  $qgemeente = $_GET['gemeente'];
}else{
  $qgemeente = "Q9920";
}

if(!file_exists(__DIR__ . "/geojson/" . $qgemeente . ".geojson") || isset($_GET['uncache'])){
  include("geojson.php");
}


include("options.php");


?><!DOCTYPE html>
<html>
<head>
  
<title>Monumenten in Haarlem</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>
  <link rel="stylesheet" href="styles.css" />

  
</head>
<body>

<div id="bigmap"></div>


<div id="legenda">
  <h1>LEGENDA</h1>
  <div class="nobag"></div> 'gebouw' of 'huis' zonder BAGid <br />
  <div class="nobagok"></div> zonder BAGid, misschien terecht <br />
  <div class="bag"></div> monument met BAGid <br />
  <div class="bagcomplex"></div> monument met BAGid, in complex <br />

  <div id="monumentlabel"></div>
  <div id="monumenttype"></div>
  <div id="monumentlink"></div>
  <div id="monumentbag"></div>

  <form>
    <select name="gemeente">
      <?php echo $options ?>
    </select>
    <button>go</button>
  </form>
</div>

<script>
  $(document).ready(function() {
    createMap();
    refreshMap();
  });

  function createMap(){
    center = [52.381016, 4.637126];
    zoomlevel = 16;
    
    map = L.map('bigmap', {
          center: center,
          zoom: zoomlevel,
          minZoom: 1,
          maxZoom: 20,
          scrollWheelZoom: true,
          zoomControl: false
      });

    L.control.zoom({
        position: 'bottomright'
    }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map);
  }

  function refreshMap(){
    $.ajax({
          type: 'GET',
          url: 'geojson/<?= $qgemeente ?>.geojson',
          dataType: 'json',
          success: function(jsonData) {
            if (typeof monuments !== 'undefined') {
              map.removeLayer(monuments);
            }

            monuments = L.geoJson(null, {
              pointToLayer: function (feature, latlng) {                    
                  return new L.CircleMarker(latlng, {
                      color: "#FC2211",
                      radius:4,
                      weight: 1,
                      opacity: 0.8,
                      fillOpacity: 0.8
                  });
              },
              style: function(feature) {
                return {
                    color: getColor(feature.properties),
                    clickable: true
                };
              },
              onEachFeature: function(feature, layer) {
                layer.on({
                    click: whenClicked
                  });
                }
              }).addTo(map);

              monuments.addData(jsonData).bringToFront();
          
              map.fitBounds(monuments.getBounds());
              //$('#straatinfo').html('');
          },
          error: function() {
              console.log('Error loading data');
          }
      });
  }

  function getColor(props) {

    if (typeof props['bagid'] == 'undefined' || props['bagid'] == null) {
      if(props['type'].includes('gebouw') || props['type'].includes('huis')){
        return '#FC0611';
      }else{
        return '#EDDD26';
      }
    }
    if(props['status'] == "in-complex"){
      return '#CD5ED9';
    }
    return '#1DA1CB';
  }

  function whenClicked(){
    $("#intro").hide();

    var props = $(this)[0].feature.properties;
    console.log(props);
    $("#monumentlabel").html('<a target="_blank" href="' + props['wdid'] + '">' + props['label'] + '</a>');
    $("#monumenttype").html(props['type']);
    $("#monumentlink").html('<a target="_blank" href="https://monumentenregister.cultureelerfgoed.nl/monumenten/' + props['mnr'] + '">monument ' + props['mnr'] + '</a>');
    if(props['bagid']!=null){
      $("#monumentbag").html('<a target="_blank" href="http://bag.basisregistraties.overheid.nl/bag/id/pand/' + props['bagid'] + '">' + props['bagid'] + '</a>');
    }else{
      $("#monumentbag").html('');
    }
    
    
  }

</script>



</body>
</html>
