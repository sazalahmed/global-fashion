@php
    use Modules\Setting\Models\Setting;

    $isReceive       = $payment->direction === 'receive';
    $directionLabel  = $isReceive ? 'Received' : 'Paid';
    $partyLabel      = $isReceive ? 'Received From' : 'Paid To';
    $isPdf           = $isPdf ?? false;

    $company = [
        'name'    => Setting::get('business', 'company_name', config('app.name', 'BizPOS Pro')),
        'address' => Setting::get('business', 'address', ''),
        'phone'   => Setting::get('business', 'phone', ''),
        'email'   => Setting::get('business', 'email', ''),
    ];
    $currency = Setting::get('localization', 'currency', 'BDT');
    $party    = $payment->party();

    $methodLabel = ucwords(str_replace('_', ' ', (string) $payment->payment_method));
    $typeLabel   = ucwords(str_replace('_', ' ', (string) $payment->payment_type));
    $money = fn ($v) => $currency . ' ' . number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt — {{ $payment->payment_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Nunito Sans', Arial, sans-serif;
            color: #2C3E50; font-size: 12px; line-height: 1.5; margin: 0; padding: 28px;
            background: #fff;
        }
        .rcpt { max-width: 760px; margin: 0 auto; }

        /* Header */
        .rcpt-head { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .rcpt-head td { vertical-align: top; }
        .rcpt-co-name { font-size: 22px; font-weight: 800; color: #1B4F72; margin: 0 0 4px; }
        .rcpt-co-meta { font-size: 11px; color: #7F8C8D; }
        .rcpt-title { text-align: right; }
        .rcpt-title h2 { font-size: 18px; font-weight: 800; letter-spacing: .04em; color: #154360; margin: 0 0 6px; text-transform: uppercase; }
        .rcpt-num { font-size: 13px; font-weight: 700; }
        .rcpt-date { font-size: 11px; color: #7F8C8D; margin-top: 2px; }

        .rcpt-badge {
            display: inline-block; margin-top: 8px; padding: 4px 12px; border-radius: 4px;
            font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #fff;
            background: {{ $isReceive ? '#1E8449' : '#C0392B' }};
        }

        .rcpt-rule { border: 0; border-top: 2px solid #1B4F72; margin: 4px 0 18px; }

        /* Two columns */
        .rcpt-cols { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .rcpt-cols td { vertical-align: top; width: 50%; padding-right: 16px; }
        .rcpt-h6 { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #1B4F72; margin: 0 0 6px; }
        .rcpt-party-name { font-size: 14px; font-weight: 800; margin-bottom: 2px; }
        .rcpt-party-meta { font-size: 11px; color: #555; margin-bottom: 1px; }

        /* Detail table */
        .rcpt-detail { width: 100%; border-collapse: collapse; }
        .rcpt-detail td { padding: 5px 0; font-size: 12px; }
        .rcpt-detail .lbl { color: #7F8C8D; width: 45%; }
        .rcpt-detail .val { font-weight: 700; text-align: right; }

        /* Allocations table */
        .rcpt-alloc { width: 100%; border-collapse: collapse; margin: 8px 0 18px; }
        .rcpt-alloc th { background: #F1F3F5; text-align: left; font-size: 10px; text-transform: uppercase;
            letter-spacing: .04em; color: #7F8C8D; padding: 8px 10px; border-bottom: 1px solid #E0E0E0; }
        .rcpt-alloc th.r, .rcpt-alloc td.r { text-align: right; }
        .rcpt-alloc td { padding: 8px 10px; border-bottom: 1px solid #EEE; font-size: 12px; }

        /* Total */
        .rcpt-total { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .rcpt-total td { padding: 14px 16px; background: #F7F9FB; border: 1px solid #E3E8ED; }
        .rcpt-total .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #7F8C8D; font-weight: 700; }
        .rcpt-total .amt { text-align: right; font-size: 24px; font-weight: 800;
            color: {{ $isReceive ? '#1E8449' : '#C0392B' }}; }

        .rcpt-note { margin-top: 16px; padding-top: 10px; border-top: 1px solid #E0E0E0; }
        .rcpt-note .lbl { font-size: 10px; text-transform: uppercase; color: #7F8C8D; font-weight: 700; margin-bottom: 2px; }

        .rcpt-foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #E0E0E0; text-align: center;
            font-size: 10px; color: #95A5A6; }
        @media print { body { padding: 0; } .rcpt { max-width: 100%; } }
    </style>
</head>
<body>
<div class="rcpt">

    <table class="rcpt-head">
        <tr>
            <td>
                <div class="rcpt-co-name">{{ $company['name'] }}</div>
                @if($company['address'])<div class="rcpt-co-meta">{{ $company['address'] }}</div>@endif
                <div class="rcpt-co-meta">
                    @if($company['phone']) Tel: {{ $company['phone'] }} @endif
                    @if($company['email']) &nbsp;·&nbsp; {{ $company['email'] }} @endif
                </div>
            </td>
            <td class="rcpt-title">
                <h2>Payment Receipt</h2>
                <div class="rcpt-num">{{ $payment->payment_number }}</div>
                <div class="rcpt-date">{{ $payment->payment_date->format('d M Y') }}</div>
                <span class="rcpt-badge">Payment {{ $directionLabel }}</span>
            </td>
        </tr>
    </table>

    <hr class="rcpt-rule">

    <table class="rcpt-cols">
        <tr>
            <td>
                <div class="rcpt-h6">{{ $partyLabel }}</div>
                @if($party)
                    <div class="rcpt-party-name">{{ $party->name }}</div>
                    @if(!empty($party->phone))<div class="rcpt-party-meta">{{ \App\Helpers\PhoneHelper::format($party->phone) }}</div>@endif
                    @if(!empty($party->email))<div class="rcpt-party-meta">{{ $party->email }}</div>@endif
                    @if(!empty($party->address))<div class="rcpt-party-meta">{{ $party->address }}</div>@endif
                    <div class="rcpt-party-meta" style="margin-top:4px;">{{ ucfirst($payment->party_type) }}</div>
                @else
                    <div class="rcpt-party-meta">Party information not available.</div>
                @endif
            </td>
            <td>
                <table class="rcpt-detail">
                    <tr><td class="lbl">Payment Method</td><td class="val">{{ $methodLabel }}</td></tr>
                    <tr><td class="lbl">Payment Account</td><td class="val">{{ $payment->paymentAccount->display_name ?? '—' }}</td></tr>
                    <tr><td class="lbl">Payment Type</td><td class="val">{{ $typeLabel }}</td></tr>
                    @if($payment->reference)
                        <tr><td class="lbl">Reference / TrxID</td><td class="val">{{ $payment->reference }}</td></tr>
                    @endif
                    <tr><td class="lbl">Created By</td><td class="val">{{ $payment->creator->name ?? '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if($payment->allocations->isNotEmpty())
        <div class="rcpt-h6">Applied To</div>
        <table class="rcpt-alloc">
            <thead>
                <tr><th>Document</th><th class="r">Allocated Amount</th><th class="r">Discount</th></tr>
            </thead>
            <tbody>
                @foreach($payment->allocations as $allocation)
                    <tr>
                        <td>
                            {{ class_basename($allocation->allocatable_type) }} —
                            {{ $allocation->allocatable->invoice_number ?? ('#' . $allocation->allocatable_id) }}
                        </td>
                        <td class="r">{{ $money($allocation->amount) }}</td>
                        <td class="r">{{ $allocation->discount_amount > 0 ? $money($allocation->discount_amount) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="rcpt-total">
        <tr>
            <td class="lbl" style="vertical-align:middle;">Amount {{ $directionLabel }}</td>
            <td class="amt">{{ $money($payment->amount) }}</td>
        </tr>
        @if($payment->discount_amount > 0)
            <tr>
                <td class="lbl" style="vertical-align:middle;">Discount Written Off</td>
                <td class="amt">{{ $money($payment->discount_amount) }}</td>
            </tr>
        @endif
    </table>

    @if($payment->note)
        <div class="rcpt-note">
            <div class="lbl">Note</div>
            <div>{{ $payment->note }}</div>
        </div>
    @endif

    <div class="rcpt-foot">
        This is a computer-generated receipt and does not require a signature.<br>
        {{ $company['name'] }} &middot; Generated on {{ now()->format('d M Y') }}
    </div>

</div>

@unless($isPdf)
    <script>window.onload = function () { window.print(); };</script>
@endunless
</body>
</html>
