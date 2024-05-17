<?php

namespace Drupal\redsys_button\lib\redsys\Utils;

/**
 * Validators class.
 *
 * Contains validation functions.
 */
class Validators {

  /**
   * Checks if a string is empty.
   *
   * @param string $value
   *   String to check.
   *
   * @return bool
   *   TRUE if it is empty, otherwise FALSE.
   */
  public static function isEmpty(string $value) {
    return '' === trim($value);
  }

  /**
   * Checks if the selected language code is valid.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return bool
   *   TRUE if it is a valid code.
   */
  public static function isValidLangcode(string $langcode) {
    $value = intval($langcode);
    return (($value > 0) and ($value < 14)) ? TRUE : FALSE;
  }

  /**
   * Verifies if the URL is in a valid format.
   *
   * @param string $url
   *   URL to verify.
   *
   * @return bool
   *   TRUE if the URL is valid.
   */
  public static function isValidUrl(string $url) {
    return filter_var($url, FILTER_VALIDATE_URL);
  }

  /**
   * Checks if the expiry date is correct.
   *
   * @param string $expirydate
   *   Format is YYMM, where YY are the last two digits of the year
   *   and MM are the two digits of the month.
   *
   * @return bool
   *   TRUE if it meets the valid date parameters.
   */
  public static function isExpiryDate(string $expirydate) {
    return (strlen(trim($expirydate)) == 4 && is_numeric($expirydate));
  }

  /**
   * Checks if the order number is valid.
   *
   * @param string $order
   *   String with the order number.
   *
   * @return bool
   *   TRUE if the value is valid.
   */
  public static function isValidOrder(string $order) {
    return (strlen($order) >= 4 && strlen($order) <= 12 && is_numeric(substr($order, 0, 4))) ? TRUE : FALSE;
  }

  /**
   * Verifies if the returned code is valid.
   *
   * @param array $response
   *   Array - server response.
   *
   * @return bool
   *   TRUE if the code is valid.
   */
  public static function validCode(array $response) {
    $code = $response['CODIGO'];

    if (!is_numeric($code)) {
      return FALSE;
    }

    $code = $response['OPERACION']['Ds_Response'];

    if (!is_numeric($code)) {
      return FALSE;
    }

    if ($code >= 0 && $code < 100) {
      return TRUE;
    }

    if ($code == 900 || $code == 400) {
      return TRUE;
    }

    return FALSE;
  }

}
