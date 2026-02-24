<?php

namespace App\Models;

use App\Enums\DelayReasonCategory;
use App\Enums\DelayReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DelayReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_stage_id',
        'submitted_by',
        'reviewed_by',
        'reason_category',
        'explanation',
        'proposed_eta',
        'status',
        'review_comment',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason_category' => DelayReasonCategory::class,
            'status' => DelayReportStatus::class,
            'proposed_eta' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function jobStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
