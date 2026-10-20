<?php

namespace App\Controllers;

use App\Models\AuthModel;
use App\Models\AwsModel;
use App\Models\FileModel;
use CodeIgniter\Controller;
use Config\Globals;

class CommonController extends Controller
{

    protected $session;
    protected $generalSettings;
    protected $settings;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->session = \Config\Services::session();
        $this->generalSettings = Globals::$generalSettings;
        $this->settings = Globals::$settings;
    }

    /**
     * Admin Login
     */
    public function adminLogin()
    {
        if (authCheck()) {
            return redirect()->to(adminUrl());
        }
        $data['title'] = trans("login");
        $data['description'] = trans("login") . " - " . $this->settings->site_title;
        $data['keywords'] = trans("login") . ', ' . $this->settings->application_name;
        $data['generalSettings'] = $this->generalSettings;
        $data['baseSettings'] = $this->settings;
        $data['sessionError'] = inputGet('session_error');
        $data['sessionStorageError'] = inputGet('session_storage_error') == 1;
        echo view('admin/login', $data);
    }


    /**
     * Admin Login Post
     */
    public function adminLoginpost()
    {
        $val = \Config\Services::validation();
        $val->setRule('email', trans("email"), 'required|max_length[200]');
        $val->setRule('password', trans("password"), 'required|max_length[200]');
        if (!$this->validate(getValRules($val))) {
            $this->session->setFlashdata('errors', $val->getErrors());
            return redirect()->back()->withInput();
        } else {
            $sessionPath = WRITEPATH . 'session';
            if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
                return redirect()->to(adminUrl('login') . '?session_storage_error=1');
            }
            $authModel = new AuthModel();
            $user = $authModel->getUserByEmail(inputPost('email'));
            if (!empty($user) && $user->role != 'admin' && $this->generalSettings->maintenance_mode_status == 1) {
                $this->session->setFlashdata('error', "Site under construction! Please try again later.");
                return redirect()->to(adminUrl('login'));
            }
            $loginResult = $authModel->login();
            if ($loginResult === 'invalid_account') {
                $this->session->setFlashdata('error', 'این حساب با شناسه نامعتبر (0) در دیتابیس ثبت شده و قابل ورود نیست. ستون AUTO_INCREMENT جدول users را اصلاح کنید و کاربر را دوباره بسازید.');
                return redirect()->to(adminUrl('login'))->withCookies();
            }
            if ($loginResult === 'banned') {
                $this->session->setFlashdata('error', trans("message_ban_error"));
                return redirect()->to(adminUrl('login'))->withCookies();
            }
            if ($loginResult === 'success') {
                $role = $authModel->getRoleByKey($user->role);
                $hasAdminPanelAccess = $user->role == 'admin' || (!empty($role) && $role->admin_panel == 1);
                if (!$hasAdminPanelAccess) {
                    $authModel->logout();
                    $this->session->setFlashdata('error', 'این حساب اجازه ورود به پنل مدیریت را ندارد. نقش یا مجوز پنل مدیریت را بررسی کنید.');
                    return redirect()->to(adminUrl('login'))->withCookies();
                }
                // Flush session + require AuthCookie before leaving this request.
                if (!persistLoginSession()) {
                    return redirect()->to(adminUrl('login') . '?session_error=write_failed')->withCookies();
                }

                // IMPORTANT: do NOT use HTTP 302 here.
                // RedirectResponse often drops Set-Cookie headers, so the browser
                // arrives at login-check with the session cookie but without
                // AuthCookie / fresh session payload -> data_missing.
                // A 200 interstitial lets cookies settle, then navigates.
                $nextUrl = adminUrl('login-check');
                $response = $this->response->setStatusCode(200)->setBody(
                    '<!DOCTYPE html><html lang="fa"><head><meta charset="utf-8">'
                    . '<meta http-equiv="refresh" content="0;url=' . esc($nextUrl, 'attr') . '">'
                    . '<title>ورود...</title></head><body>'
                    . '<script>window.location.replace(' . json_encode($nextUrl) . ');</script>'
                    . '<p style="font-family:tahoma,sans-serif;text-align:center;margin-top:40px;">در حال ورود...</p>'
                    . '<p style="font-family:tahoma,sans-serif;text-align:center;"><a href="' . esc($nextUrl) . '">ادامه</a></p>'
                    . '</body></html>'
                );
                \App\Libraries\AuthCookie::applyToResponse($response);
                return $response;
            } else {
                $this->session->setFlashdata('error', trans("login_error"));
                return redirect()->to(adminUrl('login'))->withCookies();
            }
        }
    }

    /**
     * Verify that the login session survived the redirect.
     */
    public function adminLoginCheck()
    {
        // FileHandler may have dropped vr_ses_*; recover from signed auth cookie.
        if (!authCheck()) {
            $user = \App\Libraries\AuthCookie::getUser(\Config\Database::connect());
            if (!empty($user)) {
                $authModel = new AuthModel();
                $authModel->loginUser($user);
                \Config\Globals::$authCheck = true;
                \Config\Globals::$authUser = $user;
            }
        }

        if (!authCheck()) {
            $sessionCookieName = config('App')->sessionCookieName;
            $hasSessionCookie = !empty($_COOKIE[$sessionCookieName]) || !empty($_COOKIE[session_name()]);
            $hasAuthKeys = !empty($this->session->get('vr_ses_id'))
                && $this->session->get('vr_ses_role') !== null
                && $this->session->get('vr_ses_role') !== ''
                && !empty($this->session->get('vr_ses_pass'));
            $hasAuthCookie = \App\Libraries\AuthCookie::getUser(\Config\Database::connect()) !== null
                || !empty($_COOKIE[\App\Libraries\AuthCookie::NAME])
                || !empty($_COOKIE['vr' . \App\Libraries\AuthCookie::NAME])
                || !empty($_COOKIE['vr_' . \App\Libraries\AuthCookie::NAME]);

            if (!$hasSessionCookie) {
                $reason = 'cookie_missing';
            } elseif (!$hasAuthKeys) {
                // Distinguish "session empty and auth cookie also missing" for support.
                $reason = $hasAuthCookie ? 'data_missing' : 'data_missing';
            } else {
                $reason = 'auth_mismatch';
            }
            $redirect = redirect()->to(
                adminUrl('login') . '?session_error=' . $reason . '&ac=' . ($hasAuthCookie ? '1' : '0')
            );
            return $redirect->withCookies();
        }
        if (!checkUserPermission('admin_panel')) {
            $authModel = new AuthModel();
            $authModel->logout();
            return redirect()->to(adminUrl('login') . '?permission_error=1')->withCookies();
        }
        return redirect()->to(adminUrl())->withCookies();
    }

    /**
     * Switch Dark Mode
     */
    public function switchDarkMode()
    {
        $mode = inputPost('theme_mode');
        if ($mode == 'light' || $mode == 'dark') {
            helperSetCookie('theme_mode', $mode);
        }
        redirectToBackURL();
    }

    /**
     * Download File
     */
    public function downloadFile()
    {
        $fileType = inputPost('file_type');
        $id = inputPost('id');
        $path = '';
        $name = '';
        $storage = 'local';
        $fileModel = new FileModel();
        if ($fileType == 'file') {
            $row = $fileModel->getFile($id);
            if (!empty($row)) {
                $path = FCPATH . $row->file_path;
                $name = $row->file_name;
                $storage = $row->storage;
            }
        }
        if ($fileType == 'audio') {
            $row = $fileModel->getAudio($id);
            if (!empty($row)) {
                $path = FCPATH . $row->audio_path;
                $name = $row->audio_name;
                $storage = $row->storage;
            }
        }
        if ($fileType == 'sitemap') {
            $fileName = inputPost('file_name');
            $security = \Config\Services::security();
            $fileName = $security->sanitizeFilename($fileName);
            if (file_exists(FCPATH . $fileName)) {
                return $this->response->download(FCPATH . $fileName, null)->setFileName($fileName);
            }
        }
        $response = \Config\Services::response();
        if ($storage == 'aws_s3') {
            $awsModel = new AwsModel();
            $awsModel->downloadFile($path);
        } else {
            if (file_exists($path)) {
                if (!empty($name)) {
                    return $this->response->download($path, null)->setFileName($name);
                }
                return $this->response->download($path, null);
            }
        }
        return redirect()->back();
    }

    /**
     * Logout
     */
    public function logout()
    {
        $model = new AuthModel();
        $model->logout();
        return redirect()->back();
    }

}
