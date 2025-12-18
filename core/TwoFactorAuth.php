<?php

/**
 * Two Factor Authentication
 * Simple 2FA implementation
 */
class TwoFactorAuth {
    
    /**
     * Generate secret key
     */
    public function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Generate 6-digit code
     */
    public function generateCode($secret) {
        $time = floor(time() / 30);
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $time), base32_decode($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify code
     */
    public function verifyCode($secret, $code) {
        // Check current and adjacent time windows
        for ($i = -1; $i <= 1; $i++) {
            $time = floor(time() / 30) + $i;
            $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $time), base32_decode($secret), true);
            $offset = ord($hash[strlen($hash) - 1]) & 0xf;
            $testCode = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;
            
            if (str_pad($testCode, 6, '0', STR_PAD_LEFT) === $code) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get QR code URL
     */
    public function getQRCodeUrl($secret, $username, $issuer = SITE_NAME) {
        $url = 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($username) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);
    }
}

/**
 * Base32 decode helper
 */
function base32_decode($input) {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;
    
    for ($i = 0; $i < strlen($input); $i++) {
        $val = strpos($map, $input[$i]);
        if ($val === false) continue;
        
        $buffer = ($buffer << 5) | $val;
        $bitsLeft += 5;
        
        if ($bitsLeft >= 8) {
            $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
            $bitsLeft -= 8;
        }
    }
    
    return $output;
}
?>