<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $term = trim($filters['q'] ?? '');

        $customers = $request->user()->customers()
            ->withCount('jobs')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers, 'term' => $term]);
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = $request->user()->customers()->create($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('status', 'Customer added.');
    }

    public function show(Request $request, Customer $customer): View
    {
        Gate::authorize('view', $customer);

        $jobs = $customer->jobs()
            ->sortByDate('desc')
            ->paginate(10)
            ->withQueryString();

        return view('customers.show', ['customer' => $customer, 'jobs' => $jobs]);
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        $customer->delete(); // its jobs are removed by the FK cascade

        return redirect()->route('customers.index')
            ->with('status', 'Customer deleted.');
    }
}
