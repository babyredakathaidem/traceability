<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request) {
                    $u = $request->user();
                    if (!$u) return null;

                    // Admin DN có full permissions — không cần list ra
                    // Staff thì lấy từ cột permissions (JSON array)
                    $permissions = [];
                    if ($u->isSuperAdmin()) {
                        // super admin dùng prefix 'sys.*'
                        $permissions = ['sys.enterprises.view', 'sys.enterprises.approve', 'sys.settings.manage'];
                    } elseif ($u->isEnterpriseAdmin()) {
                        // admin DN: full enterprise permissions
                        $permissions = [
                            'enterprise.products.view',
                            'enterprise.products.manage',
                            'enterprise.batches.view',
                            'enterprise.batches.manage',
                            'enterprise.trace_events.view',
                            'enterprise.trace_events.create',
                            'enterprise.trace_events.manage',
                            'enterprise.qrcodes.view',
                            'enterprise.qrcodes.manage',
                            'enterprise.users.manage',     // chỉ admin DN
                            'enterprise.settings.manage',  // chỉ admin DN
                        ];
                    } else {
                        // nhân viên: lấy từ DB
                        $permissions = $u->permissions ?? [];
                    }

                    return [
                        'id'            => $u->id,
                        'name'          => $u->name,
                        'email'         => $u->email,
                        'enterprise_id' => $u->enterprise_id,
                        'is_super_admin'=> $u->isSuperAdmin(),
                        'role'          => $u->role, // enterprise_admin | enterprise_staff | null
                        'permissions'   => array_values($permissions),
                    ];
                },
            ],
        ];
    }
}