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

<!-- ── Modal de Localização ─────────────────────────────────────────────── -->
<div class="modal fade" id="locationModal" tabindex="-1"
     aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">📍 Definir Localização</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary mb-3">
                    Para cálculos astronómicos mais precisos, indique a sua localização geográfica.
                    As coordenadas serão guardadas localmente no seu browser e não serão enviadas para nenhum servidor.
                </p>
                <div class="mb-3">
                    <button type="button" id="locationAutoBtn"
                            class="btn btn-outline-primary w-100"
                            onclick="cvRequestAutoLocation()">
                        📡 Obter automaticamente
                    </button>
                    <div id="locationAutoError" class="text-danger small mt-1 d-none"></div>
                </div>
                <hr>
                <p class="text-muted small mb-2">Ou introduza manualmente:</p>
                <div class="row g-2 mb-2">
                    <div class="col">
                        <label class="form-label small" for="locationLat">Latitude (°)</label>
                        <input type="number" id="locationLat" class="form-control form-control-sm"
                               min="-90" max="90" step="0.000001" placeholder="ex: 38.7169">
                    </div>
                    <div class="col">
                        <label class="form-label small" for="locationLon">Longitude (°)</label>
                        <input type="number" id="locationLon" class="form-control form-control-sm"
                               min="-180" max="180" step="0.000001" placeholder="ex: -9.1399">
                    </div>
                </div>
                <div id="locationFormError" class="text-danger small d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Mais tarde</button>
                <button type="button" class="btn btn-primary"
                        onclick="cvSaveLocationFromForm()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<?php if (($page ?? '') === 'mercury'): ?>
<script src="<?= BASE_PATH ?>/assets/js/mercury.js"></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="<?= BASE_PATH ?>/assets/js/settings.js"></script>
</body>
</html>
