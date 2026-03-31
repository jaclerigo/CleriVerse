    </div><!-- /.container-xl -->
</main>

<footer class="site-footer py-3 mt-auto">
    <div class="container-xl text-center">
        <div class="d-flex flex-column align-items-center gap-1">
            <span>CleriVerse &copy; <?= date('Y') ?> &mdash; Ferramentas para Cálculos Astronómicos</span>
            <span class="footer-note">Algoritmos baseados em Jean Meeus <em>Astronomical Algorithms</em> (VSOP87)</span>
        </div>
    </div>
</footer>

<?php if (($page ?? '') === 'mercury'): ?>
<script src="<?= BASE_PATH ?>/assets/js/mercury.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
