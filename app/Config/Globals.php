<?php

namespace Config;

use App\Models\PostModel;
use CodeIgniter\Config\BaseConfig;
use \App\Models\AuthModel;
use App\Libraries\AuthCookie;

class Globals extends BaseConfig
{
    private static $db = null;
    public static $themes = array();
    public static $generalSettings = array();
    public static $settings = array();
    public static $customRoutes = array();
    public static $languages = array();
    public static $defaultLang = array();
    public static $languageTranslations = array();
    public static $rolesPermissions = array();
    public static $activeLang = array();
    public static $langBaseUrl = "";
    public static $authCheck = false;
    public static $authUser = null;

    public static function setGlobals()
    {
        self::$db = \Config\Database::connect();
        $session = \Config\Services::session();
        //set themes
        self::$themes = self::$db->table('themes')->get()->getResult();
        //set general settings
        self::$generalSettings = self::$db->table('general_settings')->where('id', 1)->get()->getRow();
        //set routes
        self::$customRoutes = self::$db->table('routes')->get()->getRow();
        //set languages
        self::$languages = self::$db->table('languages')->where('status', 1)->get()->getResult();
        //set roles permissions
        self::$rolesPermissions = self::$db->table('roles_permissions')->get()->getResult();
        //set timezone
        if (!empty(self::$generalSettings->timezone)) {
            date_default_timezone_set(self::$generalSettings->timezone);
        }
        //set active language
        self::$defaultLang = self::$db->table('languages')->where('id', self::$generalSettings->site_lang)->get()->getRow();
        if (empty(self::$defaultLang)) {
            self::$defaultLang = self::$db->table('languages')->get()->getFirstRow();
        }
        $langSegment = getSegmentValue(1);
        $langId = null;
        if (!empty(self::$languages)) {
            foreach (self::$languages as $lang) {
                if ($langSegment == $lang->short_form) {
                    $langId = $lang->id;
                    break;
                }
            }
        }

        if (empty($langId)) {
            $langId = self::$defaultLang->id;
        }
        self::setActiveLanguage($langId);
        if (empty(self::$activeLang)) {
            self::$activeLang = self::$defaultLang;
        }
        //set language base URL
        self::$langBaseUrl = base_url(self::$activeLang->short_form);
        if (self::$activeLang->id == self::$defaultLang->id) {
            self::$langBaseUrl = base_url();
        }
        //set settings
        self::$settings = self::$db->table('settings')->where('lang_id', self::$activeLang->id)->get()->getRow();

        // Avoid rewriting the session file on every request when nothing changed.
        // Extra writes race with login persistence on CI 4.2 FileHandler / PHP 8.2.
        if ((string) $session->get('activeLangId') !== (string) self::$activeLang->id) {
            $session->set('activeLangId', self::$activeLang->id);
        }
        //authentication
        $sesId = $session->get('vr_ses_id');
        $sesRole = $session->get('vr_ses_role');
        $sesPass = $session->get('vr_ses_pass');
        if (!empty($sesId) && $sesRole !== null && $sesRole !== '' && !empty($sesPass)) {
            $user = self::$db->table('users')->where('id', cleanNumber($sesId))->get()->getRow();
            if (!empty($user) && (int) $user->status === 1 && md5($user->password ?? '') == $sesPass) {
                self::$authCheck = true;
                self::$authUser = $user;
            }
        }
        // CI 4.2 FileHandler can lose session data on PHP 8.2. Recover the
        // authenticated user from a short-lived, signed, host-only cookie.
        if (!self::$authCheck) {
            $user = AuthCookie::getUser(self::$db);
            if (!empty($user)) {
                self::$authCheck = true;
                self::$authUser = $user;
                $authData = [
                    'vr_ses_id' => $user->id,
                    'vr_ses_role' => $user->role,
                    'vr_ses_pass' => md5($user->password ?? '')
                ];
                $session->set($authData);
                foreach ($authData as $key => $value) {
                    $_SESSION[$key] = $value;
                }
            }
        }
    }

    public static function setActiveLanguage($langId)
    {
        if (!empty(self::$languages)) {
            foreach (self::$languages as $lang) {
                if ($langId == $lang->id) {
                    self::$activeLang = $lang;
                    //set language translations
                    self::$languageTranslations = self::$db->table('language_translations')->where('lang_id', self::$activeLang->id)->get()->getResult();
                    $arrayTranslations = array();
                    if (!empty(self::$languageTranslations)) {
                        foreach (self::$languageTranslations as $item) {
                            $arrayTranslations[$item->label] = $item->translation;
                        }
                    }
                    self::$languageTranslations = $arrayTranslations;
                    break;
                }
            }
        }
    }

    public static function updateLangBaseURL($shortForm)
    {
        self::$langBaseUrl = base_url($shortForm);
    }
}

Globals::setGlobals();
