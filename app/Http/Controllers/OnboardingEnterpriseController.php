<?php

namespace App\Http\Controllers;

use App\Mail\EnterpriseSubmittedMail;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OnboardingEnterpriseController extends Controller
{
    public function create()
    {
        return Inertia::render('Onboarding/EnterpriseCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'business_code'                 => ['required', 'string', 'max:50'],
            'business_code_issued_at'       => ['nullable', 'date'],
            'business_cert_no'              => ['nullable', 'string', 'max:100'],
            'business_cert_issued_place'    => ['nullable', 'string', 'max:255'],
            'business_license_no'           => ['nullable', 'string', 'max:100'],
            'business_license_issued_place' => ['nullable', 'string', 'max:255'],
            'province'                      => ['required', 'string', 'max:100'],
            'district'                      => ['required', 'string', 'max:100'],
            'address_detail'                => ['nullable', 'string', 'max:255'],
            'phone'                         => ['required', 'string', 'max:30'],
            'email'                         => ['required', 'email', 'max:255'],
            'representative_name'           => ['nullable', 'string', 'max:255'],
            'representative_id'             => ['nullable', 'string', 'max:50'],
            'business_cert_file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'accept_terms'                  => ['accepted'],
        ]);

        $user = $request->user();
        $enterprise = null;

        DB::transaction(function () use ($request, $user, $data, &$enterprise) {
            $path = $request->file('business_cert_file')->store('enterprise_gcn', 'public');

            $enterprise = Enterprise::create([
                'name'                          => $data['name'],
                'business_code'                 => $data['business_code'],
                'business_code_issued_at'       => $data['business_code_issued_at'] ?? null,
                'business_cert_no'              => $data['business_cert_no'] ?? null,
                'business_cert_issued_place'    => $data['business_cert_issued_place'] ?? null,
                'business_license_no'           => $data['business_license_no'] ?? null,
                'business_license_issued_place' => $data['business_license_issued_place'] ?? null,
                'province'                      => $data['province'],
                'district'                      => $data['district'],
                'address_detail'                => $data['address_detail'] ?? null,
                'phone'                         => $data['phone'],
                'email'                         => $data['email'],
                'representative_name'           => $data['representative_name'] ?? null,
                'representative_id'             => $data['representative_id'] ?? null,
                'business_cert_file_path'       => $path,
                'status'                        => 'pending',
                'created_by'                    => $user->id,
                'admin_user_id'                 => $user->id,
            ]);

            $user->enterprise_id = $enterprise->id;
            $user->role          = 'enterprise_admin';
            $user->save();
        });

        // ── Gửi mail thông báo cho tất cả super admin
        $superAdmins = User::where('is_super_admin', true)->get();
        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->queue(new EnterpriseSubmittedMail($enterprise, $user));
        }

        return redirect()->route('onboarding.enterprise.pending');
    }
}