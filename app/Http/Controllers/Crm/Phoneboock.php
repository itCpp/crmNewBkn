<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserData;
use App\Models\Incomings\SipInternalExtension;
use App\Models\User;
use Illuminate\Http\Request;

class Phoneboock extends Controller
{
    /**
     * Выводит внутренние номера
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $ips = SipInternalExtension::where('internal_addr', '!=', null)
            ->get()
            ->map(function ($row) use (&$extensions) {

                $extensions[$row->internal_addr] = $row->extension;

                return $row->internal_addr;
            });

        $callcenter = $request->user()->callcenter_id;
        $sector = $request->user()->callcenter_sector_id;
        $rows = [];

        User::select('users_sessions.ip', 'users.*')
            ->leftjoin('users_sessions', 'users_sessions.user_id', '=', 'users.id')
            ->whereIn('users_sessions.ip', $ips)
            ->where([
                ['users_sessions.created_at', '>=', now()->startOfDay()],
                ['users_sessions.deleted_at', null],
            ])
            ->when((bool) $callcenter, function ($query) use ($callcenter) {
                $query->where('users.callcenter_id', $callcenter);
            })
            ->when((bool) $sector, function ($query) use ($sector) {
                $query->where('users.callcenter_sector_id', $sector);
            })
            ->orderBy('users_sessions.created_at')
            ->get()
            ->each(function ($row) use ($extensions, &$rows) {

                $user = new UserData($row);

                $rows[$row->ip] = [
                    'id' => $row->id,
                    'user' => $user->name_full,
                    'pin' => $row->pin,
                    'number' => "6" . $row->pin,
                    'extension' => $extensions[$row->ip] ?? null,
                ];
            });

        return response()->json([
            'rows' => collect($rows)->values()->all(),
        ]);
    }
}
