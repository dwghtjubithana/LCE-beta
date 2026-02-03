<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = $this->authUser();
        $notifications = AppNotification::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
        ]);
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }
}
