<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= esc($title); ?> - <?= trans("admin"); ?>&nbsp;<?= esc($baseSettings->site_title); ?></title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="shortcut icon" type="image/png" href="<?= getFavicon(); ?>"/>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-v3/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/AdminLTE.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/_all-skins.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/custom.css'); ?>">
    <style>.alert button, .alert i{
            display: none;
        }.alert h4{ font-size: 14px; margin-bottom: 0;} </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <a href="<?= adminUrl('login'); ?>"><b><?= esc($baseSettings->application_name); ?></b>&nbsp;<?= trans("panel"); ?></a>
    </div>
    <div class="login-box-body">
        <h4 class="login-box-msg"><?= trans("login"); ?></h4>
        <?= view('admin/includes/_messages'); ?>

        <?php if (!empty($sessionStorageError)): ?>
            <div class="alert alert-danger">پوشه writable/session وجود ندارد یا PHP اجازه نوشتن در آن را ندارد.</div>
        <?php elseif ($sessionError === 'cookie_missing'): ?>
            <div class="alert alert-danger">رمز صحیح بود، اما Cookie نشست در درخواست بعدی به سرور نرسید. Cookieهای دامنه را پاک و دوباره تلاش کنید.</div>
        <?php elseif ($sessionError === 'data_missing'): ?>
            <div class="alert alert-danger">
                Cookie نشست رسید، اما اطلاعات ورود داخل فایل Session ذخیره نشده است.
                <?php if (inputGet('ac') === '0'): ?>
                    <br><small>کوکی پشتیبان ورود (AuthCookie) هم به مرورگر نرسیده یا با پیشوند اشتباه ذخیره شده است. بعد از آپلود آخرین اصلاح، Cookieهای دامنه را پاک کنید و دوباره وارد شوید.</small>
                <?php elseif (inputGet('ac') === '1'): ?>
                    <br><small>کوکی پشتیبان رسید ولی بازیابی نشست انجام نشد. یک‌بار Cookieها را پاک کنید و دوباره تست بگیرید.</small>
                <?php endif; ?>
            </div>
        <?php elseif ($sessionError === 'write_failed'): ?>
            <div class="alert alert-danger">فایل Session ایجاد شد، اما PHP نتوانست اطلاعات ورود را داخل همان فایل بنویسد.</div>
        <?php elseif ($sessionError === 'auth_mismatch'): ?>
            <div class="alert alert-danger">اطلاعات Session ذخیره شد، اما با رکورد فعلی کاربر در دیتابیس تطبیق ندارد.</div>
        <?php elseif (inputGet('permission_error') == 1): ?>
            <div class="alert alert-danger">ورود انجام شد، اما نقش این کاربر مجوز admin_panel ندارد.</div>
        <?php endif; ?>
        <form action="<?= adminUrl('login-post'); ?>" method="post">
            <?= csrf_field(); ?>
            <div class="form-group has-feedback">
                <input type="email" name="email" class="form-control form-input" placeholder="<?= trans("email"); ?>" value="<?= old('email'); ?>" required>
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control form-input" placeholder="<?= trans("password"); ?>" value="<?= old('password'); ?>" required>
                <span class=" glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-sm-8 col-xs-12"></div>
                <div class="col-sm-4 col-xs-12">
                    <button type="submit" class="btn btn-primary btn-block btn-flat"><?= trans("login"); ?></button>
                </div>
            </div>
        </form>
    </div>
    <div class="text-center m-t-15">
        <a class="btn btn-md" href="<?= langBaseUrl(); ?>"><?= trans("btn_goto_home"); ?></a>
    </div>
</div>
</body>
</html>
