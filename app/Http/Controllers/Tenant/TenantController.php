<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    use ResponseTrait;

    public function saveRentReminder(Request $request)
    {
        $request->validate([
            'rent_reminder_date' => 'required|date',
        ]);

        $tenant = Auth::user()->tenant;

        if ($tenant) {
            $tenant->rent_reminder_date = $request->rent_reminder_date;
            $tenant->save();

            return $this->success(['message' => 'Rent reminder date saved successfully.']);
        } else {
            return $this->error('Tenant not found.', 404);
        }
    }
}
