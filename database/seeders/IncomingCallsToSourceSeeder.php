<?php

namespace Database\Seeders;

use App\Models\IncomingCallsToSource;
use Illuminate\Database\Seeder;

class IncomingCallsToSourceSeeder extends Seeder
{
    /**
     * Данные
     * 
     * @var array
     */
    protected $incoming_calls_to_sources = "eyJpdiI6IlZCbERoVnlkU2ZQZWloMU9qWWY1Rmc9PSIsInZhbHVlIjoiR2ZHZjJXb1J0VnN5RU93SkQvbUdHTm1CaGhzLzI5K0k2YzlhUlo5Ui9jRzdZYUZlcGR6UVF3MEdZYW9Od0FBYkVWUXBPZlNuS2Y1aysrcVVZU3U3R1Z5Z1NnSnNOaUt3ZytEeCs3WGo4UUFzODhTQVgzSnVia3hIbWpkZHo2QmlhejUzUUZ2WktQZmFmZUVqLzQzV2dUeUF3NUl4T2Z4SjNMazBQcjdZTGlNYi9rT1p1cEt0d1F3ZSthQUFvTFlqUHNzdGFXTXRFR1JmVzlhMHI2VE95V3ZSMEJjME53dGt6N2xselhaOU16U0ZReWFqTVN3R1RyTnMra2pDdXJBZmtlMU5pRkw1SFFxYytIME9hUFJGZ1NBUVlENVo1U210K1UybVVGTFhMUmI2WlR4YXd5NE1pSDY4UU9iaWlkVWJQSjFDUitBQWw4MFNaYnhHVVQ1YUxNMzFLU29SMGRlQWFwcGMyNnVjR0l5Uy9telFGUmZJcklPemVWYmwwbjd4anJGTUUyZ001djQ2NDYzUENQWXpnanFFMUlrVjQ4OFN1Wkl4WityUlVNVTVxZ3Y5ZU5mM0lOWWJUVVZ2R1JyaUVEMXgraFhuaXVxTW03RGpIL3BxVUJRNXF2TnpzNlhQVEk1NDBtblVleWpaQzZ1K1BkL1oxNHlvRFJUcndSdEdKT1dDQ2lFRE5hUDdDWFhCbnZxMUZIR1FoQklKY0ZuWDMrZE5saWZXN0JiTUlYajV2N08zMlJWdXIwVnAvb3pTRUdCZEcrN2tHZXR6NXNSOU5VQmk1ZTV4cGFvYWE2OUpvZmhzK1VXUk9kY0g0eDhiRDFsWjBsanB2bTFNbmNVbkRPTFo3L0UrTXU1U1hleXVISFBkMFNyemw4MXgvWmtqV21nOHo2RHVsVlg3YllsMzI4Rjk5QVlPcnB5VGw4L0VqNHhoalYveXpOeENKRGIxdlBaMGl0cHZvaWNSbmV3NWRQTkl2THRKdUo5OW9kNVduODNiMi8xUnp5amZsSWRpekF0MmJQOWwvUU9XMm9oZzR2TkUrMDk3dy9wa2xUZ2RZUlRnT1Nnb3BpdVlNL0k2MHhkZjRkOG8yOU1NS0k3NnYvYzlpSm9XR1VtdWNDVUZjQ3hJK1Z3Tkdvd1BZdWJ2SjlrZlBSV3lyUnhOSkVYNTlPckdtUUNySXltdzBQWmpUVFg3TXptVEg2Q3FRZmtKbHgxaVJBajlsWEpLRWljSGRjaHJqb0ZLNmJ4SkUrR2hmSEVXc3daMi9mQzVaaWVzVTM2am9WdWREZm5vZzkxQlRuNVY2clk3OFJoYWdULzNSVUNPdm5DTGtsbk5RcERVaituNEtjeFBRNFZxQTZLVE1GRFZVZkg1TFpYbWhReEl6cExmZEVveFNHMXJOd3hsSGNyZkgvakt6bDVEWmNkOUtaeGpFNVhnUUxmZGh5dWlZZnlocCtSRkt4QTdqeHk2Qld2NmZKOHQ1R3JEKzIvVTM5RGNWdXhEcE1FcEIzYXQ1OWFEMHFlTkx1WktEM0I1UkNnaFZqV3owb1hyR1NIcW4reTZmYXdQenduU1lCQW5OM1hHV0c0WlZ3TkFzSWRYNll1aFRrUkFlNkdCWnNpeDBKMTdSVnFVUDRRTGxuSGJJQTZndUY5TURBeHdSTUZiQU9kUTdzaDRVNExlYkwvZ3Z0UitWMlVBbFFTY0pIb1NSYjVwY1RVdko5Q0lTa3c5Tkhkblkvb3hhZVRKbklWREZPQlNxcG00UnZSWnN4UnJ3K3BnMFZlNFdxYnFSMFBuZFVCTjFxZjF1cFNwU3N3ZHJsbDl6OVRxbXRnMUhYK3d4SmE2MmtFTFhENWt5NUJkOTk5OE1ZMk1IWHNQZVRXKytUUTUxK08vYTRUS3JUSXlFeVBlWUJLVXQ5TFpZbDhnQk95QnkrSkIxSGVMSjVhNVViM3pHM3dsa3Vqa0toOFk0Y2pzOW84bUdXcGxuUEtId0ZScHpvMUkzNFNqeGN1Z1NTT3h4SzNQKzB2RnBFQm92M0xrbXY4QXV4YnNiVkR6bmFOOHRzVEo2TktuUDh4SzJmWEl5eXY0emVDMXpTRno0VkhIZTRwK1BrdEdzNUxaeTZ3d0lTTkFNOGFJQ04zc3orYzJXclRLbm9pM1l3Q05YWTFjb3lQSTVXYVNJdXczeHVBUHpTQlhFelRvYzUxbE5OeTlkZjVvMU55UStxbkw3alo1WDVLaTFqVkx6aksxTXJERFBVMmlkRlJUNGVWNXZrTjhmUXVwYkdhRXVPampBNWFhME1icDNzSjJnUEtIZHBqcW94NlVSTUppQzBYK21CUEoveENpRVlSNUpCeFZsY2FWNlpmS1hlaHA1ZzNFM0RjTGhaTDNGN1Fpc1phOVlJS3FZY2NxL0Q4b3JTc2wzUWF1UmRCakl2bXZheEFGYWFaZXhhdHE1L3RIclVhcDFVVTcxNUhCcEZmdmt5THNSczJHTjJUUlh4alBKbExrc2hiSjdsemY3UnFrdkQyTEFsTGlDaTEyYmRaUk9UaDNVcUZIWjc5QmgyMnBsMGxNUk9rV0ZpYUFPL09aVXZXTVltNnZmM1dqTmZxY3IxclNVZGpJVGpod2RVbng2VndjL2dOUFBqZm1wd3NFeXAwb0VnTmpDZDBkRHdSOGpKMmdqY0I1YXhHR2VxcWtFeVJzNUd0VHVpSjN6eWNRQS9vQUc2blR6NU05aVl3eWNqU1ZQQTJjNE95aTdBaHk5NWpOTGFXZTR1RkViQ2JlZVZKWXhNQVF4K3pIRktldjJicnRMQUJVQ1hQWC9samw4QThML3dEZW5EWG95TEZrMkR2ck9EWUllOTIxd3I0UDVaNnhhUVQyVFFYRHJVUXdyWnl6aDcveW43TXo2aFhpY094Y2pJMmZEQng1Rzk2MkhNN0FERVpydmYwdmRZYUFmRDQyTGV2bTB4dk5GY2U3RVhuV09mYzZ1ZlBwRUxIUTFQV00vVk84b2hRckxWWWJLODlpWmtTYkVuR0pEcTR6Y1c3YU9rV3R6RWo5dHhnbHBseDBNREFQWldOblh5Q0M2TmlHMXFkRER2RE8vTFRBYURpSVA1UzdOMDg0T092T3dsWXR0cDZuWWVPZitQYnhsYkRRcnpscm50SFhFWVVTVDBiQXlnNVNpYWdIcEVWeVMrejlzekE4VXNuQmxFS3RFb1lFSnBNbk1jQ0plQ0lNMmtzMHdQZ0RlVWRRYkdvMlRacEwvbW9tTDRaVHRJZnNnQThuMDNuKzlKT0doQitiWUZQc2sxK1J4Um9JK0ZGZ1ZoQ2NSOFVxalRFcGJacXlPZnh2NFZsdERhand0Ri9sbWludjIrUXNxb245a21oODBjSExybzlvTUZiWmJkSWNnNFdkbC9ZZHJyUEc0ZEw5a01vaDZBVndXeXNReHhkOExrd1pRdGVqY0ZSTWlSQ2p5Zml6QzVtbUVUZU5KTFFYaENENEhRSmFzVGN4a1FMeWVEZWZxWXo1UHRvT29nVjFVdm94bDRDdExOTjhKNkNaN2wxYjNEY0tHQTJEM0hsVTZpbjVBZ1FQamNvMWJUaXN5Tzc5WTNDclRGVGpaNkJIbTRaNnFnZldUMTdoZWVHWXBzbnJqTFVhZnZvS0hpYitBSmFaMDZ0RjBtY3ZaNFJKblk0NFlGVXB1STNSb0Q2TGZrMDdLZkFhZmNwV3huTTFhZEhQbTlGc0RZWnA3YzRaWmpyRVlCZDJ4ZEhVRGxQTjZuU2x6WnYrZit6Wm5yUm9QWDhCRUpVN3AvOTViUnhRS1pUZWNlKzN4eStlTVRXTWN6WnBxVmFhQTQ3YTFBdXJ3QTJKU3g1RnBQbjRLZUV5dTV3V3hYUEZoT1loNllPOVZ5OUJGRDNWeUZvMFpPWHBZaFJxdnQxWWdITjN6QU1vTzVTeWZuMkk5TTN4V29WL1lzY3FyeFlPRG5wR1B1SERVOUhZQmtrU2UxSlFieFNTbFFDQ0syR2VBVEVCYnh2blQ5dFVjOXFTcVdNQXhMalN1c3F2bVA4YkNqUk5WaUhpbGNHUk4yUlZmZUpyTTVlTDFxbTl5ZFVudWRnNlljdENtd0QrZXBFbi9ZNDFnWnBLa2VTb2hKdm95R2V0d3pyK01DcFkrM21yV2EzZDVQcjlIeFk4VFpaeVc0K2ZwNnZPbHdlWXRPZk8wZHBoalFWa2d1ZTBEQVJWM0hiRzlsYVF1L2xWUkNvR3MvNTc5ZWRzQ1VuUDZSWjZyYkpyRFNHWWZQWlRhS09jaTBkb3ExTGhWQ3EybmZFc1luWHRhQlEvQXZUeGpxKzk1dEdsUjBoa05sckpXNzlmQ0dDZm51MmR1V20rUUxDWlB3Yms3VmtOVDlGMDNxV3ZGUURLSVR3V1JydWJidU1sYndlRWZwRnZOZXpBbWt5TlE3Q0VaeVJxeDJqN3BENFdNcWF2bi9HN1JuYVJneWdZUUE5RGhYUHd5UElrWUlpc210cVpNS01wa0hIMDRDa2JMQit3dXZ3RE9hRWdsYU5SdWxqcWhRdnFOZ1Z5RXBScGpQR0svblNxRWx0TXNYekQrdWtIdnhvPSIsIm1hYyI6IjJhZmZhMDI4Yjc2M2ZjMjRmZTkxYTAyYTMzOGEzZmUxYmRiNzk0OWYzODNjODVkNTAzMTE4OWYxNTQyNWQ5YmYiLCJ0YWciOiIifQ==";

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (decrypt($this->incoming_calls_to_sources) as $row) {
            IncomingCallsToSource::create($row);
        }
    }
}
