<?php
require_once 'config/config.php';
require_once 'functions/functions.php';

session_start();

// Oturumu sonlandır
session_unset();
session_destroy();

// Çerezleri temizle
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Ana sayfaya yönlendir
redirect('index.php'); 