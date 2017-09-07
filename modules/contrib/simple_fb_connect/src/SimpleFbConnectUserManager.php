<?php

namespace Drupal\simple_fb_connect;

use Facebook\GraphNodes\GraphNode;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Transliteration\PhpTransliteration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Component\Utility\Unicode;

/**
 * Contains all logic that is related to Drupal user management.
 */
class SimpleFbConnectUserManager {
  use StringTranslationTrait;

  protected $configFactory;
  protected $loggerFactory;
  protected $eventDispatcher;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $token;
  protected $transliteration;
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\Core\TranslationInterface $string_translation
   *   Used for translating strings in UI messages.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Used for dispatching events to other modules.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Used for loading and creating Drupal user objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Used for access Drupal user field definitions.
   * @param \Drupal\Core\Utility\Token $token
   *   Used for token support in Drupal user picture directory.
   * @param \Drupal\Core\Transliteration\PhpTransliteration $transliteration
   *   Used for user picture directory and file transiliteration.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Used for detecting the current UI language.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Token $token, PhpTransliteration $transliteration, LanguageManagerInterface $language_manager) {
    $this->configFactory      = $config_factory;
    $this->loggerFactory      = $logger_factory;
    $this->stringTranslation  = $string_translation;
    $this->eventDispatcher    = $event_dispatcher;
    $this->entityTypeManager  = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->token              = $token;
    $this->transliteration    = $transliteration;
    $this->languageManager    = $language_manager;
  }

  /**
   * Loads existing Drupal user object by given property and value.
   *
   * Note that first matching user is returned. Email address and account name
   * are unique so there can be only zero ore one matching user when
   * loading users by these properties.
   *
   * @param string $field
   *   User entity field to search from.
   * @param string $value
   *   Value to search for.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if found
   *   False otherwise
   */
  public function loadUserByProperty($field, $value) {
    $users = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties([$field => $value]);

    if (!empty($users)) {
      return current($users);
    }

    // If user was not found, return FALSE.
    return FALSE;
  }

  /**
   * Create a new user account.
   *
   * @param string $name
   *   User's name on Facebook.
   * @param string $email
   *   User's email address.
   * @param int $fbid
   *   User's Facebook ID.
   * @param \Facebook\GraphNodes\GraphNode $fb_profile_pic
   *   GraphNode object representing user's Facebook profile picture.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if user was created
   *   False otherwise
   */
  public function createUser($name, $email, $fbid, GraphNode $fb_profile_pic) {
    // Make sure we have everything we need.
    if (!$name || !$email || !$fb_profile_pic) {
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Failed to create user. Name: @name, email: @email', ['@name' => $name, '@email' => $email]);
      $this->drupalSetMessage($this->t('Error while creating user account. Please contact site administrator.'), 'error');
      return FALSE;
    }

    // Check if site configuration allows new users to register.
    if ($this->registrationBlocked()) {

      $this->loggerFactory
        ->get('simple_fb_connect')
        ->warning('Failed to create user. User registration is disabled in Drupal account settings. Name: @name, email: @email.', ['@name' => $name, '@email' => $email]);

      $this->drupalSetMessage($this->t('Only existing users can log in with Facebook. Contact system administrator.'), 'error');
      return FALSE;
    }

    // Set up the user fields.
    // - Username will be user's name on Facebook.
    // - Password can be very long since the user doesn't see this.
    // There are three different language fields.
    // - preferred_language
    // - preferred_admin_langcode
    // - langcode of the user entity i.e. the language of the profile fields
    // - We use the same logic as core and populate the current UI language to
    //   all of these. Other modules can subscribe to the triggered event and
    //   change the languages if they will.
    // Get the current UI language.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $fields = [
      'name' => $this->generateUniqueUsername($name),
      'mail' => $email,
      'init' => $email,
      'pass' => $this->userPassword(32),
      'status' => $this->getNewUserStatus(),
      'langcode' => $langcode,
      'preferred_langcode' => $langcode,
      'preferred_admin_langcode' => $langcode,
    ];

    // Check if user's picture should be downloaded from FB. We don't download
    // the default silhouette unless Drupal user picture is a required field.
    $file = FALSE;
    $is_silhouette = (bool) $fb_profile_pic->getField('is_silhouette');
    if ($this->userPictureEnabled() && ($this->userPictureRequired() || !$is_silhouette)) {
      $file = $this->downloadProfilePic($fb_profile_pic->getField('url'), $fbid);
      if (!$file) {
        $this->loggerFactory
          ->get('simple_fb_connect')
          ->error('Failed to create user. Profile picture could not be downloaded. Name: @name, email: @email', ['@name' => $name, '@email' => $email]);
        $this->drupalSetMessage($this->t('Error while creating user account. Please contact site administrator.'), 'error');
        return FALSE;
      }
      $file->save();
      $fields['user_picture'] = $file->id();
    }

    // Create new user account.
    $new_user = $this->entityTypeManager
      ->getStorage('user')
      ->create($fields);

    // Dispatch an event so that other modules can react to the user creation.
    // Set the account twice on the event: as the main subject but also in the
    // list of arguments.
    $event = new GenericEvent($new_user, ['account' => $new_user, 'fbid' => $fbid]);
    $this->eventDispatcher->dispatch('simple_fb_connect.user_created', $event);

    // Validate the new user.
    $violations = $new_user->validate();
    if (count($violations) > 0) {
      $property = $violations[0]->getPropertyPath();
      $msg      = $violations[0]->getMessage();
      $this->drupalSetMessage($this->t('Error while creating user account. Please contact site administrator.'), 'error');
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not create new user, validation failed. Property: @property. Message: @message', ['@property' => $property, '@message' => $msg]);
      return FALSE;
    }

    // Try to save the new user account.
    try {
      $new_user->save();

      $this->loggerFactory
        ->get('simple_fb_connect')
        ->notice('New user created. Username @username, UID: @uid', ['@username' => $new_user->getAccountName(), '@uid' => $new_user->id()]);

      $this->drupalSetMessage($this->t('New user account %username created.', ['%username' => $new_user->getAccountName()]));

      // Set the owner of the profile picture file if it was downloaded.
      if ($file) {
        $file->setOwner($new_user);
        $file->save();
      }
      return $new_user;
    }

    catch (EntityStorageException $ex) {
      $this->drupalSetMessage($this->t('Creation of user account failed. Please contact site administrator.'), 'error');
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not create new user. Exception: @message', ['@message' => $ex->getMessage()]);
    }

    return FALSE;
  }

  /**
   * Logs the user in.
   *
   * @todo Add Boost integtraion when Boost is available for D8
   *   https://www.drupal.org/node/2524372
   *
   * @param \Drupal\user\Entity\User $drupal_user
   *   User object.
   *
   * @return bool
   *   True if login was successful
   *   False if the login was blocked
   */
  public function loginUser(User $drupal_user) {
    // Prevent admin login if defined in module settings.
    if ($this->loginDisabledForAdmin($drupal_user)) {
      $this->drupalSetMessage($this->t('Facebook login is disabled for site administrator. Login with your local user account.'), 'error');
      return FALSE;
    }

    // Prevent login if user has one of the roles defined in module settings.
    if ($this->loginDisabledByRole($drupal_user)) {
      $this->drupalSetMessage($this->t('Facebook login is disabled for your role. Please login with your local user account.'), 'error');
      return FALSE;
    }

    // Check that the account is active and log the user in.
    if ($drupal_user->isActive()) {
      $this->userLoginFinalize($drupal_user);

      // Dispatch an event so that other modules can react to the user login.
      // Set the account twice on the event: as the main subject but also in the
      // list of arguments.
      $event = new GenericEvent($drupal_user, ['account' => $drupal_user]);
      $this->eventDispatcher->dispatch('simple_fb_connect.user_login', $event);

      // TODO: Add Boost cookie if Boost module is enabled
      // https://www.drupal.org/node/2524372
      return TRUE;
    }

    // If we are still here, account is blocked.
    $this->drupalSetMessage($this->t('You could not be logged in because your user account %username is not active.', ['%username' => $drupal_user->getAccountName()]), 'warning');
    $this->loggerFactory
      ->get('simple_fb_connect')
      ->warning('Facebook login for user @user prevented. Account is blocked.', ['@user' => $drupal_user->getAccountName()]);
    return FALSE;
  }

  /**
   * Checks if user registration is blocked in Drupal account settings.
   *
   * @return bool
   *   True if registration is blocked
   *   False if registration is not blocked
   */
  protected function registrationBlocked() {
    // Check if Drupal account registration settings is Administrators only.
    if ($this->configFactory
      ->get('user.settings')
      ->get('register') == 'admin_only') {
      return TRUE;
    }

    // If we didnt' return TRUE already, registration is not blocked.
    return FALSE;
  }

  /**
   * Ensures that Drupal usernames will be unique.
   *
   * Drupal usernames will be generated so that the user's full name on Facebook
   * will become user's Drupal username. This method will check if the username
   * is already used and appends a number until it finds the first available
   * username.
   *
   * @param string $fb_name
   *   User's full name on Facebook.
   *
   * @return string
   *   Unique username
   */
  protected function generateUniqueUsername($fb_name) {
    // Truncate to max length. We use hard coded length because using
    // USERNAME_MAX_LENGTH cause unit tests to fail.
    $max_length = 60;
    $fb_name = Unicode::substr($fb_name, 0, $max_length);

    // Add a trailing number if needed to make username unique.
    $base = $fb_name;
    $i = 1;
    $candidate = $base;
    while ($this->loadUserByProperty('name', $candidate)) {
      $i++;
      // Calculate max length for $base and truncate if needed.
      $max_length_base = $max_length - strlen((string) $i) - 1;
      $base = Unicode::substr($base, 0, $max_length_base);
      $candidate = $base . " " . $i;
    }

    // Trim leading and trailing whitespace.
    $candidate = trim($candidate);

    // Remove multiple spacebars from the username if needed.
    $candidate = preg_replace('/ {2,}/', ' ', $candidate);

    return $candidate;
  }

  /**
   * Returns the status for new users.
   *
   * @return int
   *   Value 0 means that new accounts remain blocked and require approval.
   *   Value 1 means that visitors can register new accounts without approval.
   */
  protected function getNewUserStatus() {
    if ($this->configFactory
      ->get('user.settings')
      ->get('register') == 'visitors') {
      return 1;
    }

    return 0;
  }

  /**
   * Checks if current user is admin and admin login via FB is disabled.
   *
   * @param \Drupal\user\Entity\User $drupal_user
   *   User object.
   *
   * @return bool
   *   True if current user is admin and admin login via fB is disabled.
   *   False otherwise.
   */
  protected function loginDisabledForAdmin(User $drupal_user) {
    // Check if current user is admin.
    if ($drupal_user->id() == 1) {

      // Check if admin FB login is disabled.
      if ($this->configFactory
        ->get('simple_fb_connect.settings')
        ->get('disable_admin_login')) {

        $this->loggerFactory
          ->get('simple_fb_connect')
          ->warning('Facebook login for user @user prevented. Facebook login for site administrator (user 1) is disabled in module settings.', ['@user' => $drupal_user->getAccountName()]);
        return TRUE;
      }
    }

    // User is not admin or admin login is not disabled.
    return FALSE;
  }

  /**
   * Checks if the user has one of the "FB login disabled" roles.
   *
   * @param \Drupal\user\Entity\User $drupal_user
   *   User object.
   *
   * @return bool
   *   True if login is disabled for one of this user's role
   *   False if login is not disabled for this user's roles
   */
  protected function loginDisabledByRole(User $drupal_user) {
    // Read roles that are blocked from module settings.
    $disabled_roles = $this->configFactory
      ->get('simple_fb_connect.settings')
      ->get('disabled_roles');

    // Filter out allowed roles. Allowed roles have have value "0".
    // "0" evaluates to FALSE so second parameter of array_filter is omitted.
    $disabled_roles = array_filter($disabled_roles);

    // Loop through all roles the user has.
    foreach ($drupal_user->getRoles() as $role) {
      // Check if FB login is disabled for this role.
      if (array_key_exists($role, $disabled_roles)) {
        $this->loggerFactory
          ->get('simple_fb_connect')
          ->warning('Facebook login for user @user prevented. Facebook login for role @role is disabled in module settings.', ['@user' => $drupal_user->getAccountName(), '@role' => $role]);
        return TRUE;
      }
    }

    // FB login is not disabled for any of the user's roles.
    return FALSE;
  }

  /**
   * Downloads and sets user profile picture.
   *
   * @param User $drupal_user
   *   User object to update the profile picture for.
   * @param string $picture_url
   *   Absolute URL where the picture will be downloaded from.
   * @param string $fbid
   *   User's Facebook ID.
   *
   * @deprecated This method is deprecated as of 8.x-3.1 when the logic of this
   * method was moved to method createUser because the user creation failed when
   * user_picture was required field.
   *
   * @return bool
   *   True if picture was successfully set.
   *   False otherwise.
   */
  public function setProfilePic(User $drupal_user, $picture_url, $fbid) {
    // Try to download the profile picture and add it to user fields.
    if ($this->userPictureEnabled()) {
      if ($file = $this->downloadProfilePic($picture_url, $fbid)) {
        // Set the owner of the file to be the Drupal user.
        $file->setOwner($drupal_user);
        $file->save();

        // Set user's profile picture and save user.
        $drupal_user->set('user_picture', $file->id());
        $drupal_user->save();
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Downloads the profile picture to Drupal filesystem.
   *
   * @param string $picture_url
   *   Absolute URL where to download the profile picture.
   * @param string $fbid
   *   Facebook ID of the user.
   *
   * @return \Drupal\file\FileInterface|false
   *   FileInterface object if file was succesfully downloaded
   *   False otherwise
   */
  protected function downloadProfilePic($picture_url, $fbid) {
    // Make sure that we have everything we need.
    if (!$picture_url || !$fbid) {
      return FALSE;
    }

    // Determine target directory.
    $scheme = $this->configFactory
      ->get('system.file')
      ->get('default_scheme');
    $file_directory = $this->getPictureDirectory();
    if (!$file_directory) {
      return FALSE;
    }
    $directory = $scheme . '://' . $file_directory;

    // Replace tokens.
    $directory = $this->token->replace($directory);

    // Transliterate directory name.
    $directory = $this->transliteration->transliterate($directory, 'en', '_', 50);

    if (!$this->filePrepareDirectory($directory, 1)) {
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not save FB profile picture. Directory is not writeable: @directory', ['@directory' => $directory]);
      return FALSE;
    }

    // Generate filename and transliterate. FB API always serves JPG.
    $filename = $this->transliteration->transliterate($fbid . '.jpg', 'en', '_', 50);

    $destination = $directory . '/' . $filename;

    // Download the picture to local filesystem.
    if (!$file = $this->systemRetrieveFile($picture_url, $destination, TRUE, 1)) {
      $this->loggerFactory
        ->get('simple_fb_connect')
        ->error('Could not download Facebook profile picture from url: @url', ['@url' => $picture_url]);
      return FALSE;
    }

    return $file;
  }

  /**
   * Returns whether this site supports the default user picture feature.
   *
   * We use this method instead of the procedural user_pictures_enabled()
   * so that we can unit test our own methods.
   *
   * @return bool
   *   True if user pictures are enabled
   *   False otherwise
   */
  protected function userPictureEnabled() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    return isset($field_definitions['user_picture']);
  }

  /**
   * Returns picture directory if site supports the user picture feature.
   *
   * @return string|bool
   *   Directory for user pictures if site supports user picture feature.
   *   False otherwise.
   */
  protected function getPictureDirectory() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    if (isset($field_definitions['user_picture'])) {
      return $field_definitions['user_picture']->getSetting('file_directory');
    }
    return FALSE;
  }

  /**
   * Checks if user pictures are enabled and required.
   *
   * @return bool
   *   True if user pictures are enabled and field is required.
   *   False otherwise.
   */
  protected function userPictureRequired() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    if (isset($field_definitions['user_picture'])) {
      return $field_definitions['user_picture']->get('required');
    }
    // If user_picture field is not defined, it is not required.
    return FALSE;
  }

  /**
   * Wrapper for file_prepare_directory.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @see file_prepare_directory
   */
  protected function filePrepareDirectory(&$directory, $options) {
    return file_prepare_directory($directory, $options);
  }

  /**
   * Wrapper for system_retrieve_file.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @see system_retrieve_file
   */
  protected function systemRetrieveFile($url, $destination, $managed, $replace) {
    return system_retrieve_file($url, $destination, $managed, $replace);
  }

  /**
   * Wrapper for drupal_set_message.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @see drupal_set_message
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    return drupal_set_message($message, $type, $repeat);
  }

  /**
   * Wrapper for user_password.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @see user_password
   */
  protected function userPassword($length) {
    return user_password($length);
  }

  /**
   * Wrapper for user_login_finalize.
   *
   * We need to wrap the legacy procedural Drupal API functions so that we are
   * not using them directly in our own methods. This way we can unit test our
   * own methods.
   *
   * @see user_password
   */
  protected function userLoginFinalize(UserInterface $account) {
    return user_login_finalize($account);
  }

}
