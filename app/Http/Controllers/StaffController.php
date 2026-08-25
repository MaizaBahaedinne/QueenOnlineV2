<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('staff', 'list', 'view');

        return view('staff.index', [
            'title' => 'Staff',
            'staffMembers' => Staff::query()->with(['department', 'manager', 'user.role'])->latest()->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'managers' => Staff::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'users' => User::query()->with('role')->orderBy('name')->get(['id', 'name', 'email', 'role_id']),
        ]);
    }

    public function show(Staff $staff)
    {
        $this->enforcePermission('staff', 'list', 'view');

        $staff->load(['department', 'manager', 'user.role']);

        return view('staff.show', [
            'title' => 'Profil staff',
            'staff' => $staff,
        ]);
    }

    public function create()
    {
        return redirect()->route('staff.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('staff', 'create', 'create');

        $validated = $this->validatePayload($request);
        $payload = $this->buildPayload($validated, $request);
        $payload['status'] = 'active';

        Staff::query()->create($payload);

        return redirect()->route('staff.index')->with('success', 'Membre du staff cree.');
    }

    public function update(Request $request, Staff $staff)
    {
        $this->enforcePermission('staff', 'update', 'update');

        $validated = $this->validatePayload($request, $staff->id);
        $payload = $this->buildPayload($validated, $request, $staff);

        $staff->update($payload);

        return redirect()->route('staff.index')->with('success', 'Membre du staff mis a jour.');
    }

    public function destroy(Staff $staff)
    {
        $this->enforcePermission('staff', 'delete', 'delete');

        if ($staff->photo_path) {
            Storage::disk('public')->delete($staff->photo_path);
        }

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Membre du staff supprime.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'photo' => ['nullable', 'image', 'max:3072'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'cin' => ['nullable', 'regex:/^[0-9]{8}$/', Rule::unique('staff', 'cin')->ignore($ignoreId)],
            'hire_date' => ['nullable', 'date'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(['permanent', 'part-time'])],
            'contract_type' => ['required', Rule::in(['CDI', 'CDD', 'Freelance'])],
            'manager_id' => ['nullable', 'exists:staff,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('staff', 'user_id')->ignore($ignoreId)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function buildPayload(array $validated, Request $request, ?Staff $staff = null): array
    {
        $validated['cin'] = filled($validated['cin'] ?? null) ? $validated['cin'] : null;

        if (! empty($validated['department_name'])) {
            $department = Department::query()->firstOrCreate(
                ['name' => $validated['department_name']],
                ['status' => 'active']
            );
            $validated['department_id'] = $department->id;
        }

        unset($validated['department_name']);

        if ($request->hasFile('photo')) {
            if ($staff?->photo_path) {
                Storage::disk('public')->delete($staff->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('staff', 'public');
        }

        unset($validated['photo']);

        return $validated;
    }
}
