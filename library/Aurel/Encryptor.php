<?php

/**
 * Encryptor is a class for encrypting and decrypting textstrings using openssl
 *
 * @param string $encryption_key The encryption in HEX
 */
class Aurel_Encryptor
{
    /**
     * Decrypted value
     *
     * @var string
     */
    private $_decrypted_value;

    /**
     * Encrypted value
     *
     * @var string
     */
    private $_encrypted_value;

    /**
     * Expiry seconds
     *
     * @var int
     */
    private $_expiry_seconds;

    /**
     * Is expired
     *
     * @var bool
     */
    private $_is_expired;

    /**
     * Date expired
     *
     * @var DateTime
     */
    private $_date_expired;

    /** @var string $encrypt_method Method to use for encryption */
    private    $encrypt_method = 'AES-256-CBC';
    private    $secret_key  = 'yGjCd5jkTTBUTlZbHYO8TEv42yv7br6b';
    private    $secret_iv   = '38q22yhi8zbEC7Sw115e2zZ9UPY9LkJd';

    /**
     * Undocumented function
     *
     * @param string $string
     * @param int $expirySeconds
     * @return Aurel_Encryptor
     */
    public static function getInstance($string = null, $expirySeconds = null)
    {
        return new self($string = null, $expirySeconds = null);
    }

    /**
     * Construct
     * 
     * @param string $string
     * @param int $expirySeconds
     */
    function __construct($string = null, $expirySeconds = null)
    {
        $this->setDecryptedValue($string);
        $this->setExpirySeconds($expirySeconds);
    }

    /**
     * Undocumented function
     *
     * @param string $string
     * @param int $expirySeconds
     * @return Aurel_Encryptor
     */
    public function encrypt($string = null, $expirySeconds = null)
    {
        if ($string) {
            $this->setDecryptedValue($string);
        }
        if ($expirySeconds) {
            $this->setExpirySeconds($expirySeconds);
        }

        $timeToday = new \DateTime();
        $timeToday->setTimestamp(time() + $this->_expiry_seconds);
        $this->setDateExpired($timeToday);
        $this->setExpired(time() > time() + $this->_expiry_seconds);

        $time = pack('N', $timeToday->getTimestamp());

        $key = hash('sha256', $this->secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);
        $output = openssl_encrypt($time . $this->getDecryptedValue(), $this->encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);

        $this->setEncryptedValue($output);

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string $crypted_token
     * @return Aurel_Encryptor
     */
    public function decrypt($crypted_token = null)
    {
        if ($crypted_token) {
            $this->setEncryptedValue($crypted_token);
        }

        $key = hash('sha256', $this->secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $this->secret_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($this->getEncryptedValue()), $this->encrypt_method, $key, 0, $iv);

        $time = unpack('N', substr($output, 0, 4));
        $other_data = substr($output, 4);

        $timeToday = new \DateTime();
        $timeToday->setTimestamp($time[1]);
        $this->setDateExpired($timeToday);

        $this->setDecryptedValue($other_data);
        $this->setExpired(time() > $time[1]);

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param DateTime $value
     * @return Aurel_Encryptor
     */
    public function setDateExpired(\DateTime $dateExpired)
    {
        $this->_date_expired = $dateExpired;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return DateTime
     */
    public function getDateExpired()
    {
        return $this->_date_expired;
    }

    /**
     * Undocumented function
     *
     * @param [type] $value
     * @return Aurel_Encryptor
     */
    public function setExpirySeconds($expirySeconds)
    {
        $this->_expiry_seconds = $expirySeconds;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function getExpirySeconds()
    {
        return $this->_expiry_seconds;
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function __toString()
    {
        if (!$this->getDecryptedValue()) {
            $this->decrypt();
        }
        return $this->getDecryptedValue();
    }

    /**
     * Undocumented function
     *
     * @param string $value
     * @return Aurel_Encryptor
     */
    public function setEncryptedValue($value)
    {
        $this->_encrypted_value = $value;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getEncryptedValue()
    {
        return $this->_encrypted_value;
    }

    /**
     * Undocumented function
     *
     * @param string $value
     * @return void
     */
    public function setDecryptedValue($value)
    {
        $this->_decrypted_value = $value;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getDecryptedValue()
    {
        return $this->_decrypted_value;
    }

    /**
     * Undocumented function
     *
     * @param bool $value
     * @return void
     */
    public function setExpired($expired)
    {
        $this->_is_expired = $expired;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->_is_expired;
    }
}
