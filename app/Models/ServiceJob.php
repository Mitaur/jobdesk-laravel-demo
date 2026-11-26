<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\ServiceJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A job / quote of the home-service business.
 * (Called ServiceJob so it never clashes with Laravel's queue "jobs".)
 */
class ServiceJob extends Model
{
    /** @use HasFactory<ServiceJobFactory> */
    use HasFactory;

    protected $fillable = ['customer_id', 'title', 'status', 'price', 'scheduled_at', 'notes'];

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'price' => 'decimal:2',
            'scheduled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isOwnedBy(User $user): bool
    {
        // customer is eager-loaded where it matters; fall back to a cheap query.
        $ownerId = $this->relationLoaded('customer')
            ? $this->customer?->user_id
            : Customer::whereKey($this->customer_id)->value('user_id');

        return (int) $ownerId === (int) $user->id;
    }

    /**
     * Search in the job title and the customer's name.
     * Works on queries that already join `customers` (e.g. User::serviceJobs()).
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('service_jobs.title', 'like', $like)
                ->orWhere('customers.name', 'like', $like);
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status && JobStatus::tryFrom($status)
            ? $query->where('service_jobs.status', $status)
            : $query;
    }

    /** Sort by scheduled date; jobs without a date always go last. */
    public function scopeSortByDate(Builder $query, ?string $direction): Builder
    {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderByRaw('service_jobs.scheduled_at IS NULL')
            ->orderBy('service_jobs.scheduled_at', $direction)
            ->orderBy('service_jobs.id', $direction);
    }
}
