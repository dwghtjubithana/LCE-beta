@extends('user.layout')

@section('title', 'Aanbestedingen | SuriCore LCE')
@section('page_title', 'Aanbestedingen')

@section('content')
<div class="space-y-6">
    <section class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-[linear-gradient(135deg,#0f766e,#14281f)] p-6 text-white shadow-sm">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-12 left-0 h-40 w-40 rounded-full bg-amber-400/20 blur-2xl"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">
                    Tender Radar
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Actuele aanbestedingen voor olie, overheid, mijnbouw en infra.</h2>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/80">
                    Volg relevante opdrachten, vergelijk deadlines en beoordeel kansen sneller met een helder overzicht van de Surinaamse markt.
                </p>
            </div>
            <div class="grid w-full gap-3 sm:grid-cols-3 lg:max-w-xl" id="tenderStatsCards">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-white/60">Actief</p>
                    <p class="mt-2 text-3xl font-black" id="userTenderStatTotal">--</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-white/60">Deadline deze week</p>
                    <p class="mt-2 text-3xl font-black" id="userTenderStatDeadline">--</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-white/60">Sectoren</p>
                    <p class="mt-2 text-3xl font-black" id="userTenderStatSectors">--</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.8fr)_360px]">
        <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h3 class="text-2xl font-black tracking-tight text-slate-900">Actuele feed</h3>
                    <p class="mt-2 text-sm text-slate-500">Een overzicht van actuele aanbestedingen met deadline, locatie en contracttype direct zichtbaar.</p>
                </div>
                <button class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white" id="refreshTendersBtn">
                    Vernieuwen
                </button>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2" id="tendersList">
                <div class="rounded-2xl border border-slate-200 p-5 text-sm text-slate-500">Aanbestedingen laden...</div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-xl font-black tracking-tight text-slate-900">Opdrachtgevers</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500" id="tenderFeedNote">
                    Een selectie van opdrachtgevers en marktpartijen die actief zijn binnen aanbestedingen en projectuitvragen.
                </p>
                <div class="mt-5 space-y-3 text-sm text-slate-600" id="tenderIssuerList">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">Bronnen laden...</div>
                </div>
            </div>

            <div class="rounded-[28px] border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-700">Marktfocus</p>
                <h3 class="mt-3 text-xl font-black tracking-tight text-slate-900">Kansen met hoge relevantie</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Gebruik deze pagina om snel te zien waar de meest interessante kansen liggen op basis van sector, deadline en opdrachtgever.
                </p>
            </div>
        </aside>
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initUserTendersPage === 'function') {
            initUserTendersPage();
        }
    });
</script>
@endsection
