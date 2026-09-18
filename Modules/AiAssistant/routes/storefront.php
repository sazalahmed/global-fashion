<?php

use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\Http\Controllers\Storefront\ChatController;
use Modules\AiAssistant\Http\Controllers\Storefront\SpeakController;
use Modules\AiAssistant\Http\Controllers\Storefront\TranscribeController;

/*
|--------------------------------------------------------------------------
| AI Assistant Storefront Routes
|--------------------------------------------------------------------------
|
| All routes here are publicly accessible (web middleware = session +
| CSRF). The chat endpoint is rate-limited to 30 messages per minute
| per IP. Conversation history is gated by the customer guard inside
| the controller — guests get an empty list.
*/

Route::prefix('ai')->name('storefront.ai.')->group(function () {
    Route::middleware('throttle:30,1')
        ->post('/chat', [ChatController::class, 'stream'])
        ->name('chat');

    Route::middleware('throttle:10,1')
        ->post('/transcribe', [TranscribeController::class, 'store'])
        ->name('transcribe');

    Route::middleware('throttle:20,1')
        ->post('/speak', [SpeakController::class, 'store'])
        ->name('speak');

    // Deterministic order-placement — bypasses the LLM so a stream
    // failure never costs us a sale. Stricter throttle than chat
    // because each call creates a real order.
    Route::middleware('throttle:5,1')
        ->post('/place-order', [ChatController::class, 'placeOrder'])
        ->name('place-order');

    Route::get('/conversations', [ChatController::class, 'history'])
        ->name('conversations');

    Route::get('/conversations/{id}/messages', [ChatController::class, 'messages'])
        ->where('id', '[a-zA-Z0-9\-]{20,40}')
        ->name('conversation.messages');
});
