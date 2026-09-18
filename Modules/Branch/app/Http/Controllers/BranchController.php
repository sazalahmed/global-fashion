<?php

namespace Modules\Branch\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Branch\Models\Branch;
use Modules\Branch\Services\BranchService;
use Modules\Branch\Http\Requests\StoreBranchRequest;
use Modules\Branch\Http\Requests\UpdateBranchRequest;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private BranchService $branchService)
    {
    }

    /**
     * Display a listing of branches.
     */
    public function index(Request $request)
    {
        $branches = $this->branchService->list($request->only(['search', 'status']));
        $stats = $this->branchService->getStats();

        return view('branch::index', [
            'branches'       => $branches,
            'totalBranches'  => $stats['total'],
            'activeBranches' => $stats['active'],
            'posEnabled'     => $stats['posEnabled'],
            'ecomEnabled'    => $stats['ecomEnabled'],
        ]);
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        return view('branch::create');
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request)
    {
        $this->branchService->create($request->validated());

        return redirect()->route('branches.index')->with('success', __('Branch created successfully.'));
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch)
    {
        $branch->load('users');

        return view('branch::show', [
            'branch'      => $branch,
            'users'       => $branch->users,
            'recentSales' => collect(),
        ]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch)
    {
        return view('branch::create', compact('branch'));
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->branchService->update($branch, $request->validated());

        return redirect()->route('branches.show', $branch)->with('success', __('Branch updated successfully.'));
    }

    /**
     * Toggle the branch's active status.
     */
    public function toggleStatus(Branch $branch): \Illuminate\Http\JsonResponse
    {
        $branch = $this->branchService->toggleStatus($branch);

        return response()->json(['success' => true, 'is_active' => $branch->is_active, 'message' => __('Status updated.')]);
    }

    /**
     * Remove the specified branch (soft delete).
     */
    public function destroy(Branch $branch)
    {
        if (!$this->branchService->delete($branch)) {
            return redirect()->route('branches.index')->with('error', __('Cannot delete the main branch.'));
        }

        return redirect()->route('branches.index')->with('success', __('Branch deleted successfully.'));
    }
}
