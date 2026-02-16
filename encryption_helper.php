<?php
/**
 * Encryption Helper Class
 * AES-256-CBC encryption for sensitive healthcare data
 */

class EncryptionHelper {
    private static $encryption_key = null;
    private static $cipher_method = 'aes-256-cbc';
    
    /**
     * Get encryption key from environment or generate secure key
     */
    private static function getEncryptionKey() {
        if (self::$encryption_key === null) {
            // Try to get from environment variable first
            $key = $_ENV['MEDICAL_ENCRYPTION_KEY'] ?? null;
            
            if ($key === null) {
                // Fallback to a secure key file
                $key_file = __DIR__ . '/.encryption_key';
                if (file_exists($key_file)) {
                    $key = file_get_contents($key_file);
                } else {
                    // Generate a secure key if none exists
                    $key = random_bytes(32); // 256 bits
                    file_put_contents($key_file, $key);
                    chmod($key_file, 0600); // Restrict permissions
                }
            }
            
            self::$encryption_key = $key;
        }
        
        return self::$encryption_key;
    }
    
    /**
     * Encrypt sensitive data
     * @param string $plaintext - Data to encrypt
     * @return array - ['encrypted' => base64_encoded_data, 'iv' => base64_encoded_iv]
     */
    public static function encryptData($plaintext) {
        if (empty($plaintext)) {
            return ['encrypted' => '', 'iv' => ''];
        }
        
        $key = self::getEncryptionKey();
        $iv_length = openssl_cipher_iv_length(self::$cipher_method);
        $iv = random_bytes($iv_length);
        
        $encrypted = openssl_encrypt($plaintext, self::$cipher_method, $key, OPENSSL_RAW_DATA, $iv);
        
        if ($encrypted === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
    }
    
    /**
     * Decrypt sensitive data
     * @param string $ciphertext - Base64 encoded encrypted data
     * @param string $iv - Base64 encoded initialization vector
     * @return string - Decrypted plaintext
     */
    public static function decryptData($ciphertext, $iv) {
        if (empty($ciphertext) || empty($iv)) {
            return '';
        }
        
        $key = self::getEncryptionKey();
        $encrypted_data = base64_decode($ciphertext);
        $iv_data = base64_decode($iv);
        
        $decrypted = openssl_decrypt($encrypted_data, self::$cipher_method, $key, OPENSSL_RAW_DATA, $iv_data);
        
        if ($decrypted === false) {
            throw new Exception("Decryption failed: " . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    /**
     * Generate a new encryption key
     * @return string - Base64 encoded key
     */
    public static function generateKey() {
        $key = random_bytes(32); // 256 bits
        return base64_encode($key);
    }
    
    /**
     * Validate encryption key format
     * @param string $key - Base64 encoded key
     * @return bool
     */
    public static function validateKey($key) {
        $decoded = base64_decode($key);
        return $decoded !== false && strlen($decoded) === 32;
    }
    
    /**
     * Get cipher method being used
     * @return string
     */
    public static function getCipherMethod() {
        return self::$cipher_method;
    }
}
?>
