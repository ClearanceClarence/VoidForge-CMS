            </div><!-- .admin-content -->
            
            <footer class="admin-footer">
                <p>Thank you for creating with <strong><?= CMS_NAME ?></strong> <span class="version">v<?= CMS_VERSION ?></span></p>
            </footer>
        </main>
    </div>

    <script src="<?= ADMIN_URL ?>/assets/js/admin.js"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?= ADMIN_URL ?>/assets/js/<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
