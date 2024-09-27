<?php
include("../connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Function to extract latitude and longitude from the address field
function extractLatLon($address) {
    $coords = explode('zzz', $address);
    if (count($coords) == 2) {
        return [
            'latitude' => trim($coords[0]),
            'longitude' => trim($coords[1])
        ];
    }
    return false;
}

// Fetch restaurants from the database
$restaurant_sql = "SELECT * FROM restaurant";
$restaurant_result = mysqli_query($db, $restaurant_sql);

$restaurants = [];
while ($row = mysqli_fetch_assoc($restaurant_result)) {
    $coords = extractLatLon($row['address']);  // Assuming 'address' contains latzzzlon format
    if ($coords) {
        $restaurants[] = [
            'id' => $row['rs_id'],  
            'title' => htmlspecialchars($row['title']),  
            'address' => htmlspecialchars($row['address']),  
            'image' => htmlspecialchars($row['image']),  
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude']
        ];
    }
}

// Fetch all users' locations from the database
$user_sql = "SELECT * FROM users"; 
$user_result = mysqli_query($db, $user_sql);

$users = [];
while ($row = mysqli_fetch_assoc($user_result)) {
    $coords = extractLatLon($row['address']); 
    if ($coords) {
        $users[] = [
            'name' => $row['username'],  
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude']
        ];
    }
}

// Function to fetch orders for a specific restaurant
function fetchOrdersAndUserLocations($restaurant_id, $db) {
    $orders_sql = "SELECT o.*, u.username, u.address FROM users_orders o
                   JOIN users u ON o.u_id = u.u_id
                   WHERE o.rs_id = '$restaurant_id' ORDER BY o.date DESC";
    $result = mysqli_query($db, $orders_sql);
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $coords = extractLatLon($row['address']);
        if ($coords) {
            $orders[] = [
                'username' => htmlspecialchars($row['username']),
                'title' => htmlspecialchars($row['title']),
                'quantity' => $row['quantity'],
                'price' => $row['price'],
                'date' => $row['date'],
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude']
            ];
        }
    }
    return $orders;
}

