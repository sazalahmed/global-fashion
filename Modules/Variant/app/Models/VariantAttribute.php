<?php

namespace Modules\Variant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_name', 'display_type', 'sort_order', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function values()
    {
        return $this->hasMany(VariantAttributeValue::class)->orderBy('sort_order');
    }

    public function chartRows()
    {
        return $this->hasMany(VariantAttributeChartRow::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Accessors ──

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getDisplayTypeLabelAttribute(): string
    {
        // Only Button and Color Swatch are supported. Legacy values
        // (dropdown/radio) render identically to Button, so fall through.
        return $this->display_type === 'color_swatch' ? 'Color Swatch' : 'Button';
    }

    /**
     * Customer-facing attribute name with any bracketed qualifier stripped,
     * e.g. "Size (Pant)" / "Size [Shirt]" / "Size {EU}" -> "Size". Admins use
     * the bracketed suffix to keep two charts apart (e.g. pant vs shirt sizing)
     * but shoppers should only ever see the clean base name.
     */
    public function getBaseNameAttribute(): string
    {
        $source = $this->display_name ?: $this->name;
        $clean = trim((string) preg_replace('/\s*[\(\[\{].*?[\)\]\}]\s*/u', ' ', (string) $source));

        return $clean !== '' ? $clean : trim((string) $source);
    }
}
