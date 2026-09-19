<?php

require_once "../includes/auth.php";

requireLogin();

$role = getUserRole();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Dashboard' ?></title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">

    <!-- KaiAdmin Plugins -->
    <link rel="stylesheet" href="assets/css/plugins.min.css">

    <!-- KaiAdmin Fonts -->
    <link rel="stylesheet" href="assets/css/fonts.min.css">

    <!-- KaiAdmin -->
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css">

</head>

<body>

    <div class="wrapper">
        <div class="main-panel">
            <!-- Main Header -->
            <div class="main-header">
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <!-- Welcome -->
                        <ul class="navbar-nav topbar-nav ms-auto align-items-center">
                            <li class="nav-item">
                                <span class="nav-link">
                                    Welcome,
                                    <?= htmlspecialchars($_SESSION['name']) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

