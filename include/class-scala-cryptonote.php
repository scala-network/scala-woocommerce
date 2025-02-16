<?php
/*
  Copyright (c) 2018 Scala-Integrations
*/

defined( 'ABSPATH' ) || exit;

if(!class_exists('SHA3'))
    require_once('crypto/SHA3.php');

if(!class_exists('ed25519'))
    require_once('crypto/ed25519.php');

require_once('class-scala-base58.php');

class Scala_Cryptonote
{
    protected $ed25519;
    public function __construct()
    {
        $this->ed25519 = new ed25519();
        $this->base58 = new Scala_base58();
        $this->address_prefix = MONERO_GATEWAY_ADDRESS_PREFIX;
        $this->address_prefix_integrated = MONERO_GATEWAY_ADDRESS_PREFIX_INTEGRATED;
    }

    /*
     * @param string Hex encoded string of the data to hash
     * @return string Hex encoded string of the hashed data
     *
     */
    public function keccak_256($message)
    {
        $keccak256 = SHA3::init (SHA3::KECCAK_256);
        $keccak256->absorb (hex2bin($message));
        return bin2hex ($keccak256->squeeze (32)) ;
    }

    /*
     * @return string A hex encoded string of 32 random bytes
     *
     */
    public function gen_new_hex_seed()
    {
        $bytes = random_bytes(32);
        return bin2hex($bytes);
    }

    public function sc_reduce($input)
    {
        $integer = $this->ed25519->decodeint(hex2bin($input));

        $modulo = bcmod($integer , $this->ed25519->l);

        $result = bin2hex($this->ed25519->encodeint($modulo));
        return $result;
    }

    /*
     * Hs in the cryptonote white paper
     *
     * @param string Hex encoded data to hash
     *
     * @return string A 32 byte encoded integer
     */
    public function hash_to_scalar($data)
    {
        $hash = $this->keccak_256($data);
        $scalar = $this->sc_reduce($hash);
        return $scalar;
    }

    /*
     * Derive a deterministic private view key from a private spend key
     * @param string A private spend key represented as a 32 byte hex string
     *
     * @return string A deterministic private view key represented as a 32 byte hex string
     */
    public function derive_viewkey($spendkey)
    {
        return $this->hash_to_scalar($spendkey);
    }

    /*
     * Generate a pair of random private keys
     *
     * @param string A hex string to be used as a seed (this should be random)
     *
     * @return array An array containing a private spend key and a deterministic view key
     */
    public function gen_private_keys($seed)
    {
        $spendkey = $this->sc_reduce($seed);
        $viewkey = $this->derive_viewkey($spendkey);
        $result = array("spendkey" => $spendkey,
        "viewkey" => $viewkey);

        return $result;
    }

    /*
     * Get a public key from a private key on the ed25519 curve
     *
     * @param string a 32 byte hex encoded private key
     *
     * @return string a 32 byte hex encoding of a point on the curve to be used as a public key
     */
    public function pk_from_sk($privKey)
    {
        $keyInt = $this->ed25519->decodeint(hex2bin($privKey));
        $aG = $this->ed25519->scalarmult_base($keyInt);
        return bin2hex($this->ed25519->encodepoint($aG));
    }

    /*
     * Generate key derivation
     *
     * @param string a 32 byte hex encoding of a point on the ed25519 curve used as a public key
     * @param string a 32 byte hex encoded private key
     *
     * @return string The hex encoded key derivation
     */
    public function gen_key_derivation($public, $private)
    {
        $point = $this->ed25519->scalarmult($this->ed25519->decodepoint(hex2bin($public)), $this->ed25519->decodeint(hex2bin($private)));
        $res = $this->ed25519->scalarmult($point, 8);
        return bin2hex($this->ed25519->encodepoint($res));
    }

    public function encode_variant($data)
    {
        $orig = $data;

        if ($data < 0x80)
            {
                return bin2hex(pack('C', $data));
            }

        $encodedBytes = [];
        while ($data > 0)
            {
                $encodedBytes[] = 0x80 | ($data & 0x7f);
                $data >>= 7;
            }

        $encodedBytes[count($encodedBytes)-1] &= 0x7f;
        $bytes = call_user_func_array('pack', array_merge(array('C*'), $encodedBytes));;
        return bin2hex($bytes);
    }

    public function derivation_to_scalar($der, $index)
    {
        $encoded = $this->encode_variant($index);
        $data = $der . $encoded;
        return $this->hash_to_scalar($data);
    }

