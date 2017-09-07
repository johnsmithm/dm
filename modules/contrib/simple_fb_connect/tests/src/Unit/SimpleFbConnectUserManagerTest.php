<?php

namespace Drupal\Tests\simple_fb_connect\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\simple_fb_connect\SimpleFbConnectUserManager
 * @group simple_fb_connect
 */
class SimpleFbConnectUserManagerTest extends UnitTestCase {

  protected $configFactory;
  protected $loggerFactory;
  protected $stringTranslation;
  protected $eventDispatcher;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $token;
  protected $transliteration;
  protected $userManager;
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub(
      [
        'simple_fb_connect.settings' => [
          'disable_admin_login' => 1,
          'disabled_roles' => ['blocked_role' => 'blocked_role'],
        ],
        'user.settings' => [
          'register' => 'visitors',
        ],
        'system.file' => [
          'default_scheme' => 'public',
        ],
      ]
    );

    $this->loggerFactory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');

    $this->stringTranslation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');

    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

    $this->entityTypeManager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')
      ->setMethods(['load'])
      ->getMockForAbstractClass();

    $this->entityFieldManager = $this->getMockBuilder('Drupal\Core\Entity\EntityFieldManagerInterface')
      ->getMock();

    $this->token = $this->getMockBuilder('Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();

    $this->transliteration = $this->getMockBuilder('Drupal\Core\Transliteration\PhpTransliteration')
      ->disableOriginalConstructor()
      ->getMock();

    $this->languageManager = $this->getMockBuilder('Drupal\Core\Language\LanguageManagerInterface')
      ->getMock();

    // Note that we are creating an instance of TestSimpleFbConnectUserManager
    // instead of SimpleFbConnectUserManager. The test class overriders the
    // wrapper methods that call procedural Drupal functions.
    $this->userManager = new TestSimpleFbConnectUserManager(
      $this->configFactory,
      $this->loggerFactory,
      $this->stringTranslation,
      $this->eventDispatcher,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->token,
      $this->transliteration,
      $this->languageManager
    );
  }

  /**
   * Tests loginUser method.
   *
   * @covers ::loginUser
   * @covers ::loginDisabledForAdmin
   * @covers ::loginDisabledByRole
   *
   * @dataProvider loginUserDataProvider
   */
  public function testLoginUser($user, $expected) {
    $logger_channel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory
      ->expects($this->any())
      ->method('get')
      ->with('simple_fb_connect')
      ->willReturn($logger_channel);

    $this->assertSame($expected, $this->userManager->loginUser($user));
  }

  /**
   * Data provider for testLoginUser().
   *
   * @return array
   *   Nested arrays of values to check.
   *
   * @see ::testLoginuser()
   */
  public function loginUserDataProvider() {
    $user_1 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $user_1
      ->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $user_2 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $user_2
      ->expects($this->any())
      ->method('id')
      ->willReturn(2);
    $user_2
      ->expects($this->any())
      ->method('getRoles')
      ->willReturn(['authenticated', 'blocked_role']);

    $user_3 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $user_3
      ->expects($this->any())
      ->method('id')
      ->willReturn(3);
    $user_3
      ->expects($this->any())
      ->method('getRoles')
      ->willReturn(['authenticated', 'normal_role']);
    $user_3
      ->expects($this->any())
      ->method('isActive')
      ->willReturn(FALSE);

    $user_4 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();
    $user_4
      ->expects($this->any())
      ->method('id')
      ->willReturn(4);
    $user_4
      ->expects($this->once())
      ->method('getRoles')
      ->willReturn(['authenticated', 'normal_role']);
    $user_4
      ->expects($this->any())
      ->method('isActive')
      ->willReturn(TRUE);

    return [
      [$user_1, FALSE],
      [$user_2, FALSE],
      [$user_3, FALSE],
      [$user_4, TRUE],
    ];
  }

