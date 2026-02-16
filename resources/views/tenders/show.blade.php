<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tender Details | Wapcore</title>
    <link rel="stylesheet" href="/public/css/tenders.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="/public/js/tender-detail.js" defer></script>
</head>
<body>
    <div class="page" data-tender-id="{{ $id }}">
        <header class="hero hero--detail">
            <div class="hero__content">
                <div class="brand">
                    <span class="brand__mark">SC</span>
                    <div>
                        <p class="brand__name">Wapcore</p>
                        <p class="brand__tag">Tender Radar • Details</p>
                    </div>
                </div>
                <h1 id="detailTitle">Tender laden...</h1>
                <p id="detailMeta" class="detail__meta">--</p>
                <div class="hero__actions">
                    <a class="btn btn--ghost" href="/">Terug naar overzicht</a>
                    <a id="detailLink" class="btn btn--primary" target="_blank" rel="noopener">Bekijk details</a>
                    <button id="detailUpgrade" class="btn btn--secondary">Upgrade naar Business</button>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="section__header">
                <h2>Omschrijving</h2>
                <p id="detailClient">--</p>
            </div>
            <div id="detailBody" class="detail__body">--</div>
        </main>
    </div>
</body>
</html>
