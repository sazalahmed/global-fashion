<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Services\CourierWithdrawalService;
use Modules\Ecommerce\Services\SteadfastApiService;
use Modules\Payment\Models\PaymentAccount;
use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

class SteadfastOperationsController extends Controller
{
    public function __construct(
        private readonly SteadfastApiService $steadfast,
        private readonly CourierWithdrawalService $withdrawals,
    ) {}

    public function index()
    {
        bpAuthorize('ecommerce.view');
        $configured = $this->steadfast->isConfigured();
        $balance = null;
        $payments = null;
        $error = null;
        $syncWarning = null;

        if ($configured) {
            try {
                $balance  = $this->steadfast->client()->balance()->getCurrentBalance();
                $payments = $this->steadfast->allPayments();

                // Book any payout the courier has made since the last visit.
                // Idempotent, and the settlement list it reads is cached, so
                // this costs nothing once everything is already recorded.
                $result = $this->withdrawals->syncFromSettlements();

                // A settlement the sync refused to book is money missing from
                // the books, so it has to be said out loud — a page that looks
                // normal while quietly skipping payouts is worse than one that
                // shows an error.
                if (($result['conflicts'] ?? 0) > 0) {
                    $syncWarning = trans_choice(
                        '{1} :count settlement was not recorded because a hand-entered withdrawal already covers its date. Remove the manual entry to let it sync.'
                        . '|[2,*] :count settlements were not recorded because hand-entered withdrawals already cover their dates. Remove the manual entries to let them sync.',
                        $result['conflicts'],
                        ['count' => $result['conflicts']],
                    );
                }
            } catch (SteadfastException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('ecommerce::steadfast-operations', [
            'configured'  => $configured,
            'balance'     => $balance,
            'payments'    => $payments,
            'error'       => $error,
            'syncWarning' => $syncWarning,
        ]);
    }


    public function paymentDetail(Request $request, string $paymentId): JsonResponse
    {
        bpAuthorize('ecommerce.view');

        // Settlement references come back as 'SFC-31046185'. The vendor client
        // types its argument as int, and Steadfast resolves either form, so
        // the digits are passed through.
        $numericId = (int) preg_replace('/\D/', '', $paymentId);

        if ($numericId <= 0) {
            return response()->json(['success' => false, 'message' => __('Invalid payment reference.')], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'payment' => $this->steadfast->paymentWithLocalCharges($numericId),
            ]);
        } catch (SteadfastException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
