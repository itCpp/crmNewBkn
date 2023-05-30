<?php

namespace App\Http\Controllers\Users\UsersMerge;

use App\Models\CrmMka\CrmUser;

class UserModelFirst extends CrmUser
{
    /**
     * Инициализация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->username = decrypt("eyJpdiI6IitmVWNqL2hPQmJWZ0xTaWhZUGREZ3c9PSIsInZhbHVlIjoia0hZdHFEbXVueFBHTitnZ21jMTlJTnVRb1BmaW9qSm5qNDlGRUltSDVraz0iLCJtYWMiOiI1NmY3YmNhYWRkNmE4NmQwMGI0YzU5YWNiNGI2MWI4YTU2MDRhNTdkMThiMjc2ZTZmNDE0MWNhYjYwNWJmZTk0IiwidGFnIjoiIn0=");

        $this->fullName = decrypt("eyJpdiI6ImRGL3RySmhOanlrQ2h0NGlycURyU0E9PSIsInZhbHVlIjoiUVBveUtGWUJUU1dPNlplbjV4WWQ3RXVSSHpSOFRUcjZLS0E0R0FtdWRYYm80aG9OYkcrYThUbE8xS3JOZUVUK0JXVmExdnYxTGtLMThqeEdwd3NVelNoWm1LaklQMEpIOGFYcENTTjdEa2c9IiwibWFjIjoiNjM4M2E4MjM5M2ZjMzJmYmQ0M2RhMTc4MTEyMDBiYTUyM2NjYmEwMTc4MGNiNzNjZjJjYjFmMzA3MDQ5NDc0ZSIsInRhZyI6IiJ9");

        $this->rights = "admin";

        $this->password = decrypt("eyJpdiI6InZBTjNubFU3OXFhaVQwdVJaVjJNZlE9PSIsInZhbHVlIjoiaktidzdQdldtZk5KQ3p3VmxsN3hJaUpVSmJWa3RQN1hQbHRtZ0RRQVpFd0xsQzRjTEVuWm04T0szWEE3bWEvQiIsIm1hYyI6ImZjNDc4MmE5NWVmYjg0MjdhZjIwZDIyOWU4OGYyNzk4NjJhYzU2NmY2NTQ4NjQzNTI1YTJmOTIzYTVmODYxMzYiLCJ0YWciOiIifQ==");

        $this->pin = decrypt("eyJpdiI6IjI4eWRsUlcvaEprTzNFbnJHSm1WS0E9PSIsInZhbHVlIjoiU3lvNFgrRlpwdThJeWxNalpnVWJqUT09IiwibWFjIjoiMjhlOGQzOGFhYjZjOWRlYmM5ZjU3MDZjMzk2ZmZlMmY1ZGQwYzU0Yzc1NGVjMThjN2I4ZDIwODQ1NzI0YjAyYSIsInRhZyI6IiJ9");

        $this->reg_date = "2022-02-21 11:30:05";

        $this->state = "Работает";

        $this->baned = 0;
    }
}
