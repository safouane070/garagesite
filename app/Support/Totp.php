<?php

namespace App\Support;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Tijdgebonden codes (TOTP, RFC 6238) voor tweestapsverificatie: 6 cijfers,
 * elke 30 seconden nieuw, zoals Google/Microsoft Authenticator en 1Password.
 */
class Totp
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Nieuw geheim (160 bits, base32) om in de authenticator-app te zetten. */
    public static function generateSecret(): string
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($chunk) => self::BASE32[bindec($chunk)], str_split($bits, 5)));
    }

    public static function code(string $secret, int $timestep): string
    {
        $hash = hash_hmac('sha1', pack('J', $timestep), self::decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Geldige code? Eén stap speling (±30 s) voor een scheeflopende telefoonklok.
     * Geeft de gebruikte tijdstap terug (om hergebruik te weigeren), of null.
     */
    public static function verify(string $secret, string $code, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return null;
        }

        $step = intdiv($now ?? time(), self::PERIOD);
        foreach ([$step, $step - 1, $step + 1] as $candidate) {
            if (hash_equals(self::code($secret, $candidate), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode("{$issuer}:{$account}")
            . '?' . http_build_query(['secret' => $secret, 'issuer' => $issuer, 'digits' => self::DIGITS, 'period' => self::PERIOD]);
    }

    /** QR-code (SVG) om te scannen met de authenticator-app. */
    public static function qrSvg(string $uri): string
    {
        return (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd())))->writeString($uri);
    }

    private static function decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($secret, '='))) as $char) {
            $bits .= str_pad(decbin(strpos(self::BASE32, $char)), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn ($byte) => chr(bindec($byte)), str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8)));
    }
}
