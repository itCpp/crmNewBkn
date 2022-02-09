<?php

namespace Database\Seeders;

use App\Models\Gate;
use Illuminate\Database\Seeder;

class GatesSeeder extends Seeder
{
    /**
     * Колонки данных
     * 
     * @var array
     */
    protected $rowsColumn = [
        'id',
        'addr',
        'name',
        'ami_user',
        'ami_pass',
        'channels',
        'for_sms',
        'check_incoming_sms',
        'headers',
    ];

    /**
     * Данные
     * 
     * @var array
     */
    protected $rowsData = [
        [
            1,
            '192.168.0.175',
            null,
            'eyJpdiI6ImVIci8wL0gxbnM4VDhjRy8wd0Vhbnc9PSIsInZhbHVlIjoieXJCNU9PSTdpb1I2V0p0cXBJOHNIZz09IiwibWFjIjoiZTBhZGU3NzViYTMyZWUxNGU2NDJmN2UxYThhZTQ1ODk0ZDEwNGEzY2M5YWJkYTFhYzY1NmQ5OTgxNmJhZmE3ZiIsInRhZyI6IiJ9',
            'eyJpdiI6IkMxaW41Z2ozYUxkSmpsV0Q1WUdWdlE9PSIsInZhbHVlIjoib29Ha2xGMGxFMDhDamJRdi9IMHQ3YWx1UHlqOHp2dWVWWFlyVy9ENS9Tbz0iLCJtYWMiOiIzMzAwOGNjMWEyZGE1NzRkMzFjZWNmY2Y2MjE0NmNmN2FiZjEwNzA2MGNlYzJhMmIyZTc3ZDY4ZTIwMDBmYTJmIiwidGFnIjoiIn0=',
            2,
            1,
            1,
            '{"Cookie": {"OsVer": "eyJpdiI6ImpiNTJaend3U3pYOWY5b0NVQkgwQ2c9PSIsInZhbHVlIjoiRDdTRHZIQ011U2xuSFp1QlNDakN5dz09IiwibWFjIjoiMTVkY2JjOWRlN2U1NGViNjdkOGFkZGVkZDZjODNhNDcyNDcwYzVhZGM4YzEzZjRhZTY3MTA0YzhmYTAyNzFjOCIsInRhZyI6IiJ9","curUrl": "eyJpdiI6IjRHR1AzbStjUkRrOTF2bDRUYmlCK2c9PSIsInZhbHVlIjoiTVJWMzhHNEc0dGVWcmNmalhkUU1rZz09IiwibWFjIjoiOWJkOTlhMGFjYzc4MGYyMzYyOGRlYTZmMmE4OWFkZTI4YzQ4MTJlZmNjYjdjMGNhYWUzYWNlZmIwOGNiOTM4YyIsInRhZyI6IiJ9","current": "eyJpdiI6IldqZnV3RUg5ZXVGREs0cEVSU2w2QXc9PSIsInZhbHVlIjoiSFBzcGhTMUZ6ZnUvaldkblJxZXN5QT09IiwibWFjIjoiY2Q2YTI5ZDFmZWFkNDdkOGI3YThkYmFiNzY0YWMzZDFiMmVhNjJiNzk4N2JlNmI2ODNlMGRlYmY3ZTEwZjBhZCIsInRhZyI6IiJ9","TabIndex": "eyJpdiI6IlhDUThCK0V5MFovdzVUYzNid3crWEE9PSIsInZhbHVlIjoiN3hRdjBuTkF6U05DS3VNYy9JcjA0UT09IiwibWFjIjoiNTJlMzRhMTg1NGQ3ODhkMDc5MDdmMTc5NWUyMjdkYWY1ZWM4NDhmOWU3YTlmMzFmMzNlMDgyYzkxY2IxOWFiOCIsInRhZyI6IiJ9","language": "eyJpdiI6IkMrb3N4bWF2N1ZieEtua1BsTHN1VHc9PSIsInZhbHVlIjoiQitBL0tSQ2FSSmw5NE5HcDBoQXBKQT09IiwibWFjIjoiNjFjNDBiYjZkZmE0MTRiMzBjNGFmYmNjZjhkZjEzYWQ5NmM1NmYzNWEyOTY2MGVkOWJmM2YwMTYzOGYxMTkzMiIsInRhZyI6IiJ9","password": "eyJpdiI6IldVTXFiVnlFeUlsQ2dZNFBoczNFTUE9PSIsInZhbHVlIjoiTWlWRzRPOHlqS2FjSWhsckU3cWhwaDZlNWZneTRaUUlJVzFWRklmdklqZVZUajZTVXlmckRWWmJtcDc5ZUhDM2hCZHppUUcvU05VUDNrTHY5UnRCVlE9PSIsIm1hYyI6ImU1MmYzZjE3Nzc1ZGY0MWFmNzQzYWJiNTU4NWYzYjFmOWQ5MTViYmYxOTY5MDQ2ODk0ZTU3ZDJkN2Y4NGFmODgiLCJ0YWciOiIifQ==","loginname": "eyJpdiI6IjByRnU1bW5BN1l4b0dUSHVGaG5vMEE9PSIsInZhbHVlIjoiRmNzaHd4K1g5NEplOWM2LzZuL2ZmUT09IiwibWFjIjoiZTQ3MDE3NDFmNDRjMzA0MjJiYzEwODlmOTgwZTIyZWQ1NTAwOTBhZmFiMGMyYjE2NDFjMzZkZGYyMjUwNDI4MyIsInRhZyI6IiJ9","applychange": null,"TabIndexwithback": "eyJpdiI6IjFuTjFhVmg1UHVxbmdpZmpHZk44SlE9PSIsInZhbHVlIjoiaWp2Q3VuQ2IvQWt3Zi9kTjIwYlRhZz09IiwibWFjIjoiMzUzMDQ3ZmQ4YTQwNmU0YmFmNDk1MTU5N2EyMTZiODQ1MzYyOWM1NDdhOTliNmJiMmY0YTc4MmI2MGM2Yzk3MiIsInRhZyI6IiJ9"}}'
        ],
        [
            2,
            '192.168.0.176',
            null,
            'eyJpdiI6IitPZkRCL3JqQ0pRTk1mK0g5R210Y0E9PSIsInZhbHVlIjoiS1ZOdm5BVXF6MjdZSHk0RGRLVmloQT09IiwibWFjIjoiYzg5NjZkMjA3NGU3YjQ5NjczYWJkOWJlNTg2MTAyNThmNTI5OTExYzRhZDNmMDEzNDM2ZTljYmUxNzZjNzY0YSIsInRhZyI6IiJ9',
            'eyJpdiI6ImlnU1VQdnZFWGlpY1EvazNxcEhmQ0E9PSIsInZhbHVlIjoiM3FqMCttSlNXaUozQkxGdXJSMmozUzNJSHRSb205MVZmWFpiNXFEWUdYaz0iLCJtYWMiOiJiNDM2MzdhNTc4ZTQzNDNhY2ExZmU0YjRhMjJmOGI2NTY3MjFjNWVkODAzYTEzZWE4ZWNhMmMzNmRmNzI0ZjYyIiwidGFnIjoiIn0=',
            2,
            0,
            1,
            '{"Cookie": {"OsVer": "eyJpdiI6IlZZRHBoOE1HZm8vOHQxcE1HUzIyMmc9PSIsInZhbHVlIjoiN25OT1VZQTlqYjZFbE9xYjNCcDZOZz09IiwibWFjIjoiOTBlZWYyZTJjMDJhNzc4ZTIzNjA2ZjU4ODYxOWIxMjQ3NDFiN2YwMjVmM2FlNzAyODE1NGZlN2IzZWU1ZWZlNyIsInRhZyI6IiJ9","curUrl": "eyJpdiI6IjZaZ3pVUkRkc3B2aFZtSG5URFdxekE9PSIsInZhbHVlIjoiK3RITTg3T3JWV2dsT3RCQUxoeHVrdz09IiwibWFjIjoiNmM5MmQxM2E5OWIxYmNjNGZlZDVlMDIyMTViZGFiZjVhMmM0MzE0MjQzY2ZiMmJiZjczM2Q1NTU0YjhmMzM2MCIsInRhZyI6IiJ9","current": "eyJpdiI6IjV6ZTRCYUtqU2ZSV0U2Vyt2ZEVlTmc9PSIsInZhbHVlIjoiVGNUcTJySmppM2wxQmRJWmZTV1Uwdz09IiwibWFjIjoiN2U1NWIzYzBhOThhN2FkYzAxZjAxYzFkMWRlMGRiZTZlN2Q3NzVjNTNhNmEwYjA4ZDlmMjcyZTJhNzFiMGQ1ZCIsInRhZyI6IiJ9","TabIndex": "eyJpdiI6ImE1dTA1TEhxa2I1OUgrcWlBSlNSL3c9PSIsInZhbHVlIjoia0FjQnAycTJTK0NmdEpCcUNGS08wZz09IiwibWFjIjoiODE0OWFmMzA0ZDcyMDU5Y2Y0YjNiMjI4YzkyYWM4NjU2MWFjY2QwMzQ0MGRhNTY2YzkzOGRmNTcwYmU0MGNkNCIsInRhZyI6IiJ9","language": "eyJpdiI6IkFIN2E2Y3lTUGVDYnRjRFhPVTd3M3c9PSIsInZhbHVlIjoiSm1EajJwV21zMmpOdFI5VWpETzN5Zz09IiwibWFjIjoiZjc5YTE4MmQwYTc1ODdjZmQ5MjAxZTBmZTI1OTExM2Y0NDBkOTJhOWQ0OTE3ZTZmZTQ0OTUzOGM2MTc5NmYyZiIsInRhZyI6IiJ9","password": "eyJpdiI6IkowR3pGUFhxZnV4eXJMMFVuSVNhRXc9PSIsInZhbHVlIjoianlJSUVYaU9LdEZPbmQvUnRRQUgwWXJlQWdVMHV4bUxCKzI5Rmt1NXh4OHFUQm5Tc0pGUXRXb3dLTUVob0RiVHVJZkdYYXVjSUlyYk82SllzMnBQYnc9PSIsIm1hYyI6IjNlNGUwZDIyMzczN2RkZDBhMzM3YmQ3MGUxYmZiODQ4MDQ3YzNjY2VlZGI0ZDUxMzRiZjhjNTFiOGNjZjYwMDAiLCJ0YWciOiIifQ==","loginname": "eyJpdiI6IjhZd0VLYlUvamZQMGF3V3ZQazZLekE9PSIsInZhbHVlIjoiMWdjZUZvVUxOZGVZZWEydmU0VnExdz09IiwibWFjIjoiODhmZWNiOWIxYTg1ZmRiOWRiODg1ZGNlOWE2NWRiMDJlYWZlMmNjNzY5ZmUyMTExOTFiYzc3YWM5NmQ1OTQyMCIsInRhZyI6IiJ9","applychange": null,"TabIndexwithback": "eyJpdiI6ImROZndvT3N0SGZnSkRybnZwY2EzUkE9PSIsInZhbHVlIjoicVFQZ3RIbU9kVWpZeHFQZld0SEkyUT09IiwibWFjIjoiYmIyNTczYTZlYTZlYmVjMWMwYjY3ZWQ3ZTNiMWQzYWUwMTQ2MWQ0MmZkY2NkYjk3NDJhMmE0NTFiZTY1MGM3YiIsInRhZyI6IiJ9"}}'
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->rowsData as $row) {
            CreateCrm::createRow($row, $this->rowsColumn, Gate::class);
        }
    }
}
