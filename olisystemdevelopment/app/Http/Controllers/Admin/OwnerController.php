<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OwnerService;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    use ResponseTrait;
    public $ownerService;
    public function __construct()
    {
        $this->ownerService = new OwnerService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ownerService->getAllData($request);
        } else {
            $data['pageTitle'] = __('Owners');
            return view('admin.owner.index', $data);
        }
    }

    public function getInfo(Request $request)
    {
        $owner = User::where('role', USER_ROLE_OWNER)->findOrFail($request->id);
        return $owner;
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255', 'unique:users,email,' . $request->id],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        try {
            $owner = User::where('role', USER_ROLE_OWNER)->findOrFail($request->id);
            $owner->first_name = $request->first_name;
            $owner->last_name = $request->last_name;
            $owner->contact_number = $request->contact_number;
            $owner->email = $request->email;
            if (!is_null($request->password)) {
                $owner->password = $request->password;
            }
            $owner->status = $request->status;
            $owner->save();
            // dd($request->all(), $owner);
            return $this->success([], __(UPDATED_SUCCESSFULLY));
        } catch (Exception $e) {
            $message = getErrorMessage($e, $e->getMessage());
            return $this->error([], $message);
        }
    }

    public function delete($id)
    {
        return $this->ownerService->delete($id);
    }
}
