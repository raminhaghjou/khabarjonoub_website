<!DOCTYPE html>
<html lang="<?= $activeLang->short_form ?>">
<head>
<?php
$seoBrand = trim((string) ($baseSettings->application_name ?? ''));
$seoSiteTitle = trim((string) ($baseSettings->site_title ?? ''));
$seoTitle = trim((string) ($title ?? ''));
if ($seoSiteTitle !== '' && mb_stripos($seoTitle, $seoSiteTitle) === false) {
    $seoTitle .= ' - ' . $seoSiteTitle;
}
if (mb_strlen($seoTitle) > 65) {
    $seoTitle = rtrim(mb_substr($seoTitle, 0, 62)) . '...';
}
$seoDescription = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($description ?? ''))));
$seoKeywords = trim((string) ($keywords ?? ''));
$keywordSeed = $seoBrand !== '' ? $seoBrand : $seoSiteTitle;
if ($keywordSeed !== '' && mb_stripos($seoDescription, $keywordSeed) === false) {
    $seoDescription = $keywordSeed . '، ' . $seoDescription;
}
if ($keywordSeed !== '' && ($seoKeywords === '' || mb_stripos($seoKeywords, $keywordSeed) === false)) {
    $seoKeywords = $seoKeywords === '' ? $keywordSeed : ($keywordSeed . ', ' . $seoKeywords);
}
if (mb_strlen($seoDescription) > 160) {
    $seoDescription = rtrim(mb_substr($seoDescription, 0, 157)) . '...';
}
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= escSls($seoTitle); ?></title>
<meta name="description" content="<?= escSls($seoDescription); ?>"/>
<meta name="keywords" content="<?= escSls($seoKeywords); ?>"/>
<meta name="author" content="<?= escSls($baseSettings->application_name); ?>"/>
<meta property="og:locale" content="en_US"/>
<meta property="og:site_name" content="<?= escSls($baseSettings->application_name); ?>"/>
<?php if (isset($postType)): ?>
<meta property="og:type" content="<?= $ogType; ?>"/>
<meta property="og:title" content="<?= $ogTitle; ?>"/>
<meta property="og:description" content="<?= escSls($seoDescription); ?>"/>
<meta property="og:url" content="<?= currentFullURL(); ?>"/>
<meta property="og:image" content="<?= $ogImage; ?>"/>
<meta property="og:image:width" content="<?= $ogWidth; ?>"/>
<meta property="og:image:height" content="<?= $ogHeight; ?>"/>
<meta property="article:author" content="<?= $ogAuthor; ?>"/>
<meta property="fb:app_id" content="<?= $generalSettings->facebook_app_id; ?>"/>
<?php foreach ($ogTags as $tag): ?>
<meta property="article:tag" content="<?= escSls($tag->tag); ?>"/>
<?php endforeach; ?>
<meta property="article:published_time" content="<?= $ogPublishedTime; ?>"/>
<meta property="article:modified_time" content="<?= $ogModifiedTime; ?>"/>
<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:site" content="@<?= escSls($baseSettings->application_name); ?>"/>
<meta name="twitter:creator" content="@<?= escSls($ogCreator); ?>"/>
<meta name="twitter:title" content="<?= escSls($post->title); ?>"/>
<meta name="twitter:description" content="<?= escSls($seoDescription); ?>"/>
<meta name="twitter:image" content="<?= $ogImage; ?>"/>
<?php else: ?>
<meta property="og:image" content="<?= getLogo(); ?>"/>
<meta property="og:image:width" content="240"/>
<meta property="og:image:height" content="90"/>
<meta property="og:type" content="website"/>
<meta property="og:title" content="<?= escSls($seoTitle); ?>"/>
<meta property="og:description" content="<?= escSls($seoDescription); ?>"/>
<meta property="og:url" content="<?= currentFullURL(); ?>"/>
<meta property="fb:app_id" content="<?= $generalSettings->facebook_app_id; ?>"/>
<meta name="twitter:card" content="summary_large_image"/>
<meta name="twitter:site" content="@<?= escSls($baseSettings->application_name); ?>"/>
<meta name="twitter:title" content="<?= escSls($seoTitle); ?>"/>
<meta name="twitter:description" content="<?= escSls($seoDescription); ?>"/>
<?php endif;
if ($generalSettings->pwa_status == 1): ?>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="<?= escSls($baseSettings->application_name); ?>">
<meta name="msapplication-TileImage" content="<?= base_url('assets/img/pwa/144x144.png'); ?>">
<meta name="msapplication-TileColor" content="#2F3BA2">
<meta name="msapplication-TileColor" content="#ffc40d">
<meta name="theme-color" content="#ffffff">
<link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png'); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png'); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png'); ?>">
<link rel="manifest" href="<?= base_url('site.webmanifest'); ?>">
<link rel="mask-icon" href="<?= base_url('safari-pinned-tab.svg'); ?>" color="#5bbad5">

