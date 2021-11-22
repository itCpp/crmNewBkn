<?php

namespace App\Http\Controllers\Gates;

/**
 * Декодирование и кодирование текста сообщения, полученного со шлюза
 * 
 * Принцип работы кодирования и декодирвоания взят из объекта JS, найденного
 * в исходниках шлюза `Yeastar`. Автором библиотеки является haitao.tu,
 * email: tuhaitao@foxmail.com, дата создания объявлена 2010-04-26
 */
class GateBase64
{
    /**
     * Кодирование строки
     * 
     * @param string $message
     * @return string
     */
    public function encode($message = "")
    {
        $base64 = base64_encode($message);

        $hash = "";

        for ($i = 0; mb_strlen($base64) > $i; $i++) {
            $charCodeAt = $this->charCodeAt($base64[$i]) + 2;
            $hash .= chr($charCodeAt);
        }

        return rawurldecode($hash);
    }

    /**
     * Декодирование строки
     * 
     * @param string $hash
     * @return string
     */
    public function decode($hash = "")
    {
        $hash = rawurldecode($hash);

        $input = "";

        for ($i = 0; mb_strlen($hash) > $i; $i++) {
            $charCodeAt = $this->charCodeAt($hash[$i]) - 2;
            $input .= chr($charCodeAt);
        }

        return base64_decode($input);
    }

    /**
     * Метод возвращает числовое значение Юникода для символа по указанному индексу
     * (за исключением кодовых точек Юникода, больших 0x10000).
     * 
     * Является аналогом метода JS charCodeAt()
     * @see https://developer.mozilla.org/ru/docs/Web/JavaScript/Reference/Global_Objects/String/charCodeAt
     * 
     * @param string $str Строка кодирования
     * @param int $index Начало строки
     */
    public function charCodeAt($str, $index = 0)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');

        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }
}
