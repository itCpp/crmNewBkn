<?php

use App\Broadcasting\AuthQueries;
use App\Broadcasting\RequestsAllChannel;
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

Broadcast::channel('App.Users', function ($user) {
    return $user->toPresenceData();
});

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/** Запрос авторизации */
Broadcast::channel('App.Admin.AuthQueries.{callcenter}.{sector}', AuthQueries::class);

/** Просмотр потока входящих звоков */
Broadcast::channel('App.Admin.Calls', function ($user) {
    return $user->can('dev_calls');
});

/** Одобрение или отклонение авторизации пользователей */
Broadcast::channel('App.Auth.{id}', function () {
    return true;
});

Broadcast::channel('App.Alerts.{id}', function () {
    return true;
});

/** Информация о заявках */
Broadcast::channel('App.Requests', function ($user) {
    return $user->can('requests_access');
});

/** Информация о личных заявках */
Broadcast::channel('App.Requests.{pin}', function ($user, $pin) {
    return $user->pin == $pin and $user->can('requests_access');
});

/** Информация о всех новых заявках для всех секторов или коллцентров */
Broadcast::channel('App.Requests.All.{callcenter}.{sector}', RequestsAllChannel::class);

Broadcast::channel('Chat', function ($user) {
    return $user->toPresenceData();
});

/** Информация о личных заявках */
Broadcast::channel('Chat.Room.{id}', function ($user, $id) {
    return (int) $user->id == (int) $id;
});