<?php
include("connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Function to extract latitude and longitude from the address field
function extractLatLon($address) {
    $coords = explode('zzz', $address);
    if(count($coords) == 2) {
        return [
            'latitude' => trim($coords[0]),
            'longitude' => trim($coords[1])
        ];
    }
    return false;
}

// Function to calculate the distance between two coordinates using the Haversine formula
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;  // Earth radius in kilometers
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

if(isset($_SESSION['user_id'])) {
    $user_id = mysqli_real_escape_string($db, $_SESSION['user_id']);
    $user_query = mysqli_query($db, "SELECT address FROM users WHERE u_id = '$user_id'");

    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_query);
        $user_coords = extractLatLon($user_data['address']);

        if($user_coords) {
            $user_lat = $user_coords['latitude'];
            $user_lon = $user_coords['longitude'];

            $ress = mysqli_query($db, "SELECT rs_id, title, address, image FROM restaurant");

            if ($ress && mysqli_num_rows($ress) > 0) {
                $restaurants = [];

                while($rows = mysqli_fetch_assoc($ress)) {
                    $res_coords = extractLatLon($rows['address']);
                    if($res_coords) {
                        $res_lat = $res_coords['latitude'];
                        $res_lon = $res_coords['longitude'];
                        $distance = haversineDistance($user_lat, $user_lon, $res_lat, $res_lon);

                        $restaurants[] = [
                            'id' => $rows['rs_id'],
                            'title' => htmlspecialchars($rows['title']),
                            'address' => htmlspecialchars($rows['address']),
                            'image' => htmlspecialchars($rows['image']),
                            'latitude' => $res_lat,
                            'longitude' => $res_lon,
                            'distance' => $distance
                        ];
                    }
                }

                // Sort restaurants by distance
                usort($restaurants, function($a, $b) {
                    return $a['distance'] - $b['distance'];
                });

            } else {
                echo "No restaurants found.";
            }
        } else {
            echo "Error in user address format: " . htmlspecialchars($user_data['address']);
        }
    } else {
        echo "User not found.";
    }
} else {
    echo "Please login to view restaurants.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Nearest Restaurants</title>

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" /> <!-- Added Leaflet Control Geocoder CSS -->

    <style>
        #map { height: 400px; margin-bottom: 20px; }
        .restaurant-list { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center">Find the Nearest Restaurants</h3>
        <div id="map"></div>

        <div class="restaurant-list">
            <h4>List of Restaurants (Nearest to Farthest)</h4>
            <div class="row">
                <?php if (!empty($restaurants)): ?>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <div class="col-md-4">
                                        <img src="admin/Res_img/<?php echo $restaurant['image']; ?>" alt="Restaurant" class="img-responsive">
                                    </div>
                                    <div class="col-md-8">
                                        <h4><?php echo $restaurant['title']; ?></h4>
                                        <span class="restaurant-address" data-lat="<?php echo $restaurant['latitude']; ?>" data-lon="<?php echo $restaurant['longitude']; ?>">
                                            Fetching address...
                                        </span>
                                        <p>Distance: <?php echo round($restaurant['distance'], 2); ?> km</p>
                                        <a href="dishes.php?res_id=<?php echo $restaurant['id']; ?>" class="btn btn-primary">View Menu</a>
                                        <button class="btn btn-info" onclick="showRouteOnMap(<?php echo $user_lat; ?>, <?php echo $user_lon; ?>, <?php echo $restaurant['latitude']; ?>, <?php echo $restaurant['longitude']; ?>)">Show Route</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No restaurants available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .restaurant-list .panel {
            margin-bottom: 20px;
        }
        .restaurant-list .panel-body {
            display: flex;
            align-items: center;
        }
        .restaurant-list .panel-body .col-md-4 {
            flex: 0 0 33.3333%;
            max-width: 33.3333%;
        }
        .restaurant-list .panel-body .col-md-8 {
            flex: 0 0 66.6667%;
            max-width: 66.6667%;
        }
    </style>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script> <!-- Added Leaflet Control Geocoder JS -->

    <script>
        let map = L.map('map').setView([<?php echo $user_lat; ?>, <?php echo $user_lon; ?>], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        L.marker([<?php echo $user_lat; ?>, <?php echo $user_lon; ?>]).addTo(map)
            .bindPopup('Your Location')
            .openPopup();

        <?php foreach ($restaurants as $restaurant): ?>
        L.marker([<?php echo $restaurant['latitude']; ?>, <?php echo $restaurant['longitude']; ?>]).addTo(map)
            .bindPopup('<?php echo $restaurant['title']; ?>');
        <?php endforeach; ?>

        let routingControl = null; // Declare a variable to store the routing control

        function showRouteOnMap(userLat, userLon, resLat, resLon) {
            // Remove the previous route, if any
            if (routingControl) {
                map.removeControl(routingControl);
            }

            // Use Leaflet Routing Machine to calculate and display the route
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userLat, userLon),
                    L.latLng(resLat, resLon)
                ],
                lineOptions: {
                    styles: [{ color: 'blue', opacity: 0.6, weight: 4 }]
                },
                routeWhileDragging: true,
                geocoder: L.Control.Geocoder.nominatim() // Now this should work correctly
            }).addTo(map);
        }

        function reverseGeocode(lat, lon, callback) {
            const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.address) {
                        const address = `${data.address.road || ''}, ${data.address.city || ''}, ${data.address.country || ''}`;
                        callback(address.trim());
                    } else {
                        callback("Address not found.");
                    }
                })
                .catch(error => {
                    console.error("Error in reverse geocoding:", error);
                    callback("Error in reverse geocoding.");
                });
        }

        document.querySelectorAll('.restaurant-address').forEach(function(element) {
            const lat = element.getAttribute('data-lat');
            const lon = element.getAttribute('data-lon');
            reverseGeocode(lat, lon, function(address) {
                element.textContent = address;
            });
        });
    </script>
</body>
</html>
