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
        $page = 'login';
        $contentFile = 'pages/login.php';
        break;
}