<?php
	if (!defined('INCLUDED_FROM_INDEX')) {
	    die('This file must be included from index.php');
	}

	$entries_with_geo_coords = find_lat_lon_variables_recursive($entries);
?>
        var events = <?php echo json_encode($entries_with_geo_coords); ?>;

        // Generate iframe with events
        var iframeContent = '';
        for (var i = 0; i < events.length; i++) {
            var event = events[i];
            var md5Hash = JSON.stringify(event.original_entry);
            iframeContent += '<p><a class="event-link" data-md5="' + md5Hash + '">' + md5Hash + '</a></p>';
        }

        var iframe = document.createElement('iframe');
        iframe.setAttribute('srcdoc', iframeContent);
        iframe.style.width = '100%';
        iframe.style.height = '400px';
        document.body.appendChild(iframe);

        // Create a map object
        var map = L.map('map').setView([0, 0], 2); // Set initial center and zoom level

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(map);

        // Create a marker cluster group
        var markerCluster = L.markerClusterGroup();

        // Create an array to store heatmap data
        var heatmapData = [];

        // Iterate through the events
        for (var i = 0; i < events.length; i++) {
            var event = events[i];
            var lat = event.lat;
            var lon = event.lon;

            // Create a marker and add it to the marker cluster group
            var marker = L.marker([lat, lon]);
            markerCluster.addLayer(marker);

            // Add the coordinates to the heatmap data
            heatmapData.push([lat, lon]);
        }

        // Create a heatmap layer
        var heatLayer = L.heatLayer(heatmapData, {
            radius: 25, // Adjust the radius as per your preference
            blur: 15, // Adjust the blur as per your preference
            gradient: {
                0.4: 'blue', // Define the colors and positions in the gradient
                0.6: 'cyan',
                0.7: 'lime',
                0.8: 'yellow',
                1.0: 'red'
            }
        });

        // Add the marker cluster group and the heatmap layer to the map
        markerCluster.addTo(map);
        heatLayer.addTo(map);

        // Fit the map bounds to include both markers and heatmap layer
        map.fitBounds(markerCluster.getBounds());

        // Handle click events on event links
        var eventLinks = document.getElementsByClassName('event-link');
        for (var i = 0; i < eventLinks.length; i++) {
            eventLinks[i].addEventListener('click', function() {
                var md5Hash = this.getAttribute('data-md5');
                // Replace this with your code to show more information about the event
                alert('Event MD5 Hash: ' + md5Hash);
            });
        }
