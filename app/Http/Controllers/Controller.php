<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\UsersViewPart;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Crypt;

class Controller extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	/** Ключ обработки номера телефона для полного вывода */
	const KEY_PHONE_SHOW = 3;

	/** Ключ обработки номера телефона для скрытого вывода */
	const KEY_PHONE_HIDDEN = 6;

	/**
	 * Логирование изменения данных
	 * 
	 * @param \Illuminate\Http\Request $request
	 * @param mixed $model Экземпляр затрагиваемой модели
	 * @param boolean $crypt Необходимо зашифровать данные
	 * @return \App\Models\Log
	 */
	public static function logData($request, $model, $crypt = false)
	{
		try {
			return Log::log($request, $model, $crypt);
		} catch (Exception $e) {
			return new Log;
		}
	}

	/**
	 * Метод проверки и преобразования номера телефона
	 *
	 * @param string        $str Номер телефона в любом формте
	 * @param bool|int      $type Тип преобразования
	 * - false - 79001002030
	 * - 1 - 79001002030
	 * - 2 - +7 (900) 100-20-30
	 * - 3 - 89001002030
	 * - 4 - +79001002030
	 * - 5 - +7 (***) ***-**-30
	 * - 6 - +7900*****30
	 * - 7 - 8 (900) 100-20-30
	 * 
	 * @return false|string  Вернет false в случае, если номер телефона не прошел валидацию
	 */
	public static function checkPhone($str, $type = false)
	{
		$num = preg_replace("/[^0-9]/", '', $str);
		$strlen = strlen($num); // Длина номера

		// Добавление 7 в начало номера, если его длина меньше 11 цифр
		if ($strlen != 11 and $strlen < 11)
			$num = "7" . $num;

		// Замена первой 8 на 7
		if ($strlen == 11)
			$num = "7" . substr($num, 1);

		// Проверка длины номера
		if (strlen($num) != 11)
			return false;

		// Возврат в формате 79001002030
		if ($type === false or $type == 1)
			return $num;

		// Возврат в формате +7 (900) 100-20-30
		if ($type === 2)
			return "+7 (" . substr($num, 1, 3) . ") " . substr($num, 4, 3) . "-" . substr($num, 7, 2) . "-" . substr($num, 9, 2);

		// Возврат в формате 89001002030
		if ($type === 3)
			return "8" . substr($num, 1);

		// Возврат в формате +79001002030
		if ($type === 4)
			return "+" . $num;

		// Возврат в формате +7 (***) ***-**-30
		if ($type === 5)
			return "+7 (***) ***-**-" . substr($num, 9, 2);

		// Возврат в формате +7900*****30
		if ($type == 6)
			return "+7********" . substr($num, 9, 2);

		// Возврат в формате 8 (900) 100-20-30
		if ($type == 7)
			return "8 (" . substr($num, 1, 3) . ") " . substr($num, 4, 3) . "-" . substr($num, 7, 2) . "-" . substr($num, 9, 2);

		// Возврат в формате 79001002030
		return $num;
	}

	/**
	 * Вывод номера телефона с его модификацией
	 * 
	 * @param string|int $phone
	 * @param boolean $permit Парво на вывод полного номера
	 * @param null|bool|int $key_show Ключ преобразования номера для открытого вывода
	 * @param null|bool|int $key_hidden Ключ преобразования номера для скрытого вывода
	 * @return false|string
	 */
	public static function displayPhoneNumber($phone, $permit = false, $key_show = null, $key_hidden = null)
	{
		$checked = self::checkPhone(
			$phone,
			$permit
				? ($key_show ?: self::KEY_PHONE_SHOW)
				: ($key_hidden ?: self::KEY_PHONE_HIDDEN)
		);

		return $checked ?: $phone;
	}

	/**
	 * Получение хэша номера телефона клиента
	 * 
	 * @param string $phone
	 * @return string|int|null
	 */
	public static function hashPhone($phone)
	{
		if (!$check = self::checkPhone($phone))
			return null;

		return md5($check . env('APP_KEY'));
	}

	/**
	 * Шифрование всех ключей массива
	 * 
	 * @param array|object|string|null $data
	 * @return array|string|null
	 */
	public static function encrypt($data)
	{
		if ($data == null or $data == "")
			return $data;

		if (!in_array(gettype($data), ['array', 'object']))
			return Crypt::encryptString($data);

		$response = [];

		foreach ($data as $key => $row) {
			$response[$key] = self::encrypt($row);
		}

		return $response;
	}

	/**
	 * Расшифровка всех ключей массива
	 * 
	 * @param array|object|string|null $data
	 * @param \Illuminate\Encryption\Encrypter|null $crypt
	 * @return array|string|null
	 */
	public static function decrypt($data, $crypt = null)
	{
		if ($data == null or $data == "")
			return $data;

		if (!in_array(gettype($data), ['array', 'object'])) {
			try {
				return $crypt !== null
					? $crypt->decryptString($data)
					: Crypt::decryptString($data);
			} catch (\Illuminate\Contracts\Encryption\DecryptException) {
				return $data;
			}
		}

		$response = [];

		foreach ($data as $key => $row) {
			$response[$key] = self::decrypt($row, $crypt);
		}

		return $response;
	}

	/**
	 * Шифрование данных с учетом типа переменной
	 * 
	 * @param array|object|string|null $data
	 * @return array|object|string|null
	 */
	public static function encryptSetType($data)
	{
		$type = gettype($data);
		$response = self::encrypt($data);

		settype($response, $type);

		return $response;
	}

	/**
	 * Расшифровка данных с учетом типа переменной
	 * 
	 * @param array|object|string|null $data
	 * @param \Illuminate\Encryption\Encrypter|null $crypt
	 * @return array|object|string|null
	 */
	public static function decryptSetType($data, $crypt = null)
	{
		$type = gettype($data);
		$response = self::decrypt($data, $crypt);

		settype($response, $type);

		return $response;
	}

	/**
	 * Проверяет, является ли массив простым списком
	 * 
	 * @param array
	 * @return bool
	 */
	public static function is_array_list(array $array): bool
	{
		$check_key = 0;

		foreach ($array as $key => $value) {
			if ($key !== $check_key)
				return false;

			$check_key++;
		}

		return true;
	}

	/**
	 * Преобразует строку в массив по символу разделения
	 * 
	 * @param string $key
	 * @param string $separator
	 * @return array
	 */
	public static function envExplode($key, $separator = ",")
	{
		$string = env($key, "");

		foreach (explode($separator, $string) as $row) {
			$array[] = trim($row);
		}

		return $array ?? [];
	}

	/**
	 * Обновляет время посещения раздела
	 * 
	 * @param  string $part
	 * @return null|\App\Models\UsersViewPart
	 */
	public static function setLastTimeViewPart($part)
	{
		if (!$user_id = optional(request()->user())->id or !(bool) $part)
			return null;

		$view = UsersViewPart::firstOrNew([
			'user_id' => $user_id,
			'part_name' => $part
		]);

		$view->view_at = now();
		$view->save();

		return $view;
	}
}
