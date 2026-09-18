<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Contracts\SearchableInterface;
use App\Traits\HasGlobalSearch;

class Employee extends Model implements SearchableInterface
{
    use SoftDeletes, LogsActivity, HasGlobalSearch;

    protected string $activityLogName = 'employees';

    protected $fillable = [
        'employee_id', 'name', 'phone', 'email', 'nid',
        'department_id', 'designation_id', 'branch_id', 'salary', 'salary_structure_id',
        'advance_balance', 'joining_date', 'leaving_date',
        'emergency_contact_name', 'emergency_contact_phone',
        'address', 'photo', 'status', 'user_id', 'created_by',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'advance_balance' => 'decimal:2',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'department_id' => 'integer',
        'designation_id' => 'integer',
    ];

    // Department/Designation are stored as FKs. Their name is exposed as the
    // `department` / `designation` attribute (accessor) so every existing read
    // site — Blade, exports, the mobile API JSON — keeps seeing a plain name
    // string. The underlying belongsTo relations are eager-loaded and hidden
    // from serialization to avoid an N+1 and a key clash with the accessors.
    protected $with = ['departmentInfo', 'designationInfo'];

    protected $appends = ['department', 'designation'];

    protected $hidden = ['departmentInfo', 'designationInfo'];

    public function departmentInfo(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id')->withTrashed();
    }

    public function designationInfo(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id')->withTrashed();
    }

    public function getDepartmentAttribute(): ?string
    {
        return $this->departmentInfo?->name;
    }

    public function getDesignationAttribute(): ?string
    {
        return $this->designationInfo?->name;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Modules\Branch\Models\Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(\Modules\Payroll\Models\SalaryStructure::class);
    }

    public function salaryIncrements(): HasMany
    {
        return $this->hasMany(SalaryIncrement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDepartment($query, $department)
    {
        // Accepts a department id or name (the mobile API passes a name).
        return is_numeric($department)
            ? $query->where('department_id', (int) $department)
            : $query->whereHas('departmentInfo', fn ($q) => $q->where('name', $department));
    }

    // ── Global Search ──
    public static function getSearchType(): string { return 'Employee'; }
    public static function getSearchIcon(): string { return 'fa-id-badge'; }
    public static function getSearchRoute(): string { return 'employee.show'; }
    public static function getSearchPermission(): ?string { return 'hr.view'; }
    public static function getSearchableColumns(): array { return ['name', 'phone', 'email', 'employee_id']; }
    public static function getSearchOrder(): int { return 21; }
    public function getSearchTitle(): string { return $this->name ?? ''; }
    public function getSearchSubtitle(): string { return ($this->designation ?? '') . ($this->department ? ' — ' . $this->department : ''); }
}
