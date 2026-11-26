<?php

namespace App\Policies;

use App\Models\ServiceJob;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A job belongs to a customer, and the customer belongs to a user.
 * Only that user may see or change the job; everyone else gets a 404.
 */
class ServiceJobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceJob $job): Response
    {
        return $this->owns($user, $job);
    }

    public function update(User $user, ServiceJob $job): Response
    {
        return $this->owns($user, $job);
    }

    public function delete(User $user, ServiceJob $job): Response
    {
        return $this->owns($user, $job);
    }

    private function owns(User $user, ServiceJob $job): Response
    {
        return $job->isOwnedBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
