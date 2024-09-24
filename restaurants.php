<!DOCTYPE html>
<html lang="en">
<?php
include("connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Restaurants</title>
    
    <!-- Bootstrap CSS (Using Bootstrap 3 for Tether.js compatibility) -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Animsition CSS -->
    <link href="css/animsition.min.css" rel="stylesheet">
    
    <!-- Animate CSS -->
    <link href="css/animate.css" rel="stylesheet">
    
    <!-- Custom Style CSS -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    
    <style>
        #map { height: 400px; }
    </style>
</head>

<body>
<header id="header" class="header-scroll top-header headrom">
    <nav class="navbar navbar-inverse"> <!-- Changed to navbar-inverse for Bootstrap 3 -->
        <div class="container">
     
            <div class="collapse navbar-collapse" id="mainNavbarCollapse">
            <img class="img-rounded" src="images/foodrush.png" alt="" width="50" height="50"> 
                <ul class="nav navbar-nav navbar-right">
                    <li class="active"> 
                        <a href="index.php">Home</a> 
                    </li>
                    <li class="active"> 
                        <a href="restaurants.php">Restaurants</a> 
                    </li>
                    <?php
                    if(empty($_SESSION["user_id"])) {
                        echo '<li><a href="login.php">Login</a></li>
                              <li><a href="registration.php">Register</a></li>';
                    } else {
                        echo  '<li><a href="your_orders.php">My Orders</a></li>';
                        echo  '<li><a href="logout.php">Logout</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
</header><br>

<h3 class="text-center">List Of Restaurants</h3>

<div class="page-wrapper">
    <section class="restaurants-page">
        <div class="container">
            <div class="row">
                <!-- You can add a sidebar or other content here if needed -->
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="clearfix">
                        <h3 style="font-weight: bold; text-decoration: underline; margin-left: 2%;">List Of Restaurants</h3>
                        <button id="nearestRestaurantBtn" class="btn btn-primary pull-right" style="margin-top: -40px;">
                            Show Nearest Restaurant From Me
                        </button>
                    </div>
                    <br>
                    <div class="bg-gray restaurant-entry">
                        <br>    
                        <div class="row">
                            <?php
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

                                            // Display restaurants
                                            foreach ($restaurants as $restaurant) {
                                                echo '
                                                <div class="col-sm-12 col-md-12 col-lg-8">
                                                    <div class="entry-logo">
                                                        <a class="img-fluid" href="dishes.php?res_id=' . $restaurant['id'] . '">
                                                            <img src="admin/Res_img/' . $restaurant['image'] . '" alt="Food logo" class="img-responsive">
                                                        </a>
                                                    </div>
                                                    <div class="entry-dscr">
                                                        <h5><a href="dishes.php?res_id=' . $restaurant['id'] . '">' . $restaurant['title'] . '</a></h5>
                                                        <span class="restaurant-address" data-lat="' . $restaurant['latitude'] . '" data-lon="' . $restaurant['longitude'] . '">
                                                            Fetching address...
                                                        </span>
                                                        <p>Distance: ' . round($restaurant['distance'], 2) . ' km</p>
                                                        <a href="dishes.php?res_id=' . $restaurant['id'] . '" class="btn btn-primary">View Menu</a>
                                                        <button class="btn btn-info" onclick="showRouteOnMap(' . $user_lat . ', ' . $user_lon . ', ' . $restaurant['latitude'] . ', ' . $restaurant['longitude'] . ')">Show Destination</button>
                                                    </div><br>
                                                </div>';
                                            }
                                        } else {
                                            echo "<div class='col-sm-12'><p>No restaurants found.</p></div>";
                                        }
                                    } else {
                                        echo "<div class='col-sm-12'><p>Error in user address format: " . htmlspecialchars($user_data['address']) . "</p></div>";
                                    }
                                } else {
                                    echo "<div class='col-sm-12'><p>User not found.</p></div>";
                                }
                            } else {
                                echo "<div class='col-sm-12'><p>Please <a href='login.php'>login</a> to view restaurants.</p></div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Modal -->
    <div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Route to Restaurant</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
</div><br>

<footer class="footer">
    <div class="container">
    <div class="bottom-footer">
                    <div class="row">
                        <div class="col-xs-12 col-sm-3 payment-options color-gray">
                            <h5>Payment Options</h5>
                            <ul>
                            <li><a href="#"><img src="images/esewa.jpg" alt="Esewa" width="90"></a></li>
                            <li><a href="#"><img src="images/cod.jpg" alt="Cash On Delivery" width="90"></a></li>

                            </ul>
                        </div>
                        <div class="col-xs-12 col-sm-4 address color-gray">
                                    <h5>Address</h5>
                                    <p>Kalimati Kathmandu</p>
                                    <h5>Phone: 9875696933</a></h5> </div>
                                <div class="col-xs-12 col-sm-5 additional-info color-gray">
                                    <h5>Addition informations</h5>
                                   <p>Join thousands of other restaurants who benefit from having partnered with us.</p>
                                </div>
                    </div>
                </div>
    </div>
</footer>

<!-- JS Files -->

<!-- jQuery (Using CDN for reliability) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Tether.js (Required for Bootstrap 3 tooltips/popovers) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.7/js/tether.min.js"></script>

<!-- Bootstrap JS (Using Bootstrap 3) -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<!-- Animsition JS -->
<script src="js/animsition.min.js"></script>

<!-- Leaflet JS (Using CDN to prevent 404 errors) -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<!-- Custom Script (Ensure this exists or remove if not needed) -->
<!-- <script src="js/script.js"></script> -->

<!-- Leaflet Map Initialization Script -->
<script>
    // Handle button click to redirect to nearrestaurant.php
    document.getElementById('nearestRestaurantBtn').addEventListener('click', function() {
        window.location.href = 'nearrestaurant.php';
    });
</script>

<script>
    let map;

    function showRouteOnMap(userLat, userLon, resLat, resLon) {
        console.log("Showing map modal");
        $('#mapModal').modal('show');

        // Remove existing map instance if it exists
        if (map !== undefined) {
            map.remove();
            map = undefined;
        }

        // Initialize the map after modal is fully shown
        $('#mapModal').on('shown.bs.modal', function () {
            console.log("Initializing map");
            map = L.map('map').setView([userLat, userLon], 13);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Add user marker
            L.marker([userLat, userLon]).addTo(map)
                .bindPopup('Your Location')
                .openPopup();

            // Add restaurant marker
            L.marker([resLat, resLon]).addTo(map)
                .bindPopup('Restaurant Location')
                .openPopup();

            // Fit the map bounds to include both markers
            const bounds = L.latLngBounds([[userLat, userLon], [resLat, resLon]]);
            map.fitBounds(bounds);

            // Ensure the map resizes correctly
            map.invalidateSize();
        });

        // Reload the page when modal is hidden to refresh the whole page
        $('#mapModal').on('hidden.bs.modal', function () {
            if (map !== undefined) {
                map.remove();
                map = undefined;
            }

            // Reload the page
            location.reload();
        });
    }
    function reverseGeocode(lat, lon, callback) {
        const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`;

        // Make a GET request to the Nominatim API
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.address) {
                    const address = `${data.address.road || ''}, ${data.address.city || data.address.town || ''}, ${data.address.country || ''}`;
                    callback(address);
                } else {
                    callback("Address not found");
                }
            })
            .catch(error => {
                console.error('Error fetching address:', error);
                callback("Error fetching address");
            });
    }

    // Function to update the restaurant addresses
    function updateRestaurantAddresses() {
        const restaurantElements = document.querySelectorAll('.restaurant-address');

        restaurantElements.forEach(element => {
            const lat = element.getAttribute('data-lat');
            const lon = element.getAttribute('data-lon');

            // Perform reverse geocoding for each restaurant
            reverseGeocode(lat, lon, function (address) {
                element.textContent = address;
            });
        });
    }

    // Call the function when the page loads
    document.addEventListener('DOMContentLoaded', function () {
        updateRestaurantAddresses();
    });
</script>
</body>
</html>
