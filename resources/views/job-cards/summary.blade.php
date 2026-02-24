<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Card Summary - {{ $jobCard->job_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 24px; line-height: 1.45; }
        h1, h2, h3 { margin: 0 0 8px; }
        h1 { font-size: 24px; }
        h2 { font-size: 18px; margin-top: 24px; }
        h3 { font-size: 15px; margin-top: 16px; }
        p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; font-size: 13px; }
        th { background: #f1f5f9; }
        .meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 24px; }
        .muted { color: #475569; }
        .break { page-break-before: always; }
        @media print {
            .print-note { display: none; }
            body { margin: 12mm; }
        }
    </style>
</head>
<body>
    <p class="print-note muted">PDF-ready summary. Use browser print and choose "Save as PDF".</p>
    <h1>Job Card {{ $jobCard->job_number }}</h1>
    <div class="meta">
        <p><strong>Status:</strong> {{ $jobCard->status }}</p>
        <p><strong>Received:</strong> {{ optional($jobCard->received_at)->toDayDateTimeString() ?? 'N/A' }}</p>
        <p><strong>Promised Delivery:</strong> {{ optional($jobCard->promised_delivery_at)->toDayDateTimeString() ?? 'N/A' }}</p>
        <p><strong>Closed:</strong> {{ optional($jobCard->closed_at)->toDayDateTimeString() ?? 'N/A' }}</p>
    </div>

    <h2>Vehicle & Client</h2>
    <p><strong>Vehicle:</strong> {{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }} ({{ $jobCard->vehicle->registration_number }})</p>
    <p><strong>VIN:</strong> {{ $jobCard->vehicle->vin ?? 'N/A' }}</p>
    <p><strong>Client:</strong> {{ $jobCard->vehicle->client->name }}</p>
    <p><strong>Complaint:</strong> {{ $jobCard->customer_complaint }}</p>
    <p><strong>Diagnosis Notes:</strong> {{ $jobCard->diagnosis_notes ?? 'N/A' }}</p>

    <h2>Stage Timeline</h2>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Stage</th>
            <th>Status</th>
            <th>Mechanic</th>
            <th>Started</th>
            <th>Due</th>
            <th>Completed</th>
        </tr>
        </thead>
        <tbody>
        @foreach($jobCard->jobStages as $jobStage)
            <tr>
                <td>{{ $jobStage->sequence }}</td>
                <td>{{ $jobStage->stage->name }}</td>
                <td>{{ $jobStage->status->value }}</td>
                <td>{{ $jobStage->assignedMechanic?->name ?? 'Unassigned' }}</td>
                <td>{{ optional($jobStage->started_at)->toDayDateTimeString() ?? 'N/A' }}</td>
                <td>{{ optional($jobStage->due_at)->toDayDateTimeString() ?? 'N/A' }}</td>
                <td>{{ optional($jobStage->completed_at)->toDayDateTimeString() ?? 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="break"></div>

    <h2>Delay Reports</h2>
    @foreach($jobCard->jobStages as $jobStage)
        @if($jobStage->delayReports->isNotEmpty())
            <h3>{{ $jobStage->stage->name }}</h3>
            <table>
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
                        <tr>
                            <td>{{ $report->status->value }}</td>
                            <td>{{ $report->reason_category->value }}</td>
                            <td>{{ $report->explanation }}</td>
                            <td>{{ $report->proposed_eta->toDayDateTimeString() }}</td>
                            <td>{{ $report->submitter?->name ?? 'N/A' }}</td>
                            <td>{{ $report->reviewer?->name ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <h2>Audit Log</h2>
    @foreach($jobCard->jobStages as $jobStage)
        <h3>{{ $jobStage->sequence }}. {{ $jobStage->stage->name }}</h3>
        <table>
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
                    <td>{{ $log->happened_at->toDayDateTimeString() }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->from_status ?? 'N/A' }}</td>
                    <td>{{ $log->to_status ?? 'N/A' }}</td>
                    <td>{{ $log->actor?->name ?? 'System' }}</td>
                    <td>{{ $log->metadata ? json_encode($log->metadata) : 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No log entries.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
