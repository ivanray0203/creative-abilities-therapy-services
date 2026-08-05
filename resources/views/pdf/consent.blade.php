<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 72px; }
        body { font-family: Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; }
        .title { font-family: Helvetica-Bold, sans-serif; font-size: 18pt; margin-bottom: 12px; }
        .info-row { margin-bottom: 4px; }
        .info-label { color: #555; }
        .doc-list { margin-top: 18px; }
        .doc-list li { margin-bottom: 6px; }
        .page-break { page-break-before: always; }
        .heading { font-family: Helvetica-Bold, sans-serif; font-size: 14pt; margin-bottom: 4px; }
        .meta { font-size: 9pt; color: #555; margin-bottom: 16px; }
        .section-title { font-family: Helvetica-Bold, sans-serif; font-size: 11pt; margin-top: 14px; margin-bottom: 4px; }
        p { line-height: 1.5; }
    </style>
</head>
<body>
    <p class="title">Consent Acceptance Summary</p>

    <div class="info-row"><span class="info-label">Child Name:</span> {{ $intake->child_first_name }} {{ $intake->child_last_name }}</div>
    <div class="info-row"><span class="info-label">Parent/Guardian:</span> {{ $intake->primary_parent_name }}</div>
    <div class="info-row"><span class="info-label">Email:</span> {{ $intake->primary_parent_email }}</div>
    <div class="info-row"><span class="info-label">Reference Number:</span> {{ $intake->reference_number }}</div>
    <div class="info-row"><span class="info-label">Submission Date:</span> {{ $intake->created_at?->format('F j, Y') }}</div>

    <p class="section-title">Accepted Documents</p>
    <ul class="doc-list">
        @foreach ($documents as $document)
            <li>{{ $document->title }} (v{{ $document->version }})</li>
        @endforeach
    </ul>

    @foreach ($documents as $document)
        <div class="page-break">
            <p class="heading">{{ $document->title }}</p>
            <p class="meta">
                Version {{ $document->version }} &middot;
                Effective {{ $document->effective_date?->format('F j, Y') }} &middot;
                Accepted on {{ $intake->created_at?->format('F j, Y') }}
            </p>

            @foreach ($document->clauses as $clause)
                <p class="section-title">{{ $clause->order === 0 ? 'Introduction' : "Section {$clause->order}" }}</p>
                <p>{!! nl2br(e($clause->text_template)) !!}</p>
            @endforeach
        </div>
    @endforeach
</body>
</html>
