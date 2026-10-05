<?php

namespace App\Oidc\Command;

use App\Core\Command\CommandInterface;
use App\Core\Command\Input;

/**
 * Create certificate command
 */
class CreateCertificateCommand implements CommandInterface
{
    public function execute(Input $input): int
    {
        $name = $input->arguments->get(0);

        $directory = APP_DIR . '/certs';
        $privatePath = $directory . '/' . $name . '-private.key';
        $publicPath = $directory . '/' . $name . '-public.key';

        if (file_exists($privatePath) || file_exists($publicPath)) {
            fwrite(STDERR, "Key files already exist in {$directory}; refusing to overwrite them.\n");
            return 1;
        }

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            fwrite(STDERR, "Could not create certificate directory: {$directory}\n");
            return 1;
        }

        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        if ($key === false) {
            fwrite(STDERR, "Could not generate RSA private key.\n");
            return 1;
        }

        $passphrase = getenv('OIDC_PASSPHRASE') ?: null;
        if (!openssl_pkey_export($key, $privateKey, $passphrase)) {
            fwrite(STDERR, "Could not export RSA private key.\n");
            return 1;
        }

        $details = openssl_pkey_get_details($key);
        $publicKey = $details['key'] ?? null;
        if ($publicKey === null || file_put_contents($privatePath, $privateKey, LOCK_EX) === false) {
            fwrite(STDERR, "Could not write private key to {$privatePath}.\n");
            return 1;
        }
        chmod($privatePath, 0600);

        if (file_put_contents($publicPath, $publicKey, LOCK_EX) === false) {
            unlink($privatePath);
            fwrite(STDERR, "Could not write public key to {$publicPath}.\n");
            return 1;
        }
        chmod($publicPath, 0644);

        fwrite(STDOUT, "Generated private.key and public.key in {$directory}.\n");
        return 0;
    }

    public static function getName(): string
    {
        return 'oidc:mkcert';
    }

    public static function getDescription(): string
    {
        return 'Create certificate';
    }

    public static function getArguments(): array
    {
        return [];
    }

    public static function getOptions(): array
    {
        return [];
    }
}