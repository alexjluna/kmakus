<?php

namespace Drupal\redsys_button\lib\redsys\Utils;

/**
 * Utils class.
 *
 * Contains encryption and conversion functions.
 */
class Utils {

  /**
   * Converts an array to JSON format.
   *
   * @param array $data
   *   Array to be converted.
   *
   * @return string
   *   JSON formatted string.
   */
  public static function arrayToJson(array $data) {
    return json_encode($data);
  }

  /**
   * Converts a JSON string to an array.
   *
   * @param string $data
   *   JSON formatted string.
   *
   * @return array
   *   Array from the JSON data.
   */
  public static function jsonToArray(string $data) {
    return json_decode($data, TRUE);
  }

  /**
   * Converts an XML string to an array.
   *
   * @param string $xml
   *   XML string to convert to an array.
   *
   * @return array
   *   Array from the XML string.
   */
  public static function xmlToArray(string $xml) {
    $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $response = json_decode($json, TRUE);
    return $response;
  }

  /**
   * Generates HMAC SHA256 encryption.
   *
   * @param string $data
   *   Data to encrypt.
   * @param string $key
   *   Merchant's public key.
   *
   * @return string
   *   SHA256 encrypted string.
   */
  public static function hmac256(string $data, string $key) {
    return hash_hmac('sha256', $data, $key, TRUE);
  }

  /**
   * Generates 3DES encryption.
   *
   * @param string $data
   *   Data to encrypt.
   * @param string $key
   *   Merchant's public key.
   *
   * @return string
   *   3DES encrypted string.
   */
  public static function encrypt3des(string $data, string $key) {
    $iv = "\0\0\0\0\0\0\0\0";
    $data_padded = $data;

    if (strlen($data_padded) % 8) {
      $data_padded = str_pad($data_padded, strlen($data_padded) + 8 - strlen($data_padded) % 8, "\0");
    }

    return openssl_encrypt($data_padded, "DES-EDE3-CBC", $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
  }

  /**
   * Encodes a string to base64 URL.
   *
   * @param string $input
   *   String to encrypt.
   *
   * @return string
   *   Encrypted string.
   */
  public static function base64UrlEncode(string $input) {
    return strtr(base64_encode($input), '+/', '-_');
  }

  /**
   * Decodes a base64 URL encoded string.
   *
   * @param string $input
   *   Encrypted string.
   *
   * @return string
   *   Decrypted string.
   */
  public static function base64UrlDecode(string $input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

  /**
   * Encodes a string in base64.
   *
   * @param string $data
   *   String to encrypt.
   *
   * @return string
   *   Encrypted string.
   */
  public static function encodeBase64(string $data) {
    return base64_encode($data);
  }

  /**
   * Decodes a base64 encrypted string.
   *
   * @param string $data
   *   Encrypted string.
   *
   * @return string
   *   Decrypted string.
   */
  public static function decodeBase64(string $data) {
    return base64_decode($data);
  }

  /**
   * Decodes received parameters.
   *
   * @param string $data
   *   Encoded string with parameters.
   *
   * @return bool|string
   *   Decoded parameters.
   */
  public static function decodeParameters(string $data) {
    return base64_decode(strtr($data, '-_', '+/'));
  }

}
