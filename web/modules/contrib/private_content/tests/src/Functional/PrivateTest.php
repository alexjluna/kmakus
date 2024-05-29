<?php

namespace Drupal\Tests\private_content\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the private module.
 *
 * @group private
 */
class PrivateTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'search', 'private_content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Rebuild node access.
   */
  public function setUp(): void {
    parent::setUp();
    $this->createContentType(['type' => 'article']);
    node_access_rebuild();
  }

  /**
   * Test the "private" node access.
   *
   * - Create 3 users with "access content" and "create article" permissions.
   * - Each user creates one private and one not private article.
   * - Run cron to update search index.
   * - Test that each user can view the other user's non-private article.
   * - Test that each user cannot view the other user's private article.
   * - Test that each user finds only appropriate (non-private + own private)
   *   in search results.
   * - Create another user with 'view private content'.
   * - Test that user 4 can view all content created above.
   * - Test that user 4 can search for all content created above.
   * - Test that user 4 cannot edit private content above.
   * - Create another user with 'edit private content'
   * - Test that user 5 can edit private content.
   * - Test that user 5 can delete private content.
   * - Test listings of nodes with 'node_access' tag on database search.
   */
  public function testNodeAccessBasic() {
    $num_simple_users = 3;
    $simple_users = [];

    // Nodes keyed by uid and nid: $nodes[$uid][$nid] = $is_private;.
    $nodes_by_user = [];
    // Titles keyed by nid.
    $titles = [];
    // Array of nids marked private.
    $private_nodes = [];
    for ($i = 0; $i < $num_simple_users; $i++) {
      $simple_users[$i] = $this->drupalCreateUser([
        'access content',
        'create article content',
        'search content',
        'mark content as private',
      ]);
    }

    foreach ($simple_users as $web_user) {
      $this->drupalLogin($web_user);
      foreach ([0 => 'Public', 1 => 'Private'] as $is_private => $type) {
        $edit = [
          'title[0][value]' => "$type Article created by " . $web_user->name->value,
        ];
        if ($is_private) {
          $edit['private[0][stored]'] = TRUE;
          $edit['body[0][value]'] = 'private node';
        }
        else {
          $edit['body[0][value]'] = 'public node';
        }
        $this->drupalGet('node/add/article');
        $this->submitForm($edit, 'Save');
        $nid = \Drupal::database()->query('SELECT nid FROM {node_field_data} WHERE title = :title', [':title' => $edit['title[0][value]']])->fetchField();
        $node = \Drupal::service('entity_type.manager')->getStorage('node')->load($nid);
        $this->assertEquals($is_private, $node->private->value, 'Node was properly set to private or not private in private field.');
        if ($is_private) {
          $private_nodes[] = $nid;
        }
        $titles[$nid] = $edit['title[0][value]'];
        $nodes_by_user[$web_user->id()][$nid] = $is_private;
      }
    }

    // Build the search index.
    $this->cronRun();
    foreach ($simple_users as $web_user) {
      $this->drupalLogin($web_user);
      // Check to see that we find the number of search results expected.
      $this->checkSearchResults('Private node', 1);
      // Check own nodes to see that all are readable.
      foreach (array_keys($nodes_by_user) as $uid) {
        // All of this user's nodes should be readable to same.
        if ($uid == $web_user->id()) {
          foreach ($nodes_by_user[$uid] as $nid => $is_private) {
            $this->drupalGet('node/' . $nid);
            $this->assertSession()->statusCodeEquals(200);
            $this->assertSession()->titleEquals($titles[$nid] . ' | Drupal');
          }
        }
        else {
          // Otherwise, for other users, private nodes should get a 403,
          // but we should be able to read non-private nodes.
          foreach ($nodes_by_user[$uid] as $nid => $is_private) {
            $this->drupalGet('node/' . $nid);
            $this->assertSession()->statusCodeEquals($is_private ? 403 : 200);
            if (!$is_private) {
              $this->assertSession()->titleEquals($titles[$nid] . ' | Drupal');
            }
          }
        }
      }
    }

    // Now test that a user with 'access private content' can view content.
    $access_user = $this->drupalCreateUser([
      'access content',
      'create article content',
      'access private content',
      'search content',
    ]);
    $this->drupalLogin($access_user);

    // Check to see that we find the number of search results expected.
    $this->checkSearchResults('Private node', 3);

    foreach ($nodes_by_user as $uid => $private_status) {
      foreach ($private_status as $nid => $is_private) {
        $this->drupalGet('node/' . $nid);
        $this->assertSession()->statusCodeEquals(200);
      }
    }

    // Test that a privileged user can edit and delete private content.
    // This test should go last, as the nodes get deleted.
    $edit_user = $this->drupalCreateUser([
      'access content',
      'access private content',
      'edit private content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($edit_user);
    foreach ($private_nodes as $nid) {
      $body = $this->randomString(200);
      $edit = ['body[0][value]' => $body];
      $this->drupalGet('node/' . $nid . '/edit');
      $this->submitForm($edit, 'Save');
      $this->assertSession()->pageTextContains('has been updated');
      $this->drupalGet('node/' . $nid . '/delete');
      $this->submitForm([], 'Delete');
      $this->assertSession()->pageTextContains(t('has been deleted'));
    }

  }

  /**
   * Check search result.
   *
   * On the search page, search for a string and assert the expected
   * number of results.
   *
   * @param string $search_query
   *   String to search for.
   * @param int $expected_result_count
   *   Expected result count.
   */
  public function checkSearchResults($search_query, $expected_result_count) {
    $this->drupalGet('search/node');
    $this->submitForm(['keys' => $search_query], 'Search');
    $search_results = $this->xpath("//ol[contains(@class, 'search-results')]/li");
    $this->assertEquals($expected_result_count, count($search_results), 'Found the expected number of search results');
  }

}
