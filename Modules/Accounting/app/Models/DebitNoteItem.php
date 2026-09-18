<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class DebitNoteItem extends Model
{
    protected $fillable = [
        'debit_note_id', 'product_id', 'description',
        'quantity', 'unit_price', 'tax_amount', 'total',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
