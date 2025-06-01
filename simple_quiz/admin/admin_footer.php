    </main>

    <script>
        // Handle mobile menu toggle
        document.querySelector('.menu-toggle')?.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
    <?php if (isset($additional_scripts)): ?>
    <script>
        <?= $additional_scripts ?>
    </script>
    <?php endif; ?>
</body>
</html> 