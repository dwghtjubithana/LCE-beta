<?php

namespace App\Http\Controllers;

use App\Models\PlanCatalog;
use Illuminate\Http\JsonResponse;

class PlanCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = PlanCatalog::query()
            ->where('is_active', true)
            ->orderBy('rank')
            ->orderBy('id')
            ->get([
                'plan_key',
                'plan_label',
                'description',
                'rank',
                'is_default',
                'available_for_signup',
                'available_for_upgrade',
                'requires_payment_proof',
            ]);

        return response()->json([
            'status' => 'success',
            'plans' => $plans,
        ]);
    }
}
