<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #1e293b; margin: 0; padding: 0; }
  .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 32px 40px; }
  .logo { font-size: 28px; font-weight: 900; }
  .invoice-title { font-size: 14px; opacity: 0.8; margin-top: 4px; }
  .content { padding: 32px 40px; }
  .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
  .meta-box h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin: 0 0 8px; }
  .meta-box p { margin: 4px 0; font-size: 13px; }
  .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
  .badge-paid { background: #dcfce7; color: #16a34a; }
  .badge-pending { background: #fef9c3; color: #ca8a04; }
  .badge-overdue { background: #fee2e2; color: #dc2626; }
  table { width: 100%; border-collapse: collapse; margin: 24px 0; }
  th { background: #f1f5f9; padding: 10px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; }
  td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
  .totals { margin-left: auto; width: 280px; }
  .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
  .total-row.grand-total { font-weight: 900; font-size: 16px; border-top: 2px solid #6366f1; padding-top: 10px; margin-top: 6px; color: #6366f1; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
  <div class="logo">WrkPlan</div>
  <div class="invoice-title">TAX INVOICE</div>
  <div style="float: right; text-align: right; margin-top: -48px;">
    <div style="font-size: 22px; font-weight: 900;">{{ $invoice->invoice_number }}</div>
    <div style="opacity: 0.8; font-size: 12px; margin-top: 4px;">
      Issued: {{ $invoice->issue_date->format('M d, Y') }}
    </div>
  </div>
</div>
<div class="content">
  <div class="meta-grid">
    <div class="meta-box">
      <h4>Bill To</h4>
      <p style="font-weight: 700;">{{ $invoice->tenant->company_name }}</p>
      <p>{{ $invoice->tenant->contact_name }}</p>
      <p>{{ $invoice->tenant->contact_email }}</p>
      @if($invoice->tenant->address)
      <p>{{ $invoice->tenant->address }}, {{ $invoice->tenant->city }}</p>
      @endif
    </div>
    <div class="meta-box">
      <h4>Invoice Details</h4>
      <p><strong>Status:</strong>
        <span class="badge badge-{{ $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'overdue' ? 'overdue' : 'pending') }}">
          {{ strtoupper($invoice->status) }}
        </span>
      </p>
      <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
      @if($invoice->paid_date)
      <p><strong>Paid On:</strong> {{ $invoice->paid_date->format('M d, Y') }}</p>
      @endif
      @if($invoice->subscription)
      <p><strong>Plan:</strong> {{ $invoice->subscription->plan->name }}</p>
      <p><strong>Cycle:</strong> {{ ucfirst($invoice->subscription->billing_cycle) }}</p>
      @endif
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th style="text-align:right">Qty</th>
        <th style="text-align:right">Unit Price</th>
        <th style="text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      @if($invoice->line_items)
        @foreach($invoice->line_items as $item)
        <tr>
          <td>{{ $item['description'] ?? '' }}</td>
          <td style="text-align:right">{{ $item['quantity'] ?? 1 }}</td>
          <td style="text-align:right">{{ $invoice->currency }} {{ number_format($item['unit_price'] ?? 0, 2) }}</td>
          <td style="text-align:right">{{ $invoice->currency }} {{ number_format($item['total'] ?? 0, 2) }}</td>
        </tr>
        @endforeach
      @else
      <tr>
        <td>{{ $invoice->subscription?->plan?->name ?? 'Services' }}</td>
        <td style="text-align:right">1</td>
        <td style="text-align:right">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
        <td style="text-align:right">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
      </tr>
      @endif
    </tbody>
  </table>

  <div class="totals">
    <div class="total-row">
      <span>Subtotal</span>
      <span>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
    </div>
    @if($invoice->discount_amount > 0)
    <div class="total-row" style="color: #16a34a">
      <span>Discount</span>
      <span>-{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</span>
    </div>
    @endif
    @if($invoice->tax_amount > 0)
    <div class="total-row">
      <span>Tax</span>
      <span>{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
    </div>
    @endif
    <div class="total-row grand-total">
      <span>Total Due</span>
      <span>{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</span>
    </div>
  </div>

  @if($invoice->notes)
  <div style="margin-top: 32px; padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #6366f1;">
    <p style="font-size: 12px; font-weight: 700; color: #64748b; margin: 0 0 6px;">NOTES</p>
    <p style="font-size: 13px; margin: 0;">{{ $invoice->notes }}</p>
  </div>
  @endif
</div>
<div class="footer">
  WrkPlan Platform &bull; Generated on {{ now()->format('M d, Y H:i') }} &bull; Thank you for your business!
</div>
</body>
</html>
