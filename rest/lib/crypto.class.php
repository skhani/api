<?php
/**
 * Experimental bidirectional encryption for sensitive data
 */

/**
 * The block cipher to use for encryption.
 */
define( 'CRYPTO_CIPHER_ALG', MCRYPT_RIJNDAEL_128 );

/**
 * The hash function to use for HMAC.
 */
define( 'CRYPTO_HMAC_ALG', 'sha256' );

/**
 * The byte length of the encryption and HMAC keys.
 */
define( 'CRYPTO_KEY_BYTE_SIZE', 16 );

/**
 * The block cipher mode of operation to use.
 */
define( 'CRYPTO_CIPHER_MODE', 'cbc' );

/**
 * The length of an HMAC, so it can be extracted from the ciphertext.
 */
define( 'CRYPTO_HMAC_BYTES',
        strlen( hash_hmac( CRYPTO_HMAC_ALG, '', '', TRUE ) )
);



/**
 * @ignore Class not yet implemented
 */
class Crypto
{
    /**
     * Output format: [____HMAC____][____IV____][____CIPHERTEXT____].
     */


    /**
     * 
     * @param string $plainText
     * @param string $key
     * 
     * @return boolean|string
     */
    public static function encrypt( $plainText, $key )
    {
        if ( strlen( $key ) < 16 )
        {
            trigger_error( "Key too small.", E_USER_ERROR );
            return FALSE;
        }

        /**
         * Open the encryption module and get some parameters.
         */
        $crypt   =
                mcrypt_module_open( CRYPTO_CIPHER_ALG, "", CRYPTO_CIPHER_MODE,
                                    "" );
        $keySize = mcrypt_enc_get_key_size( $crypt );
        $ivSize  = mcrypt_enc_get_iv_size( $crypt );

        /**
         * Generate a sub-key for encryption.
         */
        $eKey = self::createSubKey( $key, "encryption", $keySize );

        /**
         * Generate a random initialization vector.
         */
        $iv = self::secureRandom( $ivSize );

        /**
         * Pad the plaintext to a multiple of the block size (PKCS #7)
         */
        $block = mcrypt_enc_get_block_size( $crypt );
        $pad   = $block - (strlen( $plainText ) % $block);
        $plainText .= str_repeat( chr( $pad ), $pad );

        /**
         * Do the encryption.
         */
        mcrypt_generic_init( $crypt, $eKey, $iv );
        $cipherText = $iv . mcrypt_generic( $crypt, $plainText );
        mcrypt_generic_deinit( $crypt );
        mcrypt_module_close( $crypt );

        /**
         * Generate a sub-key for authentication.
         */
        $akey = self::createSubKey( $key, "authentication", CRYPTO_KEY_BYTE_SIZE );

        /**
         * Apply the HMAC.
         */
        $auth       = hash_hmac( CRYPTO_HMAC_ALG, $cipherText, $akey, TRUE );
        $cipherText = $auth . $cipherText;

        return $cipherText;
    }


    /**
     * 
     * @param type $cipherText
     * @param type $key
     * 
     * @return boolean
     */
    public static function decrypt( $cipherText, $key )
    {
        // Extract the HMAC from the front of the ciphertext.
        if ( strlen( $cipherText ) <= CRYPTO_HMAC_BYTES )
            return FALSE;
        $hmac       = substr( $cipherText, 0, CRYPTO_HMAC_BYTES );
        $cipherText = substr( $cipherText, CRYPTO_HMAC_BYTES );

        // Re-generate the same authentication sub-key.
        $akey = self::createSubKey( $key, "authentication", CRYPTO_KEY_BYTE_SIZE );

        // Make sure the HMAC is correct. If not, the ciphertext has been changed.
        if ( $hmac === hash_hmac( CRYPTO_HMAC_ALG, $cipherText, $akey, TRUE ) )
        {
            // Open the encryption module and get some parameters.
            $crypt   = mcrypt_module_open( CRYPTO_CIPHER_ALG, "",
                                           CRYPTO_CIPHER_MODE, "" );
            $keySize = mcrypt_enc_get_key_size( $crypt );
            $ivSize  = mcrypt_enc_get_iv_size( $crypt );

            // Re-generate the same encryption sub-key.
            $eKey = self::createSubKey( $key, "encryption", $keySize );

            // Extract the initialization vector from the ciphertext.
            if ( strlen( $cipherText ) <= $ivSize )
                return FALSE;
            $iv         = substr( $cipherText, 0, $ivSize );
            $cipherText = substr( $cipherText, $ivSize );

            // Do the decryption.
            mcrypt_generic_init( $crypt, $eKey, $iv );
            $plainText = mdecrypt_generic( $crypt, $cipherText );
            mcrypt_generic_deinit( $crypt );
            mcrypt_module_close( $crypt );

            // Remove the padding.
            $pad       = ord( $plainText[ strlen( $plainText ) - 1 ] );
            $plainText = substr( $plainText, 0, strlen( $plainText ) - $pad );

            return $plainText;
        }
        else
        {
            // If the ciphertext has been modified, refuse to decrypt it.
            return FALSE;
        }
    }

    /*
     * Creates a sub-key from a master key for a specific purpose.
     */


    public static function createSubKey( $master, $purpose, $bytes )
    {
        $source = hash_hmac( "sha512", $purpose, TRUE );
        if ( strlen( $source ) < $bytes )
        {
            trigger_error( "Subkey too big.", E_USER_ERROR );
            return $source; // fail safe
        }

        return substr( $source, 0, $bytes );
    }

    /*
     * Returns a random binary string of length $octets bytes.
     */


    public static function secureRandom ($octets )
    {
        return mcrypt_create_iv( $octets, MCRYPT_DEV_URANDOM );
    }

    /*
     * A simple test and demonstration of how to use this class.
     */


    public static function Test()
    {
        echo "Running crypto test...\n";

        $key  = mcrypt_create_iv( 16, MCRYPT_DEV_URANDOM );
        $data = "EnCrYpT EvErYThInG\x00\x00";

        $ciphertext = Crypto::encrypt( $data, $key );
        echo "Ciphertext: " . bin2hex( $ciphertext ) . "\n";

        $decrypted = Crypto::decrypt( $ciphertext, $key );
        echo "Decrypted: " . $decrypted . "\n";

        if ( $decrypted != $data )
        {
            echo "FAIL: Decrypted data is not the same as the original.";
            return FALSE;
        }

        if ( Crypto::decrypt( $ciphertext . "a", $key ) !== FALSE )
        {
            echo "FAIL: Ciphertext tampering not detected.";
            return FALSE;
        }

        $ciphertext[ 0 ] = chr( (ord( $ciphertext[ 0 ] ) + 1) % 256 );
        if ( Crypto::decrypt( $ciphertext, $key ) !== FALSE )
        {
            echo "FAIL: Ciphertext tampering not detected.";
            return FALSE;
        }

        echo "PASS\n";
        return TRUE;
    }

}

// Run the test when and only when this script is executed on the command line.
if ( isset( $argv ) && realpath( $argv[ 0 ] ) == __FILE__ )
{
    Crypto::Test();
}