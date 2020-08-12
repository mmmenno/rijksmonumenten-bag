<?php

ini_set('memory_limit', '1024M');

$sparqlQueryString = "
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX bag: <http://bag.basisregistraties.overheid.nl/def/bag#>
SELECT DISTINCT ?item ?typeofLabel ?itemLabel ?status ?monnr ?bagid ?coords WHERE { 
    ?item wdt:P359 ?monnr .
    ?item wdt:P131 wd:" . $qgemeente . " .
    ?item wdt:P31 ?typeof .
    ?item wdt:P625 ?coords .
    OPTIONAL {
      ?item wdt:P361 ?groterding .
      ?groterding wdt:P31 wd:Q1497375 .
      BIND('in-complex' AS ?status) .
    }
    SERVICE wikibase:label { bd:serviceParam wikibase:language \"nl\". }
    OPTIONAL{
      ?item wdt:P5208 ?bagid .
    }
}
LIMIT 2000
";

$endpointUrl = 'https://query.wikidata.org/sparql';
$url = $endpointUrl . '?query=' . urlencode($sparqlQueryString) . "&format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch,CURLOPT_USERAGENT,'MonumentMap');
$headers = [
    'Accept: application/sparql-results+json'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec ($ch);
curl_close ($ch);

$data = json_decode($response, true);



// QUERY BAG APART

$sparqlQueryString = "
PREFIX geo: <http://www.opengis.net/ont/geosparql#>
PREFIX bag: <http://bag.basisregistraties.overheid.nl/def/bag#>
PREFIX pand: <http://bag.basisregistraties.overheid.nl/bag/id/pand/>
SELECT DISTINCT ?baguri ?wkt WHERE { 
    VALUES ?baguri { ";

foreach ($data['results']['bindings'] as $k => $v) {
	if(strlen($v['bagid']['value'])){
		$sparqlQueryString .= "pand:" . $v['bagid']['value'] . " ";
		$i++;
	}
}

$sparqlQueryString .= "}
    graph ?pandVoorkomen {
      ?baguri geo:hasGeometry/geo:asWKT ?wkt .
    }
    filter not exists { ?pandVoorkomen bag:eindGeldigheid [] } 
      
}
LIMIT 10000
";

unset($response);

$endpointUrl = 'https://bag.basisregistraties.overheid.nl/sparql';
//$url = $endpointUrl . '?query=' . urlencode($sparqlQueryString) . "&format=json";

$curldata = "query=" . urlencode($sparqlQueryString);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$endpointUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $curldata);
curl_setopt($ch,CURLOPT_USERAGENT,'MonumentMap');
$headers = [
    'Accept: application/sparql-results+json'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec ($ch);
curl_close ($ch);

$bagdata = json_decode($response, true);

$wkts = array();
if(isset($bagdata['results']['bindings'])){
	foreach ($bagdata['results']['bindings'] as $k => $v) {
		$bagid = str_replace("http://bag.basisregistraties.overheid.nl/bag/id/pand/","",$v['baguri']['value']);
		$wkts[$bagid] = $v['wkt']['value'];
	}
}else{
	"no results from endpoint " . $endpointUrl;
	die;
}



$fc = array("type"=>"FeatureCollection", "features"=>array());

$beenthere = array();

foreach ($data['results']['bindings'] as $k => $v) {

	// we don't want multiple features of one wikidata item, just because it has multiple 'types'
	if(in_array($v['item']['value'] . "-" . $v['bagid']['value'],$beenthere)){
		continue;
	}
	$beenthere[] = $v['item']['value'] . "-" . $v['bagid']['value'];


	$monument = array("type"=>"Feature");
	$props = array(
		"wdid" => $v['item']['value'],
		"label" => $v['itemLabel']['value'],
		"mnr" => $v['monnr']['value'],
		"bagid" => $v['bagid']['value'],
		"type" => $v['typeofLabel']['value']
	);
	if(strlen($v['status']['value'])){
		$props['status'] = $v['status']['value'];	
	}else{
		$props['status'] = "m";
	}
	if(isset($wkts[$v['bagid']['value']])){
		$monument['geometry'] = wkt2geojson($wkts[$v['bagid']['value']]);	
	}else{
		$coords = str_replace(array("Point(",")"), "", $v['coords']['value']);
		$latlon = explode(" ", $coords);
		$monument['geometry'] = array("type"=>"Point","coordinates"=>array((double)$latlon[0],(double)$latlon[1]));
	}
	$monument['properties'] = $props;
	$fc['features'][] = $monument;

}

$json = json_encode($fc);

file_put_contents("geojson/" . $qgemeente . '.geojson', $json);










function wkt2geojson($wkt){
	$coordsstart = strpos($wkt,"(");
	$type = trim(substr($wkt,0,$coordsstart));
	$coordstring = substr($wkt, $coordsstart);

	switch ($type) {
	    case "LINESTRING":
	    	$geom = array("type"=>"LineString","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", trim($v));
	    		$geom['coordinates'][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "POLYGON":
	    	$geom = array("type"=>"Polygon","coordinates"=>array());
			preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "MULTILINESTRING":
	    	$geom = array("type"=>"MultiLineString","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "POINT":
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$coords = explode(" ", $coordstring);
	    	//print_r($coords);
	    	$geom = array("type"=>"Point","coordinates"=>array((double)$coords[0],(double)$coords[1]));
	    	return $geom;
	        break;
	}
}







