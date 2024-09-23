<?php
require_once __DIR__ . '/vendor/autoload.php'; // Load mpdf using Composer's autoload
use Mpdf\Mpdf;
include("connection/connect.php");
session_start();

if (empty($_SESSION['user_id'])) {
    header('location:login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['orderId'])) {
    $orderId = $_POST['orderId'];

    // Query to get order details
    $query = "SELECT users_orders.*, users.username, users.email FROM users_orders 
              JOIN users ON users_orders.u_id = users.u_id 
              WHERE o_id = '$orderId' AND users_orders.u_id = '" . $_SESSION['user_id'] . "'";
    $result = mysqli_query($db, $query);

    if (mysqli_num_rows($result) > 0) {
        $orderData = mysqli_fetch_assoc($result);

        // Prepare the HTML content for the invoice
        $html = "
        <h1>Invoice</h1>
        <p>Order ID: " . $orderData['o_id'] . "</p>
        <p>Customer: " . $orderData['username'] . "</p>
        <p>Email: " . $orderData['email'] . "</p>
        <p>Date: " . $orderData['date'] . "</p>
        <hr>
        <h3>Order Details</h3>
        <table border='1' cellspacing='0' cellpadding='10'>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>" . $orderData['title'] . "</td>
                    <td>" . $orderData['quantity'] . "</td>
                    <td>Rs." . $orderData['price'] . "</td>
                    <td>Rs." . ($orderData['price'] * $orderData['quantity']) . "</td>
                </tr>
            </tbody>
        </table>
        <br><br>
        <h3>Grand Total: Rs." . ($orderData['price'] * $orderData['quantity']) . "</h3>
        <p>Thank you for ordering with us!</p>";

        // Generate PDF using mpdf
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        
        // Set the file name for the invoice
        $filename = "Invoice_" . $orderId . ".pdf";
        
        // Output the PDF to the browser
        $mpdf->Output($filename, 'I'); // 'I' for inline display in browser
    } else {
        echo "Invalid order ID or you do not have permission to view this invoice.";
    }
} else {
    echo "No order ID provided.";
}
?>
