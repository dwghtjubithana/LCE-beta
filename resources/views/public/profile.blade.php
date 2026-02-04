<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuriCore Digitale ID</title>
    <link rel="stylesheet" href="/public/css/public-profile.css">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="/public/js/public-profile.js" defer></script>
</head>
<body>
    <div class="profile" data-slug="{{ $slug }}">
        <header class="profile__header">
            <div class="profile__brand">
                <span class="brand__mark">SC</span>
                <div>
                    <p class="brand__name">SuriCore</p>
                    <p class="brand__tag">Digitale ID</p>
                </div>
            </div>
            <button id="shareBtn" class="btn btn--primary">
                <i data-lucide="share-2"></i> Deel via WhatsApp
            </button>
        </header>

        <main class="profile__card">
            <div class="profile__avatar" id="profileAvatar">SC</div>
            <div class="profile__info">
                <h1 id="profileName">--</h1>
                <p id="profileSector" class="profile__sector">--</p>
                <div class="profile__badges">
                    <span id="statusBadge" class="badge badge--gray">GRIJS</span>
                </div>
                <div class="profile__details">
                    <div>
                        <p class="label">Adres</p>
                        <p id="profileAddress">--</p>
                    </div>
                    <div>
                        <p class="label">Contact</p>
                        <p id="profileContact">--</p>
                    </div>
                </div>
            </div>
        </main>

        <section class="profile__map">
            <h2>Locatie</h2>
            <div class="map" id="mapPlaceholder">Geen locatie beschikbaar.</div>
        </section>
    </div>
</body>
</html>