  /**
   * Tests createUser method when user creation is blocked in Drupal settings.
   *
   * @covers ::createUser
   * @covers ::registrationBlocked
   */
  public function testCreateUserWhenRegistrationBlocked() {
    // Set registration to be blocked in Drupal account settings.
    $new_config_factory = $this->getConfigFactoryStub(
      [
        'user.settings' => [
          'register' => 'admin_only',
        ],
      ]
    );
    $this->userManager->setConfigFactory($new_config_factory);

    $logger_channel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory
      ->expects($this->any())
      ->method('get')
      ->with('simple_fb_connect')
      ->willReturn($logger_channel);

    $fb_profile_pic = $this->getMockBuilder('Facebook\GraphNodes\GraphNode')
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertFalse($this->userManager->createUser('Firstname Lastname', 'foo@example.com', 12345, $fb_profile_pic));
  }

  /**
   * Tests createUser method when user creation is allowed.
   *
   * @covers ::createUser
   * @covers ::registrationBlocked
   */
  public function testCreateUser() {
    // User object that will be created in this test.
    $user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    // Storage object that will be created in this test.
    $storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();

    // Language object that will be created in this test.
    $language = $this->getMockBuilder('Drupal\Core\Language\LanguageInterface')
      ->getMock();

    // Called when we're generating an unique username.
    $storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    // Called when user is created.
    $storage
      ->expects($this->once())
      ->method('create')
      ->willReturn($user);

    // EntityTypeManager that will return $storage.
    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($storage);

    // Called when current UI language is detected.
    $language
      ->expects($this->once())
      ->method('getId')
      ->willReturn('fi');

    // LanguageManager that will return $language.
    $this->languageManager
      ->expects($this->once())
      ->method('getCurrentLanguage')
      ->willReturn($language);

    $logger_channel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory
      ->expects($this->any())
      ->method('get')
      ->with('simple_fb_connect')
      ->willReturn($logger_channel);

    $fb_profile_pic = $this->getMockBuilder('Facebook\GraphNodes\GraphNode')
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertInstanceOf('Drupal\user\Entity\User', $this->userManager->createUser('Firstname Lastname', 'foo@example.com', 12345, $fb_profile_pic));
  }

  /**
   * Tests the generateUniqueUsername() when there is no conflicting username.
   *
   * @covers ::generateUniqueUsername
   * @covers ::loadUserByProperty
   *
   * @dataProvider generateUniqueUsernameWithNoConflictsDataProvider
   */
  public function testGenerateUniqueUsernameWithNoConflicts($fb_name, $expected) {
    $storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();

    $storage
      ->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    $this->entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('user')
      ->willReturn($storage);

    $this->assertEquals($expected, $this->userManager->subGenerateUniqueUsername($fb_name));
  }

  /**
   * Data provider for testGenerateUniqueUsernameWithNoConflicts().
   *
   * @return array
   *   Nested arrays of values to check.
   *
   * @see ::testGenerateUniqueUsernameWithNoConflicts()
   */
  public function generateUniqueUsernameWithNoConflictsDataProvider() {
    return [
      ['Firstname Lastname', 'Firstname Lastname'],
      ['Space  Between', 'Space Between'],
      [' Leading Whitespace', 'Leading Whitespace'],
      ['Trailing Whitespace ', 'Trailing Whitespace'],
      [' Whitespace  and Spaces ', 'Whitespace and Spaces'],
      ['The length of this string is very long and it easily exceeds the maximum', 'The length of this string is very long and it easily exceeds'],
    ];
  }

  /**
   * Tests generateUniqueUsername() when there is a conflicting username.
   *
   * @covers ::generateUniqueUsername
   * @covers ::loadUserByProperty
   *
   * @dataProvider generateUniqueUsernameWithConflictDataProvider
   */
  public function testGenerateUniqueUsernameWithConflict($fb_name, $expected) {
    $existing_user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();

    $storage
      ->expects($this->any())
      ->method('loadByProperties')
      ->will($this->onConsecutiveCalls(
          [1 => $existing_user],
          [2 => $existing_user],
          [3 => $existing_user],
          [4 => $existing_user],
          [5 => $existing_user],
          [6 => $existing_user],
          [7 => $existing_user],
          [8 => $existing_user],
          [9 => $existing_user],
          []
        ));

    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($storage);

    $this->assertEquals($expected, $this->userManager->subGenerateUniqueUsername($fb_name));
  }

