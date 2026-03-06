<?php
/**
 * includes/footer.php
 *
 * Closes the page wrapper and loads JS.
 */
$rootPath = $rootPath ?? '';
?>
</div><!-- /.container -->
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> AutoCare Workshop. All rights reserved.</p>
    </div>
</footer>

</div><!-- /.page-wrapper -->
<script src="<?= $rootPath ?>assets/js/main.js"></script>
</body>
</html>
