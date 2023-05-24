<!DOCTYPE html>
<html>
<head>
  <title>Phone Sensor Data</title>
<?php
	include("headers.php");
?>
</head>
<body>
  <h1>Phone Sensor Data</h1>
  <button id="startButton" onclick="startRecording()">Start Recording</button>
  <button id="submitButton" onclick="submitSensorData()" disabled>Submit</button>

  <script>
	var geoWatchId = null;
	var lightSensor = null;
	var proximitySensor = null;
	var sensorData = {}; // Global object to store sensor data
	var isRecording = false;

	function log(message) {
		console.log("[Debug] " + message);
	}

	function startRecording() {
		isRecording = true;
		document.getElementById('startButton').disabled = true;
		document.getElementById('submitButton').disabled = false;
		log("Started recording sensor data...");

		// Check for accelerometer data
		if ('DeviceMotionEvent' in window) {
			window.addEventListener('devicemotion', function(event) {
				if (isRecording) {
					sensorData.acceleration = event.acceleration;
					sensorData.rotationRate = event.rotationRate;
				}
			});

			log("Accelerometer data tracking enabled...");
		}

		// Check for geolocation data
		if ('geolocation' in navigator) {
			var geoOptions = {
			enableHighAccuracy: true,
				timeout: 5000,
				maximumAge: 0
			};

			geoWatchId = navigator.geolocation.watchPosition(function(position) {
				if (isRecording) {
					sensorData.latitude = position.coords.latitude;
					sensorData.longitude = position.coords.longitude;
				}
			}, function(error) {
				console.error('Geolocation Error:', error);
			}, geoOptions);
			log("Geolocation tracking enabled (Watch ID: " + geoWatchId + ")...");
		}

		// Check for battery data
		if ('getBattery' in navigator) {
			navigator.getBattery().then(function(battery) {
				if (isRecording) {
					sensorData.batteryLevel = battery.level;
				}
			});
			log("Battery data tracking enabled...");
		}

		// Check for ambient light data
		if ('AmbientLightSensor' in window) {
			lightSensor = new AmbientLightSensor();
			lightSensor.onreading = function() {
				if (isRecording) {
					sensorData.illuminance = lightSensor.illuminance;
				}
			};
			lightSensor.onerror = function(error) {
				console.error('Ambient Light Sensor Error:', error);
			};
			lightSensor.start();
			log("Ambient light data tracking enabled...");
		}

		// Check for proximity data
		if ('ProximitySensor' in window) {
			proximitySensor = new ProximitySensor();
			proximitySensor.onreading = function() {
				if (isRecording) {
					sensorData.proximity = proximitySensor.proximity;
				}
			};
			proximitySensor.onerror = function(error) {
				console.error('Proximity Sensor Error:', error);
			};
			proximitySensor.start();
			log("Proximity data tracking enabled...");
		}
	}

	function submitSensorData() {
		console.log("Current sensor data:", sensorData);
		isRecording = false;


		// Remove event listeners and stop sensors
		try {
			window.removeEventListener('devicemotion');
		} catch (e) {
		}

		try {
			navigator.geolocation.clearWatch(geoWatchId);
		} catch (e) {
		}

		try {
			lightSensor.stop();
		} catch (e) {
		}

		try {
			proximitySensor.stop();
		} catch (e) {
		}

		document.getElementById('startButton').disabled = false;
		document.getElementById('submitButton').disabled = true;
		log("Submitting recorded sensor data...");

		if (Object.keys(sensorData).length > 0) {
			// Prepare JSON data to be submitted
			var jsonData = "new_entry_data=" + JSON.stringify(sensorData);
			log("JSON data to be submitted: " + jsonData);

			// Submit JSON data to the server
			$.ajax({
			url: 'submit.php',
				type: 'POST',
				data: jsonData,
				success: function(response) {
					try {
						log("Server response: " + response);
						toastr.success("Success", JSON.parse(response)["success"]);
					} catch (e) {
						toastr.error("Error", e);
					}
				},
				error: function(xhr, status, error) {
					console.error("AJAX error:", error);
				}
			});

			// Reset sensor data object
			sensorData = {};
		} else {
			console.warn("sensorData is empty");
		}
	}
  </script>
</body>
</html>