  /**
   * Data provider for testGenerateUniqueUsernameWithConflict().
   *
   * @return array
   *   Nested arrays of values to check.
   *
   * @see ::testGenerateUniqueUsernameWithConflict()
   */
  public function generateUniqueUsernameWithConflictDataProvider() {
    return [
      ['Firstname Lastname', 'Firstname Lastname 10'],
      ['The length of this string is very long and it easily exceeds the maximum', 'The length of this string is very long and it easily exce 10'],
    ];
  }

  /**
   * Tests downloadProfilePic when user pictures are not enabled.
   *
   * @covers ::downloadProfilePic
   * @covers ::getPictureDirectory
   */
  public function testDownloadProfilePicWhenUserPicturesNotInUse() {
    $this->entityFieldManager
      ->expects($this->once())
      ->method('getFieldDefinitions')
      ->with('user', 'user')
      ->willReturn([]);

    $this->assertFalse($this->userManager->subDownloadProfilePic('http://www.example.com/picture.jpg', '1234'));
  }

  /**
   * Tests downloadProfilePic when target directory is not writeable.
   *
   * @covers ::downloadProfilePic
   * @covers ::getPictureDirectory
   */
  public function testDownloadProfilePicWhenDirectoryNotWriteable() {
    $picture_directory = 'not/writeable/directory';

    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');

    $field_definition
      ->expects($this->once())
      ->method('getSetting')
      ->with('file_directory')
      ->willReturn($picture_directory);

    $this->entityFieldManager
      ->expects($this->any())
      ->method('getFieldDefinitions')
      ->with('user', 'user')
      ->willReturn(['user_picture' => $field_definition]);

    $this->token
      ->expects($this->once())
      ->method('replace')
      ->willReturn($picture_directory);

    $this->transliteration
      ->expects($this->once())
      ->method('transliterate')
      ->willReturn($picture_directory);

    $logger_channel = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();

    $this->loggerFactory
      ->expects($this->any())
      ->method('get')
      ->with('simple_fb_connect')
      ->willReturn($logger_channel);

    $this->assertFalse($this->userManager->subDownloadProfilePic('http://www.example.com/picture.jpg', '1234'));
  }

  /**
   * Tests setProfilePic when target directory is writeable.
   *
   * @covers ::setProfilePic
   * @covers ::userPictureEnabled
   * @covers ::downloadProfilePic
   * @covers ::getPictureDirectory
   */
  public function testSetProfilePic() {
    $picture_directory = 'writeable/directory';

    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');

    $field_definition
      ->expects($this->once())
      ->method('getSetting')
      ->with('file_directory')
      ->willReturn($picture_directory);

    $this->entityFieldManager
      ->expects($this->any())
      ->method('getFieldDefinitions')
      ->with('user', 'user')
      ->willReturn(['user_picture' => $field_definition]);

    $this->token
      ->expects($this->once())
      ->method('replace')
      ->willReturn($picture_directory);

    // Transliteration is called twice: first for directory and then for file.
    $this->transliteration
      ->expects($this->any())
      ->method('transliterate')
      ->will($this->onConsecutiveCalls(
          $picture_directory,
          '12345.jpg'
        ));

    // File object.
    $file = $this->getMockBuilder('Drupal\file\Entity\File')
      ->disableOriginalConstructor()
      ->getMock();
    $file
      ->expects($this->once())
      ->method('id')
      ->willReturn(1);

    $storage = $this->getMockBuilder('Drupal\Core\Entity\EntityStorageInterface')
      ->getMock();

    $storage
      ->expects($this->once())
      ->method('create')
      ->willReturn($file);

    // EntityTypeManager that will return $storage.
    $this->entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $user = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue($this->userManager->setProfilePic($user, 'http://www.example.com/picture.jpg', '12345'));
  }

}
