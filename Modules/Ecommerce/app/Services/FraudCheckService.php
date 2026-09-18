<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Log;
use Modules\Ecommerce\Models\FraudCheckReport;

class FraudCheckService
{
    public function __construct(
        private readonly BdCourierApiService $bdCourier
    ) {}

    /**
     * Look up a phone number's fraud profile.
     *
     * Cache-first: returns the row from `fraud_check_reports` if one exists.
     * On a miss (or when $forceRefresh = true) calls BD Courier API and
     * persists the result.
     *
     * Returns null if the phone is invalid OR the API call failed *and* we have
     * no cached row yet.
     */
    public function check(string $phoneNumber, bool $forceRefresh = false): ?array
    {
        $phone = $this->normalizePhone($phoneNumber);
        if ($phone === null) {
            return null;
        }

        if (!$forceRefresh) {
            $cached = FraudCheckReport::where('phone', $phone)->first();
            if ($cached) {
                return $this->rowToReport($cached, fromCache: true);
            }
        }

        if (!$this->bdCourier->isConfigured()) {
            return null;
        }

        $report = $this->bdCourier->check($phone);
        if ($report === null) {
            // Transient failure — fall back to a stale cached row if we have one.
            $cached = FraudCheckReport::where('phone', $phone)->first();
            return $cached ? $this->rowToReport($cached, fromCache: true) : null;
        }

        $riskLevel = $this->riskLevelFromAggregate($report['aggregate'] ?? []);

        $row = FraudCheckReport::updateOrCreate(
            ['phone' => $phone],
            [
                'risk_level'      => $riskLevel,
                'aggregate'       => $report['aggregate']    ?? [],
                'courier_data'    => $report['courier_data'] ?? [],
                'reports'         => $report['reports']      ?? [],
                'source'          => $report['source']       ?? 'bdcourier',
                'not_found'       => (bool) ($report['not_found'] ?? false),
                'last_checked_at' => now(),
            ]
        );

        return $this->rowToReport($row, fromCache: false);
    }

    /**
     * Bucket the success ratio into a risk level.
     */
    public function getRiskLevel(?array $fraudReport): string
    {
        if (!$fraudReport || !isset($fraudReport['aggregate'])) {
            return 'unknown';
        }
        return $this->riskLevelFromAggregate($fraudReport['aggregate']);
    }

    public function getRiskBadge(string $riskLevel): array
    {
        return match ($riskLevel) {
            'low'      => ['color' => 'success', 'label' => 'Low Risk', 'icon' => 'fa-shield-check'],
            'medium'   => ['color' => 'warning', 'label' => 'Medium Risk', 'icon' => 'fa-triangle-exclamation'],
            'high'     => ['color' => 'danger', 'label' => 'High Risk', 'icon' => 'fa-circle-exclamation'],
            'critical' => ['color' => 'danger', 'label' => 'Critical Risk', 'icon' => 'fa-skull-crossbones'],
            'new'      => ['color' => 'info', 'label' => 'New Customer', 'icon' => 'fa-user-plus'],
            default    => ['color' => 'secondary', 'label' => 'Unknown', 'icon' => 'fa-question-circle'],
        };
    }

    private function normalizePhone(string $phoneNumber): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phone) === 13 && str_starts_with($phone, '880')) {
            $phone = '0' . substr($phone, 3);
        }
        if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
            return null;
        }
        return $phone;
    }

    private function riskLevelFromAggregate(array $aggregate): string
    {
        $total = (int) ($aggregate['total_deliveries'] ?? 0);
        if ($total === 0) {
            return 'new';
        }
        $ratio = (float) ($aggregate['success_ratio'] ?? 0);
        if ($ratio >= 80) return 'low';
        if ($ratio >= 60) return 'medium';
        if ($ratio >= 40) return 'high';
        return 'critical';
    }

    private function rowToReport(FraudCheckReport $row, bool $fromCache): array
    {
        return [
            'aggregate'       => $row->aggregate ?? [],
            'courier_data'    => $row->courier_data ?? [],
            'reports'         => $row->reports ?? [],
            'source'          => $row->source ?? 'bdcourier',
            'not_found'       => (bool) $row->not_found,
            'from_cache'      => $fromCache,
            'last_checked_at' => optional($row->last_checked_at)->toIso8601String(),
        ];
    }
}