// Calculate distance between two points (Haversine formula)
function calculateDistance($coordsA, $coordsB) {
    $latA = $coordsA[0];
    $lonA = $coordsA[1];
    $latB = $coordsB[0];
    $lonB = $coordsB[1];

    $earthRadius = 6371; // Earth's radius in kilometers
    $dLat = deg2rad($latB - $latA);
    $dLon = deg2rad($lonB - $lonA);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($latA)) * cos(deg2rad($latB)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * c; // Distance in kilometers
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Delivery Routes</title>

    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <style>
        #map { height: 400px; margin-bottom: 20px; }
        .order-list { margin-top: 20px; }
        .order { padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center">Delivery Routes</h3>
        <h3 class="text-center">Map</h3>
        <div id="map"></div>

        <h4 class="text-center">Select a Restaurant</h4>
        <div class="text-center">
            <select id="restaurantSelect" class="form-control" style="width: 50%; margin: 0 auto;">
                <option value="" selected disabled>Select a restaurant</option>
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?php echo $restaurant['id']; ?>" data-lat="<?php echo $restaurant['latitude']; ?>" data-lon="<?php echo $restaurant['longitude']; ?>">
                        <?php echo $restaurant['title']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Orders list -->
        <div id="orderList" class="order-list"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

    <script>
        let map = L.map('map').setView([27.7172, 85.3240], 13);  // Default center (Kathmandu)

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let restaurantMarkers = [];
        let userMarkers = [];

        document.getElementById('restaurantSelect').addEventListener('change', function() {
            const restaurantSelect = document.getElementById('restaurantSelect'); 
            const selectedRestaurantId = restaurantSelect.value;
            const lat = parseFloat(restaurantSelect.options[restaurantSelect.selectedIndex].getAttribute('data-lat'));
            const lon = parseFloat(restaurantSelect.options[restaurantSelect.selectedIndex].getAttribute('data-lon'));

            // Remove existing markers
            if (restaurantMarkers.length > 0) {
                restaurantMarkers.forEach(marker => map.removeLayer(marker));
                restaurantMarkers = [];
            }
            if (userMarkers.length > 0) {
                userMarkers.forEach(marker => map.removeLayer(marker));
                userMarkers = [];
            }

            // Add the selected restaurant marker
            const restaurantMarker = L.marker([lat, lon]).addTo(map)
                .bindPopup(restaurantSelect.options[restaurantSelect.selectedIndex].text)
                .openPopup();
            restaurantMarkers.push(restaurantMarker);

            map.setView([lat, lon], 15);

            fetchOrders(selectedRestaurantId);
        });

        function fetchOrders(restaurantId) {
            const orderList = document.getElementById('orderList');
            orderList.innerHTML = 'Loading orders...';

            fetch(`fetch_orders.php?restaurant_id=${restaurantId}`)
                .then(response => response.json())
                .then(orders => {
                    orderList.innerHTML = '';
                    if (orders.error) {
                        orderList.innerHTML = `<p>${orders.error}</p>`;
                    } else if (orders.length === 0) {
                        orderList.innerHTML = '<p>No orders found for this restaurant.</p>';
                    } else {
                        let userLocations = [];
                        const restaurantSelect = document.getElementById('restaurantSelect');
                        let lat = parseFloat(restaurantSelect.options[restaurantSelect.selectedIndex].getAttribute('data-lat'));
                        let lon = parseFloat(restaurantSelect.options[restaurantSelect.selectedIndex].getAttribute('data-lon'));

                        orders.forEach(order => {
                            orderList.innerHTML += `
                                <div class="order">
                                    <strong>User:</strong> ${order.username}<br>
                                    <strong>Order Details:</strong> ${order.title} - Quantity: ${order.quantity} - Price: ${order.price}<br>
                                    <strong>Order Date:</strong> ${order.date}
                                </div>`;

                            userLocations.push([order.latitude, order.longitude]);

                            const userMarker = L.marker([order.latitude, order.longitude]).addTo(map)
                                .bindPopup(`User: ${order.username}`);
                            userMarkers.push(userMarker);
                        });

                        const optimizedRoute = calculateCheapestInsertion([lat, lon], userLocations);

                        // Debugging: Log the waypoints
                        console.log('Waypoints:', optimizedRoute);

                        if (optimizedRoute.length > 0) {
                            const waypoints = optimizedRoute.map(coords => L.latLng(coords[0], coords[1]));

                            // Initialize the routing machine only if waypoints exist
                            L.Routing.control({
                                waypoints: waypoints,
                                routeWhileDragging: true
                            }).addTo(map);
                        } else {
                            console.error('No valid waypoints available for routing.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching orders:', error);
                });
        }

        // Calculate the optimal route (using a greedy algorithm)
        function calculateCheapestInsertion(restaurantLocation, userLocations) {
            let waypoints = [restaurantLocation];
            let remainingUsers = [...userLocations];

            while (remainingUsers.length > 0) {
                let closestUser = null;
                let minDistance = Infinity;

                remainingUsers.forEach((userLocation, index) => {
                    const distance = calculateDistance(waypoints[waypoints.length - 1], userLocation);
                    if (distance < minDistance) {
                        closestUser = index;
                        minDistance = distance;
                    }
                });

                if (closestUser !== null) {
                    waypoints.push(remainingUsers[closestUser]);
                    remainingUsers.splice(closestUser, 1);
                }
            }

            return waypoints;
        }

        // Distance calculation function
        function calculateDistance(pointA, pointB) {
            const lat1 = pointA[0], lon1 = pointA[1];
            const lat2 = pointB[0], lon2 = pointB[1];

            const R = 6371;  // Earth radius in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);

            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);

            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c;

            return distance;
        }

        // Helper function to convert degrees to radians
        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }
    </script>
</body>
</html>
