<?php
session_start();
require_once '../auth/auth_functions.php';  // include the auth_functions.php

// Call the logout function
logout();

// Redirect to login page after logout
header('Location: login.php');
exit();
