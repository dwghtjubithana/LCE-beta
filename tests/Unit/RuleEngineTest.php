<?php

namespace Tests\Unit;

use App\Models\ComplianceRule;
use App\Services\RuleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_issue_date_fails(): void
    {
        ComplianceRule::create([
            'document_type' => 'TestDoc',
            'max_age_months' => 12,
            'constraints' => ['issue_date_required' => true],
        ]);

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate('TestDoc', [
            'issue_date' => '2020-01-01',
            'ocr_confidence' => 0.95,
        ]);

        $this->assertEquals('FAIL', $result['status']);
    }

    public function test_missing_issue_date_fails_when_required(): void
    {
        ComplianceRule::create([
            'document_type' => 'TestDoc2',
            'max_age_months' => 12,
            'constraints' => ['issue_date_required' => true],
        ]);

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate('TestDoc2', [
            'ocr_confidence' => 0.95,
        ]);

        $this->assertEquals('FAIL', $result['status']);
    }

    public function test_parses_non_iso_issue_date(): void
    {
        ComplianceRule::create([
            'document_type' => 'TestDoc3',
            'max_age_months' => 12,
            'constraints' => ['issue_date_required' => true],
        ]);

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate('TestDoc3', [
            'issue_date' => date('d-m-Y'),
            'ocr_confidence' => 0.95,
        ]);

        $this->assertEquals('PASS', $result['status']);
    }

    public function test_missing_expiry_date_fails_when_required(): void
    {
        ComplianceRule::create([
            'document_type' => 'TestDoc4',
            'max_age_months' => 24,
            'constraints' => ['expiry_date_required' => true],
        ]);

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate('TestDoc4', [
            'issue_date' => date('Y-m-d'),
            'ocr_confidence' => 0.95,
        ]);

        $this->assertEquals('FAIL', $result['status']);
    }

    public function test_expired_document_fails(): void
    {
        ComplianceRule::create([
            'document_type' => 'TestDoc5',
            'max_age_months' => 24,
            'constraints' => ['expiry_date_required' => true],
        ]);

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate('TestDoc5', [
            'issue_date' => date('Y-m-d'),
            'expiry_date' => '2000-01-01',
            'ocr_confidence' => 0.95,
        ]);

        $this->assertEquals('FAIL', $result['status']);
    }
}
