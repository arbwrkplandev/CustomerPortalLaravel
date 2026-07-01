<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1e293b; margin: 0; }
  .header { background: #1e1b4b; color: white; padding: 28px 40px; }
  .content { padding: 32px 40px; }
  .signature-section { border: 2px solid #6366f1; border-radius: 8px; padding: 20px; margin-top: 32px; }
  .sig-image { max-width: 300px; max-height: 80px; border-bottom: 1px solid #333; }
  .field-row { margin-bottom: 12px; display: flex; gap: 16px; }
  .field-label { font-size: 11px; color: #64748b; text-transform: uppercase; }
  .field-value { font-size: 14px; font-weight: 600; }
  .watermark { position: fixed; top: 45%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 60px; font-weight: 900; color: rgba(99,102,241,0.08); z-index: 0; }
</style>
</head>
<body>
<div class="watermark">SIGNED</div>
<div class="header">
  <div style="font-size: 24px; font-weight: 900;">WrkPlan</div>
  <div style="font-size: 13px; opacity: 0.7; margin-top: 4px;">SIGNED CONTRACT DOCUMENT</div>
</div>
<div class="content">
  <h2>{{ $contract->title }}</h2>
  <p><strong>Contract #:</strong> {{ $contract->contract_number }}</p>
  <p><strong>Customer:</strong> {{ $contract->tenant->company_name }}</p>
  <p><strong>Period:</strong> {{ $contract->start_date?->format('M d, Y') }} – {{ $contract->end_date?->format('M d, Y') }}</p>

  @if($contract->html_content)
  <div style="margin: 24px 0; padding: 20px; background: #f8fafc; border-radius: 8px;">
    {!! $contract->html_content !!}
  </div>
  @endif

  <div class="signature-section">
    <h3 style="margin: 0 0 16px; color: #6366f1;">Electronic Signature</h3>
    @if(isset($signatureData['signature_data']))
    <img src="{{ $signatureData['signature_data'] }}" class="sig-image">
    @endif
    <div class="field-row" style="margin-top: 12px">
      <div>
        <div class="field-label">Signed By</div>
        <div class="field-value">{{ $signatureData['signer_name'] ?? $contract->signer_name }}</div>
      </div>
      <div>
        <div class="field-label">Date & Time</div>
        <div class="field-value">{{ $signedAt }}</div>
      </div>
      <div>
        <div class="field-label">IP Address</div>
        <div class="field-value">{{ request()->ip() }}</div>
      </div>
    </div>
    <p style="font-size: 11px; color: #64748b; margin: 12px 0 0;">
      This document was electronically signed using WrkPlan's secure e-signature system. 
      The signature above is legally binding and represents the signer's agreement to the terms herein.
    </p>
  </div>
</div>
</body>
</html>
