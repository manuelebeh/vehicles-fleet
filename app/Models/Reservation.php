<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'start_date',
        'end_date',
        'status',
        'purpose',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isPending(): bool
    {
        return $this->status === ReservationStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === ReservationStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReservationStatus::COMPLETED;
    }

    /**
     * Check if the reservation overlaps with the given date range.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return bool
     */
    public function overlaps(Carbon $startDate, Carbon $endDate): bool
    {
        return $this->start_date < $endDate && $this->end_date > $startDate;
    }

    /**
     * Scope a query to only include confirmed reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::CONFIRMED);
    }

    /**
     * Scope a query to only include pending reservations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::PENDING);
    }

    /**
     * Scope a query to only include active reservations (confirmed or pending).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED]);
    }
}
