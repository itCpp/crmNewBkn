<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Данные
     * 
     * @var array
     */
    protected $offices = "eyJpdiI6ImU2V2FqaU01eEhGRWRVY2d1VEhrTXc9PSIsInZhbHVlIjoiMTI3SzdTVzB4M0llOThQSWxRUmFtQ0JOLy90UFJVYjRyS1llRXBpZmtaY2hyZUJCb0gzTVpKL1lyK0dkcGZ6MVJOaWhCSjRLd0E5L3pjN1lWK2RIdzFGSDV6OUloWkZxUHhVUFJsNEVTdkZJSUJYQ1pkTGhYOFpaT0lIQ3Z4czcwNnZycHBhc0FzM0hIbmlwT0xlSExSWXVPQmZXejQwTS82VXNkNGl1dUYvRXZGOXFFRFBKMjFXc21laldKbm9lQjU1aWJuc2gzbFpPWC9WRlo1ei9qejdSOFhlZExrRFU4YWtlMkV0dnVtQTBMRjhSc0xXbGJLRVVKSjBTaW9kRjdhUk13WnFLZ3VlZHVuMDQ4RlRCdlk3MTR4TkFQMHZhL09zRmEyZHlKWHAxZ0lKQnN4N0JnV2pvU2pzN3Nld2wzYXFXT21hZlRtcmY5QTN6MVdyRitSZlFrY2ZnWHhyS2hUNnM4cFUxazBWL1FJcGl0Z0c5QVpJdlRoOFprY0VBbXIraFZDYWs2QllBdDVzVmkwdjQ5MjVsa2s5eUE0ZHVNaEdKWXVRcnFieWU5bERpZWp5K2RRelcyclBOb2thdHA1czlYa3FaQlBaeGsvV1RvZkZpb1hPWGw0V0xWYzlHT0lTZHlBR1FwSU0zVHdCYmcwb3lFd3MwNVRFVThDeU9teEVwQVI2eUwySkttdGNQazM2QkVIWURRazNQTmNmYlExRnlqY2NZQ2IvUmhXb0lkTEtyZnYxSDdlMTNyV3AvdmJKQWZYL1BWbU5QM2MxNUR6RTJoY0lrRDVBQTlIbmJ1Sng1ZWc2NEtsQ2dwRHpQOHBhVVdtTHYwSzc3Q0YwWm5HaFExYm9MQXArUitGaW9TT3dlMjZTQ3lxNE1SQWF5LzZVWmN5Z0dpMitHRDA2WDFpT0NHMHZsR2ZQMTd1dnl5azBqdXRUcFFwek1IUzZZazFpdEZUaElYT1BzYWVrYVZBMTQyeHpOSEE4WmFIZ3JQaG42WURiaEg3Sys1VExabzNGYWVXNVB4QVNVaG14N0VKckd5dWlHN09FSGRGalBuTEtBd1JDMm90bDNKOXRCMGtKMi96dVJvZlRtbTVIL1NBOCtFSENVeVdaT2pBWDNUMXY1OFFxUnpsYldKcFJYMG0zdTdabXpTbGQyRmZ3eGtFTHYrblNLbE9pOXVlZXl0S0dlQWllZU5FWnlCcDJaNGhwTjdMUmg1OWd0Vy9WQzY3WG5wVS92SFN4bEcrWjZMdHNCeFpoaEdPRDBNZTNaeVhkd2VncG1RdGRiZlhnUzIxeVNubW10NDZ5UW0zeVJjcG1Sbk56eTluamlReExpVnpQeU9Oc3RMcVNHK1pWN3hRUkl0clZ2aFdUMVBTS1hPT3FPVSt0N2RKeWUrN1VUVnNtaUFwRjNSRlcwRWF6alN5dVp1NHVIaXg5UTNOQjcrZ0hackh2Zkw0S0RleDlkb0JlNVhjcFJOcm9talFGUG03VnA3eDlVbUtXd3psUktnbWpXSVJZeEIzNWFNYW1ybzBWdGlxeFJvY014Zyt3cmRMSDdBcGg4TWNEa2M5cXB5Mlc3aFhQQ2RPbGFqLzNmMnBUN2tCbmFKdnJlSDBPT09BUXhvQjRjSUQ1bE4yODU5SDlvc2pYa0RkWXhkOEhRNTE5OCtKaWUyY25LdDFaQVVBYnpGTkNQb3pCTjZkYWFPTk1IYUZudTBKZGhEM3hpcTVBV2FoOFk4RkphWlB6cUw2Rm0rSW4zVVhhZjV1b0ljR3dGQjNBdm9YbVg5Ri9Jb2h5S2p1S0tQTndKT3I2Y0ZzdkF6cGhCTzVSdm1iSC9kMTVpQVpMb3dHcnBFQllZT1F0VTZDejBIV1VwQkFhUU1HdG41cFNoNEo4TUNFRE4yTDgwNjVwcEhxYkpmeGF0KzQwZk0yenIrWTNMdlJYd21OeERXNVJ1eDZOTkpPd3p2dnU2dlkxS25kcTdEcnIrTGM3WWhIT29LZjEzTWVXcHhNQWhBTzlKQXFzaU9tTnRlRWNGZTBKTjc5aGsvS1RBSlg1VDg2eXRiWWtGUGFLc2pHNVNNRHhON004TjV2b1BnTVIzb2swYUxlVHM5RDhBQm9KWEVKZUFvMkZIMFlGdW1JYm9GK1dCNUs5UzFiVWZoa1ppMGRMTnZsaVJTZEIrZSt6aGpRMDgwNHc2K1UyUC9WU1d4Q0Q0MVVVYzYwQk83RHRacXN0eTVIU1dSc0ZkcUsrbndYQ01adXRNdUhmVE1yQTRyWVhZR2pIV0pSYkZ5UUFzTU1zTlR0K29PUzRiMDhXWnMzd0M5QmUwUW5VbUR4MUNwb0g4QXVWYVZiaUN4Q0ZZU0Z1bVdUeXp2TGpqeGFoV0k5bDdzb2l5SktkQ2dGQmpHV3FLSTZBanlYRUI3RzY2UjBOWGZGcUJYc1JFenFaaDN6ZjBrQW1heVpXcjhkbFJCTHBLQ2o2cHJJZnY0dmVUQTZzK3YyeFV6elg5YndxRUhaOHU4OGZkWmhrQU82clcybTlScE9SRnFySHdjazZyMkU1NnhuZWlBRGVCRkJqbWNKYWRRSitXKzdkOXdZY0hnRVFHd2Jzc2RwbkM5T0h4bVgyV1o5c1daUWVPZ0Nnc1NrdTZqeXNpQS9oUFcxdnBwMWFvZGNucHpmYXBLQ3lyOXVlbnA1V1FxSHNmc2FRZWNPNFN3Y0hOM3R5UkxiMXJDeG0vbWR6MzRjMkl0NDUyMWNMM2dNTVlSQU1rWUx4b0I3YmQyY0J6VEJkZ1hiQTE5ZnFaUHBzOWMyMFFVdXJMVmFiTzRJNi9GbHdHVjdYRkpUUXFPWlVRUWZzK25ValQrbkI1d093bmxHOC9aZml3cnN0eFhGYVJRdk14Ny9ONzdaRFF4SWJGT2JYendZUDFISkVPK2tLdmpUWjN5b0YxTm5YL1dqN0VCODlQaHJBMHlwa3lxaytjc2krTTM3ZVl3cXE3MmwyS0xaamVRbldNb3R3RGdjRnBOQU1sb0lBMWkxY1ZyMGJNVzZxajJXK2s5ZXRkSjIvQlZDUzFKUk5FeEdERWVrK2hYSEs0Tzk0UUZ1ckNzS0tJOWJBenhRcWV3VG9wUTBjSDZuaWFjVHlEVUczSE0rano1R3NTbXllblJncDRDQ3BJZHdXZWNuR1doNTFxaGtmK0xtdWFqUi8vb21Tb0RxWWxhbDRkV2NnUHJ3bWlZRStlR0R6bUI1WkdaZnRSbUZMZHg3OXVCT2lXUlRpN0FNZEQydlBZNnJkVmE4VmJSM3AyTkJWKytOdThFd2JoMHA3VVVicnBoZXhybS8xdit1T3JGZEppYlFwdVlSRVdFSG0vaXdadVJvOThtdS9oZldKYlBONXFqZnlVditVMTRrWk1sbE91S3lVQ2xobVhhdFNYT2F6cmcrNjc4cW5rcFB2Zm8vbjZ4eDhRQjIyYklUMWRyUzdaN2VyenI5bVFCNG9iU2JoVzVLNjNNc2oyRXVSWmFWY2dVMkVqOHNrZVNoVFFkYmNPQ3UzcEN5VzZ5RSt1Nm83U1NjQUxBRFVlcnlvWHF1RjFTZDU0dE5RVmJKVzJtRFBEUGtZcUZZeUdMdVc0eHhrZitZeWZJekhPSUowUk11Tk9xbmtmUnNkdTV4ckxOZ2ZJRE41RUU4Sm5pMTNtbkZaeXpPOG8yeUUzcWpqZnN0ZTVTZ2d4MEVlRFhabEhLQzdkbWx4SlMxdWlmQWpwbHp0bjF3VFh0TUdlbHc4SDVWMnErM1Z2Zkdpb3g3Z2RQSmZLWVhZY0ltUk5yMS9ueTN1ejVqNUE2QmZ4bTkyTFhNTU1QdFhJbXU3bmFTaVVmbDJMb2lLenFWc3h0V290dTQwY3BIUkVPRHZZRDlzMHVxRGs3UEdKeTJ5cWFMTWhSSXRkN2dsQWNNUmNYNUtVWlpObExvMEZ1ekFERGZIajUzb0YyUXNjS2Fac3VZa1JxYUcvR2FENkFJVXJSakJMTjVCS3AyR0w4RmtLbTc1TGZGRXVqdWpKY3cvQ0VoejhFR0hyYXlZU2ZDR2ttaXBDbEwyTkhFcU9PNlJ6NWZuWDZVeXBYRlEzWUhaLzJDT21iekwyeGw5ZlRYK2w1MTBNTUxZUmlYcGk4UCtFdjVUYWhFUlp0eTE5MC9QL0k4WXFrbjV2Uld3L2xlaWtvU0N2aEszd0lhZzQ3SXlmZWdYVXZ4ZjNBRWs3YUxGOEYrZFo1Yy84d3MyRytYaVluR1hBeDFVenBWb05DL3NWRDlIUzliV2lZMzIyMHY0dFVNL1V5NVhkbTIrc3dtdzJGYTd5V05aU3pnNFhwY2dPcHhwZnNVQ0d5eXJUVDdTM3hpeWxtMmZjdTZhWUwxK20yY2szaWtPd0xHL2J3UzVYOFh0bmFHbnhlMHhSUit6THZIaU52dmM2MzRBbnk0REJuVStOcFRhbDV0cEdDOElCOHNhWUtXcDRkeXpCM3UvYnJrb0xJTmY1Z0V4WUNkWlFvcGM0Rmp4bWRFRXlFWUxBRzBteWk3dVczM3VYaWovS0JKdzczUURXVlFVVS9GdWcySTYzRnNEMXg4SmEyLzVPT0JtVnJiNjJ1RU5STFprMDdnZkN2bE51a0VuRUJ6RllGTFR4SHFRU29oZnkyaDMyUlVoYnp3RE1VQ2xvRU1xa0dxWkNDWDdzR0E4SEdyalM0NXNhR2dVL0tLam9UQTNSOFUrclRidysvNlhUcGN0UEZ0UkcvOGQ2YUQxdDVhcVpkcm56eWl1cE5tYlIxUHd0THQvT2ZuOW1iM0JYaWwvazlUd3BsUitZS0Z4QUVtT0JoVVlaWUh6SWF6RjlBQmN0QWpSRDQ1Tm5sVm1PaWpnS2YvQlBxSGgyRFdyTFUvelhSRHFWNFRwaWcrckdtZWhsOC9jU0owVUdsdDR4SWFEWExLQ2pHRFd2YVI1U0FxZ0toTEdHZFo4VzVEY3FtMFVpOCtFeWQ1N3pma2MzTWNobkVyNGZtOXEzTGpyK1MxdlVYNE9kQ3Q5Qy85dVR6a3RIM1d3Tm41QkgxeVEyOWdTUlczOU5UN0xyMHMxQ2h4VG1sSkl5ZHZYRkU1d2pnaS9ncUxORW8zM29OemhWNkpQN29FTndNTjFsaUVOc1FzUnJmaEZVZGhpU1BDM28vN0VNUUM2bW41eXpscHJPUFdmWmQ1cWlPQllHV3JvZFp1ZEpWTjlrRWEySUxVYURidnl3MkdMQW9zamJKV2tRZXQwRXMxakt6OFc0N1oxbElaK0RNZGtJanRKbHIxdC9zWk1nbFpVSUUrcTZvQXNzWFFQVUJxM1Rsa2xWK1FSNUdLSEpKMVUvYTd1T0FvblF4bVM0Zm9BYnVDNmU5VDdacE9qSDl1S0pFZ2dBYUh6a3JLcEpoZTlVQXAxNll3Ym83YlNTRzQ3bllLY0h0T0R5OTQyRjZaZk1kZFBiNERWcVZocVdCcEZidlJSRElRL1B2VnoyVlRqNWhtdVQ1NGpkYVJsUlhvajRFMmJXZmdNVE9lYjQyRzg0ck5JYjFyVWdTdHlmNWJCZVVyUDBWSFRrRlJYOTIxTG5UMXVJL05FaGtxK3JDMEIzZXJFU1lKcGUxUGZyUXpwRDhoc29BN3lJazUwUGwveGQzTHUrSDZsTXF4YXhWUzlXZ3BoeFFuSHFuQT09IiwibWFjIjoiODFlOTU1ZjcyNDczYzQ3MDdhNDRkNDZjNzIzNzJmYjViYjNlMTNiOWZjNWYyMTc3YTRjNWM4OGFhMzg5OGQ0NCIsInRhZyI6IiJ9";

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (decrypt($this->offices) as $row) {
            Office::create($row);
        }
    }
}