<link rel="manifest" href="<?= base_url('manifest.json'); ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/img/pwa/144x144.png'); ?>">
<?php endif; ?>
<link rel="shortcut icon" type="image/png" href="<?= getFavicon(); ?>"/>
<link rel="canonical" href="<?= currentFullURL(); ?>"/>
<link rel="alternate" href="<?= currentFullURL(); ?>" hreflang="<?= $activeLang->language_code; ?>"/>
<?php if (!empty($generalSettings->show_rss) && $generalSettings->show_rss == 1): ?>
<link rel="alternate" type="application/rss+xml" title="<?= escSls($baseSettings->site_title); ?> - RSS" href="<?= langBaseUrl('rss/latest-posts'); ?>"/>
<?php endif; ?>
<?php if (isset($post) && !empty($post)): ?>
<?= view('common/_jsonld_article', ['post' => $post, 'ogImage' => $ogImage ?? null, 'postUser' => $postUser ?? null, 'description' => $description ?? '', 'baseSettings' => $baseSettings]); ?>
<?php endif; ?>
<?= view('common/_fonts'); ?>
<link href="<?= assetURL('assets/vendor/bootstrap-v3/css/bootstrap.min.css'); ?>" rel="stylesheet"/>
<link href="<?= assetURL('assets/vendor/font-icons/css/font-icon-2.1.min.css'); ?>" rel="stylesheet"/>
<link href="<?= assetURL($assetsPath . '/css/style-2.1.min.css'); ?>" rel="stylesheet"/>
<link href="<?= assetURL($assetsPath . '/css/plugins-2.1.css'); ?>" rel="stylesheet"/>
<?php if ($darkMode == true) : ?>
<link href="<?= assetURL($assetsPath . '/css/dark-2.1.min.css'); ?>" rel="stylesheet"/>
<?php endif; ?>
<script>var rtl = false;</script>
<?php if ($activeLang->text_direction == "rtl"): ?>
<link href="<?= assetURL($assetsPath . '/css/rtl-2.1.min.css'); ?>" rel="stylesheet"/>
<script>var rtl = true;</script>
<?php endif; ?>
<?= loadView('partials/_css_js_header'); ?>
<script>
    !function(e,t,n){e.yektanetAnalyticsObject=n,e[n]=e[n]||function(){e[n].q.push(arguments)},e[n].q=e[n].q||[];var a=t.getElementsByTagName("head")[0],r=new Date,c="https://cdn.yektanet.com/superscript/amNY04Qy/native-khabarjonoub.com-45095/yn_pub.js?v="+r.getFullYear().toString()+"0"+r.getMonth()+"0"+r.getDate()+"0"+r.getHours(),s=t.createElement("link");s.rel="preload",s.as="script",s.href=c,a.appendChild(s);var l=t.createElement("script");l.async=!0,l.src=c,a.appendChild(l)}(window,document,"yektanet");
</script>
<?= $generalSettings->custom_header_codes; ?>
</head>
<body itemscope itemtype="https://schema.org/WebPage">
<meta itemprop="name" content="<?= escSls($seoTitle); ?>">
<meta itemprop="description" content="<?= escSls($seoDescription); ?>">
<header id="header">
<?= loadView('nav/_nav_top'); ?>
<div class="logo-banner">
<div class="container">
<div class="col-sm-12">
<div class="row">
<div class="left">
<a href="<?= langBaseUrl(); ?>">
<img src="<?= $darkMode == 1 ? getLogoFooter() : getLogo(); ?>" alt="logo" class="logo" width="190" height="60">
</a>
</div>
<div class="right">
<div class="pull-right">
<?= loadView('partials/_ad_spaces', ['adSpace' => 'header', 'class' => 'bn-header']); ?>
</div>
</div>
</div>
</div>
</div>
</div>
<?= loadView('nav/_nav_main'); ?>
<div class="mobile-nav-container">
<div class="nav-mobile-header">
<div class="container-fluid">
<div class="row">
<div class="nav-mobile-header-container">
<div class="menu-icon">
<a href="javascript:void(0)" class="btn-open-mobile-nav"><i class="icon-menu"></i></a>
</div>
<div class="mobile-logo">
<a href="<?= langBaseUrl(); ?>">
<img src="<?= $darkMode == 1 ? getLogoFooter() : getLogo(); ?>" alt="logo" class="logo" width="150" height="50">
</a>
</div>
<div class="mobile-search">
<a class="search-icon"><i class="icon-search"></i></a>
</div>
</div>
</div>
</div>
</div>
</div>
</header>
<div id="overlay_bg" class="overlay-bg"></div>
<div class="mobile-nav-search">
<div class="search-form">
<form action="<?= generateURL('search'); ?>" method="get">
<input type="text" name="q" maxlength="300" pattern=".*\S+.*" class="form-control form-input" placeholder="<?= trans("placeholder_search"); ?>" required>
<button class="btn btn-default"><i class="icon-search"></i></button>
</form>
</div>
</div>
<?= loadView('nav/_nav_mobile'); ?>
<?= loadView('partials/_modals'); ?>
