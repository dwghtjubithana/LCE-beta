<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TenderController extends Controller
{
    public function index(): JsonResponse
    {
        $tenders = Tender::query()
            ->orderByDesc('date')
            ->get()
            ->map(fn (Tender $tender) => $this->applyGating($tender, $this->authUser()));

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tender' => $this->applyGating($tender, $this->authUser()),
        ]);
    }

    public function indexPublic(): JsonResponse
    {
        $guest = $this->guestUser();
        $tenders = Tender::query()
            ->orderByDesc('date')
            ->get()
            ->map(fn (Tender $tender) => $this->applyGating($tender, $guest));

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
        ]);
    }

    public function showPublic(int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tender' => $this->applyGating($tender, $this->guestUser()),
        ]);
    }

    private function applyGating(Tender $tender, User $user): array
    {
        $isBusiness = ($user->plan ?? 'FREE') === 'BUSINESS' && ($user->plan_status ?? 'ACTIVE') === 'ACTIVE';
        $data = $tender->toArray();

        if (!$isBusiness) {
            $data['description'] = $data['description'] ? str_repeat('•', 20) : null;
            $data['details_url'] = null;
            $data['attachments'] = null;
        }

        return $data;
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }

    private function guestUser(): User
    {
        $user = new User();
        $user->plan = 'FREE';
        $user->plan_status = 'ACTIVE';
        return $user;
    }
}
