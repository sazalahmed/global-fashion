<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Setting\Services\LogViewerService;

class SystemLogController extends Controller
{
    public function __construct(
        private readonly LogViewerService $logViewer,
    ) {}

    public function index()
    {
        bpAuthorize('settings.view');
        $files = $this->logViewer->getLogFiles();

        // Aggregate today's errors across all files
        $todayErrors = 0;
        $todayWarnings = 0;
        foreach ($files as $file) {
            if (str_contains($file['filename'], date('Y-m-d')) || $file['filename'] === 'laravel.log') {
                $stats = $this->logViewer->getLogStats($file['filename']);
                $todayErrors += ($stats['error'] ?? 0) + ($stats['critical'] ?? 0) + ($stats['emergency'] ?? 0) + ($stats['alert'] ?? 0);
                $todayWarnings += $stats['warning'] ?? 0;
            }
        }

        return view('setting::logs.index', compact('files', 'todayErrors', 'todayWarnings'));
    }

    public function show(Request $request, string $filename)
    {
        bpAuthorize('settings.view');
        $filters = $request->only(['level', 'search', 'date']);
        $entries = $this->logViewer->parseLogFile($filename, $filters, 100);
        $stats = $this->logViewer->getLogStats($filename);

        return view('setting::logs.show', compact('filename', 'entries', 'stats', 'filters'));
    }

    public function download(string $filename)
    {
        bpAuthorize('settings.view');
        $filepath = $this->logViewer->getFilePath($filename);

        if (!$filepath) {
            return back()->with('error', __('Log file not found.'));
        }

        return response()->download($filepath);
    }

    public function clear(string $filename)
    {
        bpAuthorize('settings.edit');
        $this->logViewer->clearLogFile($filename);

        return back()->with('success', "Log file {$filename} cleared.");
    }

    public function delete(string $filename)
    {
        bpAuthorize('settings.edit');
        $this->logViewer->deleteLogFile($filename);

        return redirect()->route('system-logs.index')->with('success', "Log file {$filename} deleted.");
    }
}
