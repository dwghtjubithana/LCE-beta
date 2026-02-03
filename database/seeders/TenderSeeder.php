<?php

namespace Database\Seeders;

use App\Models\Tender;
use Illuminate\Database\Seeder;

class TenderSeeder extends Seeder
{
    public function run(): void
    {
        Tender::updateOrCreate(
            ['title' => 'Road Infrastructure Upgrade'],
            [
                'project' => 'Road Infrastructure Upgrade',
                'date' => now()->subDays(2)->format('Y-m-d'),
                'client' => 'Ministry of Public Works',
                'details_url' => 'https://example.com/tenders/road-upgrade',
                'attachments' => ['specs.pdf', 'boq.xlsx'],
                'description' => 'Upgrade road surfaces and drainage in Paramaribo.',
            ]
        );

        Tender::updateOrCreate(
            ['title' => 'Solar Farm Development'],
            [
                'project' => 'Solar Farm Development',
                'date' => now()->subDays(7)->format('Y-m-d'),
                'client' => 'Energy Authority',
                'details_url' => 'https://example.com/tenders/solar-farm',
                'attachments' => ['rfi.pdf'],
                'description' => 'Design and build a 20MW solar farm.',
            ]
        );
    }
}
