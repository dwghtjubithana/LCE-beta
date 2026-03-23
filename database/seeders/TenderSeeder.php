<?php

namespace Database\Seeders;

use App\Models\Tender;
use Illuminate\Database\Seeder;

class TenderSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'Offshore Catering & Camp Services',
                'project' => 'Saramacca Operations Support',
                'date' => now()->subDays(2)->format('Y-m-d'),
                'submission_deadline' => now()->addDays(9)->format('Y-m-d'),
                'client' => 'Staatsolie Maatschappij Suriname N.V.',
                'location' => 'Saramacca / Paramaribo',
                'sector' => 'Olie & Gas',
                'reference_code' => 'SC-STAATSOLIE-2026-001',
                'contract_type' => 'RFP',
                'budget_label' => 'Middelgroot contract',
                'eligibility' => 'Ervaring met offshore catering, HSE-documentatie, geldige bedrijfsregistratie en lokale mobilisatiecapaciteit.',
                'details_url' => 'https://www.staatsolie.com/procurement-opportunities/',
                'source_name' => 'Staatsolie Procurement',
                'source_url' => 'https://www.staatsolie.com/procurement-opportunities/',
                'cover_image_url' => 'https://picsum.photos/seed/staatsolie-offshore/1400/900',
                'description' => 'Levering van catering, housekeeping en lichte camp support voor operationele teams in de Saramacca-regio.',
                'status' => 'APPROVED',
                'attachments' => [
                    ['type' => 'url', 'url' => 'https://www.staatsolie.com/procurement-opportunities/'],
                ],
            ],
            [
                'title' => 'Drainage Rehabilitation & Civil Works',
                'project' => 'Waterafvoer Noord-Paramaribo',
                'date' => now()->subDays(4)->format('Y-m-d'),
                'submission_deadline' => now()->addDays(12)->format('Y-m-d'),
                'client' => 'Ministerie van Openbare Werken',
                'location' => 'Paramaribo',
                'sector' => 'Overheid / Infra',
                'reference_code' => 'SC-GOV-OW-2026-014',
                'contract_type' => 'Openbare aanbesteding',
                'budget_label' => 'Groot infrastructureel werk',
                'eligibility' => 'Lokale aannemer of combinatie, relevante civiele referenties, actuele belasting- en registratiebewijzen.',
                'details_url' => 'https://www.gov.sr/ministerie-van-openbare-werken/',
                'source_name' => 'Gov.sr - Openbare Werken',
                'source_url' => 'https://www.gov.sr/ministerie-van-openbare-werken/',
                'cover_image_url' => 'https://picsum.photos/seed/gov-civil-works/1400/900',
                'description' => 'Herstel van hoofdafwatering, civiele betonwerken en verharding in kritieke zones met hoge wateroverlast.',
                'status' => 'APPROVED',
                'attachments' => [
                    ['type' => 'url', 'url' => 'https://www.gov.sr/ministerie-van-openbare-werken/'],
                ],
            ],
            [
                'title' => 'Fleet Maintenance & Site Logistics Support',
                'project' => 'Rosebel Operational Logistics',
                'date' => now()->subDays(1)->format('Y-m-d'),
                'submission_deadline' => now()->addDays(16)->format('Y-m-d'),
                'client' => 'Rosebel Gold Mines N.V.',
                'location' => 'Brokopondo',
                'sector' => 'Mijnbouw',
                'reference_code' => 'SC-ROSEBEL-2026-003',
                'contract_type' => 'RFQ',
                'budget_label' => 'Operationeel support contract',
                'eligibility' => 'Aantoonbare ervaring met fleet maintenance, transportcoordinatie, veiligheidsprocedures en remote-site support.',
                'details_url' => 'https://www.rosebelgoldmines.com/',
                'source_name' => 'Rosebel Gold Mines',
                'source_url' => 'https://www.rosebelgoldmines.com/',
                'cover_image_url' => 'https://picsum.photos/seed/rosebel-mining/1400/900',
                'description' => 'Ondersteuning voor site logistics, planning van materieel en eerstelijns onderhoud van operationele voertuigen.',
                'status' => 'APPROVED',
                'attachments' => [
                    ['type' => 'url', 'url' => 'https://www.rosebelgoldmines.com/'],
                ],
            ],
            [
                'title' => '8 lassers gezocht voor shutdown ondersteuning',
                'project' => 'Direct Werk',
                'date' => now()->format('Y-m-d'),
                'submission_deadline' => now()->addDays(2)->format('Y-m-d'),
                'client' => 'Industrial Services Partner',
                'location' => 'Wanica',
                'sector' => 'Direct Werk',
                'reference_code' => 'SC-DW-2026-019',
                'contract_type' => 'Snelle inzet',
                'budget_label' => 'Dagprijs per team',
                'eligibility' => 'Direct inzetbaar team met geldige ID, basisveiligheid en eigen vervoer.',
                'details_url' => 'https://wa.me/597000000',
                'source_name' => 'Direct placement',
                'source_url' => 'https://wa.me/597000000',
                'cover_image_url' => 'https://picsum.photos/seed/direct-work-welding/1400/900',
                'description' => 'Snelle shutdown-opdracht voor gecertificeerde lassers. Start binnen 48 uur na bevestiging.',
                'is_direct_work' => true,
                'status' => 'APPROVED',
                'attachments' => [],
            ],
        ];

        foreach ($items as $item) {
            Tender::updateOrCreate(
                ['title' => $item['title']],
                $item
            );
        }
    }
}
