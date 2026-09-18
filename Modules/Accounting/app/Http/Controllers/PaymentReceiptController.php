<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\PaymentReceipt;
use Modules\Accounting\Services\PaymentReceiptService;

class PaymentReceiptController extends Controller
{
    public function __construct(
        private readonly PaymentReceiptService $service,
    ) {}

    public function index(Request $request)
    {
        bpAuthorize('accounting.view');
        $receipts = $this->service->list(
            $request->only(['search', 'receipt_type', 'date_from', 'date_to']),
        );
        $stats = $this->service->getStats();

        return view('accounting::receipts.index', compact('receipts', 'stats'));
    }

    public function show(PaymentReceipt $receipt)
    {
        bpAuthorize('accounting.view');
        $receipt = $this->service->find($receipt->id);

        return view('accounting::receipts.show', compact('receipt'));
    }

    public function print(PaymentReceipt $receipt)
    {
        bpAuthorize('accounting.view');
        $receipt = $this->service->find($receipt->id);

        return view('accounting::receipts.print', compact('receipt'));
    }
}
