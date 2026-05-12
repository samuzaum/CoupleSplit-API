<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function index()
    {
        $user          = Auth::user();
        $notifications = $this->service->getActive($user);

        return view('notifications.index', compact('notifications'));
    }

    public function dismiss(Request $request)
    {
        $keys = $request->input('keys', []);

        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $keys = array_filter($keys); // remove vazios/nulos

        if (!empty($keys)) {
            $this->service->dismiss(Auth::user(), $keys);
        }

        return redirect()->back();
    }
}
