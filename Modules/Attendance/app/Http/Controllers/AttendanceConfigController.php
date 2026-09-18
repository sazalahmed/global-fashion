<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Attendance\Models\Holiday;
use Modules\Attendance\Models\WeekendDay;
use Modules\Branch\Models\Branch;

class AttendanceConfigController extends Controller
{
    private const DAYS = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];

    public function config()
    {
        bpAuthorize('hr.view');
        $weekendDays = WeekendDay::whereNull('branch_id')->get()->keyBy('day_of_week');
        $holidays = Holiday::active()->orderBy('start_date')->get();
        $branches = Branch::where('is_active', true)->get();
        $dayNames = self::DAYS;
        $shiftStart = \Modules\Setting\Models\Setting::get('attendance', 'shift_start', '09:00');
        $shiftEnd = \Modules\Setting\Models\Setting::get('attendance', 'shift_end', '18:00');

        return view('attendance::config', compact('weekendDays', 'holidays', 'branches', 'dayNames', 'shiftStart', 'shiftEnd'));
    }

    public function saveShift(Request $request)
    {
        bpAuthorize('hr.edit');
        $validated = $request->validate([
            'shift_start' => ['required', 'date_format:H:i'],
            'shift_end'   => ['required', 'date_format:H:i', 'after:shift_start'],
        ]);

        \Modules\Setting\Models\Setting::set('attendance', 'shift_start', $validated['shift_start']);
        \Modules\Setting\Models\Setting::set('attendance', 'shift_end', $validated['shift_end']);

        return back()->with('success', __('Shift timing updated.'));
    }

    public function saveWeekends(Request $request)
    {
        bpAuthorize('hr.edit');
        $weekendDays = $request->input('weekends', []);

        foreach (self::DAYS as $dayNum => $dayName) {
            WeekendDay::updateOrCreate(
                ['day_of_week' => $dayNum, 'branch_id' => null],
                [
                    'name'       => $dayName,
                    'is_weekend' => in_array((string) $dayNum, $weekendDays),
                ]
            );
        }

        return back()->with('success', __('Weekend days updated successfully.'));
    }

    public function storeHoliday(Request $request)
    {
        bpAuthorize('hr.create');
        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'description'  => 'nullable|string|max:500',
            'is_recurring' => 'nullable|boolean',
            'branch_id'    => 'nullable|exists:branches,id',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_active'] = true;

        Holiday::create($validated);

        return back()->with('success', __('Holiday added successfully.'));
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        bpAuthorize('hr.edit');
        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'description'  => 'nullable|string|max:500',
            'is_recurring' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
            'branch_id'    => 'nullable|exists:branches,id',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['is_active'] = $request->boolean('is_active', true);

        $holiday->update($validated);

        return back()->with('success', __('Holiday updated successfully.'));
    }

    public function destroyHoliday(Holiday $holiday)
    {
        bpAuthorize('hr.delete');
        $holiday->delete();

        return back()->with('success', __('Holiday deleted successfully.'));
    }
}
