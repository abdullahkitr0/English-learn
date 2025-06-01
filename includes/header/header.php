<?php
if (!isset($pageTitle)) {
    $pageTitle = "İngilizce Kelime Öğrenme";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
	<link rel="icon" href="https://anahtar.abdullahki.com/favicon.png" type="image/x-icon">
</head>
<body>
    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>
    
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="<?php echo BASE_URL; ?>">
                        İngilizce Kelime Öğren
                    </a>
                </h1>
                <!-- Dark Mode Toggle -->
                <div class="nav-item me-3">
                    <button id="darkModeToggle" class="btn btn-icon" title="Dark Mode">
                        <i class="ti ti-moon"></i>
                    </button>
                </div>
                <div class="navbar-nav flex-row order-md-last">
                    <?php if (isLoggedIn()): ?>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                                <span class="avatar avatar-sm">
                                    <?php echo substr($_SESSION['username'], 0, 2); ?>
                                </span>
                                <div class="d-none d-xl-block ps-2">
                                    <div><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <a href="<?php echo BASE_URL; ?>/profile.php" class="dropdown-item">Profil</a>
                                <a href="<?php echo BASE_URL; ?>/my-words.php" class="dropdown-item">Kelimelerim</a>
                                <a href="<?php echo BASE_URL; ?>/my-tests.php" class="dropdown-item">Testlerim</a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item">Çıkış Yap</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="nav-item d-none d-md-flex me-3">
                            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-outline-primary">Kayıt Ol</a>
                        </div>
                        <div class="nav-item d-none d-md-flex">
                            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">Giriş Yap</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <!-- Navbar Menu -->
        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Ana Sayfa</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/word-lists.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-book"></i>
                                    </span>
                                    <span class="nav-link-title">Kelime Listeleri</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/tests.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-writing"></i>
                                    </span>
                                    <span class="nav-link-title">Testler</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/flashcards.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-cards"></i>
                                    </span>
                                    <span class="nav-link-title">Sana Özel Flashcards</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/public-flashcards.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-cards"></i>
                                    </span>
                                    <span class="nav-link-title">Flashcards</span>
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings"></i>
                                    </span>
                                    <span class="nav-link-title">Yönetim</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/words.php">
                                        Kelime Yönetimi
                                    </a>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/categories.php">
                                        Kategori Yönetimi
                                    </a>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php">
                                        Kullanıcı Yönetimi
                                    </a>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/tests.php">
                                        Test Yönetimi
                                    </a>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/add-word.php">
                                        Kelime Ekle 
                                    </a>
                                </div>
                            </li>
                            <?php endif; ?>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <!-- Page Body -->
            <div class="page-body"> 