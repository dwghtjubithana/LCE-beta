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
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="displayNameInput">Weergavenaam (Digital ID)</label>
                <input type="text" id="displayNameInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Naam op publieke kaart">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="publicSlugInput">Digitale ID slug</label>
                <input type="text" id="publicSlugInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="mijn-bedrijf">
                <p class="text-xs text-slate-400 mt-1">Wordt gebruikt voor je publieke link: /p/slug</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="addressInput">Adres</label>
                <input type="text" id="addressInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Paramaribo, Suriname">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="latInput">Latitude</label>
                <input type="text" id="latInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="5.8520">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="lngInput">Longitude</label>
                <input type="text" id="lngInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="-55.2038">
            </div>
            <div class="md:col-span-2">
                <button type="button" id="geocodeBtn" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Locatie automatisch invullen
                </button>
                <span id="geocodeStatus" class="text-xs text-slate-400 ml-3"></span>
            </div>
            <div class="md:col-span-2 border-t border-slate-100 pt-4">
                <h4 class="text-sm font-bold text-slate-700 mb-3">Branding & Socials</h4>
                <p class="text-xs text-slate-500 mb-4">Deze gegevens worden automatisch gebruikt op je Digitale ID. Je profielfoto kan je bedrijfslogo zijn.</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyWebsiteInput">Website</label>
                <input type="url" id="companyWebsiteInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="https://www.bedrijf.sr">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyWhatsappInput">WhatsApp link</label>
                <input type="url" id="companyWhatsappInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="https://wa.me/597...">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyFacebookInput">Facebook link</label>
                <input type="url" id="companyFacebookInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="https://facebook.com/...">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="companyLinkedinInput">LinkedIn link</label>
                <input type="url" id="companyLinkedinInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="https://linkedin.com/company/...">
            </div>
            <div class="md:col-span-2">
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <button type="button" id="uploadPhotoBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                        <i data-lucide="image" class="w-4 h-4"></i> Logo / profielfoto uploaden
                    </button>
                    <input type="file" id="profilePhotoInput" class="hidden" accept="image/*">
                    <a href="/digital-id" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Bekijk gesynchroniseerde Digitale ID</a>
                </div>
                <div class="mt-3 text-sm text-slate-500" id="digitalIdStatus"></div>
                <div class="mt-3">
                    <div class="text-xs text-slate-400 mb-2">Huidige logo/profielfoto</div>
                    <img id="profilePhotoPreview" class="w-24 h-24 rounded-xl border border-slate-200 object-cover hidden" alt="Logo">
                </div>
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