    // this is a one way function used for both encrypting and decrypting 8 byte payment IDs
    public function stealth_payment_id($payment_id, $tx_pub_key, $viewkey)
    {
        if(strlen($payment_id) != 16)
            {
                throw new Exception("Error: Incorrect payment ID size. Should be 8 bytes");
            }
        $der = $this->gen_key_derivation($tx_pub_key, $viewkey);
        $data = $der . '8d';
        $hash = $this->keccak_256($data);
        $key = substr($hash, 0, 16);
        $result = bin2hex(pack('H*',$payment_id) ^ pack('H*',$key));
        return $result;
    }

    // takes transaction extra field as hex string and returns transaction public key 'R' as hex string
    public function txpub_from_extra($extra)
    {
        $parsed = array_map("hexdec", str_split($extra, 2));

        if($parsed[0] == 1)
            {
                return substr($extra, 2, 64);
            }

        if($parsed[0] == 2)
            {
                if($parsed[0] == 2 || $parsed[2] == 1)
                    {
                        $offset = (($parsed[1] + 2) *2) + 2;
                        return substr($extra, (($parsed[1] + 2) *2) + 2, 64);
                    }
            }
    }

    public function derive_public_key($der, $index, $pub)
    {
        $scalar = $this->derivation_to_scalar($der, $index);
        $sG = $this->ed25519->scalarmult_base($this->ed25519->decodeint(hex2bin($scalar)));
        $pubPoint = $this->ed25519->decodepoint(hex2bin($pub));
        $key = $this->ed25519->encodepoint($this->ed25519->edwards($pubPoint, $sG));
        return bin2hex($key);
    }

    /*
     * Perform the calculation P = P' as described in the cryptonote whitepaper
     *
     * @param string 32 byte transaction public key R
     * @param string 32 byte reciever private view key a
     * @param string 32 byte reciever public spend key B
     * @param int output index
     * @param string output you want to check against P
     */
    public function is_output_mine($txPublic, $privViewkey, $publicSpendkey, $index, $P)
    {
        $derivation = $this->gen_key_derivation($txPublic, $privViewkey);
        $Pprime = $this->derive_public_key($derivation, $index, $publicSpendkey);

        if($P == $Pprime)
            {
                return true;
            }
        else
            return false;
    }

    /*
     * Create a valid base58 encoded Scala address from public keys
     *
     * @param string Public spend key
     * @param string Public view key
     *
     * @return string Base58 encoded Scala address
     */
    public function encode_address($pSpendKey, $pViewKey)
    {
        $data = $this->address_prefix . $pSpendKey . $pViewKey;
        $encoded = $this->base58->encode($data);
        return $encoded;
    }

    public function verify_checksum($address)
    {
        $decoded = $this->base58->decode($address);
        $checksum = substr($decoded, -8);
        $checksum_hash = $this->keccak_256(substr($decoded, 0, -8));
        $calculated = substr($checksum_hash, 0, 8);
        return $checksum == $calculated;
    }

/*
 * Decode a base58 encoded Scala address
 *
 * @param string A base58 encoded Scala address
 *
 * @return array An array containing the Address network byte, public spend key, and public view key
 */
    public function decode_address($address)
    {
        $decoded = $this->base58->decode($address);

        if(!$this->verify_checksum($address)){
            throw new Exception("Error: invalid checksum");
        }

        $expected_prefix = $this->encode_variant($this->address_prefix);
        $expected_prefix_length = strlen($expected_prefix);

        $network_byte = substr($decoded, 0, $expected_prefix_length);
        $public_spendkey = substr($decoded, $expected_prefix_length, 64);
        $public_viewkey = substr($decoded, 64+$expected_prefix_length, 64);

        return array(
            "networkByte" => $network_byte,
            "spendkey" => $public_spendkey,
            "viewkey" => $public_viewkey
        );
    }

    /*
     * Get an integrated address from public keys and a payment id
     *
     * @param string A 32 byte hex encoded public spend key
     * @param string A 32 byte hex encoded public view key
     * @param string An 8 byte hex string to use as a payment id
     */
    public function integrated_addr_from_keys($public_spendkey, $public_viewkey, $payment_id)
    {
        $prefix = $this->encode_variant($this->address_prefix_integrated);
        $data = $prefix.$public_spendkey.$public_viewkey.$payment_id;
        $checksum = substr($this->keccak_256($data), 0, 8);
        $result = $this->base58->encode($data.$checksum);
        return $result;
    }

    /*
     * Generate a Scala address from seed
     *
     * @param string Hex string to use as seed
     *
     * @return string A base58 encoded Scala address
     */
    public function address_from_seed($hex_seed)
    {
        $private_keys = $this->gen_private_keys($hex_seed);
        $private_viewkey = $private_keys["viewkey"];
        $private_spendkey = $private_keys["spendkey"];

        $public_spendkey = $this->pk_from_sk($private_spendkey);
        $public_viewkey = $this->pk_from_sk($private_viewkey);

        $address = $this->encode_address($public_spendkey, $public_viewkey);
        return $address;
    }
}
