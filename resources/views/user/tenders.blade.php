@extends('user.layout')

@section('title', 'Aanbestedingen | Wapcore')
@section('page_title', 'Aanbestedingen')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Beschikbare aanbestedingen</h3>
                    <p class="text-sm text-slate-500">Alle goedgekeurde aanbestedingen die zichtbaar zijn voor gebruikers.</p>
                </div>
                <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm" id="refreshTendersBtn">Vernieuwen</button>
            </div>
            <div class="mt-4" id="tendersList">
                <p class="text-sm text-slate-500">Aanbestedingen laden...</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Mijn inzendingen</h3>
                    <p class="text-sm text-slate-500">Bekijk de status van de aanbestedingen die je hebt ingestuurd.</p>
                </div>
                <button class="px-4 py-2 rounded-lg border border-slate-200 text-sm" id="refreshMyTendersBtn">Vernieuwen</button>
            </div>
            <div class="mt-4" id="myTendersList">
                <p class="text-sm text-slate-500">Inzendingen laden...</p>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-800">Nieuwe aanbesteding insturen</h3>
            <p class="text-sm text-slate-500 mb-4">Vul de basisinformatie in. Na goedkeuring verschijnt de aanbesteding in de lijst.</p>
            <form id="tenderForm" class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-700">Titel *</label>
                    <input id="tenderTitle" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Bijv. Levering zand" required />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Opdrachtgever</label>
                    <input id="tenderClient" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Bijv. Ministerie OW" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Datum</label>
                    <input id="tenderDate" type="date" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Type opdracht</label>
                    <select id="tenderDirectWork" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2">
                        <option value="0">Standaard aanbesteding</option>
                        <option value="1">Direct werk (micro-gig)</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Details URL</label>
                    <input id="tenderUrl" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="https://..." />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Bijlagen (1 URL per regel)</label>
                    <textarea id="tenderAttachments" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="https://...\nhttps://..."></textarea>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Document/foto bijlage (optioneel)</label>
                    <input id="tenderAttachmentFiles" type="file" multiple class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Omschrijving</label>
                    <textarea id="tenderDescription" rows="4" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2" placeholder="Korte beschrijving van de aanbesteding"></textarea>
                </div>
                <button class="w-full bg-blue-600 text-white rounded-lg py-2 font-semibold" type="submit" id="tenderSubmitBtn">Insturen voor goedkeuring</button>
                <p class="text-xs text-slate-400" id="tenderFormStatus"></p>
            </form>
        </div>
    </aside>
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
