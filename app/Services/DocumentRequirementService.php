<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ComplianceRule;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentRequirementService
{
    private const LEVELS = ['FREE', 'BUSINESS', 'ENTERPRISE'];

    public function normalizeLevel(?string $value): string
    {
        $normalized = strtoupper(trim((string) $value));
        if ($normalized === 'PRO') {
            return 'BUSINESS';
        }
        return in_array($normalized, self::LEVELS, true) ? $normalized : 'FREE';
    }

    public function nextLevel(string $level): ?string
    {
        $level = $this->normalizeLevel($level);
        $idx = array_search($level, self::LEVELS, true);
        if ($idx === false || !isset(self::LEVELS[$idx + 1])) {
            return null;
        }
        return self::LEVELS[$idx + 1];
    }

    public function requiredTypesForCompany(Company $company, ?string $level = null): array
    {
        return $this->typesForCompany($company, $level, true);
    }

    public function uploadTypesForCompany(Company $company, ?string $level = null): array
    {
        return $this->typesForCompany($company, $level, false);
    }

    public function uploadCategoriesForCompany(Company $company, ?string $level = null): array
    {
        $level = $this->normalizeLevel($level);
        $required = $this->requiredTypesForCompany($company, $level);
        $requiredKeys = [];
        foreach ($required as $type) {
            $requiredKeys[$this->normalizeKey($type)] = true;
        }

        $rows = [];
        foreach ($this->uploadTypesForCompany($company, $level) as $type) {
            $rows[] = [
                'type' => $type,
                'required' => isset($requiredKeys[$this->normalizeKey($type)]),
            ];
        }

        return $rows;
    }

    public function checklistTypesForCompany(Company $company, ?string $level = null): array
    {
        $types = $this->requiredTypesForCompany($company, $level);
        $types[] = 'ID Bewijs';
        return $this->uniqueOrdered($types);
    }

    public function allowedTypesForCompany(Company $company, ?string $level = null): array
    {
        return $this->uploadTypesForCompany($company, $level);
    }

    public function levelProgress(Company $company, ?string $level = null): array
    {
        $currentLevel = $this->normalizeLevel($level);
        $nextLevel = $this->nextLevel($currentLevel);

        $currentRequired = $this->requiredTypesForCompany($company, $currentLevel);
        $currentStatuses = $this->latestStatuses($company, $currentRequired);
        $currentValid = collect($currentStatuses)->filter(fn ($status) => $status === 'VALID')->count();
        $currentMissing = [];
        foreach ($currentRequired as $type) {
            if (($currentStatuses[$this->normalizeKey($type)] ?? 'MISSING') !== 'VALID') {
                $currentMissing[] = $type;
            }
        }
        $currentPercent = count($currentRequired) > 0
            ? (int) floor(($currentValid / count($currentRequired)) * 100)
            : 0;

        $nextPayload = null;
        if ($nextLevel) {
            $nextRequired = $this->requiredTypesForCompany($company, $nextLevel);
            $nextStatuses = $this->latestStatuses($company, $nextRequired);
            $nextValid = collect($nextStatuses)->filter(fn ($status) => $status === 'VALID')->count();
            $nextMissing = [];
            foreach ($nextRequired as $type) {
                if (($nextStatuses[$this->normalizeKey($type)] ?? 'MISSING') !== 'VALID') {
                    $nextMissing[] = $type;
                }
            }
            $nextPercent = count($nextRequired) > 0
                ? (int) floor(($nextValid / count($nextRequired)) * 100)
                : 0;
            $nextPayload = [
                'level' => $nextLevel,
                'percent' => $nextPercent,
                'valid_count' => $nextValid,
                'total_required' => count($nextRequired),
                'missing_documents' => $nextMissing,
            ];
        }

        return [
            'current' => [
                'level' => $currentLevel,
                'percent' => $currentPercent,
                'valid_count' => $currentValid,
                'total_required' => count($currentRequired),
                'missing_documents' => $currentMissing,
            ],
            'next' => $nextPayload,
        ];
    }

    private function latestStatuses(Company $company, array $types): array
    {
        $statuses = [];
        foreach ($types as $type) {
            $doc = Document::query()
                ->where('company_id', $company->id)
                ->where('category_selected', $type)
                ->orderByDesc('id')
                ->first();
            $statuses[$this->normalizeKey($type)] = $doc ? strtoupper((string) $doc->status) : 'MISSING';
        }
        return $statuses;
    }

    private function typesForCompany(Company $company, ?string $level, bool $requiredOnly): array
    {
        $level = $this->normalizeLevel($level);
        $types = [];
        $rules = ComplianceRule::query()->orderBy('document_type')->get();

        foreach ($rules as $rule) {
            $documentType = trim((string) $rule->document_type);
            if ($documentType === '') {
                continue;
            }

            $constraints = is_array($rule->constraints) ? $rule->constraints : [];
            if (!$this->matchesLevel($constraints, $level)) {
                continue;
            }
            if (!$this->matchesSector($rule->sector_applicability, (string) $company->sector)) {
                continue;
            }
            if (!$this->matchesCompanyType($constraints, $company->company_type_key ?? null)) {
                continue;
            }
            if ($requiredOnly && !$this->isRequiredDocument($constraints)) {
                continue;
            }

            $types[] = $documentType;
        }

        if ($this->requiresBedrijfsVergunning($company) && $this->levelRank($level) >= $this->levelRank('BUSINESS')) {
            $types[] = 'Bedrijfs Vergunning';
        }

        return $this->uniqueOrdered($types);
    }

    private function isRequiredDocument(array $constraints): bool
    {
        if (!array_key_exists('required_document', $constraints)) {
            return true;
        }

        return $this->boolValue($constraints['required_document'], true);
    }

    private function matchesLevel(array $constraints, string $level): bool
    {
        $raw = $constraints['required_levels'] ?? null;
        $levels = [];
        if (is_array($raw)) {
            $levels = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $levels = array_map('trim', explode(',', $raw));
        }

        if ($levels === []) {
            return true;
        }

        $target = $this->normalizeLevel($level);
        foreach ($levels as $candidate) {
            if ($this->normalizeLevel((string) $candidate) === $target) {
                return true;
            }
        }

        return false;
    }

    private function matchesSector($sectorApplicability, string $companySector): bool
    {
        if (!is_array($sectorApplicability) || $sectorApplicability === []) {
            return true;
        }

        $target = $this->normalizeKey($companySector);
        foreach ($sectorApplicability as $sector) {
            $value = $this->normalizeKey((string) $sector);
            if ($value === '' || in_array($value, ['GENERAL', 'ALL', '*'], true)) {
                return true;
            }
            if ($value === $target) {
                return true;
            }
        }

        return false;
    }

    private function matchesCompanyType(array $constraints, ?string $companyTypeKey): bool
    {
        $raw = $constraints['company_type_keys'] ?? null;
        $keys = [];
        if (is_array($raw)) {
            $keys = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $keys = array_map('trim', explode(',', $raw));
        }

        if ($keys === []) {
            return true;
        }

        $target = $this->normalizeKey((string) $companyTypeKey);
        if ($target === '') {
            return false;
        }

        foreach ($keys as $key) {
            if ($this->normalizeKey((string) $key) === $target) {
                return true;
            }
        }

        return false;
    }

    private function requiresBedrijfsVergunning(Company $company): bool
    {
        $companyTypeKey = trim((string) ($company->company_type_key ?? ''));
        if ($companyTypeKey === '') {
            return false;
        }

        if (!$this->hasCompanyTypeRequirementsTable()) {
            return false;
        }

        try {
            return DB::table('company_type_requirements')
                ->where('company_type_key', $companyTypeKey)
                ->where('requires_bedrijfsvergunning', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasCompanyTypeRequirementsTable(): bool
    {
        try {
            return Schema::hasTable('company_type_requirements');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function levelRank(string $level): int
    {
        $normalized = $this->normalizeLevel($level);
        $idx = array_search($normalized, self::LEVELS, true);
        return $idx === false ? 0 : $idx;
    }

    private function uniqueOrdered(array $types): array
    {
        $result = [];
        $seen = [];
        foreach ($types as $type) {
            $label = trim((string) $type);
            if ($label === '') {
                continue;
            }
            $key = $this->normalizeKey($label);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $label;
        }
        return $result;
    }

    private function normalizeKey(string $value): string
    {
        return strtoupper(str_replace([' ', '-'], '_', trim($value)));
    }

    private function boolValue($value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return ((int) $value) !== 0;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        return $default;
    }
}
