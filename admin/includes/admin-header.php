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

        <?php require_once "includes/admin-sidebar.php"; ?>

        <div class="main-panel">

            <div class="main-header">

                <nav class="navbar border-bottom">

                    <div class="container-fluid">

                        <button
                            type="button"
                            onclick="document.documentElement.classList.toggle('nav_open')"
                            style="
                    display: none;
                    border: none;
                    background: transparent;
                    font-size: 24px;
                    padding: 8px;
                    cursor: pointer;
                "
                            id="mobileMenuButton">
                            ☰
                        </button>

                        <div style="margin-left: auto;">
                            Welcome,
                            <?= htmlspecialchars($_SESSION['name']) ?>
                        </div>

                    </div>

                </nav>

            </div>

            <script>
                if (window.innerWidth <= 991) {
                    document.getElementById('mobileMenuButton').style.display = 'block';
                }
            </script>