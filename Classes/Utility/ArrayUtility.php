<?php

declare(strict_types=1);
namespace In2code\Migration\Utility;

class ArrayUtility
{
    public static function intArray(array $array): array
    {
        foreach ($array as &$key) {
            $key = (int)$key;
        }
        return $array;
    }
}
