<?php

namespace App\Oidc\Server;

/**
 * Carries the "nonce" query param from the /authorize request through to the
 * id_token issued at the /token endpoint. Safe as a static holder because a
 * single PHP process only ever handles one HTTP request at a time.
 */
class NonceContext
{
    private static ?string $nonce = null;

    public static function set(?string $nonce): void
    {
        self::$nonce = $nonce;
    }

    public static function get(): ?string
    {
        return self::$nonce;
    }
}
