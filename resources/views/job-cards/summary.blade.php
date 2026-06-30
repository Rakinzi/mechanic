<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card Summary — {{ $jobCard->job_number }}</title>
    <style>
        @page {
            margin: 18mm 16mm;
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 13px;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ── Header bar ─────────────────────────────── */
        .doc-header {
            background: #0f172a;
            color: #ffffff;
            padding: 18px 24px 14px;
            margin-bottom: 24px;
        }
        .doc-header-inner {
            width: 100%;
        }
        .doc-header td {
            border: none;
            padding: 0;
            vertical-align: top;
            background: transparent;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: -0.3px;
            color: #ffffff;
            margin: 0 0 2px;
        }
        .doc-subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }
        .doc-badge {
            font-size: 12px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 999px;
            display: inline-block;
            text-align: right;
            float: right;
        }
        .badge-open   { background: #dbeafe; color: #1d4ed8; }
        .badge-closed { background: #dcfce7; color: #15803d; }
        .badge-other  { background: #f1f5f9; color: #475569; }

        /* ── Section headings ───────────────────────── */
        .section-heading {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 20px 0 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Info cards (2-col table) ───────────────── */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .info-grid td {
            border: none;
            padding: 3px 0;
            vertical-align: top;
            font-size: 13px;
            width: 50%;
        }
        .info-label {
            color: #64748b;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: block;
        }
        .info-value {
            color: #1e293b;
            font-size: 13px;
            display: block;
        }

        /* ── Info card block ────────────────────────── */
        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .card-row {
            width: 100%;
            border-collapse: collapse;
        }
        .card-row td {
            border: none;
            padding: 4px 8px 4px 0;
            vertical-align: top;
            width: 50%;
            background: transparent;
        }

        /* ── Data tables ────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            page-break-inside: avoid;
        }
        .data-table th {
            background: #0f172a;
            color: #f1f5f9;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            text-align: left;
            border: none;
        }
        .data-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 12.5px;
            color: #334155;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* ── Status pills ───────────────────────────── */
        .pill {
            display: inline-block;
            font-size: 10.5px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 999px;
            letter-spacing: 0.3px;
        }
        .pill-not-started  { background: #f1f5f9; color: #475569; }
        .pill-in-progress  { background: #fef9c3; color: #a16207; }
        .pill-completed    { background: #dcfce7; color: #15803d; }
        .pill-pending      { background: #fff7ed; color: #c2410c; }
        .pill-approved     { background: #dcfce7; color: #15803d; }
        .pill-rejected     { background: #fee2e2; color: #b91c1c; }

        /* ── Sub-section headings (audit / delay per stage) */
        .sub-heading {
            font-size: 13px;
            font-weight: bold;
            color: #334155;
            margin: 16px 0 6px;
            padding: 6px 10px;
            background: #f1f5f9;
            border-left: 3px solid #0f172a;
            border-radius: 2px;
        }

        /* ── Empty state ────────────────────────────── */
        .empty-row td {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            padding: 16px;
        }

        /* ── Page break ─────────────────────────────── */
        .break { page-break-before: always; }

        /* ── Footer ─────────────────────────────────── */
        .doc-footer {
            margin-top: 32px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10.5px;
            color: #94a3b8;
        }

        @php
            $statusClass = match(strtoupper($jobCard->status)) {
                'OPEN'   => 'badge-open',
                'CLOSED' => 'badge-closed',
                default  => 'badge-other',
            };
        @endphp
    </style>
</head>
<body>

    {{-- ── Document header ────────────────────────────── --}}
    <div class="doc-header">
        <table class="doc-header-inner">
            <tr>
                <td>
                    <p class="doc-title">{{ $jobCard->job_number }}</p>
                    <p class="doc-subtitle">Job Card Summary &mdash; Generated {{ now()->toDayDateTimeString() }}</p>
                </td>
                <td style="text-align:right; vertical-align:middle;">
                    @php
                        $statusClass = match(strtoupper((string) $jobCard->status)) {
                            'OPEN'   => 'badge-open',
                            'CLOSED' => 'badge-closed',
                            default  => 'badge-other',
                        };
                    @endphp
                    <span class="doc-badge {{ $statusClass }}">{{ $jobCard->status }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Job overview ─────────────────────────────── --}}
    <div class="card">
        <table class="card-row">
            <tr>
                <td>
                    <span class="info-label">Received</span>
                    <span class="info-value">{{ optional($jobCard->received_at)->toDayDateTimeString() ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="info-label">Promised Delivery</span>
                    <span class="info-value">{{ optional($jobCard->promised_delivery_at)->toDayDateTimeString() ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="info-label">Closed</span>
                    <span class="info-value">{{ optional($jobCard->closed_at)->toDayDateTimeString() ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="info-label">Created By</span>
                    <span class="info-value">{{ $jobCard->creator?->name ?? 'N/A' }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Vehicle & Client ─────────────────────────── --}}
    <p class="section-heading">Vehicle &amp; Client</p>
    <div class="card">
        <table class="card-row">
            <tr>
                <td>
                    <span class="info-label">Vehicle</span>
                    <span class="info-value">{{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }} ({{ $jobCard->vehicle->registration_number }})</span>
                </td>
                <td>
                    <span class="info-label">VIN</span>
                    <span class="info-value">{{ $jobCard->vehicle->vin ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="info-label">Client</span>
                    <span class="info-value">{{ $jobCard->vehicle->client->name }}</span>
                </td>
                <td>
                    <span class="info-label">Colour / Year</span>
                    <span class="info-value">{{ $jobCard->vehicle->color ?? 'N/A' }} &mdash; {{ $jobCard->vehicle->model_year ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="info-label">Customer Complaint</span>
                    <span class="info-value">{{ $jobCard->customer_complaint }}</span>
                </td>
            </tr>
            @if($jobCard->diagnosis_notes)
            <tr>
                <td colspan="2">
                    <span class="info-label">Diagnosis Notes</span>
                    <span class="info-value">{{ $jobCard->diagnosis_notes }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ── Stage Timeline ────────────────────────────── --}}
    <p class="section-heading">Stage Timeline</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Stage</th>
                <th>Status</th>
                <th>Technicians</th>
                <th>Planned Duration</th>
                <th>Started</th>
                <th>Due</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
        @foreach($jobCard->jobStages as $jobStage)
            @php
                $statusVal = strtoupper((string) $jobStage->status->value);
                $pillClass = match($statusVal) {
                    'IN_PROGRESS'  => 'pill-in-progress',
                    'COMPLETED'    => 'pill-completed',
                    default        => 'pill-not-started',
                };
            @endphp
            <tr>
                <td style="text-align:center; font-weight:bold;">{{ $jobStage->sequence }}</td>
                <td style="font-weight:600;">{{ $jobStage->stage->name }}</td>
                <td><span class="pill {{ $pillClass }}">{{ str_replace('_', ' ', $statusVal) }}</span></td>
                <td>
                    @if($jobStage->technicians->isNotEmpty())
                        {{ $jobStage->technicians->pluck('name')->join(', ') }}
                    @else
                        <span style="color:#94a3b8;">Unassigned</span>
                    @endif
                </td>
                <td>
                    @if($jobStage->planned_duration_value)
                        {{ $jobStage->planned_duration_value }} {{ $jobStage->planned_duration_unit }}
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                </td>
                <td>{{ optional($jobStage->started_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ optional($jobStage->due_at)->format('d M Y H:i') ?? '—' }}</td>
                <td>{{ optional($jobStage->completed_at)->format('d M Y H:i') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- ── Delay Reports ────────────────────────────── --}}
    <div class="break"></div>
    <p class="section-heading">Delay Reports</p>

    @php $hasDelays = $jobCard->jobStages->some(fn($s) => $s->delayReports->isNotEmpty()); @endphp

    @if(! $hasDelays)
        <p style="color:#94a3b8; font-style:italic; margin-left:4px;">No delay reports recorded.</p>
    @else
        @foreach($jobCard->jobStages as $jobStage)
            @if($jobStage->delayReports->isNotEmpty())
                <p class="sub-heading">{{ $jobStage->sequence }}. {{ $jobStage->stage->name }}</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Explanation</th>
                            <th>Proposed ETA</th>
                            <th>Submitted By</th>
                            <th>Reviewed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobStage->delayReports as $report)
                            @php
                                $drStatus = strtoupper((string) $report->status->value);
                                $drPill = match($drStatus) {
                                    'APPROVED' => 'pill-approved',
                                    'REJECTED' => 'pill-rejected',
                                    default    => 'pill-pending',
                                };
                            @endphp
                            <tr>
                                <td><span class="pill {{ $drPill }}">{{ $drStatus }}</span></td>
                                <td>{{ $report->reason_category->value }}</td>
                                <td>{{ $report->explanation }}</td>
                                <td>{{ optional($report->proposed_eta)->format('d M Y H:i') ?? '—' }}</td>
                                <td>{{ $report->submitter?->name ?? '—' }}</td>
                                <td>{{ $report->reviewer?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif

    {{-- ── Audit Log ────────────────────────────────── --}}
    <p class="section-heading" style="margin-top:24px;">Audit Log</p>

    @foreach($jobCard->jobStages as $jobStage)
        <p class="sub-heading">{{ $jobStage->sequence }}. {{ $jobStage->stage->name }}</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Action</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Actor</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
            @forelse($jobStage->logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->happened_at->format('d M Y H:i') }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->from_status ?? '—' }}</td>
                    <td>{{ $log->to_status ?? '—' }}</td>
                    <td>{{ $log->actor?->name ?? 'System' }}</td>
                    <td style="font-size:11px; color:#64748b;">{{ $log->metadata ? json_encode($log->metadata) : '—' }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6">No log entries for this stage.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- ── Footer ───────────────────────────────────── --}}
    <div class="doc-footer">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="border:none; padding:0; font-size:10.5px; color:#94a3b8;">
                    {{ config('app.name') }} &mdash; Confidential
                </td>
                <td style="border:none; padding:0; text-align:right; font-size:10.5px; color:#94a3b8;">
                    {{ now()->format('d M Y, H:i') }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
