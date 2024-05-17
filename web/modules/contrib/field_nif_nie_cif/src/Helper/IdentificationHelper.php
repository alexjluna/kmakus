<?php

namespace Drupal\field_nif_nie_cif\Helper;

/**
 * Provides helper functions for validating Spanish (NIF, NIE, CIF).
 */
class IdentificationHelper {

  /**
   * Validates a Spanish NIF.
   *
   * @param string $nif
   *   The NIF number to validate.
   *
   * @return bool
   *   TRUE if the NIF is valid, otherwise FALSE.
   */
  public static function validateNif(string $nif): bool {
    $nifCodes = 'TRWAGMYFPDXBNJZSQVHLCKE';
    if (preg_match('/^[0-9]{8}[A-Z]$/', $nif)) {
      $num = substr($nif, 0, 8);
      return $nif[8] === $nifCodes[(int) $num % 23];
    }
    elseif (preg_match('/^[KLM]/', $nif)) {
      $sum = self::getCifSum($nif);
      $n = (10 - $sum % 10) % 10;
      return $nif[8] === chr($n + 64);
    }
    elseif (preg_match('/^[T][A-Z0-9]{8}$/', $nif)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validates a Spanish NIE.
   *
   * @param string $nie
   *   The NIE number to validate.
   *
   * @return bool
   *   TRUE if the NIE is valid, otherwise FALSE.
   */
  public static function validateNie(string $nie): bool {
    // Corrected regex pattern to properly match NIE format.
    if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $nie)) {
      $num = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $nie);
      $expectedControlDigit = 'TRWAGMYFPDXBNJZSQVHLCKE'[(int) substr($num, 0, 8) % 23];
      return $nie[8] === $expectedControlDigit;
    }
    return FALSE;
  }

  /**
   * Validates a Spanish CIF.
   *
   * @param string $cif
   *   The CIF number to validate.
   *
   * @return bool
   *   TRUE if the CIF is valid, otherwise FALSE.
   */
  public static function validateCif(string $cif): bool {
    $cifCodes = 'JABCDEFGHI';
    $sum = self::getCifSum($cif);
    $n = (10 - $sum % 10) % 10;

    if (preg_match('/^[ABCDEFGHJNPQRSUVW]/', $cif)) {
      if (in_array($cif[0], ['A', 'B', 'E', 'H'])) {
        // Numeric type.
        return $cif[8] == (string) $n;
      }
      elseif (in_array($cif[0], ['K', 'P', 'Q', 'S'])) {
        // Letter type.
        return $cif[8] === $cifCodes[$n];
      }
      else {
        // Alphanumeric type.
        return is_numeric($cif[8]) ? $cif[8] == (string) $n : $cif[8] === $cifCodes[$n];
      }
    }
    return FALSE;
  }

  /**
   * Calculates the control sum for a Spanish CIF.
   *
   * @param string $cif
   *   The CIF number for which to calculate the sum.
   *
   * @return int
   *   The calculated sum.
   */
  public static function getCifSum(string $cif): int {
    $sum = $cif[2] + $cif[4] + $cif[6];
    for ($i = 1; $i < 8; $i += 2) {
      $tmp = (string) (2 * $cif[$i]);
      $tmp = $tmp[0] + ((strlen($tmp) == 2) ? $tmp[1] : 0);
      $sum += $tmp;
    }

    return $sum;
  }

}
