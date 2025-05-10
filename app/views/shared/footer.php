</div><!-- fechando a div container -->
    </main><!-- fechando a main-content -->
</div><!-- fechando a layout-container -->

<footer class="app-footer">
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
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/approval-modal.js"></script>

</body>
</html>