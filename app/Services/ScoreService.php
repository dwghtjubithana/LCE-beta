<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;

class ScoreService
{
    public function calculate(Company $company): array
    {
        $requiredTypes = Company::REQUIRED_DOC_TYPES;
        $validDocs = Document::query()
            ->where('company_id', $company->id)
            ->where('status', 'VALID')
            ->whereIn('category_selected', $requiredTypes)
            ->count();

        $totalRequired = count($requiredTypes);
        $score = $totalRequired > 0 ? (int) floor(($validDocs / $totalRequired) * 100) : 0;

        return [
            'score' => $score,
            'valid_count' => $validDocs,
            'total_required' => $totalRequired,
            'required_types' => $requiredTypes,
        ];
    }
}
