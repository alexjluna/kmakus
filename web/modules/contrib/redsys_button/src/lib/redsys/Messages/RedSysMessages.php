<?php

namespace Drupal\redsys_button\lib\redsys\Messages;

/**
 * RedSysMessages class.
 *
 * Provides functionality to identify the error code sent by the bank.
 */
class RedSysMessages {

  /**
   * Stores the content of files with message data.
   *
   * @var array
   */
  private static $messages = [];

  /**
   * Gets all message data from the "data" folder.
   *
   * @return array
   *   Array with data from all files.
   */
  public static function getAll() {
    return self::load();
  }

  /**
   * Gets all information for a given code.
   *
   * @param string $code
   *   The code to retrieve information for.
   *
   * @return array|null
   *   Array with stored data about the given code, or NULL if not found.
   */
  public static function getByCode($code) {
    self::load();

    if (preg_match('/^[0-9]+$/', $code)) {
      $code = (int) $code;
    }

    if (isset(self::$messages[$code])) {
      return self::$messages[$code];
    }

    return NULL;
  }

  /**
   * Loads all message data from the "data" folder.
   *
   * @return array
   *   Array with data from all files.
   */
  private static function load() {
    if (self::$messages) {
      return self::$messages;
    }

    foreach (glob(__DIR__ . '/data/*.inc') as $file) {
      self::$messages += require $file;
    }

    return self::$messages;
  }

}
