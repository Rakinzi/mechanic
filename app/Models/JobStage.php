<?php

namespace App\Models;

use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobStage extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    /**
     * The column that serves as the model's unique identifier for route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_card_id',
        'stage_id',
        'assigned_technician_id',
        'sequence',
        'planned_duration_value',
        'planned_duration_unit',
        'status',
        'started_at',
        'actual_started_at',
        'paused_at',
        'blocked_at',
        'due_at',
        'planned_due_at',
        'completed_at',
        'actual_completed_at',
        'handoff_ready_at',
        'last_status_changed_at',
        'latest_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StageStatus::class,
            'planned_duration_value' => 'integer',
            'started_at' => 'datetime',
            'actual_started_at' => 'datetime',
            'paused_at' => 'datetime',
            'blocked_at' => 'datetime',
            'due_at' => 'datetime',
            'planned_due_at' => 'datetime',
            'completed_at' => 'datetime',
            'actual_completed_at' => 'datetime',
            'handoff_ready_at' => 'datetime',
            'last_status_changed_at' => 'datetime',
            'sequence' => 'integer',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'job_stage_technicians')->withTimestamps();
    }

    public function scopeAssignedToTechnician(Builder $query, User|int $technician): Builder
    {
        $technicianId = $technician instanceof User ? $technician->getKey() : $technician;

        return $query->where(function (Builder $builder) use ($technicianId): void {
            $builder
                ->where('assigned_technician_id', $technicianId)
                ->orWhereHas('technicians', function (Builder $techniciansQuery) use ($technicianId): void {
                    $techniciansQuery->whereKey($technicianId);
                });
        });
    }

    public function isAssignedToTechnician(User|int $technician): bool
    {
        $technicianId = $technician instanceof User ? $technician->getKey() : $technician;

        if ($this->assigned_technician_id === $technicianId) {
            return true;
        }

        if ($this->relationLoaded('technicians')) {
            return $this->technicians->contains('id', $technicianId);
        }

        return $this->technicians()->whereKey($technicianId)->exists();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StageLog::class)->orderByDesc('happened_at');
    }

    public function delayReports(): HasMany
    {
        return $this->hasMany(DelayReport::class)->latest();
    }
}
