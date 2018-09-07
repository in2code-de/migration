<?php

namespace In2code\Migration\Utility;

class FrontendUserUtility
{
    /**
     * @return array|false
     */
    public static function getCurrentFrontendUserRecord()
    {
        if (self::isLoggedIn()) {
            return $GLOBALS['TSFE']->fe_user->user;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        if ($GLOBALS['TSFE']->loginUser) {
            return true;
        }

        return false;
    }

    /**
     * @param $extbaseType
     * @return bool
     */
    public static function isCurrentUserExtbaseType($extbaseType)
    {
        if (self::isLoggedIn()) {
            $userRecord = self::getCurrentFrontendUserRecord();
            
            if ($userRecord['tx_extbase_type'] === $extbaseType) {
                return true;
            }
        }

        return false;
    }
}
