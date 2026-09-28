<?php

$page = $_GET['page'] ?? 'login';

if ($page === 'login') {
    $contentFile = 'pages/login.php';
} elseif ($page === 'home') {
    $contentFile = null;
} else {
    $page = 'login';
    $contentFile = 'pages/login.php';
}