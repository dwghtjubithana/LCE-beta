<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tender Details | SuriCore LCE</title>
    <link rel="stylesheet" href="/public/css/tenders.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="/public/js/tender-detail.js" defer></script>
</head>
<body>
    <div class="page" data-tender-id="{{ $id }}">
        <header class="hero hero--detail">
            <div class="hero__copy">
                <div class="brand">
                    <span class="brand__mark">SC</span>
                    <div>
                        <p class="brand__name">SuriCore LCE</p>
                        <p class="brand__tag">Tender detailweergave</p>
                    </div>
                </div>
                <h1 id="detailTitle">Tender laden...</h1>
                <p id="detailMeta" class="detail__meta">--</p>
                <div class="hero__actions">
                    <a class="btn btn--ghost" href="/tenders">Terug naar overzicht</a>
                    <a id="detailLink" class="btn btn--primary" target="_blank" rel="noopener">Bekijk bron</a>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="detail-grid">
                <section class="detail-card">
                    <h2>Omschrijving</h2>
                    <div id="detailBody" class="detail__body">--</div>
                </section>

                <aside class="detail-card">
                    <h3>Projectinformatie</h3>
                    <div class="detail__stack" id="detailFacts"></div>
                </aside>
            </div>

            <div class="detail-grid">
                <section class="detail-card">
                    <h2>Geschiktheid</h2>
                    <div id="detailEligibility" class="detail__body">--</div>
                </section>

                <section class="detail-card">
                    <h3>Bijlagen en bron</h3>
                    <div class="detail__attachments" id="detailAttachments"></div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
