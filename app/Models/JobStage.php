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
        'status',
        'started_at',
        'paused_at',
        'due_at',
        'completed_at',
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
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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
