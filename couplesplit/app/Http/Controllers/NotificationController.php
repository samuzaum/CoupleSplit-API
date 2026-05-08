<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function index()
    {
        $user          = Auth::user();
        $notifications = $this->service->getActive($user);

        // marcar categorias excedidas como vistas na sessão
        $seenCategories = collect($notifications)
            ->filter(fn($n) => in_array($n['type'], ['budget_exceeded', 'budget_warning']))
            ->pluck('category')
            ->toArray();
        session(['budget_notifications_seen' => $seenCategories]);

        return view('notifications.index', compact('notifications'));
    }

    public function dismiss()
    {
        $user          = Auth::user();
        $notifications = $this->service->getActive($user);

        $seenCategories = collect($notifications)
            ->filter(fn($n) => in_array($n['type'], ['budget_exceeded', 'budget_warning']))
            ->pluck('category')
            ->toArray();
        session(['budget_notifications_seen' => $seenCategories]);

        return redirect()->back();
    }
}
