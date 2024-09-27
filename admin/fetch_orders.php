<?php
include("../connection/connect.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json'); // Set the header to expect JSON output

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

if (isset($_GET['restaurant_id'])) {
    $restaurant_id = $_GET['restaurant_id']; // Get the restaurant ID from query params

    // Fetch orders and user locations for the selected restaurant
    $sql = "SELECT users_orders.o_id, users.username, users.address, users_orders.title, 
            users_orders.quantity, users_orders.price, users_orders.status, 
            users_orders.date 
            FROM users_orders 
            JOIN users ON users.u_id = users_orders.u_id 
            JOIN dishes ON dishes.title = users_orders.title 
            JOIN restaurant ON restaurant.rs_id = dishes.rs_id 
            WHERE restaurant.rs_id = ? LIMIT 0, 25;";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param('i', $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $coords = extractLatLon($row['address']); // Extract latitude and longitude from address
            if ($coords) {
                $orders[] = [
                    'order_id' => $row['o_id'],
                    'username' => $row['username'],
                    'title' => $row['title'],
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                    'status' => $row['status'],
                    'date' => $row['date'],
                    'latitude' => $coords['latitude'],   // Add latitude to the response
                    'longitude' => $coords['longitude']  // Add longitude to the response
                ];
            }
        }

        // Return the orders and user locations as a JSON response
        echo json_encode($orders);
    } else {
        echo json_encode(['error' => 'Error in query preparation']);
    }
} else {
    echo json_encode(['error' => 'No restaurant selected']);
}
?>
