<?php
// includes/auth_functions.php


function checkAuthentication() {
    if (!isset($_SESSION['logged_in'])) {
        header("Location: index.php");
        exit();
    }
}
?>