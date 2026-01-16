<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $companies = Company::select('id', 'company_name')
            ->orderBy('id', 'desc')
            ->paginate(2)
            ->withQueryString();

        return Inertia::render('Company/Index', [
            'companies' => $companies,
        ]);
    }

    // Display the specified resource.
    public function show(Company $company)
    {
        return Inertia::render('Company/Show', [
            'company' => $company->load(['creator', 'updater']),
        ]);
    }

    // Implementation for storing a new company
    public function store(Request $request)
    {
        // dd($request->all());
        // Validation and creation logic would go here
        $validated = $request->validate([
            'company_name' => 'required|string|max:80|unique:companies,company_name',
        ]);

        // Assuming the authenticated user's ID is available
        $validated['created_by'] = auth()->id();

        // Create the company
        Company::create($validated);

        // Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Implementation for updating an existing company
    public function update(Request $request, Company $company)
    {
        // Validation and update logic would go here
        $validated = $request->validate([
            'company_name' => 'required|string|max:80|unique:companies,company_name,' . $company->id,
        ]);
        // Assuming the authenticated user's ID is available
        $validated['updated_by'] = auth()->id();

        // Update the company
        $company->update($validated);

        // Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Implementation for sofre deleting a company
    public function destroy(Company $company)
    {
        // Delete the company
        $company->delete();
        // Redirect back to the company index with a success message
        return redirect()->back();
    }

    // Display a listing of soft-deleted companies.
    public function trash()
    {
        $companies = Company::onlyTrashed()
            ->select('id', 'company_name', 'deleted_at')
            ->orderBy('deleted_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Company/Trash', [
            'companies' => $companies,
        ]);
    }

    // Restore a soft-deleted company.
    public function restore(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->restore();

        return redirect()->back();
    }

    // Permanently delete a soft-deleted company.
    public function forceDelete(int $id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->forceDelete();

        return redirect()->back();
    }
}
