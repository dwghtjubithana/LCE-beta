<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wapcore Tender Radar</title>
    <link rel="stylesheet" href="/public/css/tenders.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="/public/js/tenders.js" defer></script>
</head>
<body>
    <div class="page">
        <header class="hero">
            <div class="hero__content">
                <div class="brand">
                    <span class="brand__mark">SC</span>
                    <div>
                        <p class="brand__name">Wapcore</p>
                        <p class="brand__tag">Tender Radar • Suriname</p>
                    </div>
                </div>
                <h1>Vind elke dag nieuwe aanbestedingen</h1>
                <p>Een live overzicht van olie, overheid en bouw. Klik om details te zien en upgrade voor volledige toegang.</p>
                <div class="hero__actions">
                    <button class="btn btn--primary" id="refreshBtn">
                        <i data-lucide="refresh-ccw"></i> Vernieuwen
                    </button>
                    <a class="btn btn--ghost" href="/index.html">Inloggen</a>
                </div>
                <div class="hero__stats">
                    <div>
                        <p class="stat__label">Total</p>
                        <p class="stat__value" id="statTotal">--</p>
                    </div>
                    <div>
                        <p class="stat__label">Nieuw vandaag</p>
                        <p class="stat__value" id="statToday">--</p>
                    </div>
                    <div>
                        <p class="stat__label">Direct Werk</p>
                        <p class="stat__value" id="statDirect">--</p>
                    </div>
                </div>
            </div>
            <div class="hero__panel">
                <div class="panel__glass">
                    <h3>Filters</h3>
                    <div class="field">
                        <label for="filterQuery">Zoeken</label>
                        <input id="filterQuery" type="text" placeholder="Zoek op titel of opdrachtgever">
                    </div>
                    <div class="field">
                        <label for="filterCategory">Categorie</label>
                        <select id="filterCategory">
                            <option value="all">Alle categorieen</option>
                            <option value="oil">Olie & Gas</option>
                            <option value="government">Overheid</option>
                            <option value="construction">Bouw</option>
                            <option value="direct">Direct Werk</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="filterSort">Sorteren</label>
                        <select id="filterSort">
                            <option value="new">Nieuwste eerst</option>
                            <option value="old">Oudste eerst</option>
                        </select>
                    </div>
                    <button class="btn btn--secondary" id="applyFilters">Toepassen</button>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="section__header">
                <h2>Actuele aanbestedingen</h2>
                <p id="feedStatus">Verbinden met Tender Radar...</p>
            </div>
            <div id="tenderGrid" class="grid"></div>
        </main>
    </div>

    <div id="tenderModal" class="modal hidden">
        <div class="modal__backdrop" data-modal-close></div>
        <div class="modal__card">
            <button class="modal__close" data-modal-close><i data-lucide="x"></i></button>
            <div class="modal__content">
                <div>
                    <p class="modal__meta" id="modalMeta">--</p>
                    <h3 id="modalTitle">--</h3>
                    <p id="modalClient" class="modal__client">--</p>
                </div>
                <div class="modal__body" id="modalBody">--</div>
                <div class="modal__actions">
                    <a id="modalLink" class="btn btn--primary" target="_blank" rel="noopener">Bekijk details</a>
                    <button id="modalUpgrade" class="btn btn--ghost">Upgrade naar Business</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
