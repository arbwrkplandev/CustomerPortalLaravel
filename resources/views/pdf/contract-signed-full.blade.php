<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.6; }
.page { padding: 50px 55px; }
.header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #6366f1; padding-bottom: 18px; margin-bottom: 28px; }
.brand { font-size: 26px; font-weight: 800; color: #6366f1; letter-spacing: -1px; }
.brand-sub { font-size: 10px; font-weight: 400; color: #94a3b8; margin-top: 3px; }
.status-badge { padding: 5px 14px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.badge-signed { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
.contract-ref { font-size: 10px; color: #64748b; margin-top: 5px; font-family: monospace; }
.meta-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 22px; margin-bottom: 28px; }
.meta-grid { display: flex; flex-wrap: wrap; gap: 0; }
.meta-item { width: 50%; padding: 5px 0; }
.meta-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 2px; }
.meta-val { font-size: 12px; color: #1e293b; font-weight: 600; }
.section-heading { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 7px; margin-bottom: 14px; }
.contract-body { font-size: 10.5px; line-height: 1.85; color: #334155; word-wrap: break-word; }
.contract-body h1 { font-size: 1.6em; font-weight: 800; margin: 0.6em 0 0.3em; color: #1e293b; }
.contract-body h2 { font-size: 1.25em; font-weight: 700; margin: 0.55em 0 0.25em; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
.contract-body h3 { font-size: 1.05em; font-weight: 700; margin: 0.5em 0 0.2em; color: #1e293b; }
.contract-body p { margin: 0.4em 0; }
.contract-body strong, .contract-body b { font-weight: 700; }
.contract-body em, .contract-body i { font-style: italic; }
.contract-body ul { list-style: disc; padding-left: 20px; margin: 0.4em 0; }
.contract-body ol { list-style: decimal; padding-left: 20px; margin: 0.4em 0; }
.contract-body li { margin: 0.15em 0; }
.contract-body blockquote { border-left: 3px solid #6366f1; padding-left: 12px; color: #475569; margin: 10px 0; font-style: italic; }
.ql-align-center { text-align: center; }
.ql-align-right { text-align: right; }
.ql-align-justify { text-align: justify; }
.sig-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 22px; margin-bottom: 18px; }
.sig-row { padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.sig-row:last-child { border-bottom: none; padding-bottom: 0; }
.sig-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; margin-bottom: 6px; }
.sig-img { max-width: 260px; max-height: 70px; border: 1px solid #e2e8f0; border-radius: 6px; background: white; display: block; }
.sig-text { font-size: 13px; color: #1e293b; font-weight: 600; }
.sig-date { font-size: 12px; color: #3b82f6; font-weight: 600; }
.audit-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-top: 22px; }
.audit-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #1e40af; margin-bottom: 12px; }
.audit-row { display: flex; padding: 4px 0; font-size: 9.5px; }
.audit-key { width: 130px; font-weight: 600; color: #1d4ed8; flex-shrink: 0; }
.audit-val { color: #1e3a8a; }
.page-break { page-break-before: always; }
.footer { border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 30px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div>
      <div class="brand">WrkPlan</div>
      <div class="brand-sub">Contract Management Platform</div>
    </div>
    <div style="text-align:right">
      @if($contract->status === 'signed')
        <div class="status-badge badge-signed">&#10003; Electronically Signed</div>
      @else
        <div class="status-badge badge-pending">Pending Signature</div>
      @endif
      <div class="contract-ref">{{ $contract->contract_number }}</div>
    </div>
  </div>

  {{-- Contract Metadata --}}
  <div class="meta-box">
    <div class="meta-grid">
      <div class="meta-item">
        <div class="meta-label">Contract Title</div>
        <div class="meta-val">{{ $contract->title }}</div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Customer</div>
        <div class="meta-val">{{ $contract->tenant->company_name }}</div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Contract Type</div>
        <div class="meta-val">{{ ucfirst($contract->type) }} Agreement</div>
      </div>
      <div class="meta-item">
        <div class="meta-label">Status</div>
        <div class="meta-val">{{ ucwords(str_replace('_', ' ', $contract->status)) }}</div>
      </div>
      @if($contract->start_date)
      <div class="meta-item">
        <div class="meta-label">Valid From</div>
        <div class="meta-val">{{ $contract->start_date->format('F d, Y') }}</div>
      </div>
      @endif
      @if($contract->end_date)
      <div class="meta-item">
        <div class="meta-label">Valid Until</div>
        <div class="meta-val">{{ $contract->end_date->format('F d, Y') }}</div>
      </div>
      @endif
    </div>
  </div>

  {{-- Contract Content --}}
  @if($contract->html_content)
  <div style="margin-bottom: 28px">
    <div class="section-heading">Contract Terms &amp; Conditions</div>
    <div class="contract-body">{!! $contract->html_content !!}</div>
  </div>
  @if($contract->status === 'signed')
  <div class="page-break"></div>
  @endif
  @endif

  {{-- Signature Record --}}
  @if($contract->status === 'signed')
  <div style="margin-bottom: 28px">
    <div class="section-heading">Electronic Signature Record</div>
    <div class="sig-box">

      {{-- Main Signature Image --}}
      @if(!empty($signatureData['signature_image']))
      <div class="sig-row">
        <div class="sig-label">Digital Signature</div>
        <img class="sig-img" src="{{ $signatureData['signature_image'] }}" alt="Signature">
      </div>
      @endif

      {{-- Signer Name --}}
      @if(!empty($signatureData['signer_name']))
      <div class="sig-row">
        <div class="sig-label">Signed By</div>
        <div class="sig-text">{{ $signatureData['signer_name'] }}</div>
      </div>
      @endif

      {{-- Other Sign Fields --}}
      @foreach($contract->signFields as $field)
        @if($field->value && !in_array($field->field_type, ['signature', 'initials']))
        <div class="sig-row">
          <div class="sig-label">{{ $field->label }}</div>
          @if($field->field_type === 'date')
            <div class="sig-date">{{ $field->value }}</div>
          @else
            <div class="sig-text">{{ $field->value }}</div>
          @endif
        </div>
        @endif
      @endforeach
    </div>

    {{-- Audit Trail --}}
    <div class="audit-box">
      <div class="audit-title">Electronic Signature Audit Trail</div>
      <div class="audit-row"><div class="audit-key">Signed At</div><div class="audit-val">{{ $signedAt }}</div></div>
      <div class="audit-row"><div class="audit-key">Signed By</div><div class="audit-val">{{ $signatureData['signer_name'] ?? 'N/A' }}</div></div>
      <div class="audit-row"><div class="audit-key">Signer IP Address</div><div class="audit-val">{{ $contract->signer_ip ?? 'N/A' }}</div></div>
      <div class="audit-row"><div class="audit-key">Contract Reference</div><div class="audit-val">{{ $contract->contract_number }}</div></div>
      <div class="audit-row"><div class="audit-key">Document Integrity</div><div class="audit-val">SHA-256: {{ substr(hash('sha256', $contract->contract_number . $signedAt), 0, 40) }}...</div></div>
      <div style="margin-top:10px; font-size:9px; color:#1e40af; line-height:1.5">
        This document was electronically signed using WrkPlan's secure e-signature platform.
        The signature is legally binding under applicable electronic signature laws and regulations.
      </div>
    </div>
  </div>
  @endif

  <div class="footer">
    <div>Generated by WrkPlan Contract Management &bull; {{ now()->format('Y') }}</div>
    <div>{{ $contract->contract_number }}</div>
  </div>

</div>
</body>
</html>
