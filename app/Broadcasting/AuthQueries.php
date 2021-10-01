<?php

namespace App\Broadcasting;

use App\Http\Controllers\Users\UserData;

class AuthQueries
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Http\Controllers\Users\UserData  $user
     * @param  string  $callcenter
     * @param  string  $sector
     * @return array|bool
     */
    public function join(UserData $user, $callcenter, $sector)
    {

        if ((int) $callcenter == 0 AND !$user->can('user_auth_query_all'))
            return false;

        if ((int) $sector == 0 AND !$user->can('user_auth_query_all_sectors'))
            return false;
        
        return $user->can('user_auth_query');

    }
}
