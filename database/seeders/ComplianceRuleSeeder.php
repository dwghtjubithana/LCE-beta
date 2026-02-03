<?php

namespace Database\Seeders;

use App\Models\ComplianceRule;
use Illuminate\Database\Seeder;

class ComplianceRuleSeeder extends Seeder
{
    public function run(): void
    {
        ComplianceRule::updateOrCreate(
            ['document_type' => 'KKF Uittreksel'],
            [
                'sector_applicability' => ['general'],
                'required_keywords' => ['KVK', 'KKF', 'Uittreksel', 'Handelsregister'],
                'max_age_months' => 12,
                'constraints' => ['issue_date_required' => true],
            ]
        );

        ComplianceRule::updateOrCreate(
            ['document_type' => 'Vergunning'],
            [
                'sector_applicability' => ['construction', 'energy', 'general'],
                'required_keywords' => ['Vergunning', 'License', 'Permit'],
                'max_age_months' => 24,
                'constraints' => ['issue_date_required' => true, 'expiry_date_required' => true],
            ]
        );

        ComplianceRule::updateOrCreate(
            ['document_type' => 'Belastingverklaring'],
            [
                'sector_applicability' => ['general'],
                'required_keywords' => ['Belasting', 'Tax', 'Aanslag', 'Verklaring'],
                'max_age_months' => 12,
                'constraints' => ['issue_date_required' => true],
            ]
        );

        ComplianceRule::updateOrCreate(
            ['document_type' => 'ID Bewijs'],
            [
                'sector_applicability' => ['general'],
                'required_keywords' => ['ID', 'Identiteit', 'Passport', 'Rijbewijs'],
                'max_age_months' => 120,
                'constraints' => ['issue_date_required' => false, 'expiry_date_required' => true],
            ]
        );
    }
}
