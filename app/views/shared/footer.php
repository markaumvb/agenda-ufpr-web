</main>
    
    <footer>
        <div class="container">
            <p><?= date('Y') ?> - <?= APP_NAME ?> v<?= APP_VERSION ?></p>
        </div>
    </footer>
    
    <style>
        footer {
            background-color: #004a8f;
            color: #fff;
            text-align: center;
            padding: 1rem 0;
            margin-top: 2rem;
        }
    </style>
    
    <!-- Script principal do site - Observe o caminho corrigido com /app/ -->
    <script src="<?= PUBLIC_URL ?>/app/assets/js/main.js"></script>
</body>
</html>