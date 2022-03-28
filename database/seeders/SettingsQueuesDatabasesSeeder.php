<?php

namespace Database\Seeders;

use App\Models\SettingsQueuesDatabase;
use Illuminate\Database\Seeder;

class SettingsQueuesDatabasesSeeder extends Seeder
{
    /**
     * Данные
     * 
     * @var array
     */
    protected $databases = [
        [
            'name' => 'ЦПП',
            'domain' => decrypt("eyJpdiI6IklBcHEvUnM1YlFjckZoOU1nSDZYcWc9PSIsInZhbHVlIjoiMnN4M3dqdmptNksyTWVBNWtHUE9NekQyQUNycUwyRWVkUmVZVFNxMS8wVT0iLCJtYWMiOiI1MjIyZjA0ODIyZWM5Yjc3MjQ4NGY1ZjNlNmU4M2E1YWRkNmZlYzIzOTI4MjRmNGM0Mjg5NjU5YTc5YWMwM2ZhIiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6IjU4MmZscGtOeEQvL2JBRjZrTDBweGc9PSIsInZhbHVlIjoidGMwNS9PaCsyOXcwTk9zRW9UNklnZz09IiwibWFjIjoiMjRlNmZiNjNlZDgzYWMxMWI0OGQ5YjUyM2Q5ZGMwYjc1YTlhNDJkYmM5ZDJiMjVkYTBhODU2YTBkYjBjOTMxZSIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6ImpjKy9qUENFTmlJNVMwbjdXQ2JHOUE9PSIsInZhbHVlIjoiNXNjUmhXK3pGWVpaWjgyWERuTzdBQT09IiwibWFjIjoiZDA1MDM1OTFiNjRjNGJkZWFhNGViOTUyODY3ZTRiZDk2Y2I2NzNlOWIxOTE1ZTNlYWZmMzA5MzEwYTQ4YTA1ZiIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6InU3UkhMTFNuT3dZQU1kN2JtYVczd3c9PSIsInZhbHVlIjoiY3JTdG1SK3VGSE96MDFKTUtVMUtxQT09IiwibWFjIjoiODU3MmE5ZjUwMGRlOTdjYjQ4YjViN2MwYjg5ZDkxYzM0ZmNjZDFmNjZlNGZjMDJiNWQ5Njg2OGE4NzUxOWU0NCIsInRhZyI6IiJ9',
            'database' => 'eyJpdiI6IjZvTGVnZjZwZmQ1cHJuVE8zVGhzTlE9PSIsInZhbHVlIjoiRXRtNW50S3puTUV5cWVBQ0JjbWR3QT09IiwibWFjIjoiZGE0MmFiNGM5YTYxZmZhYWEwZGRhM2M3MTkwYWM4NjhhN2MzZDZhOGI2ZjYxMGVjMWZjODk5ODY3MGE3YzQ3NCIsInRhZyI6IiJ9',
            'table_name' => NULL
        ],
        [
            'name' => 'Юрцентр',
            'domain' => decrypt("eyJpdiI6ImtnY0VuL3lvMWlpRjFuWmpJRmo3cFE9PSIsInZhbHVlIjoiTDRxRnl3K2taT0NNUUFJdHBseEtLN2lKUU41aFFMRFJscEhWR25ZUUhuTT0iLCJtYWMiOiI2OGJjOTViYmVlY2YzZWVlY2RiMzVmMzNmODkwZGIxY2NjYjA5ZjBmYmM2MTM4ZGM5ZTgxMDJjNjM5YjljMjhhIiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6ImppeXZSTytjclFuZFRvMHpUM2ZwVHc9PSIsInZhbHVlIjoiSEZLVUUrNkwvVWtzWDk2bGtVWjFkdz09IiwibWFjIjoiMTI4MTMyMTBjMDc1Y2YzYmUxMTNmNzNhN2U3MDRmOGQ5NDIxZTU5OTFlMzIwY2JmNTMxODRiMzA5ZjRiZGU3OSIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6Im11SHIrbFY3ZkFBSXVYaHliTkpiMHc9PSIsInZhbHVlIjoiMU9KbkxLSXdHWFppVlBXWGZBWXVCTUM3bllDUkg2Q0t4MmM2djFETE1ROD0iLCJtYWMiOiI0ZDYwODI1YjM4MWRjYWJhZTU0NjhhZmMwMTRlNjkwYTgyZWNiMWRkNWEwOGQ3ODIxMDUyZjg5M2M1NTA3MTQwIiwidGFnIjoiIn0==',
            'password' => 'eyJpdiI6IkJuU1UwdHp0RlhFUmRJdTFuRDB6cHc9PSIsInZhbHVlIjoiZkcvUVE1eXYwOGVZNklKakRVTE9XTjh3cjhDQjljVHF6ckRPRjZnbVFBbz0iLCJtYWMiOiI2ZTRlYmE4YjI0YjBlZmIxOGEzNTA3OTQ3YjU2YjM2ODNlNzQwZGIxMzMxMTZiOGM3OTAyYjVkMjUyNGU0NGQ0IiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6ImRIL0lWYUlrRjh3VE5nclVCK2RMc0E9PSIsInZhbHVlIjoiR3ZQOGVuSzJjdC9yVFVzUW00OEZ6Zz09IiwibWFjIjoiM2JhMTc2MGM1OWU3Mjg3YTg1MzBlODEyMDNmODc2MDA5YmVjYmQ4ZTBhNTMzN2RmYzk2YWVkMTBjNjdjNDNlYSIsInRhZyI6IiJ9=',
            'table_name' => NULL
        ],
        [
            'name' => 'Таг',
            'domain' => decrypt("eyJpdiI6IjB6YzlJSnV2NXZma01QUmI4dVUwWWc9PSIsInZhbHVlIjoiMEQ4L1l0ZnV6VWFTNDlsb0lJOXR6WjZzb0xiajBuRGdFTUFhWWRJSmJQQT0iLCJtYWMiOiJkYTRjNWNmZDc0NTI2NTFkZTg3ZDIyMjI4YmMzN2M2NDUyMmE0ZDRmODRhZGQwNmIzYjYwMjIwOTBjNDkzM2E1IiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6IlRBV2tNLzJwZ0VjY1RZK1o5OXUwaGc9PSIsInZhbHVlIjoiamlBMlplc0MrelFaUUJJcDhkUjhEZz09IiwibWFjIjoiMzUyMjVjMzhjZTgyMjFmYmYwMjkzNGVhOWRkMmRlMGQ0NDVjNDVhMWEwMzQ2NWJmY2I2M2U4ZTc4YjkwYzVhOSIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6Im9jekdJYnlKUUxSZU10eUNhZHVRZHc9PSIsInZhbHVlIjoiaHdIMzBiRHRXZVJCOWFHR3BIQ21iQT09IiwibWFjIjoiYmZlNWRhNTIxZTBkMmE2MzA3ZWIyOGJkNDUwYzYyOWQzZmUyMDVmNWFhNGFiNDFlYzlhY2IzNTg2OGFkMWVhMiIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6IklmaGY2Wm5DUUtFcEZnYlZEM3hhMkE9PSIsInZhbHVlIjoicktRamtWY0tjeEpXdjY1TFl5STJJUW5IdC80Mi8rYmx3OHg2UHpBRElLQT0iLCJtYWMiOiIyYzMyODQwMGU2N2NjYjc1NTc0OWZmZmI2OTUyNTA1ZGRiNWFmZTZiMTJlZjE5ZjU3ZThmNDZmYWNiZGNhZDYzIiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6ImMwWjl0ZW1xYjJYV2tSYTdiMGRGUEE9PSIsInZhbHVlIjoiMXFNQTk3dFE4QlV0THBCYTZVYWtDUT09IiwibWFjIjoiOGE1YzdjY2E0MmIyODE5ODRmNGVlZjBiMWU2OTg4OTYwY2EyNzM3MmMzYTA3ZGFiZTA4NmI1ZmJhMTljZDExMSIsInRhZyI6IiJ9',
            'table_name' => NULL
        ],
        [
            'name' => 'Бас',
            'domain' => decrypt("eyJpdiI6InVqWXI1eThkeWNFbDJ0cUg5Z3haL1E9PSIsInZhbHVlIjoiVzZncG9iMkxDcElhSlhiUWVBZDl0dHVnaWVDa1lpK0tmQ0NYWkQxQzByWT0iLCJtYWMiOiJhNmIwMGNjNzJhNmQ2OGY1YmMzNmY5MzgzYzEwMTNmN2YxYTQ3NzRkMGMxOGY4YmRjNTRjZTFiZjU3M2VkZmY4IiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6IlBBZ1BLSkJVOFJHM3dCMkl5OXZBOEE9PSIsInZhbHVlIjoicTQvL1NIQ1lVY2RranJtSndwazczQT09IiwibWFjIjoiMmYzYTY2OWVkODdhMjA2YTEzOTg5NWQ5ZGZkMjhhMDZhNmMzYTdhNDI2NWRmMjc0NWQ1ZTBlNDg2MTE4ZDliOSIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6IlMxSWp5MlB4S25KTGdUZzVwaUdQVmc9PSIsInZhbHVlIjoibVA3eWFpVW1neUxTMi8xT2dhckNqQT09IiwibWFjIjoiYTRmMjMxZDIxMTNiZTVmMDQ3ODhjZTM0MjVjNTYxMDY4NGY2ZGIxMDY0NjJjYjM5YmI4YTk1ZDYxZTQzMDhmZiIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6IkNDcnNHemYyU1l6ZnFqN1ZNS3ZXekE9PSIsInZhbHVlIjoiTHFQdzM3ampvQjBUeWpjM1c3L21TVEFVd05KdFJpVGVXMEJ0a3VneEhxUT0iLCJtYWMiOiIyMGM4MzA5NWIxOTc2OWIyYjljOWQ4YjA1YWJjNTVkOWVjZmMwMDQ3NDk2YzYyMTdhZmIwOTY0NjU1YzY4NDcxIiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6ImNwNlc3WSsyQy93WHp0RmFFV0J1TUE9PSIsInZhbHVlIjoiUGNUR2J2SGVZNFI3Uk9nS3JRVnNTVUhEcmYxZllzWXFnM3ExU3ZmL1NOUT0iLCJtYWMiOiI5ODZjZWUwOWUwYzJiMWUwNzU3MDI1MjY0ZWNlMWY0NDc0OTg1NzUyMzdhMjhjYzEwYmRkZWI1Y2Q4YTI0OTJiIiwidGFnIjoiIn0=',
            'table_name' => NULL
        ],
        [
            'name' => 'Эксперты права',
            'domain' => decrypt("eyJpdiI6Im5wa1RhU0loampWQW5Ld3U3Zk9FV0E9PSIsInZhbHVlIjoiYllsd3F1K2hQUURoQmVEaE1seFdMVk41eDVzMzN5TlF1RGZ4RkNvcWNyQUExWm9KajZQTUlMWHpiTzFVNURxZyIsIm1hYyI6ImVhMDgwZWVhYjgxNjg0OGYyNjc3YzE5NjliMjAwZDEzMTI4ZGFhZGJmMzZhNWE0ZWExMzk1NjVlOWZjZDFlOTIiLCJ0YWciOiIifQ=="),
            'host' => 'eyJpdiI6IlNrZXFKNlk5QlNsZlVXYTFXTXNlRmc9PSIsInZhbHVlIjoicHFxRjVxV2g3NSthKzZQS2FOaTBFZz09IiwibWFjIjoiZmU5M2UyZGM1NGMzNWM1OWViMTZhMTMzYTBmNzA4M2MyNWYwZTMwOTJjNWIxYWE2ODkzNzU2ZDQxZDY3ZDUyNCIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6ImtzZjQ5dmRLcTRQeWs2UWdFY2VDaUE9PSIsInZhbHVlIjoieFRMbzhlSE9qbGlHQi9rRDdsdlpkZz09IiwibWFjIjoiYjgwYzAwYTM2OTExODBmM2ZmYmU2MGJiMjg0MjlkZTkxODAzMzNjN2ZkNWJkMmUzNzBjZDM3M2U0MWEzOGMyMSIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6ImJxUTVLRTF4YS9jbEFDVGhFNVVqYWc9PSIsInZhbHVlIjoia05YNzc2QktEK1dXcVVOZkVwcEJ1cjNab3NDMnFJWE9UYXFzdGw0WFJjVT0iLCJtYWMiOiJjYzY3OGZkZWVmYjdjMWY2ZDk2OWUzMmI1NDUyZjVlOTI1ZWE0MzNkNzJhYWU0Y2Q2YzBkYzc3MTM4OWFhY2ExIiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6IkhVZUp2RFlHbFpBU25XRlc2bnJIRGc9PSIsInZhbHVlIjoiTk0wU1lHQUFoZkxzNkVpVEZWbG1Ydz09IiwibWFjIjoiZjNlOTA1NGY1NTQzNmQzNmVlMGVkZTU2M2M4NjJmYTI3YmYwZTExNDZkODAwNjU4ZWJhZjk5NDE5YzUxMmZkMCIsInRhZyI6IiJ9',
            'table_name' => NULL
        ],
        [
            'name' => 'Юрэксперты',
            'domain' => decrypt("eyJpdiI6InlWMzh3eW1wL3Uwc3JOclE4ck9kQmc9PSIsInZhbHVlIjoia1hMbi9rNDVYZGJHcnZWY2RGZGQyUFFDeUsraHhSTHBiUWlIOGRSRE9LWT0iLCJtYWMiOiIzOTdhZjhjNTJlYjBkMGNjOGE2Y2VlY2IxNjBlYjZhODExMzEwODQwYzZjNjA5MDA2ZTg3MWU1OTU4OTJiNjk2IiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6Iko5UXNxb2Z2Qkx3TytPZS9JMThwM1E9PSIsInZhbHVlIjoiRHNDdmQyRXE4Wmw4d0Riek5OZURpZz09IiwibWFjIjoiMGE4YjEwYTlhMjAzZjM4OTk5MDAzNzg4ZmIyZDk0YjgwNjAyMmVkZGQyNGFhNmU1M2NmMWRmZTY2ODkxZWYyZiIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6IlFHVS84OTBxdVJ3Tzl3Vlpxa2hZdEE9PSIsInZhbHVlIjoiT3VkRnZIN2xNUmMzOHUwRE1MVkNXdz09IiwibWFjIjoiOWEwOWUwYmEyNGNkMWViMGE0NjVjMWIxMGVjNGRjZTAwZmQ2MmE3OWVkMWM5MjRhZTQ2YjBjZTRhN2MxNjY2OCIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6Inc0MlJ0NGNBOTFSYnRUVzE0UWtZZGc9PSIsInZhbHVlIjoickNHWTYwN29VOFZZR29TbkhMTDZDc1VwencwdXdiaDBiOTBrdmI4UjU4Zz0iLCJtYWMiOiI2Y2U0OWJiZjA4ODQ4MjZjNzcyYzJkNjI3YTM1OTIwM2U3M2U1OWUzMDU4NWJjNTQ2ZjRjNjUwMTU4NjY3ZTI1IiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6IlZPNDZrWEUrRTBjblNoZzFqV3hxMEE9PSIsInZhbHVlIjoiWmtQZVRydkFuMWZTL0U0WFNxSCtaZz09IiwibWFjIjoiOTIzNDJmN2U2NTA1NDkwNzhkNzMyYTI3NDUyYmZjNDVmMjIxOTIwNmQ5MjQ0Mjg1ZTA3MTJkYTJlM2NjZjcyNSIsInRhZyI6IiJ9',
            'table_name' => NULL
        ],
        [
            'name' => 'Росюристы',
            'domain' => decrypt("eyJpdiI6InBZQkFZelMyT2d4VUQyR1dzd0c0cVE9PSIsInZhbHVlIjoic1BZQzdjSDJhKzZrVHcwUjFXNUtMTXFuRHVKMUpJQ2wzSTBPRVlXQVpIcz0iLCJtYWMiOiJlZTE2Y2NiNjY1YzBlZjZhZDE5ODRmYjdhMDQ1YTdhNDBmOWIwOGY3ZGU5ZDgwYmZhYjZjMjRjNDFhNDNiZmJhIiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6IndxZEtYZ29xVGxOQzg1amozKzdPdEE9PSIsInZhbHVlIjoicm52VFJYd0o0Mmg3M1NQcE5mTTZLZz09IiwibWFjIjoiMWZjOGMwYjA5ZTM4ZWI4NjBmZGE1YjU5NDc3M2MwNThlNDI4YjA3YmVjNDExM2U1MGY3NThmODRmMGFhNGFiZCIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6IlhLLzFwcDdyNE9HbXJqbnFMR2cwZmc9PSIsInZhbHVlIjoiSmNETGk1MUtpL1JkZWw2QVFZUFVMZz09IiwibWFjIjoiNzg4MjE2ZWY0YTJmZDZjZjlmYzE3MjVkMGRmZThlNjllY2I5YWQ4NWZiMGQyZjNkMDQ1MmE1NjE1MzUzM2NiMyIsInRhZyI6IiJ9',
            'password' => 'eyJpdiI6IlptZytkajdXSUhYSC9sSDEveXlndUE9PSIsInZhbHVlIjoiSlpMWnl1eTR4dHRwbXhDYUsydWp1SFZZT01mdXIxbmkvM0QyWm5xM0ZvUT0iLCJtYWMiOiJmMjQyZGQwZWQyNGY0YjAwZjI1MzYxMjEyM2I0ODlkZmZmNjVkMWI1MjNjNWQzZGNjMDkwOTVjYzRlMWRkMWYyIiwidGFnIjoiIn0=',
            'database' => 'eyJpdiI6Ik9VYnVabm50ckZGN1B5Tk9UanVNRmc9PSIsInZhbHVlIjoicWJSZEtlNTd2dW50MTVwTENaY0dDZz09IiwibWFjIjoiNzY5YjYyYjQxOTA5YWYwNTgzNGU5NjU1ZWFlMmRmZTg2ZjNmZTk0NzJjMjc5MTEyYmNhZTBmNTlkMTUyZjI4ZSIsInRhZyI6IiJ9',
            'table_name' => NULL
        ],
        // [
        //     'name' => 'Желтый сайт',
        //     'active' => 0,
        //     'host' => 'eyJpdiI6InVKL0xEc0x3SlVtcVY5eGpqYjVONmc9PSIsInZhbHVlIjoiNXkyS3dtMlNwdDRLMllBdStrbGtuZz09IiwibWFjIjoiMWY5ZDI3Y2FlNGI3ZTM2N2M2ZmQ2YWI1Y2U3YTJhN2JlMDhhMDNlMWUwMThlY2RhODE2NTc2YzBmM2VkMTJhMiIsInRhZyI6IiJ9',
        //     'port' => NULL,
        //     'user' => 'eyJpdiI6InBCZzgwa09QcTB5Y01SYWtkbzZvYnc9PSIsInZhbHVlIjoiWE1IQTdYUnluZmpTL05acmR6WEpUdz09IiwibWFjIjoiNzYyNjI5MmM3MDZjYTljMTQyZDAyNDZmZGZiMzhhNzI0NzcxYjUzMjAxZGY5MmQ5NTEwZTI2MzYwZGFmNjQ4MCIsInRhZyI6IiJ9',
        //     'password' => 'eyJpdiI6IkhtTmh1OWZrSFhBSEhmbXR5QzB1dXc9PSIsInZhbHVlIjoib0Q4cm5abFNYUTBJbytHQURlZFlodG5WYnZFWEpyMGErd0ozYmJhVndsYz0iLCJtYWMiOiJjZTYxYzhlMTgzZDMxMmQ3ZTM2MzUxYjM5NjRlNDQ2OTQyMGFiNjBmYjAyOTE3ZjllN2RiYTgzZTc4ZTI5ODUxIiwidGFnIjoiIn0=',
        //     'database' => 'eyJpdiI6IjI3My9hdzZpaFdmNnhFN0k4di84Y2c9PSIsInZhbHVlIjoiRHFhYkd3QTdTRW53SU5jbGhTUTB0Zz09IiwibWFjIjoiNTE4YjhiNjQ1MjVmMzI0MDMxMzNhYmExNDNhZmU5NTFhMzc2NWNmYjAyMjI1N2YwYzZiMTRjNzk2NWI4MTk1MiIsInRhZyI6IiJ9',
        //     'table_name' => NULL
        // ],
        [
            // 'name' => 'Желтый сайт - Новый',
            'name' => 'Желтый сайт',
            'domain' => decrypt("eyJpdiI6IlFIUlppeVVPTWNYZzNKUXRVYWxXRGc9PSIsInZhbHVlIjoiVk5LaXR5ZGxzencweXFPNmMyL2dCc1MzUENkb2NPREF2NkJZL0R5ejJKND0iLCJtYWMiOiI4OWYxMGUxZmNlYjUzYWE2YTFiOWIwNWI5YjY2MmRmY2EyNTc0MjE3MmVmMzMyNmQ4MGM5NGQwMzE3YmFkMjg4IiwidGFnIjoiIn0="),
            'host' => 'eyJpdiI6IkduYnVGdXNkU25xUVEyNFQ1Mm9NNUE9PSIsInZhbHVlIjoiSlcwcWZtSk55Y2JQZy8ybnB2eEpYQT09IiwibWFjIjoiMzRiM2E1ZTU1NzcxNDUwZGE0YTYyOGYxYmE2MzJlZWRmN2EwYzFiMzdmZWU2ZTk2ZGM2MTJmYzI0MTEzN2MyNCIsInRhZyI6IiJ9',
            'port' => NULL,
            'user' => 'eyJpdiI6IlhyZFVQUktoSHhMaHJFSzdDZmxjOUE9PSIsInZhbHVlIjoiRXdONlVWeXpjajlwdE05bmdxdGk1am5lYnVlcW9wbXk4NUlBRFBSUThuND0iLCJtYWMiOiJjY2Y5Yzg5MDFiMGNmNGJlMTA5ZGI5MDI5MmRlMTE4Y2ZmNGM1ZWFiNzFlMTZkMzY4MTFkNTMyNzY0YTM1NjQ0IiwidGFnIjoiIn0=',
            'password' => 'eyJpdiI6IkRCL0RpZExMWHgwYnhsTHZhbWpEZVE9PSIsInZhbHVlIjoiUk1uZUJYRWdhcGVXVlRYTEhud21XZz09IiwibWFjIjoiZDkwNDkzMWM5NTExZjU5YzBlYTc0YjA4ZDU1ZTM4ZDBlNDhlNTQ5MWU2MTYzYTVmOGI5MjljMDNkM2IzMmU3NyIsInRhZyI6IiJ9',
            'database' => 'eyJpdiI6InRYTkR5b0hUSTY2aUk1QzRGZFZ1Q2c9PSIsInZhbHVlIjoiYmJHOTNISXd5akpCWGNrWVYyQyt1dz09IiwibWFjIjoiZTljMTk5ZGRkODA3N2Y1NjBiYjI2Nzk2MmNmZjhhYTNjM2YwMmMzZmI0NTYxMmVjMjk4YTQyMjAxOTY2NmVmNCIsInRhZyI6IiJ9',
            'table_name' => NULL
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->databases as $db) {
            SettingsQueuesDatabase::create($db);
        }
    }
}
