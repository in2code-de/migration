<?php
namespace In2code\Migration\Utility;

/**
 * Class ArrayUtility
 */
class ArrayUtility
{
    /**
     * @param array $array
     * @return int[]
     */
    public static function intArray(array $array): array
    {
        foreach ($array as &$key) {
            $key = (int)$key;
        }
        return $array;
    }
}
