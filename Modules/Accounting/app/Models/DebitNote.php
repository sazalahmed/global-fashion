<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Branch\Models\Branch;
use App\Traits\LogsActivity;
use Modules\Supplier\Models\Supplier;

class DebitNote extends Model
{
    use SoftDeletes, LogsActivity;

    protected string $activityLogName = 'debit_notes';

    protected $fillable = [
        'debit_note_number', 'supplier_id', 'purchase_id', 'purchase_return_id',
        'issue_date', 'reason', 'subtotal', 'tax_amount', 'total_amount',
        'status', 'applied_amount', 'remaining_amount', 'notes',
        'journal_entry_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'issue_date'       => 'date',
        'subtotal'         => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'applied_amount'   => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // ── Relationships ──

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ── Scopes ──

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn ($q) =>
            $q->where('debit_note_number', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%")
        );
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}
