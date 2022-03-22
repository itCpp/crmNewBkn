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

[/documentation/api.md](Документация API)

## Cron
Для выполнения задач необходимо активировать следующие процессы

```sh
# Проверка входящих СМС на шлюзах
* 9-21 * * * php /var/www/<API>/artisan sms:incomings >> /var/www/.logs/call.sms.incomings.log 2>&1

# Проверка очереди заявок
* * * * * php /var/www/<API>/artisan requests:getfromsite --while --sleep=10 >> /var/www/.logs/requests.queues.log 2>&1

# Перешифровка данных событий с использованием внутреннего ключа шифрования
* * * * * php /var/www/<API>/artisan events:recrypt

# Запись истории рейтинга колл-центра
55 23 * * * php /var/www/<API>/artisan rating:write
```
Пути поменять при необходимости

