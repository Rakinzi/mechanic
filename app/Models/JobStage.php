<?php

namespace App\Models;

use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobStage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_card_id',
        'stage_id',
        'assigned_mechanic_id',
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

    public function assignedMechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_mechanic_id');
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
