<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * ShouldntReport: a denied permission is a handled 403 (rendered below as a
 * flash/JSON error), not an application failure — logging every occurrence
 * as production.ERROR with a full stack trace only drowns the log.
 */
class PermissionDeniedException extends Exception implements ShouldntReport
{
    /**
     * Render the exception: JSON 403 for API/AJAX, redirect-back with a flash
     * error for normal web requests.
     */
    public function render($request): JsonResponse|RedirectResponse
    {
        $message = __('Permission denied — you are not allowed to perform this action.');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
