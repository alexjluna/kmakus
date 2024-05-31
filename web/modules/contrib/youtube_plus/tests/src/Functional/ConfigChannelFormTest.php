<?php

namespace Drupal\Tests\youtube_plus\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test module.
 *
 * @group youtube_plus
 */
class ConfigChannelFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'youtube_plus',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * Test anonymous access to configuration page.
   */
  public function testAnonymousCanAccessForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/services/youtube_plus');
    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

  }

  /**
   * Test access to configuration page.
   */
  public function testAccessForm() {

    $account = $this->drupalCreateUser([
      'administer youtube plus',
    ]);

    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/services/youtube_plus');

    $this->assertSession()->pageTextContains('Youtube Plus');

    $this->drupalGet('/admin/config/services/youtube_plus/add');

    $this->assertSession()->pageTextContains('Add Youtube Channel');

    $this->assertSession()->buttonExists('Save');

  }

}
