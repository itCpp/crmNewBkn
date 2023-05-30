# Новая ЦРМ

## Перенос
Для переноса всех данных используй команду 
```sh
php artisan createcrm --users --requests --cdr
```
Бутут перенесены все сотрудники, заявки и история звонков

После основного переноса данных и готовности к запуску новой ЦРМ, поменять значение ключа `NEW_CRM_OFF` в `.env` файле на `false`

После переноса основных данных необходимо выполнить команду для переноса истории изменений в заявках
```sh
php artisan old:requestshistory
```
Данный процесс переноса может длиться пару дней, а то и больше

## API

[Документация API](/documentation/api.md)

## Cron
Для выполнения задач необходимо активировать следующие процессы

```sh
# Проверка входящих СМС на шлюзах
* 9-21 * * * php /<DOCUMENT_ROOT>/artisan sms:incomings >> /var/www/.logs/call.sms.incomings.log 2>&1

# Проверка очереди заявок
* * * * * php /<DOCUMENT_ROOT>/artisan requests:getfromsite --while --sleep=10 >> /var/www/.logs/requests.queues.log 2>&1

# Перешифровка данных событий с использованием внутреннего ключа шифрования
* * * * * php /<DOCUMENT_ROOT>/artisan events:recrypt

# Запись истории рейтинга колл-центра
55 23 * * * php /<DOCUMENT_ROOT>/artisan rating:write

# Завершает все активные сессии
0 23 * * * php /<DOCUMENT_ROOT>/artisan users:endsessions
```
Пути поменять при необходимости

