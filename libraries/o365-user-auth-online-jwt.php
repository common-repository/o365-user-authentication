<?php
/**
 * JSON Web Token implementation, based on this spec:
 * @category Authentication
 */
class o365_online_JWT
{
    public static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
    );
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string      $jwt           The JWT
     * @param string|Array|null $key     The secret key, or map of keys
     * @param Array       $allowed_algs  List of supported verification algorithms
     *
     * @return object      The JWT's payload as a PHP object
     *
     * @throws DomainException              Algorithm was not provided
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @throws SignatureInvalidExceptionOnline    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidExceptiononline         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidExceptiononline         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredExceptionOnline             Provided JWT has since expired, as defined by the 'exp' claim
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public static function decode($o365_online_JWT, $key = null, $allowed_algs = array())
    {
        $tks = explode('.', $o365_online_JWT);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = o365_online_JWT::jsonDecode(o365_online_JWT::urlsafeB64Decode($headb64)))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = o365_online_JWT::jsonDecode(o365_online_JWT::urlsafeB64Decode($bodyb64))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        $sig = o365_online_JWT::urlsafeB64Decode($cryptob64);
        if (isset($key)) {
            if (empty($header->alg)) {
                throw new DomainException('Empty algorithm');
            }
            if (empty(self::$supported_algs[$header->alg])) {
                throw new DomainException('Algorithm not supported');
            }
            if (!is_array($allowed_algs) || !in_array($header->alg, $allowed_algs)) {
                throw new DomainException('Algorithm not allowed');
            }
            if (is_array($key)) {
                if (isset($header->kid)) {
                    $key = $key[$header->kid];
                } else {
                    throw new DomainException('"kid" empty, unable to lookup correct key');
                }
            }
            // Check the signature
            if (!o365_online_JWT::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
                throw new SignatureInvalidExceptionOnline('Signature verification failed');
            }
            // Check if the nbf if it is defined. This is the time that the
            // token can actually be used. If it's not yet that time, abort.
            if (isset($payload->nbf) && $payload->nbf > time()) {
                throw new BeforeValidExceptiononline(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
                );
            }
            // Check that this token has been created before 'now'. This prevents
            // using tokens that have been created for later use (and haven't
            // correctly used the nbf claim).
            if (isset($payload->iat) && $payload->iat > time()) {
                throw new BeforeValidExceptiononline(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
                );
            }
            // Check if this token has expired.
            if (isset($payload->exp) && time() >= $payload->exp) {
                throw new ExpiredExceptionOnline('Expired token');
            }
        }
        return $payload;
    }
    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload PHP object or array
     * @param string       $key     The secret key
     * @param string       $alg     The signing algorithm. Supported
     *                              algorithms are 'HS256', 'HS384' and 'HS512'
     *
     * @return string      A signed JWT
     * @uses jsonEncode
     * @uses urlsafeB64Encode
     */
    public static function encode($payload, $key, $alg = 'HS256', $keyId = null)
    {
        $header = array('typ' => 'o365_online_JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        $segments = array();
        $segments[] = o365_online_JWT::urlsafeB64Encode(o365_online_JWT::jsonEncode($header));
        $segments[] = o365_online_JWT::urlsafeB64Encode(o365_online_JWT::jsonEncode($payload));
        $signing_input = implode('.', $segments);
        $signature = o365_online_JWT::sign($signing_input, $key, $alg);
        $segments[] = o365_online_JWT::urlsafeB64Encode($signature);
        return implode('.', $segments);
    }
    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg          The message to sign
     * @param string|resource $key The secret key
     * @param string $alg       The signing algorithm. Supported algorithms
     *                               are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string          An encrypted message
     * @throws DomainException Unsupported algorithm was specified
     */
    public static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to sign data");
                } else {
                    return $signature;
                }
        }
    }
    /**
     * Verify a signature with the mesage, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     * @param string $msg the original message
     * @param string $signature
     * @param string|resource $key for HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string $alg
     * @return bool
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to verify data: " . openssl_error_string());
                } else {
                    return $signature;
                }
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (self::safeStrlen($signature) ^ self::safeStrlen($hash));
                return ($status === 0);
        }
    }
    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object          Object representation of JSON string
     * @throws DomainException Provided string was invalid JSON
     */
    public static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /**
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length = strlen((string) PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{'.$max_int_length.',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            o365_online_JWT::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }
    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string          JSON representation of the PHP object or array
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            o365_online_JWT::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }
        return $json;
    }
    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        );
        throw new DomainException(
            isset($messages[$errno])
            ? $messages[$errno]
            : 'Unknown JSON error: ' . $errno
        );
    }
    /**
     * Get the number of bytes in cryptographic strings.
     *
     * @param string
     * @return int
     */
    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }
}
