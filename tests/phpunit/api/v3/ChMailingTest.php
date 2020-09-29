<?php

use CRM_Churlrewrite_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class api_v3_ChMailingTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;
  use \Civi\Test\DbTestTrait;

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * APIv3 result from creating an example footer
   * @var array
   */
  protected $footer;

  public function setUp() {
    // DGW
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    $this->_contactID = $this->individualCreate();
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = [
      'subject' => 'Hello {contact.display_name}',
      'body_text' => "This is {contact.display_name}.\nhttps://civicrm.org\n{domain.address}{action.optOutUrl}",
      'body_html' => "<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Zilla+Slab:500,700' rel='stylesheet' type='text/css'><p><a href=\"http://{action.forward}\">Forward this email</a><a href=\"{action.forward}\">Forward this email with no protocol</a></p<p>This is {contact.display_name}.</p><p><a href='https://civicrm.org/'>CiviCRM.org</a></p><p>{domain.address}{action.optOutUrl}</p>",
      'name' => 'mailing name',
      'created_id' => $this->_contactID,
      'header_id' => '',
      'footer_id' => '',
    ];

    $this->footer = civicrm_api3('MailingComponent', 'create', [
      'name' => 'test domain footer',
      'component_type' => 'footer',
      'body_html' => '<p>From {domain.address}. To opt out, go to {action.optOutUrl}.</p>',
      'body_text' => 'From {domain.address}. To opt out, go to {action.optOutUrl}.',
    ]);
  }

  public function tearDown() {
    // DGW
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;
    parent::tearDown();
  }

  public function testMailerPreview() {
    // BEGIN SAMPLE DATA
    $contactID = $this->individualCreate();
    $displayName = $this->callAPISuccess('contact', 'get', ['id' => $contactID]);
    $displayName = $displayName['values'][$contactID]['display_name'];
    $this->assertTrue(!empty($displayName));

    $params = $this->_params;
    $params['api.Mailing.preview'] = [
      'id' => '$value.id',
      'contact_id' => $contactID,
    ];
    $params['options']['force_rollback'] = 1;
    // END SAMPLE DATA

    $maxIDs = [
      'mailing' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing'),
      'job' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_job'),
      'group' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_group'),
      'recipient' => CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_mailing_recipients'),
    ];
    $result = $this->callAPISuccess('mailing', 'create', $params);
    // 'Preview should not create any mailing records'
    $this->assertDBQuery($maxIDs['mailing'], 'SELECT MAX(id) FROM civicrm_mailing');
    // 'Preview should not create any mailing_job record'
    $this->assertDBQuery($maxIDs['job'], 'SELECT MAX(id) FROM civicrm_mailing_job');
    // 'Preview should not create any mailing_group records'
    $this->assertDBQuery($maxIDs['group'], 'SELECT MAX(id) FROM civicrm_mailing_group');
    // 'Preview should not create any mailing_recipient records'
    $this->assertDBQuery($maxIDs['recipient'], 'SELECT MAX(id) FROM civicrm_mailing_recipients');
    $baseurl = CRM_Utils_System::baseCMSURL();
    $previewResult = $result['values'][$result['id']]['api.Mailing.preview'];
    $this->assertEquals("Hello $displayName", $previewResult['values']['subject']);
    $this->assertContains("This is $displayName", $previewResult['values']['body_text']);
    $this->assertContains("<p>This is $displayName.</p>", $previewResult['values']['body_html']);
    $this->assertContains('<a href="' . $baseurl . 'index.php?q=dms/mailing/forward&amp;reset=1&amp;jid=&amp;qid=&amp;h=">Forward this email with no protocol</a>', $previewResult['values']['body_html']);
    $this->assertNotContains("http://http://", $previewResult['values']['body_html']);
  }

  /**
   * @param $testCase
   * @param $expectedValues
   * @param $actualValues
   */
  public function assertAttributesEquals(&$testCase, &$expectedValues, &$actualValues) {
    foreach ($expectedValues as $paramName => $paramValue) {
      if (isset($actualValues[$paramName])) {
        $testCase->assertEquals($paramValue, $actualValues[$paramName]);
      }
      else {
        $testCase->fail("Attribute '$paramName' not present in actual array.");
      }
    }
  }

}
