<?php require_once 'route.php'?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <!-- css-boostrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <!-- icon boostrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Chế độ light/dark sẽ chạy sớm nhất trước khi trang vẽ ra màn hình -->
    <script>
        if(localStorage.getItem('theme') == 'dark'){
            document.documentElement.classList.add('dark-mode-preload');
        }
    </script>
    <!-- my css -->
     <link rel="stylesheet" href="css/homePage.css">
    <title>Seedgrowthnote</title>
</head>
<body>
    <!-- Import file header vào -->
    <?php include 'components/header.php'; ?>
    <main class="app-main d-flex">
        <div class="app-main-inner d-flex w-100 min-vh-100">
            <!-- Import sidebar -->
            <?php include 'components/sidebar.php'; ?>
            <div class="content-area flex-grow-1">
                <?php include $contentFile; ?>
            </div>
        </div>
    </main>


    <!-- js-boostrap -->
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <!-- my javascript -->
     <script src="js/homePage.js"></script>
     <script src=''></script>
</body>
</html>