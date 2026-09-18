<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Modules\Setting\Http\Requests\SaveEmailConfigRequest;
use Modules\Setting\Http\Requests\TestEmailRequest;
use Modules\Setting\Http\Requests\UpdateEmailTemplateRequest;
use Modules\Setting\Models\EmailTemplate;
use Modules\Setting\Models\Setting;

class EmailSettingController extends Controller
{
    public function config()
    {
        bpAuthorize('settings.view');
        $emailSettings = Setting::getGroup('email');
        $templates = EmailTemplate::orderBy('name')->get();

        return view('setting::email.config', compact('emailSettings', 'templates'));
    }

    public function saveConfig(SaveEmailConfigRequest $request)
    {
        bpAuthorize('settings.edit');
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::set('email', $key, $value ?? '');
        }

        return back()->with('success', __('Email settings saved successfully.'));
    }

    public function testEmail(TestEmailRequest $request)
    {
        bpAuthorize('settings.edit');
        try {
            $settings = Setting::getGroup('email');

            Config::set('mail.default', $settings['mail_driver'] ?? 'smtp');
            Config::set('mail.mailers.smtp.host', $settings['mail_host'] ?? '');
            Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? 587);
            Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? '');
            Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? '');
            Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? 'tls');
            Config::set('mail.from.address', $settings['mail_from_address'] ?? 'test@bizpos.com');
            Config::set('mail.from.name', $settings['mail_from_name'] ?? 'BizPOS');

            Mail::raw('This is a test email from BizPOS Pro.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('BizPOS — Test Email');
            });

            return back()->with('success', 'Test email sent to ' . $request->test_email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Email failed: ' . $e->getMessage());
        }
    }

    public function templateEdit(EmailTemplate $template)
    {
        bpAuthorize('settings.view');
        return view('setting::email.template-edit', compact('template'));
    }

    public function templateUpdate(UpdateEmailTemplateRequest $request, EmailTemplate $template)
    {
        bpAuthorize('settings.edit');
        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active', true);
        $template->update($validated);

        return redirect()->route('settings.email')
            ->with('success', __('Email template updated successfully.'));
    }
}
