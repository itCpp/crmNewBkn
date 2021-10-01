<?php

use App\Broadcasting\AuthQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/** Запрос авторизации */
Broadcast::channel('App.Admin.AuthQueries.{callcenter}.{sector}', AuthQueries::class);

/** Одобрение или отклонение авторизации пользователей */
Broadcast::channel('App.Auth.{id}', function () {
    return true;
});

Broadcast::channel('App.Alerts.{id}', function () {
    return true;
});