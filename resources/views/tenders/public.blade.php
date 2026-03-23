<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuriCore LCE Tender Radar</title>
    <link rel="stylesheet" href="/public/css/tenders.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="/public/js/tenders.js" defer></script>
</head>
<body>
    <div class="page">
        <header class="hero hero--showcase">
            <div class="hero__orb hero__orb--one"></div>
            <div class="hero__orb hero__orb--two"></div>
            <div class="hero__copy">
                <div class="brand">
                    <span class="brand__mark">SC</span>
                    <div>
                        <p class="brand__name">SuriCore LCE</p>
                        <p class="brand__tag">Aanbestedingen radar voor Suriname</p>
                    </div>
                </div>

                <div class="hero__pill">
                    <span>Live tender feed</span>
                    <span>Staatsolie, Rosebel, overheid en meer</span>
                </div>

                <h1>Actuele aanbestedingen uit de Surinaamse markt, helder gepresenteerd.</h1>
                <p class="hero__intro">
                    Ontdek relevante opdrachten, volg deadlines en verken kansen uit olie, overheid, infra en mijnbouw in een modern overzicht.
                </p>

                <div class="hero__actions">
                    <button class="btn btn--primary" id="refreshBtn">
                        <i data-lucide="refresh-ccw"></i>
                        Vernieuwen
                    </button>
                    <a class="btn btn--ghost" href="/index.html">Inloggen</a>
                </div>

                <div class="hero__stats">
                    <div class="stat">
                        <p class="stat__label">Actieve items</p>
                        <p class="stat__value" id="statTotal">--</p>
                    </div>
                    <div class="stat">
                        <p class="stat__label">Deadline deze week</p>
                        <p class="stat__value" id="statToday">--</p>
                    </div>
                    <div class="stat">
                        <p class="stat__label">Direct werk</p>
                        <p class="stat__value" id="statDirect">--</p>
                    </div>
                </div>
            </div>

            <aside class="hero__panel panel">
                <div class="panel__eyebrow">Feed Controls</div>
                <h2>Filter de markt in seconden</h2>
                <div class="field">
                    <label for="filterQuery">Zoeken</label>
                    <input id="filterQuery" type="text" placeholder="Titel, opdrachtgever, locatie">
                </div>
                <div class="field__row">
                    <div class="field">
                        <label for="filterCategory">Sector</label>
                        <select id="filterCategory">
                            <option value="all">Alle sectoren</option>
                            <option value="oil">Olie & Gas</option>
                            <option value="government">Overheid</option>
                            <option value="mining">Mijnbouw</option>
                            <option value="construction">Bouw & Infra</option>
                            <option value="direct">Direct werk</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="filterSort">Sorteren</label>
                        <select id="filterSort">
                            <option value="deadline">Deadline eerst</option>
                            <option value="new">Nieuwste eerst</option>
                            <option value="old">Oudste eerst</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn--secondary btn--full" id="applyFilters">Filters toepassen</button>
                <p class="panel__note" id="feedStatus">Verbinden met Tender Radar...</p>
            </aside>
        </header>

        <main class="content">
            <section class="featured">
                <div class="section__header">
                    <div>
                        <p class="section__eyebrow">Featured</p>
                        <h2>Uitgelichte opportuniteiten</h2>
                    </div>
                    <p>Geselecteerd voor snelle evaluatie, met deadline, locatie en contracttype direct zichtbaar.</p>
                </div>
                <div class="featured__rail" id="featuredRail"></div>
            </section>

            <section class="feed">
                <div class="section__header">
                    <div>
                        <p class="section__eyebrow">Tender Feed</p>
                        <h2>Actuele aanbestedingen</h2>
                    </div>
                    <p>Een overzicht van actuele kansen met duidelijke context rond deadline, locatie en contracttype.</p>
                </div>
                <div id="tenderGrid" class="grid"></div>
            </section>
        </main>
    </div>

    <div id="tenderModal" class="modal hidden">
        <div class="modal__backdrop" data-modal-close></div>
        <div class="modal__card">
            <button class="modal__close" data-modal-close>
                <i data-lucide="x"></i>
            </button>
            <div class="modal__media" id="modalMedia"></div>
            <div class="modal__content">
                <div class="modal__header">
                    <p class="modal__meta" id="modalMeta">--</p>
                    <h3 id="modalTitle">--</h3>
                    <p id="modalClient" class="modal__client">--</p>
                </div>

                <div class="modal__facts" id="modalFacts"></div>

                <div class="modal__section">
                    <h4>Omschrijving</h4>
                    <p id="modalBody">--</p>
                </div>

                <div class="modal__section">
                    <h4>Geschiktheid</h4>
                    <p id="modalEligibility">--</p>
                </div>

                <div class="modal__actions">
                    <a id="modalLink" class="btn btn--primary" target="_blank" rel="noopener">Bekijk bron</a>
                    <a id="modalDetailLink" class="btn btn--ghost">Volledige pagina</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
