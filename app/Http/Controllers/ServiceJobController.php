<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\ServiceJobRequest;
use App\Models\ServiceJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceJobController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(JobStatus::class)],
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $jobs = $request->user()->serviceJobs()
            ->with('customer')
            ->search($filters['q'] ?? null)
            ->status($filters['status'] ?? null)
            ->sortByDate($filters['sort'] ?? 'desc')
            ->paginate(10)
            ->withQueryString(); // keeps ?q=&status=&sort= in the page links

        return view('jobs.index', [
            'jobs' => $jobs,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
                'sort' => $filters['sort'] ?? 'desc',
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $customers = $request->user()->customers()->orderBy('name')->get(['id', 'name']);

        $job = new ServiceJob([
            'status' => JobStatus::Quote,
            'customer_id' => $request->integer('customer_id') ?: null,
        ]);

        return view('jobs.create', ['job' => $job, 'customers' => $customers]);
    }

    public function store(ServiceJobRequest $request): RedirectResponse
    {
        $job = ServiceJob::create($request->validated());

        return redirect()->route('jobs.show', $job)->with('status', 'Job created.');
    }

    public function show(ServiceJob $job): View
    {
        Gate::authorize('view', $job);

        return view('jobs.show', ['job' => $job->load('customer')]);
    }

    public function edit(Request $request, ServiceJob $job): View
    {
        Gate::authorize('update', $job);

        $customers = $request->user()->customers()->orderBy('name')->get(['id', 'name']);

        return view('jobs.edit', ['job' => $job, 'customers' => $customers]);
    }

    public function update(ServiceJobRequest $request, ServiceJob $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $job->update($request->validated());

        return redirect()->route('jobs.show', $job)->with('status', 'Job updated.');
    }

    public function destroy(ServiceJob $job): RedirectResponse
    {
        Gate::authorize('delete', $job);

        $job->delete();

        return redirect()->route('jobs.index')->with('status', 'Job deleted.');
    }
}
