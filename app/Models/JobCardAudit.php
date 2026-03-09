<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardAudit extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_card_id',
        'job_stage_id',
        'actor_id',
        'event',
        'description',
        'metadata',
        'happened_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'happened_at' => 'datetime',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    public function jobStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
