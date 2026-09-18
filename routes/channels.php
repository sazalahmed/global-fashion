<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private and presence channel authorization for the admin panel.
| Public channels (used for ecommerce-order toasts) need no entry here.
|
*/

// admin.notifications — any authenticated admin user can listen
Broadcast::channel('admin.notifications', function ($user) {
    return $user !== null;
});
