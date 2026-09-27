<?php

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        $contentFile = 'pages/login.php';
        break;

    case 'home':
        $contentFile = null;
        break;

    default:
        $contentFile = 'pages/login.php';
        break;
}