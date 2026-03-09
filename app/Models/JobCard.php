<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_id',
        'created_by',
        'job_number',
        'customer_complaint',
        'diagnosis_notes',
        'status',
        'current_job_stage_id',
        'received_at',
        'promised_delivery_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'promised_delivery_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentJobStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'current_job_stage_id');
    }

    public function jobStages(): HasMany
    {
        return $this->hasMany(JobStage::class)->orderBy('sequence');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(JobCardAudit::class)->orderByDesc('happened_at');
    }
}
