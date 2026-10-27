<?php
//require_once __DIR__ . '/route.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    

    <!-- css-bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <!-- icon bootstrap -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    >

    <!-- js bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chế độ light/dark sẽ chạy sớm nhất trước khi trang vẽ ra màn hình -->
    <script>
        if (localStorage.getItem('theme') == 'dark') {
            document.documentElement.classList.add('dark-mode-preload');
        }
    </script>

    <!-- my css -->
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/darkMode&Respon.css">
    <link rel="stylesheet" href="css/homePage.css">
    <link rel="stylesheet" href="css/note_board.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="css/password.css">
    <link rel="stylesheet" href="css/filter_tags.css">
    <link rel="stylesheet" href="css/verify_email_toast.css">
    <link rel="stylesheet" href="css/shareWithMe.css">
    <link rel="stylesheet" href="css/shareNote.css">

    <!-- css login -->
    <?php // if ($page === 'login'): ?>
        <!-- <link rel="stylesheet" href="css/login.css"> -->
    <?php // endif; ?>

    <title>Seedgrowthnote</title>
</head>

<body>
    <?php //if ($page === 'login'): ?>

        <?php //include 'components/hero.php'; ?>

        <?php //include 'components/blurBG.php'; ?>

    <?php //else: ?> 
        <!-- Import file header vào -->
        <?php include 'components/header.php'; ?>

        <main class="app-main d-flex">
            <div class="app-main-inner d-flex w-100 min-vh-100">
                <!-- Import sidebar -->
                <?php include 'components/sidebar.php'; ?>
                <!-- Import account -->
                <?php include 'components/account.php'; ?>
                <!-- Import password -->
                <?php include 'components/password.php'; ?>
                <!-- Import verify_email_toast -->
                <?php include 'components/verify_email_toast.php'; ?>
                <!-- Import filter tags -->
                <?php include 'components/filter_tags.php'; ?>

                <!-- Import note board -->
                <?php //include 'components/note_board.php';?>
                <!-- Import shared with me -->
                <?php include 'components/shareWithMe.php'; ?>
            </div>
        </main>

        <!-- Import share note -->
        <?php include 'components/shareNote.php'; ?>

    <?php //endif; ?>

    <!-- my javascript -->
     <script src="js/homePage.js"></script>
     <script src="js/account.js"></script>
     <script src="js/password.js"></script>
    <script src="js/note_board.js"></script>
    <script src="js/shareWithMe.js"></script>
</body>
</html>