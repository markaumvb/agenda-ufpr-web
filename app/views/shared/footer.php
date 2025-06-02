</div><!-- fechando a div container -->
    </main><!-- fechando a main-content -->
</div><!-- fechando a layout-container -->

<footer class="app-footer">
    <div class="container">
        <p><?= date('Y') ?> - <?= APP_NAME ?> v<?= APP_VERSION ?></p>
    </div>
</footer>

<?php 
// Identificar a página atual
$currentUri = $_SERVER['REQUEST_URI'];

// CORREÇÃO: Página inicial - incluir JS específico para home
if ($currentUri == '/' || $currentUri == '/agenda_ufpr/' || $currentUri == '/agenda_ufpr/index.php' || 
    strpos($currentUri, '/?') !== false || strpos($currentUri, '/agenda_ufpr/?') !== false) {
    echo '<script src="' . PUBLIC_URL . '/app/assets/js/home.js"></script>';
}

// Outros JavaScripts específicos por página
if (strpos($currentUri, '/notifications') !== false) {
    echo '<script src="' . PUBLIC_URL . '/app/assets/js/notifications-page.js"></script>';
}

if (strpos($currentUri, '/compromissos') !== false && strpos($currentUri, '/meuscompromissos') === false) {
    echo '<script src="' . PUBLIC_URL . '/app/assets/js/compromissos-calendar.js"></script>';
}
?>

<style>
    footer {
        background-color: #004a8f;
        color: #fff;
        text-align: center;
        padding: 1rem 0;
        margin-top: 2rem;
    }
    
    .app-footer {
        background-color: #004a8f;
        color: #fff;
        text-align: center;
        padding: 1rem 0;
        margin-top: 2rem;
    }
</style>

<!-- Scripts base -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/main.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/sidebar.js"></script>

</body>
</html>