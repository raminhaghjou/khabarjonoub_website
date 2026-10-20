<?php

namespace App\Libraries;

use CodeIgniter\Cookie\Cookie;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Durable login cookie used when CI 4.2 FileHandler drops session values
 * across redirects on PHP 8.2.
 *
 * IMPORTANT: Config\Cookie::$prefix (from .env cookie.prefix=vr) must NOT be
 * applied. Cookie options treat prefix '' as "use default", so we must call
 * withPrefix('') after construction. Otherwise the browser stores
 * "vrkhabarjonoub_auth_v1" while getUser() looks for "khabarjonoub_auth_v1".
 */
class AuthCookie
{
    const NAME = 'khabarjonoub_auth_v1';
    const LIFETIME = 259200;

    /** @var bool */
    private static $issued = false;

    /** @var string|null */
    private static $lastValue = null;

    /** @var int|null */
    private static $lastExpires = null;

    public static function issue($user): bool
    {
        if (empty($user) || empty($user->id) || empty($user->password)) {
            return false;
        }

        $expires = time() + self::LIFETIME;
        $payload = ((int) $user->id) . '|' . $expires;
        $value = $payload . '|' . self::signature($payload, $user);

        self::$lastValue = $value;
        self::$lastExpires = $expires;
        self::$issued = true;

        // Available immediately in this request.
        $_COOKIE[self::NAME] = $value;

        // Attach to the shared response (may be replaced by redirect()).
        self::applyToResponse(Services::response());

        // Native header queue — exact name, no Config\Cookie prefix.
        if (!headers_sent()) {
            setcookie(self::NAME, $value, self::cookieOptions($expires));
        }

        return true;
    }

    /**
     * Attach the last issued auth cookie to a response (including RedirectResponse).
     */
    public static function applyToResponse(ResponseInterface $response): void
    {
        if (self::$lastValue === null || self::$lastExpires === null) {
            return;
        }

        // secure MUST match $request->isSecure() or CI throws when dispatching cookies.
        $secure = self::requestIsSecure();

        $cookie = (new Cookie(self::NAME, self::$lastValue, [
            'expires'  => self::$lastExpires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]))->withPrefix('');

        $response->setCookie($cookie);
    }

    public static function wasIssued(): bool
    {
        return self::$issued;
    }

    public static function getUser($db)
    {
        $value = self::readRawValue();
        $parts = $value !== '' ? explode('|', $value) : [];
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
            return null;
        }

        $userId = (int) $parts[0];
        $expires = (int) $parts[1];
        if ($userId < 1 || $expires < time() || $expires > time() + self::LIFETIME + 300) {
            return null;
        }

        $user = $db->table('users')->where('id', $userId)->get()->getRow();
        if (empty($user) || (int) $user->status !== 1 || empty($user->password)) {
            return null;
        }

        $payload = $userId . '|' . $expires;
        if (!hash_equals(self::signature($payload, $user), $parts[2])) {
            return null;
        }

        return $user;
    }

    public static function clear(): bool
    {
        unset($_COOKIE[self::NAME]);
        foreach (self::possibleNames() as $name) {
            unset($_COOKIE[$name]);
        }

        self::$issued = false;
        self::$lastValue = null;
        self::$lastExpires = null;

        $cookie = (new Cookie(self::NAME, '', [
            'expires'  => 1,
            'path'     => '/',
            'domain'   => '',
            'secure'   => self::requestIsSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]))->withPrefix('');
        Services::response()->setCookie($cookie);

        if (!headers_sent()) {
            $options = self::cookieOptions(1);
            setcookie(self::NAME, '', $options);
            foreach (self::possibleNames() as $name) {
                if ($name !== self::NAME) {
                    setcookie($name, '', $options);
                }
            }
        }

        return true;
    }

    private static function cookieOptions(int $expires): array
    {
        return [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => self::requestIsSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function requestIsSecure(): bool
    {
        try {
            if (Services::request()->isSecure()) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fall through to server/baseURL checks.
        }

        return self::isHttps();
    }

    private static function readRawValue(): string
    {
        foreach (self::possibleNames() as $name) {
            if (!empty($_COOKIE[$name]) && is_string($_COOKIE[$name])) {
                return $_COOKIE[$name];
            }
        }

        return '';
    }

    /**
     * All historical/accidental cookie names we might have set.
     */
    private static function possibleNames(): array
    {
        $names = [self::NAME];

        $prefix = getenv('cookie.prefix');
        if (is_string($prefix) && $prefix !== '') {
            $prefix = trim($prefix, " \t\n\r\0\x0B'\"");
            if ($prefix !== '') {
                // helperSetCookie style: prefix + '_' + name
                $names[] = $prefix . '_' . self::NAME;
                // Config\Cookie style: prefix + name (NO underscore) — the actual bug
                $names[] = $prefix . self::NAME;
            }
        }

        // Hard-code the known production prefix so recovery works even if getenv fails.
        $names[] = 'vr_' . self::NAME;
        $names[] = 'vr' . self::NAME;

        return array_values(array_unique($names));
    }

    private static function signature($payload, $user): string
    {
        $key = hash('sha256', (string) $user->password . '|' . (string) ($user->token ?? ''), true);
        return hash_hmac('sha256', $payload, $key);
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return strpos((string) config('App')->baseURL, 'https://') === 0;
    }
}
