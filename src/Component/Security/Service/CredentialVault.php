<?php

declare(strict_types=1);

namespace Gandalf\Component\Security\Service;

/**
 * Encrypts and decrypts credential data using sodium_crypto_secretbox.
 * The encryption key comes from GANDALF_CREDENTIALS_KEY env var.
 */
class CredentialVault
{
    private readonly string $key;

    public function __construct(
        #[\SensitiveParameter]
        string $encryptionKey,
    ) {
        $decoded = base64_decode($encryptionKey, true);

        if (false === $decoded || \SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== \strlen($decoded)) {
            throw new \InvalidArgumentException(sprintf(
                'GANDALF_CREDENTIALS_KEY must be a base64-encoded %d-byte key. Generate with: php -r "echo base64_encode(sodium_crypto_secretbox_keygen());"',
                \SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            ));
        }

        $this->key = $decoded;
    }

    /**
     * Encrypts an associative array into a base64 string for storage.
     *
     * @param array<string, mixed> $data
     */
    public function encrypt(array $data): string
    {
        $json = json_encode($data, \JSON_THROW_ON_ERROR);
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($json, $nonce, $this->key);

        return base64_encode($nonce . $cipher);
    }

    /**
     * Decrypts a base64 string back to an associative array.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException if decryption fails (wrong key or corrupted data)
     */
    public function decrypt(string $encrypted): array
    {
        $decoded = base64_decode($encrypted, true);

        if (false === $decoded) {
            throw new \RuntimeException('Invalid base64 credential data.');
        }

        if (\strlen($decoded) < \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Credential data too short.');
        }

        $nonce = substr($decoded, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($decoded, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $json = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);

        if (false === $json) {
            throw new \RuntimeException('Credential decryption failed — wrong key or corrupted data.');
        }

        return json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
    }
}
