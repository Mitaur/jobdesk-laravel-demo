<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A user can only see and change their own customers.
 * Other users get a 404 (we don't even reveal that the record exists).
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): Response
    {
        return $this->owns($user, $customer);
    }

    public function update(User $user, Customer $customer): Response
    {
        return $this->owns($user, $customer);
    }

    public function delete(User $user, Customer $customer): Response
    {
        return $this->owns($user, $customer);
    }

    private function owns(User $user, Customer $customer): Response
    {
        return (int) $customer->user_id === (int) $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
