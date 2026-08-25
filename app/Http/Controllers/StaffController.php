<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ...$this->formOptions(),
        ]);
    }

    public function show(Staff $staff)
    {
        $this->enforcePermission('staff', 'list', 'view');

        $staff->load(['department', 'manager', 'user.role', 'documents.uploader']);

        return view('staff.show', [
            'title' => 'Profil staff',
            'staff' => $staff,
            ...$this->formOptions(),
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

        foreach ($staff->documents as $document) {
            Storage::disk('public')->delete($document->file_path);
        }

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Membre du staff supprime.');
    }

    public function storeDocument(Request $request, Staff $staff)
    {
        $this->enforcePermission('staff', 'update', 'update');

        $validated = $request->validate([
            'document_type' => ['required', Rule::in(array_keys($this->documentTypeOptions()))],
            'document_label' => ['nullable', 'string', 'max:255'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('document_file')->store('staff/documents', 'public');

        $staff->documents()->create([
            'document_type' => $validated['document_type'],
            'document_label' => $validated['document_label'] ?: null,
            'original_name' => $request->file('document_file')->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => Auth::id(),
        ]);

        return redirect()->route('staff.show', $staff)->with('success', 'Document ajoute.');
    }

    public function destroyDocument(Staff $staff, StaffDocument $document)
    {
        $this->enforcePermission('staff', 'update', 'update');

        abort_unless($document->staff_id === $staff->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('staff.show', $staff)->with('success', 'Document supprime.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'photo' => ['nullable', 'image', 'max:3072'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'cin' => ['nullable', 'regex:/^[0-9]{8}$/', Rule::unique('staff', 'cin')->ignore($ignoreId)],
            'cin_issued_at' => ['nullable', 'date'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'governorate' => ['nullable', 'string', 'max:255'],
            'delegation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'marital_status' => ['nullable', Rule::in(array_keys($this->maritalStatusOptions()))],
            'dependent_children_count' => ['nullable', 'integer', 'min:0'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone_secondary' => ['nullable', 'string', 'max:50'],
            'employee_code' => ['nullable', 'string', 'max:100', Rule::unique('staff', 'employee_code')->ignore($ignoreId)],
            'hire_date' => ['nullable', 'date'],
            'position_title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(['permanent', 'part-time'])],
            'contract_type' => ['required', Rule::in(['CDI', 'CDD', 'Freelance'])],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'probation_period' => ['nullable', 'string', 'max:255'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'work_schedule' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:staff,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('staff', 'user_id')->ignore($ignoreId)],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'exited'])],
            'exit_date' => ['nullable', 'date'],
            'exit_reason' => ['nullable', 'string', 'max:1000'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'fixed_bonus' => ['nullable', 'numeric', 'min:0'],
            'variable_bonus' => ['nullable', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys($this->paymentMethodOptions()))],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'rib' => ['nullable', 'string', 'max:255'],
            'cnss_number' => ['nullable', 'string', 'max:255'],
            'cnss_affiliation_number' => ['nullable', 'string', 'max:255'],
            'tax_information' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function buildPayload(array $validated, Request $request, ?Staff $staff = null): array
    {
        foreach ([
            'date_of_birth',
            'birth_place',
            'nationality',
            'cin',
            'cin_issued_at',
            'address_line',
            'governorate',
            'delegation',
            'phone',
            'email',
            'marital_status',
            'dependent_children_count',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'emergency_contact_phone_secondary',
            'employee_code',
            'hire_date',
            'department_id',
            'contract_start_date',
            'contract_end_date',
            'probation_period',
            'work_location',
            'work_schedule',
            'manager_id',
            'user_id',
            'exit_date',
            'exit_reason',
            'base_salary',
            'fixed_bonus',
            'variable_bonus',
            'allowances',
            'payment_method',
            'bank_name',
            'rib',
            'cnss_number',
            'cnss_affiliation_number',
            'tax_information',
        ] as $nullableField) {
            $validated[$nullableField] = filled($validated[$nullableField] ?? null) ? $validated[$nullableField] : null;
        }

        if (! empty($validated['department_name'])) {
            $department = Department::query()->firstOrCreate(
                ['name' => $validated['department_name']],
                ['status' => 'active']
            );
            $validated['department_id'] = $department->id;
        }

        unset($validated['department_name']);

        if (($validated['contract_type'] ?? null) !== 'CDD') {
            $validated['contract_end_date'] = null;
        }

        if (($validated['status'] ?? 'active') !== 'exited') {
            $validated['exit_date'] = null;
            $validated['exit_reason'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($staff?->photo_path) {
                Storage::disk('public')->delete($staff->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('staff', 'public');
        }

        unset($validated['photo']);

        return $validated;
    }

    private function formOptions(): array
    {
        return [
            'maritalStatusOptions' => $this->maritalStatusOptions(),
            'paymentMethodOptions' => $this->paymentMethodOptions(),
            'documentTypeOptions' => $this->documentTypeOptions(),
        ];
    }

    private function maritalStatusOptions(): array
    {
        return [
            'single' => 'Celibataire',
            'married' => 'Marie(e)',
            'divorced' => 'Divorce(e)',
            'widowed' => 'Veuf / Veuve',
            'other' => 'Autre',
        ];
    }

    private function paymentMethodOptions(): array
    {
        return [
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Especes',
            'check' => 'Cheque',
            'other' => 'Autre',
        ];
    }

    private function documentTypeOptions(): array
    {
        return [
            'cin_copy' => 'Copie CIN',
            'employment_contract' => 'Contrat de travail',
            'diploma_certificate' => 'Diplomes / certificats',
            'attestation' => 'Attestations',
            'rib' => 'RIB',
            'cnss' => 'Documents CNSS',
            'administrative' => 'Documents administratifs',
            'contract_addendum' => 'Avenants au contrat',
            'exit_document' => 'Documents de sortie',
        ];
    }
}
