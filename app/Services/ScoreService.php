<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;

class ScoreService
{
    public function __construct(private DocumentRequirementService $requirements)
    {
    }

    public function calculate(Company $company, ?string $level = null): array
    {
        $level = $this->requirements->normalizeLevel($level);
        $requiredTypes = $this->requirements->requiredTypesForCompany($company, $level);
        $validDocs = Document::query()
            ->where('company_id', $company->id)
            ->where('status', 'VALID')
            ->whereIn('category_selected', $requiredTypes)
            ->count();

        $totalRequired = count($requiredTypes);
        $score = $totalRequired > 0 ? (int) floor(($validDocs / $totalRequired) * 100) : 100;

        return [
            'score' => $score,
            'valid_count' => $validDocs,
            'total_required' => $totalRequired,
            'required_types' => $requiredTypes,
            'level' => $level,
        ];
    }
}
