<!DOCTYPE html>
<html>
  <head>
    <style>
		body, html {
		  height: 100%;
		  width: 100%;
		  margin:0;
		}
	
       #map {
        height: 100%;
        width: 100%;
       }
	   
	   #popup {
	    position:absolute; top:0px; height:300px; width: 400px; background-color:grey;
	   }
    </style>
  </head>
  <body>
	<script type="text/javascript"> 
	var locations = [ 
	<?php 
		$GLOBALS["connStr"] = 'mysql:host=localhost:3306;dbname=transport';
		$GLOBALS["dbUserName"] = 'root';
		$GLOBALS["dbUserPassword"] = 'ihateprogramming';
		
		$obj_ConnTemp = new PDO($GLOBALS['connStr'], $GLOBALS['dbUserName'], $GLOBALS['dbUserPassword']);
		$obj_ConnTemp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$obj_Select = $obj_ConnTemp->prepare('
			SELECT ASWKT(TP.Coordinate), S.ID, S.Name FROM TrackPoint TP
			INNER JOIN Station S ON S.TrackPointID = TP.ID
		');
		$obj_Select->execute();
		
		$arrCoordinatesAndStations = $obj_Select->fetchAll();
		
		for ($i = 0; $i < count($arrCoordinatesAndStations); ++$i) {
			if ($i != 0) {
				echo ",";
			}
			
			$strCoordinate = str_replace(" ", ",", str_replace(")", "", str_replace('POINT(', '', $arrCoordinatesAndStations[$i][0])));
			$arrStrCoordinate = explode(",", $strCoordinate);
			echo "{ latLng: { lat:" . $arrStrCoordinate[0] . ", lng: " . $arrStrCoordinate[1] . "}, station: { id: " .$arrCoordinatesAndStations[$i][1]  .", name: \"" . $arrCoordinatesAndStations[$i][2] . "\" } }";
			//echo "{ \"latLng\": { \"lat\":" . $arrStrCoordinate[0] . ", \"lng\": " . $arrStrCoordinate[1] . "} }";
			//", stationname: '" . $arrCoordinatesAndStations[$i][1] . "'
			//"\"" . str_replace(" ", ",", str_replace(")", "", str_replace('POINT(', '', $arrCoordinatesAndStations[$i][0]))) . "\"";
		}
		
		$obj_ConnTemp = null;
	?>
	];
	</script>
    <h3>My Google Maps Demo</h3>
    <div id="map"></div>
	<div id="popup">
		<div >Speed <input id="speed" type="text" value="10" /></div>
		<div >Tracks <input id="tracks" type="text" value="2" /></div>
		<div >Station <input id="station" type="text" value="lidcome" /></div>
		<div id="BeginLineContainer"> Begin Line <button id="BeginLineButton" onclick="beginLine()">Begin Line</button></div>
		<div >Line Colour: 
			<select id="ColourSelector">
				<option value="#0000FF">blue</option>
				<option value="#008000">green</option>
				<option value="#FF0000">red</option>
				<option value="#800080">purple</option>
				<option value="#FFFF00">yellow</option>
				<option value="#D38D00">orange</option>
				<option value="#00FFFF">cyan</option>
				<option value="#808080">grey</option>
			</select>
		</div>
		<div id="EndLineContainer">End Line <button id="EndLineButton" onclick="endLine()" disabled="disabled">End Line</button></div>
		<div style="max-height:170px;overflow-y:scroll;background-color:white">
			<table id="tableRecordedLineStations" style="">
				<tbody>
					
				</tbody>
			</table>
		</div>
	</div>
	
	<table id="recordedlocation">
		<tbody>
		
		</tbody>
	</table>
	
    <script
			  src="http://code.jquery.com/jquery-1.12.4.js"
			  integrity="sha256-Qw82+bXyGq6MydymqBxNPYTaUXXq7c8v3CwiYwLLNXU="
			  crossorigin="anonymous"></script>
	<script 
		src="./jquery-ui-1.12.1.custom/jquery-ui.js">
	</script>
	<script>
      var marker = null;
	  var poly = null;
	  var map = null;
	  var placeservice = null;
	  
	  var lineStations = [];
	  
	  $("#popup").draggable();
	  
	  function beginLine() {
		 lineStations = [];
		 
		 var colourSelector = document.getElementById("ColourSelector");
		 var selectedValue = colourSelector.options[colourSelector.selectedIndex].value;

		  poly = new google.maps.Polyline({
			  strokeColor: selectedValue,
			  strokeOpacity: 1.0,
			  strokeWeight: 3,
			  map: map
		  });
		  
		  document.getElementById("BeginLineButton").disabled = true;
		  document.getElementById("EndLineButton").disabled = false;
	  }
	  
	  function endLine() {
		  poly = null;
		  
		  document.getElementById("BeginLineButton").disabled = false;
		  document.getElementById("EndLineButton").disabled = true;
		  
		  if (confirm("Do you wish to save this line?")) {
			  // save the line
			  // clear everything
		  }
	  }
	  
	  function initMap() {
          map = new google.maps.Map(document.getElementById('map'), {
		  center: {lat: -33.9007636, lng: 151.0219963},
          zoom: 14,
          styles: [{
            featureType: 'poi',
            stylers: [{ visibility: 'off' }]  // Turn off POI.
          },
          {
            featureType: 'transit.station',
            stylers: [{ visibility: 'on' }]  // Turn off bus, train stations etc.
          }],
          disableDoubleClickZoom: true,
          streetViewControl: false,
        });
		
		placeservice = new google.maps.places.PlacesService(map);
		
		poly = new google.maps.Polyline({
          strokeColor: '#000000',
          strokeOpacity: 1.0,
          strokeWeight: 3,
		  map: map
        });
		
		map.addListener('click', function(e) {
		  recordClickOnMap(e);
        });
		
		google.maps.event.addListenerOnce(map, 'idle', function(){
			// do something only the first time the map is loaded
			for (var i = 0; i < locations.length; ++i) {
				createMarker(locations[i].latLng, locations[i].station.id + "|" + locations[i].station.name);
			}
		});
      }
	  
	  function getPlaceDetails(placeId, newCell) {
		  $("#station").css("background-color", "red");
		  placeservice.getDetails({placeId: placeId}, function(place, status) {
			  if (status === 'OK') {
				$("#station").val(place.name);
			  }
			  else {
				$("#station").val("");
			  }
			  $("#station").removeAttr("style");
			  
			  newText = document.createTextNode($("#station").val());
			  newCell.appendChild(newText);
			});
		}
	  
	  function recordClickOnMap (e) {
		if ((document.getElementById("BeginLineButton").disabled == true) && (1 == 0)) {
			var tableRef = document.getElementById('recordedlocation').getElementsByTagName('tbody')[0];

			// Insert a row in the table at the last row
			var newRow  = tableRef.insertRow(tableRef.rows.length);

			// Insert a cell in the row at index 0
			var newCell  = newRow.insertCell(0);

			// Append a text node to the cell
			var newText  = document.createTextNode(e.latLng.lat() + ", " + e.latLng.lng());
			newCell.appendChild(newText);
			
			newCell  = newRow.insertCell(1);
			newText = document.createTextNode($("#speed").val());
			newCell.appendChild(newText);
			
			newCell  = newRow.insertCell(2);
			newText = document.createTextNode($("#tracks").val());
			newCell.appendChild(newText);

			newCell  = newRow.insertCell(3);
			if (e.placeId) {
				getPlaceDetails(e.placeId, newCell);
			  }
			  else {
				$("#station").val("");
			}
			
			
			var path = poly.getPath();

			// Because path is an MVCArray, we can simply append a new coordinate
			// and it will automatically appear.
			path.push(e.latLng);

			// Add a new marker at the new plotted point on the polyline.
			createMarker(e.latLng, '#' + path.getLength());
		}
		else {
			alert("Please begin line and please click on a marker only");
		}
	  }
	  
	  function markerOnClick(marker) {
		if (document.getElementById("BeginLineButton").disabled == true) {
			lineStations.push(marker);
			
			var newTableRow = insertNewTableRow('tableRecordedLineStations');
			
			var idNameSplit = marker.title.split("|");
			
			insertNewTableCell_WithTextNodeAndText(newTableRow, 0, idNameSplit[0]);
			insertNewTableCell_WithTextNodeAndText(newTableRow, 1, idNameSplit[1]);
			insertNewTableCell_WithHtmlElement(newTableRow, 2, "button", "Delete me", function () { alert(1); });
		}
		else {
			alert("Please begin line");
		}
	  }
	  
	  function createMarker(latLng,markerTitle) {

		var marker = new google.maps.Marker({
          position: latLng,
          title: markerTitle,
          map: map
        });
		
		google.maps.event.addListener(marker, 'click', function(e){
			markerOnClick(marker);
		});
	  }
	  
	  function getTableBody(tableID) {
		  return document.getElementById(tableID).getElementsByTagName('tbody')[0];
	  }
	  
	  function insertNewTableRow(tableID) {
		  var tbody = getTableBody(tableID);
		  return tbody.insertRow(tbody.rows.length);
	  }
	  
	  function insertNewTableCell_WithTextNodeAndText(tableRow, index, text) {
		  var newCell = tableRow.insertCell(index);
		  newText = document.createTextNode(text);
		  newCell.appendChild(newText);
		  return newCell;
	  }
	  
	  function insertNewTableCell_WithHtmlElement(tableRow, index, element, text) {
		  var newCell = tableRow.insertCell(index);
		  newText = document.createTextNode(text);
		  newElement = document.createElement(element);
		  newElement.appendChild(newText);
		  newCell.appendChild(newElement);
		  return newCell;
	  }
	  
    </script>
    <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAblck7LelTxyeWmBIxF5RMQ0Wc8wtmKmM&callback=initMap&libraries=places">
    </script>
  </body>
</html>