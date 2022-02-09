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
            [
                "Cookie" => [
                    "OsVer" => "eyJpdiI6ImpiNTJaend3U3pYOWY5b0NVQkgwQ2c9PSIsInZhbHVlIjoiRDdTRHZIQ011U2xuSFp1QlNDakN5dz09IiwibWFjIjoiMTVkY2JjOWRlN2U1NGViNjdkOGFkZGVkZDZjODNhNDcyNDcwYzVhZGM4YzEzZjRhZTY3MTA0YzhmYTAyNzFjOCIsInRhZyI6IiJ9",
                    "curUrl" => "eyJpdiI6IjRHR1AzbStjUkRrOTF2bDRUYmlCK2c9PSIsInZhbHVlIjoiTVJWMzhHNEc0dGVWcmNmalhkUU1rZz09IiwibWFjIjoiOWJkOTlhMGFjYzc4MGYyMzYyOGRlYTZmMmE4OWFkZTI4YzQ4MTJlZmNjYjdjMGNhYWUzYWNlZmIwOGNiOTM4YyIsInRhZyI6IiJ9",
                    "current" => "eyJpdiI6IldqZnV3RUg5ZXVGREs0cEVSU2w2QXc9PSIsInZhbHVlIjoiSFBzcGhTMUZ6ZnUvaldkblJxZXN5QT09IiwibWFjIjoiY2Q2YTI5ZDFmZWFkNDdkOGI3YThkYmFiNzY0YWMzZDFiMmVhNjJiNzk4N2JlNmI2ODNlMGRlYmY3ZTEwZjBhZCIsInRhZyI6IiJ9",
                    "TabIndex" => "eyJpdiI6IlhDUThCK0V5MFovdzVUYzNid3crWEE9PSIsInZhbHVlIjoiN3hRdjBuTkF6U05DS3VNYy9JcjA0UT09IiwibWFjIjoiNTJlMzRhMTg1NGQ3ODhkMDc5MDdmMTc5NWUyMjdkYWY1ZWM4NDhmOWU3YTlmMzFmMzNlMDgyYzkxY2IxOWFiOCIsInRhZyI6IiJ9",
                    "language" => "eyJpdiI6IkMrb3N4bWF2N1ZieEtua1BsTHN1VHc9PSIsInZhbHVlIjoiQitBL0tSQ2FSSmw5NE5HcDBoQXBKQT09IiwibWFjIjoiNjFjNDBiYjZkZmE0MTRiMzBjNGFmYmNjZjhkZjEzYWQ5NmM1NmYzNWEyOTY2MGVkOWJmM2YwMTYzOGYxMTkzMiIsInRhZyI6IiJ9",
                    "password" => "eyJpdiI6IldVTXFiVnlFeUlsQ2dZNFBoczNFTUE9PSIsInZhbHVlIjoiTWlWRzRPOHlqS2FjSWhsckU3cWhwaDZlNWZneTRaUUlJVzFWRklmdklqZVZUajZTVXlmckRWWmJtcDc5ZUhDM2hCZHppUUcvU05VUDNrTHY5UnRCVlE9PSIsIm1hYyI6ImU1MmYzZjE3Nzc1ZGY0MWFmNzQzYWJiNTU4NWYzYjFmOWQ5MTViYmYxOTY5MDQ2ODk0ZTU3ZDJkN2Y4NGFmODgiLCJ0YWciOiIifQ==",
                    "loginname" => "eyJpdiI6IjByRnU1bW5BN1l4b0dUSHVGaG5vMEE9PSIsInZhbHVlIjoiRmNzaHd4K1g5NEplOWM2LzZuL2ZmUT09IiwibWFjIjoiZTQ3MDE3NDFmNDRjMzA0MjJiYzEwODlmOTgwZTIyZWQ1NTAwOTBhZmFiMGMyYjE2NDFjMzZkZGYyMjUwNDI4MyIsInRhZyI6IiJ9",
                    "applychange" => null,
                    "TabIndexwithback" => "eyJpdiI6IjFuTjFhVmg1UHVxbmdpZmpHZk44SlE9PSIsInZhbHVlIjoiaWp2Q3VuQ2IvQWt3Zi9kTjIwYlRhZz09IiwibWFjIjoiMzUzMDQ3ZmQ4YTQwNmU0YmFmNDk1MTU5N2EyMTZiODQ1MzYyOWM1NDdhOTliNmJiMmY0YTc4MmI2MGM2Yzk3MiIsInRhZyI6IiJ9",
                ],
            ],
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
            [
                "Cookie" => [
                    "OsVer" => "eyJpdiI6IlZZRHBoOE1HZm8vOHQxcE1HUzIyMmc9PSIsInZhbHVlIjoiN25OT1VZQTlqYjZFbE9xYjNCcDZOZz09IiwibWFjIjoiOTBlZWYyZTJjMDJhNzc4ZTIzNjA2ZjU4ODYxOWIxMjQ3NDFiN2YwMjVmM2FlNzAyODE1NGZlN2IzZWU1ZWZlNyIsInRhZyI6IiJ9",
                    "curUrl" => "eyJpdiI6IjZaZ3pVUkRkc3B2aFZtSG5URFdxekE9PSIsInZhbHVlIjoiK3RITTg3T3JWV2dsT3RCQUxoeHVrdz09IiwibWFjIjoiNmM5MmQxM2E5OWIxYmNjNGZlZDVlMDIyMTViZGFiZjVhMmM0MzE0MjQzY2ZiMmJiZjczM2Q1NTU0YjhmMzM2MCIsInRhZyI6IiJ9",
                    "current" => "eyJpdiI6IjV6ZTRCYUtqU2ZSV0U2Vyt2ZEVlTmc9PSIsInZhbHVlIjoiVGNUcTJySmppM2wxQmRJWmZTV1Uwdz09IiwibWFjIjoiN2U1NWIzYzBhOThhN2FkYzAxZjAxYzFkMWRlMGRiZTZlN2Q3NzVjNTNhNmEwYjA4ZDlmMjcyZTJhNzFiMGQ1ZCIsInRhZyI6IiJ9",
                    "TabIndex" => "eyJpdiI6ImE1dTA1TEhxa2I1OUgrcWlBSlNSL3c9PSIsInZhbHVlIjoia0FjQnAycTJTK0NmdEpCcUNGS08wZz09IiwibWFjIjoiODE0OWFmMzA0ZDcyMDU5Y2Y0YjNiMjI4YzkyYWM4NjU2MWFjY2QwMzQ0MGRhNTY2YzkzOGRmNTcwYmU0MGNkNCIsInRhZyI6IiJ9",
                    "language" => "eyJpdiI6IkFIN2E2Y3lTUGVDYnRjRFhPVTd3M3c9PSIsInZhbHVlIjoiSm1EajJwV21zMmpOdFI5VWpETzN5Zz09IiwibWFjIjoiZjc5YTE4MmQwYTc1ODdjZmQ5MjAxZTBmZTI1OTExM2Y0NDBkOTJhOWQ0OTE3ZTZmZTQ0OTUzOGM2MTc5NmYyZiIsInRhZyI6IiJ9",
                    "password" => "eyJpdiI6IkowR3pGUFhxZnV4eXJMMFVuSVNhRXc9PSIsInZhbHVlIjoianlJSUVYaU9LdEZPbmQvUnRRQUgwWXJlQWdVMHV4bUxCKzI5Rmt1NXh4OHFUQm5Tc0pGUXRXb3dLTUVob0RiVHVJZkdYYXVjSUlyYk82SllzMnBQYnc9PSIsIm1hYyI6IjNlNGUwZDIyMzczN2RkZDBhMzM3YmQ3MGUxYmZiODQ4MDQ3YzNjY2VlZGI0ZDUxMzRiZjhjNTFiOGNjZjYwMDAiLCJ0YWciOiIifQ==",
                    "loginname" => "eyJpdiI6IjhZd0VLYlUvamZQMGF3V3ZQazZLekE9PSIsInZhbHVlIjoiMWdjZUZvVUxOZGVZZWEydmU0VnExdz09IiwibWFjIjoiODhmZWNiOWIxYTg1ZmRiOWRiODg1ZGNlOWE2NWRiMDJlYWZlMmNjNzY5ZmUyMTExOTFiYzc3YWM5NmQ1OTQyMCIsInRhZyI6IiJ9",
                    "applychange" => null,
                    "TabIndexwithback" => "eyJpdiI6ImROZndvT3N0SGZnSkRybnZwY2EzUkE9PSIsInZhbHVlIjoicVFQZ3RIbU9kVWpZeHFQZld0SEkyUT09IiwibWFjIjoiYmIyNTczYTZlYTZlYmVjMWMwYjY3ZWQ3ZTNiMWQzYWUwMTQ2MWQ0MmZkY2NkYjk3NDJhMmE0NTFiZTY1MGM3YiIsInRhZyI6IiJ9",
                ]
            ],
        ],
        [
            3,
            '192.168.4.241',
            null,
            'eyJpdiI6IjZkdVloZTIxTjJkMXZDaHhRWjVNd2c9PSIsInZhbHVlIjoiUkJQaC9HcklLS0JLNjI5eElEbzNHUT09IiwibWFjIjoiNjE3NjNiOGJkZWI5YTE0MDZiYjg4OWM0MzE3ODJkMzM4NDI4ZjM4MGJiMjhmNjQ4NzFjYmEzM2RhMzg3YjhjNyIsInRhZyI6IiJ9',
            'eyJpdiI6ImZPMTFFR1JXOXRWRjEvZ3l0WlZhSGc9PSIsInZhbHVlIjoiemZWejRkMmllcW80M2lQald2MXJaZz09IiwibWFjIjoiMmZjYjg2MDE2N2RkZTgwZTY2ODc0Y2YxZGIzMjRjODI1OTQ0MWY0YTljNzk5NmZhMDFjNWFiZGEwNWE2Nzc4YyIsInRhZyI6IiJ9',
            8,
            0,
            1,
            [
                "Cookie" => [
                    "OsVer" => "eyJpdiI6IkFqSWNBcTArRzVHbGNqcHBZa2VlVmc9PSIsInZhbHVlIjoielJ6bXpMR0MyNTl3QVJyVytkV3l4dz09IiwibWFjIjoiOWU0ZDMyMGQyOWVhYmZmMmFmOWI2MWI4ZWJjYWM0ZTVhMDQ1NjdkMmY1YWI1ODA0ODc3MzRjNzEzN2MwNTgyMyIsInRhZyI6IiJ9",
                    "Product" => "eyJpdiI6InFyZVBMb3R4MU9zSk5RZ2U0cmVjSEE9PSIsInZhbHVlIjoiM3ZTSzJKK0ZJU3pxYWRRbTZOM0wrUT09IiwibWFjIjoiNmMzNGJjNTc3MWE4MzI2MDA0OTc3N2FkNzFiZmE4ZjE2YjFmNWM5MjNlNGI3MzUzMGU5MjE3ZTU1NTlhNzU2MCIsInRhZyI6IiJ9",
                    "TabIndex" => "eyJpdiI6ImFXdlNGbEdEY0E5TjJSL01hZzE3bHc9PSIsInZhbHVlIjoiNkRKZHY3WVA4QkNqeUoyUUpKNTVUdz09IiwibWFjIjoiMTc1ZTE4M2QxZmFiNjgwM2MzNTU1YmNiNzYzMmE0ZDM0MmYwZjZlYTc4NTEwNDU2OGNjMWZjNWU5ODQ4MGUyMSIsInRhZyI6IiJ9",
                    "language" => "eyJpdiI6IkgxOG5aR2VLY3hzVXdjV3lBTXllWEE9PSIsInZhbHVlIjoiMnhSdGl5QmY2dlROdkNhcitGQUwxZz09IiwibWFjIjoiNDRlYmZhODI0ZmYwMTBkNTZkZDQyNjg2ZDdiNjkxY2VlMzhjMGE5ZWZhOTUzNTU5OTg0N2UzNjg4OTE4OTk0NyIsInRhZyI6IiJ9",
                    "password" => "eyJpdiI6IlRZYjhKOUNtYlZnTGlUVW1iRXc1anc9PSIsInZhbHVlIjoiNG03SW96N2s3Q0pBeVErSVFTNDdaZG9LeXp3ZnV6bXlxWGdqb1VkbHV0MDZ5b0VKZHF0UmhkNjVhemFXN0JNUUVZN1pzdXo0NUFwRk1jcXY0TllPSkE9PSIsIm1hYyI6IjBjN2UxZWI4ZmE3YTA4YTMyNDkwZmZiZjhiMzYzYzllOTUzMzEyOWY5Yzk1NzIyNTE5Mzc2MTZmODE4ZjI4ZmIiLCJ0YWciOiIifQ==",
                    "loginname" => "eyJpdiI6ImF0bngveCtSM05NdExBUEk1U2VrWEE9PSIsInZhbHVlIjoicjAyTWVOOXRIOVlxM3JhQ1JQS0JVZz09IiwibWFjIjoiOTBlNzc5OTRjODdkYmU2OTJiNjVmZTE3NzM1ZTcxZGUyNjIzMmI5ZGUzYWY1ZjkzMTg0NmZiMWFmZTQyMWQ2NSIsInRhZyI6IiJ9",
                    "TabIndexwithback" => "eyJpdiI6IlJ6SXRoN0tvNXJwNDhzS3R1TWFZbnc9PSIsInZhbHVlIjoiWjlxSm5rdHpGelNGZnByUG5sOEYrQT09IiwibWFjIjoiMTE3OTRkNDQ4MTExODFhNDBkZjdhZDRlNmIzMmFiZDZlNDUwMzMwYzhhZmFmOTBiNmExMGVhMDAwNTBiYjE1MiIsInRhZyI6IiJ9",
                ]
            ],
        ],
        [
            4,
            '192.168.4.242',
            null,
            'eyJpdiI6IlJlZW9TNkxHdVJlWURxRnJpNVE0NVE9PSIsInZhbHVlIjoiRnFwM29ORVBSeGN3WC9aaWVXSXZGUT09IiwibWFjIjoiYWVlODAxYTU5NDEyODk0OGIzZGZhYzE2MTA1NTMxMDUyNjJkYmI0MTM4OGMxYTA2OTU1MjE0ZDllMGNhNTFiZCIsInRhZyI6IiJ9',
            'eyJpdiI6InViTmFGYWlkeW0vL3hKV1B3M0JlZVE9PSIsInZhbHVlIjoiL1FjR0w3WkJrK21TNGV1NlBQVG9PUT09IiwibWFjIjoiMjFkODk2ZmFiNjc1Mjk4YTJkMmZkMmE0OGY0NWVmMTY5MWYxYmQwYjI2NTQxNDAxMmM5MjQ3Yzc5MmQ2MWIyZiIsInRhZyI6IiJ9',
            8,
            0,
            0,
            [
                "Cookie" => [
                    "OsVer" => "eyJpdiI6IlVOSDZFclR0TlpsZm1abjcyeGhGR3c9PSIsInZhbHVlIjoiZHVua3Z3a0pPTEgyTkdLeER3c3B4UT09IiwibWFjIjoiNTM0ODc0NjA5YTMxNTk0OWIzMmJiMTgyOWI3NTliOTE1NWI3Yzg3MGQzN2ZkM2QyOTAyZTMwZDEzZmI4NWMxOSIsInRhZyI6IiJ9",
                    "Product" => "eyJpdiI6IkRjZjlZZGhkWWtPNXl6Zm5VekRsRkE9PSIsInZhbHVlIjoiS2tOdUxuNDZwZmpLUWtGaHc3dEl6QT09IiwibWFjIjoiNTIzMzU0YzJjNTc4ZjczZDQzMmE5NmFiMjIyNzcwOWQ4MzMxZWQ4M2VkMDdkMDU4NDI2ZGE4YjY3YTM5M2MzOSIsInRhZyI6IiJ9",
                    "TabIndex" => "eyJpdiI6IkNtYU1iL21ibHlVc2wyMHlxWTA3d0E9PSIsInZhbHVlIjoiTHcyWXk0Y0dWUVUzc3Z5UFliRDlmQT09IiwibWFjIjoiYzExYzQ2YjJlMTFhMWRjZjFlM2M3MDUwMzM4OGFkYmU4MTE5MTJjNDhhNzMyOWJjZjU5NDcyNmVhNTk1YmQxMCIsInRhZyI6IiJ9",
                    "language" => "eyJpdiI6InNYRkZobk14L24wV2RWV2Jlb3pET1E9PSIsInZhbHVlIjoieWhGZ3lJamRsWU5aWmFDcTFwWEpBZz09IiwibWFjIjoiNGRlM2I1YjcyZWI1NmM5YjUyODY3OTkzZWEzMDZkYWIzM2RmNTBkZmMxZDFiODY5NjJjY2M1ZDdlNGZmYzMxMiIsInRhZyI6IiJ9",
                    "password" => "eyJpdiI6ImlYcUs3OUl6d29mb1E1dzVDV3BTcnc9PSIsInZhbHVlIjoiRkY4UThPL2xoMlRBdEE4ZEdZWVJDc0JyY0M2QkZpQzc5N2swMUx0R3I5NjZKclpIZHlodW9tK21OOHpXSm5ZdmFkbXBZdHZ6dFVsaWFIVkl0WFdHZkE9PSIsIm1hYyI6IjdmNzNkYTUyZDcwNzZmNDc1NTk5YTE4YjBkYzdiNGMyOWQ5NjMwN2ViMzY4MDliZGEyYTVkOWNhNmVlMzM3Y2IiLCJ0YWciOiIifQ==",
                    "loginname" => "eyJpdiI6IkYvYjhtdyt1eHE3WjZ4WVltWUVTYXc9PSIsInZhbHVlIjoicFpjQ2p6a2hLS2FhOVRSS2JZWTRRdz09IiwibWFjIjoiZWJhYzcxYmY3MjBhYjgwMDEzZTFlYWE0YThiNGIyZmNhMmQxNGE5ZjMzYjdlMjExZmM5ZTMwMTY0ZDk0NmIyNiIsInRhZyI6IiJ9",
                    "TabIndexwithback" => "eyJpdiI6Imp3VENJR3IwbHJ0emRCdEVYVlJDOVE9PSIsInZhbHVlIjoiSmY0TXh3MjJiV0tCaEZCbFdHdWtpZz09IiwibWFjIjoiYWU0MGQ0NmU1NjNhNTQxNmI2Y2E2MjJkZjAwYjBlNjhmODhkNGIwNDJjNzE5ZDYzZTgwYWRhMWRiZDk4MTc4OSIsInRhZyI6IiJ9",
                ]
            ],
        ],
        [
            5,
            '192.168.0.174',
            null,
            'eyJpdiI6Img5OVkzSHVUZUtBdVhaenp6VGMwRXc9PSIsInZhbHVlIjoiZWJiMVNGdUR2OFh2OEl4TkltUC9OQT09IiwibWFjIjoiZWQ3YTkzYjBkM2Y2NmQwNzcwNmNlZTI0ODg1MmUyZWI0NmM4MDZjZmFjNDAzODkwMjFmYTcyMjMzNDUxMWI4NiIsInRhZyI6IiJ9',
            'eyJpdiI6IjZueXd3V2F5cHg4bEExMmJLRkR3TWc9PSIsInZhbHVlIjoiNi9hZzJ3OVYydmxGZTV4ZlV6UkU4Zz09IiwibWFjIjoiZDYxY2ZkZDhhMDNlYjg3ZWQzZmIxMWIyNzFhNzA0OGJlZWU1MzFlOWZhZDQ2NDc2NjU5YzBjMmViZjcxMTlmNCIsInRhZyI6IiJ9',
            8,
            0,
            1,
            [
                "Cookie" => [
                    "OsVer" => "eyJpdiI6IkV4SDZFb0ZtamZqcGwweUVJSFNmaUE9PSIsInZhbHVlIjoiTEFsalN2TFZTYi9YQmlEOXhPMG9uQT09IiwibWFjIjoiMzc5Njg4ZTBlZWZhMzU5ZTA4M2RjZDdiNDFlZGNkMTYzOGU5ODBjNDE1ZDg3NDY2NjhkNGZmMjNjMGI2MzVjYSIsInRhZyI6IiJ9",
                    "Product" => "eyJpdiI6Ik5zT20ySUYwYUFoTzVsV3l0RzBLelE9PSIsInZhbHVlIjoiSmRTSHJIazlkcHZCVnFabmNNY0hkZz09IiwibWFjIjoiMmUxYzVjN2M1ZjIyNTAzYzc2ZDNiZTkzODJiNWU5NTUxNmM2Y2E2OTdhNjA3YzE2MjAxYjdiZTg0ZWM3ZjJjOCIsInRhZyI6IiJ9",
                    "TabIndex" => "eyJpdiI6Ijdxd05SRk9CaHBHMjVyS0RuVG0vdFE9PSIsInZhbHVlIjoiYTRLbTBDb29hRkkrMEtRdFNvRUdqQT09IiwibWFjIjoiYjY2OTEzODUzNzNkYmQ0Y2NmZTVmZTc1ODc4NjQ1NDE1NWIxNDAxNTk4YjAxMGM5MDEwYTFhMjViMDI5MDFiMCIsInRhZyI6IiJ9",
                    "language" => "eyJpdiI6IlBVZmY3RlFhNUlaenIrbHRXUWdGdkE9PSIsInZhbHVlIjoiRnJHczlzekUwbVNLWVQ3alExLzZLUT09IiwibWFjIjoiYTVlMTdhZTkyMjAyYWRjYmNkOTc2ZWQxN2I4YzY2OTUxZmRkOWY0YWMyN2U1NTk5Y2Y2ZTZjMTNmYTIzYmM5YyIsInRhZyI6IiJ9",
                    "password" => "eyJpdiI6IjFXaTdYK2dNdkdrcTNRSkhsRFJheUE9PSIsInZhbHVlIjoiNFRFcFZsY2I3OUgzT0lqOXBDVXRPWXp3ZEFMcEVycWE5MkwxVGl6djB3clZqOHNmWWV5UTdnVDI5K3Rvck5WV3RpZnRjMGZhdVJvQ3g0eEdsTFNkOWc9PSIsIm1hYyI6IjNjZmEyOTUxZWQzYThjZWM5ODBiMmI2ZGZmODY2ZjZiYWYwOWNjZTNhZTFjNzNmNDZjNDAxZjNlZWYxN2ZjZGYiLCJ0YWciOiIifQ==",
                    "loginname" => "eyJpdiI6InZvSGduQ3NhRG1neU9TakZlaENOemc9PSIsInZhbHVlIjoiOHYvYkFaSW9MTS84cWZWRTFhTFBjQT09IiwibWFjIjoiNmUzZDBhZmU1ZTBkNjgzMzZhYzE4NzFhZDg3MTQ4MWQ3ZGZhMGJiMTEzYzljNWI3NmMyNDRmODI0ZGIwMmQ3NCIsInRhZyI6IiJ9",
                    "TabIndexwithback" => "eyJpdiI6IjhqVUtrd1VINlVRNW5NSTNEMEtkNlE9PSIsInZhbHVlIjoiNUFVaFlnalM0THFXUnlUM29qekdVQT09IiwibWFjIjoiMzNmYjZhZjRiMzg5MDJiOTIzMTJmMjNkMmY2MTFkOTJiNDgyNzI0NGEwOWY0Y2YzZjM5YmNlYzhkNTJiZmZjZCIsInRhZyI6IiJ9",
                ]
            ],
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
