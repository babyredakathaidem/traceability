<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    // Danh sách permissions có thể gán cho nhân viên
    const AVAILABLE_PERMISSIONS = [
        'enterprise.products.view'       => 'Xem sản phẩm',
        'enterprise.products.manage'     => 'Quản lý sản phẩm',
        'enterprise.batches.view'        => 'Xem lô hàng',
        'enterprise.batches.manage'      => 'Quản lý lô hàng',
        'enterprise.trace_events.view'   => 'Xem sự kiện truy xuất',
        'enterprise.trace_events.create' => 'Tạo sự kiện truy xuất',
        'enterprise.trace_events.manage' => 'Quản lý sự kiện truy xuất',
        'enterprise.qrcodes.view'        => 'Xem QR Codes',
        'enterprise.qrcodes.manage'      => 'Quản lý QR Codes',
    ];

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $users = User::where('enterprise_id', $request->user()->enterprise_id)
            ->where('role', 'enterprise_staff')
            ->orderByDesc('id')
            ->get(['id', 'name', 'email', 'role', 'permissions', 'created_at']);

        return Inertia::render('Enterprise/Users/Index', [
            'staffList'            => $users,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', Rules\Password::defaults()],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(self::AVAILABLE_PERMISSIONS))],
        ]);

        User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'enterprise_id' => $request->user()->enterprise_id,
            'role'          => 'enterprise_staff',
            'permissions'   => $data['permissions'] ?? [],
        ]);

        return back()->with('success', 'Đã thêm nhân viên.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        $this->assertSameEnterprise($request, $user);

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(self::AVAILABLE_PERMISSIONS))],
        ]);

        $user->update([
            'name'        => $data['name'] ?? $user->name,
            'permissions' => $data['permissions'] ?? [],
        ]);

        return back()->with('success', 'Đã cập nhật nhân viên.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeAdmin($request);
        $this->assertSameEnterprise($request, $user);

        // Không cho xóa chính mình
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'Không thể xóa tài khoản của chính mình.']);
        }

        $user->delete();

        return back()->with('success', 'Đã xóa nhân viên.');
    }

    // ── helpers ──────────────────────────────────────────────

    private function authorizeAdmin(Request $request): void
    {
        if (!$request->user()->isEnterpriseAdmin()) {
            abort(403, 'Chỉ Admin DN mới có quyền này.');
        }
    }

    private function assertSameEnterprise(Request $request, User $user): void
    {
        if ((int) $user->enterprise_id !== (int) $request->user()->enterprise_id) {
            abort(403);
        }
    }
}