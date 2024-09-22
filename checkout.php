<!DOCTYPE html>
<html lang="en">
<?php
include("connection/connect.php");
include_once 'product-action.php';
error_reporting(0);
session_start();

function function_alert() { 
    echo "<script>alert('Thank you. Your Order has been placed!');</script>"; 
    echo "<script>window.location.replace('your_orders.php');</script>"; 
} 

if (empty($_SESSION["user_id"])) {
    header('location:login.php');
} else {
    $item_total = 0; // Initialize item total

    // Calculate total amount based on cart items
    if (isset($_SESSION["cart_item"])) {
        foreach ($_SESSION["cart_item"] as $item) {
            $item_total += ($item["price"] * $item["quantity"]);
        }
    }

    if (isset($_POST['submit'])) {
        if ($_POST['mod'] == 'esewa') {
            // Ensure the total is greater than zero
            if ($item_total > 0) {
                // Convert total to paisa (1 Rupee = 100 paisa)
                $total_in_rupees = $item_total; // Total amount in paisa

                // Redirect to eSewa payment page
                echo '
                <form action="https://uat.esewa.com.np/epay/main" method="POST" id="esewa_form">
                    <input value="' . $total_in_rupees . '" name="tAmt" type="hidden">
                    <input value="' . $total_in_rupees . '" name="amt" type="hidden">
                    <input value="0" name="txAmt" type="hidden"> <!-- Transaction amount -->
                    <input value="0" name="psc" type="hidden">
                    <input value="0" name="pdc" type="hidden">
                    <input value="EPAYTEST" name="scd" type="hidden"> <!-- Replace with your actual merchant code -->
                    <input value="' . uniqid() . '" name="pid" type="hidden"> <!-- Unique Order ID -->
                    <input value="http://yourwebsite.com/success.php" type="hidden" name="su"> <!-- Success URL -->
                    <input value="http://yourwebsite.com/failure.php" type="hidden" name="fu"> <!-- Failure URL -->
                </form>
                <script>
                    document.getElementById("esewa_form").submit();
                </script>
                ';
            } else {
                echo "<script>alert('Cart is empty or total amount is invalid.');</script>";
            }
        } else {
            // Insert order into the database for COD
            foreach ($_SESSION["cart_item"] as $item) {
                $SQL = "INSERT INTO users_orders(u_id, title, quantity, price) VALUES('" . $_SESSION["user_id"] . "', '" . $item["title"] . "', '" . $item["quantity"] . "', '" . $item["price"] . "')";
                mysqli_query($db, $SQL);
            }
            unset($_SESSION["cart_item"]);
            function_alert();
        }
    }
}
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="#">
    <title>Checkout</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/animsition.min.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="site-wrapper">
        <header id="header" class="header-scroll top-header headrom">
            <nav class="navbar navbar-dark">
                <div class="container">
                    <button class="navbar-toggler hidden-lg-up" type="button" data-toggle="collapse" data-target="#mainNavbarCollapse">&#9776;</button>
                    <a class="navbar-brand" href="index.php"><img class="img-rounded" src="images/foodrush.png" alt="" width="50" height="50"></a>
                    <div class="collapse navbar-toggleable-md float-lg-right" id="mainNavbarCollapse">
                        <ul class="nav navbar-nav">
                            <li class="nav-item"><a class="nav-link active" href="index.php">Home <span class="sr-only">(current)</span></a></li>
                            <li class="nav-item"><a class="nav-link active" href="restaurants.php">Restaurants <span class="sr-only"></span></a></li>
                            <?php
                            if (empty($_SESSION["user_id"])) {
                                echo '<li class="nav-item"><a href="login.php" class="nav-link active">Login</a></li>
                                      <li class="nav-item"><a href="registration.php" class="nav-link active">Register</a></li>';
                            } else {
                                echo '<li class="nav-item"><a href="your_orders.php" class="nav-link active">My Orders</a></li>';
                                echo '<li class="nav-item"><a href="logout.php" class="nav-link active">Logout</a></li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <div class="page-wrapper">
            <div class="top-links">
                <div class="container">
                    <ul class="row links">
                        <li class="col-xs-12 col-sm-4 link-item"><span>1</span><a href="restaurants.php">Choose Restaurant</a></li>
                        <li class="col-xs-12 col-sm-4 link-item"><span>2</span><a href="#">Pick Your favorite food</a></li>
                        <li class="col-xs-12 col-sm-4 link-item active"><span>3</span><a href="checkout.php">Order and Pay</a></li>
                    </ul>
                </div>
            </div>
            <div class="container">
                <div class="container m-t-30">
                    <form action="" method="post">
                        <div class="widget clearfix">
                            <div class="widget-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="cart-totals margin-b-20">
                                            <div class="cart-totals-title">
                                                <h4>Cart Summary</h4>
                                            </div>
                                            <div class="cart-totals-fields">
                                                <table class="table">
                                                    <tbody>
                                                        <tr>
                                                            <td>Cart Subtotal</td>
                                                            <td><?php echo "Rs." . $item_total; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Delivery Charges</td>
                                                            <td>Free</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-color"><strong>Total</strong></td>
                                                            <td class="text-color"><strong><?php echo "Rs." . $item_total; ?></strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="payment-option">
                                            <ul class="list-unstyled">
                                                <li>
                                                    <label class="custom-control custom-radio m-b-20">
                                                        <input name="mod" id="radioStacked1" checked value="COD" type="radio" class="custom-control-input"> 
                                                        <span class="custom-control-indicator"></span> 
                                                        <span class="custom-control-description">Cash on Delivery</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="custom-control custom-radio m-b-10">
                                                        <input name="mod" type="radio" value="esewa" class="custom-control-input" id="radioStacked2"> 
                                                        <span class="custom-control-indicator"></span> 
                                                        <span class="custom-control-description">eSewa 
                                                            <img src="images/esewa.jpg" alt="" width="90">
                                                        </span> 
                                                    </label>
                                                </li>
                                            </ul>
                                            <p class="text-xs-center"> 
                                                <input type="submit" onclick="return confirm('Do you want to confirm the order?');" name="submit" class="btn btn-success btn-block" value="Order Now"> 
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <footer class="footer">
                <div class="row bottom-footer">
                    <div class="container">
                        <div class="row">
                            <div class="col-xs-12 col-sm-3 payment-options color-gray">
                                <h5>Payment Options</h5>
                                <ul>
                                    <li><a href="#"><img src="images/esewa.jpg" alt="Esewa" width="90"></a></li>
                                    <li><a href="#"><img src="images/cod.jpg" alt="Cash On Delivery" width="90"></a></li>
                                    
                                </ul>
                            </div>
                            <div class="col-xs-12 col-sm-9">
                                <ul class="footer-links">
                                    <li><a href="#">About Us</a></li>
                                    <li><a href="#">Contact Us</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">Terms & Conditions</a></li>
                                </ul>
                                <p class="color-gray">© 2024 All Rights Reserved.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/animsition.min.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
