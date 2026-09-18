<?php

// One-off cleanup: void posted journal entries whose source payment was
// deleted without its journal being voided (pre-dated the voiding logic in
// the delete flows). Creates proper void reversals via JournalEntryService
// so the audit trail is preserved.
//
// Run with: php artisan tinker fix-orphan-payment-journals.php
// Delete this file afterwards.

$journalService = app(\Modules\Accounting\Services\JournalEntryService::class);

$orphans = \Modules\Accounting\Models\JournalEntry::query()
    ->where('source_type', 'payment')
    ->where('status', 'posted')
    ->whereNotIn('source_id', function ($q) {
        $q->select('id')->from('payments')->whereNull('deleted_at');
    })
    ->get();

if ($orphans->isEmpty()) {
    echo "no orphaned payment journals found\n";
}

foreach ($orphans as $entry) {
    $journalService->void($entry, 'Cleanup: source payment was deleted without voiding this entry');
    echo "voided {$entry->entry_number} ({$entry->entry_date->format('Y-m-d')}) — {$entry->description}\n";
}

echo 'cleanup complete: ' . $orphans->count() . " entries voided\n";
