@extends('user.layout')

@section('title', 'Bedrijfsprofiel')
@php($active = 'profile')
@section('page_title', 'Bedrijfsprofiel')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div id="companyCreatePanel" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-bold text-slate-800 text-lg">Bedrijf aanmaken</h3>
            <p class="text-sm text-slate-500">Je hebt nog geen bedrijf. Vul dit eerst in.</p>
        </div>
        <form id="companyCreateForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="createCompanyName">Bedrijfsnaam</label>
                <input type="text" id="createCompanyName" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="createCompanySector">Sector</label>
                <input type="text" id="createCompanySector" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="createCompanyExperience">Ervaring</label>
                <input type="text" id="createCompanyExperience" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="createCompanyContact">Contact e-mail</label>
                <input type="email" id="createCompanyContact" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Bedrijf aanmaken
                </button>
                <span id="companyCreateStatus" class="text-sm text-slate-500 ml-3"></span>
            </div>
        </form>
    </div>

    <div id="companyProfilePanel" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Bedrijfsprofiel</h3>
                <p class="text-sm text-slate-500">Vul je gegevens in en download je profiel-PDF.</p>
            </div>
            <button id="downloadProfileBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                <i data-lucide="download" class="w-4 h-4"></i> Download Profiel
            </button>
        </div>

        <form id="companyProfileForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyNameInput">Bedrijfsnaam</label>
                <input type="text" id="companyNameInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companySectorInput">Sector</label>
                <input type="text" id="companySectorInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyExperienceInput">Ervaring</label>
                <input type="text" id="companyExperienceInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyContactInput">Contact</label>
                <input type="text" id="companyContactInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="info@bedrijf.sr" required>
            </div>

            <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" id="saveProfileBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Profiel Opslaan
                </button>
                <div id="profileStatus" class="text-sm text-slate-500 flex items-center"></div>
            </div>
        </form>
    </div>
</div>
@endsection
