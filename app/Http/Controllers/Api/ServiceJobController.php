<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateJobStatusRequest;
use App\Http\Requests\ServiceJobRequest;
use App\Http\Resources\ServiceJobCollection;
use App\Http\Resources\ServiceJobResource;
use App\Models\ServiceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * REST API for jobs (Sanctum bearer token required).
 *   GET   /api/jobs              ?search= &status= &per_page=
 *   GET   /api/jobs/{id}
 *   POST  /api/jobs
 *   PATCH /api/jobs/{id}/status
 */
class ServiceJobController extends Controller
{
    public function index(Request $request): ServiceJobCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(JobStatus::class)],
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $jobs = $request->user()->serviceJobs()
            ->with('customer')
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->sortByDate($filters['sort'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return new ServiceJobCollection($jobs);
    }

    public function show(ServiceJob $job): ServiceJobResource
    {
        Gate::authorize('view', $job);

        return new ServiceJobResource($job->load('customer'));
    }

    public function store(ServiceJobRequest $request): JsonResponse
    {
        $job = ServiceJob::create($request->validated())->load('customer');

        return (new ServiceJobResource($job))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.jobs.show', $job));
    }

    public function updateStatus(UpdateJobStatusRequest $request, ServiceJob $job): ServiceJobResource
    {
        // Ownership is already checked in UpdateJobStatusRequest::authorize().
        $job->update(['status' => $request->validated('status')]);

        return new ServiceJobResource($job->load('customer'));
    }
}
