<?php

namespace App\Broadcasting;

use App\Http\Controllers\Users\UserData;

class RequestsAllChannel
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
     * @param  int  $callcenter
     * @param  int  $sector
     * @return array|bool
     */
    public function join(UserData $user, int $callcenter, int $sector)
    {
        if ($callcenter === 0 and $sector === 0)
            return $user->can('requests_all_callcenters');

        if ($sector === 0 and $callcenter === $user->callcenter_id)
            return $user->can('requests_all_sectors');

        if ($sector === $user->callcenter_sector_id and $callcenter === $user->callcenter_id)
            return $user->can('requests_all_my_sector');

        return false;
    }
}
