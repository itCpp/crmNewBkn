<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Crypt;

class Controller extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	/**
	 * Логирование изменения данных
	 * 
	 * @param \Illuminate\Http\Request $request
	 * @param $model Экземпляр затрагиваемой модели
	 * @return \App\Models\Log
	 */
	public static function logData($request, $model) {

		return \App\Models\Log::log($request, $model);

	}

    /**
	 * Метод проверки и преобразования номера телефона
	 *
	 * @param string        $str Номер телефона в любом формте
	 * @param bool|int      $type Тип преобразования
	 * -- false - 79001002030
     * -- 1 - 79001002030
	 * -- 2 - +7 (900) 100-20-30
	 * -- 3 - 89001002030
	 * -- 4 - +79001002030
	 * -- 5 - +7 (***) ***-**-30
	 * -- 6 - +7900*****30
	 * -- 7 - 8 (900) 100-20-30
     * 
	 * @return false|string  Вернет false в случае, если номер телефона не прошел валидацию
	 */
	public static function checkPhone($str, $type = false) {

		$num = preg_replace("/[^0-9]/", '', $str);
		$strlen = strlen($num); // Длина номера

		// Добавление 7 в начало номера, если его длина меньше 11 цифр
		if ($strlen != 11 AND $strlen < 11)
			$num = "7" . $num;

		// Замена первой 8 на 7
		if ($strlen == 11)
			$num = "7" . substr($num, 1);

		// Проверка длины номера
		if (strlen($num) != 11)
			return false;

        // Возврат в формате 79001002030
		if ($type === false OR $type == 1)
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
     * Шифрование всех ключей массива
     * 
     * @param array|object $data
     * @return array
     */
    public static function encrypt($data)
    {

        if (!is_array($data) AND !is_object($data))
            return Crypt::encryptString($data);

        $response = [];

        foreach ($data as $key => $row) {

            $response[$key] = (is_array($row) OR is_object($row))
                ? Controller::encrypt($row)
                : Crypt::encryptString($row);

        }

        return $response;

    }

    /**
     * Расшифровка всех ключей массива
     * 
     * @param array|object $data
	 * @param \Illuminate\Encryption\Encrypter|null $crypt
     * @return array
     */
    public static function decrypt($data, $crypt = null)
    {

        if (!is_array($data) AND !is_object($data))
            return ($crypt !== null
				? $crypt->decryptString($data)
				: Crypt::decryptString($data)
			);

        $response = [];

        foreach ($data as $key => $row) {

			try {
				$response[$key] = (is_array($row) OR is_object($row))
					? Controller::decrypt($row, $crypt)
					: ($crypt !== null
						? $crypt->decryptString($row)
						: Crypt::decryptString($row)
					);
			}
			catch (\Illuminate\Contracts\Encryption\DecryptException) {
				$response[$key] = $row;
			}
                
        }

        return $response;

    }

}
