<?php

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranscribeAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                'mimes:webm,mp3,wav,m4a,ogg,aac,mp4',
                'max:5120',
            ],
            'language' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.max' => 'Audio file too large. Maximum size is 5 MB.',
            'audio.mimes' => 'Audio must be webm, mp3, wav, m4a, ogg, aac, or mp4 format.',
        ];
    }
}
