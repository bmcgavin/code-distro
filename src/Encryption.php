<?php

namespace Codedistro;

class Encryption {

    private $td = null;
    private $iv = null;
    private $ks = null;
    private $keyLocation = null;

    public function __construct($keyLocation) {
        $this->keyLocation = $keyLocation;
        $this->td = mcrypt_module_open("twofish", "", "cfb", "");

        $this->iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->td), MCRYPT_DEV_URANDOM);
        $this->ks = mcrypt_enc_get_key_size($this->td);

    }

    public function encrypt($plaintext) {
        $key = file_get_contents($this->keyLocation, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
        $key = substr($key, 0, $this->ks);
        mcrypt_generic_init($this->td, $key, $this->iv);
        $key = 'overwrittenwithplaceholder';
        unset($key);
        $encrypted = mcrypt_generic($this->td, json_encode($plaintext));
        $encrypted = $this->iv.$encrypted;
        mcrypt_generic_deinit($this->td);
        return base64_encode($encrypted);
    }
    
    public function decrypt($ciphertext) {
        $ciphertext = base64_decode($ciphertext);
        $key = file_get_contents($this->keyLocation, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
        $key = substr($key, 0, $this->ks);
        $this->iv = substr($ciphertext, 0, mcrypt_enc_get_iv_size($this->td));
        $ciphertext = substr($ciphertext, mcrypt_enc_get_iv_size($this->td));
        mcrypt_generic_init($this->td, $key, $this->iv);
        $key = 'overwrittenwithplaceholder';
        unset($key);
        $decrypted = mdecrypt_generic($this->td, $ciphertext);
        return json_decode($decrypted);
    }

    public function __destruct() {
        if (is_resource($this->td)) {
            mcrypt_module_close($this->td);
        }
    }

}
