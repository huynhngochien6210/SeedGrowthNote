<?php

$page = $_GET['page'] ?? 'login';

switch ($page) {

    case 'login':
        $contentFile = 'pages/login.php';
        break;

    case 'signup': 
        $contentFile = 'pages/signup.php'; 
        break; 

    case 'verify': 
        $contentFile = 'pages/verify.php'; 
        break;
    case 'forgot_password':
    $contentFile = 'pages/forgot_password.php';
    break;

    case 'reset_password':
    $contentFile = 'pages/reset_password.php';
    break;    

    case 'home':
        $contentFile = null;
        break;

    default:
        $page = 'login';
        $contentFile = 'pages/login.php';
        break;
}