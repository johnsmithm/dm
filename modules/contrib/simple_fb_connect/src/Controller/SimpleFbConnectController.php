<?php

namespace Drupal\simple_fb_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\simple_fb_connect\SimpleFbConnectFbManager;
use Drupal\simple_fb_connect\SimpleFbConnectUserManager;
use Drupal\simple_fb_connect\SimpleFbConnectPostLoginManager;
use Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler;
use Drupal\simple_fb_connect\SimpleFbConnectFbFactory;

/**
 * Returns responses for Simple FB Connect module routes.
 */
class SimpleFbConnectController extends ControllerBase {

  protected $fbManager;
  protected $userManager;
  protected $postLoginManager;
  protected $persistentDataHandler;
  protected $fbFactory;

  /**
   * Constructor.
   *
   * The constructor parameters are passed from the create() method.
   *
   * @param \Drupal\simple_fb_connect\SimpleFbConnectFbManager $fb_manager
   *   SimpleFbConnectFbManager object.
   * @param \Drupal\simple_fb_connect\SimpleFbConnectUserManager $user_manager
   *   SimpleFbConnectUserManager object.
   * @param \Drupal\simple_fb_connect\SimpleFbConnectPostLoginManager $post_login_manager
   *   SimpleFbConnectPostLoginManager object.
   * @param \Drupal\simple_fb_connect\SimpleFbConnectPersistentDataHandler $persistent_data_handler
   *   SimpleFbConnectPersistentDataHandler object.
   * @param \Drupal\simple_fb_connect\SimpleFbConnectFbFactory $fb_factory
   *   SimpleFbConnectFbFactory object.
   */
  public function __construct(SimpleFbConnectFbManager $fb_manager, SimpleFbConnectUserManager $user_manager, SimpleFbConnectPostLoginManager $post_login_manager, SimpleFbConnectPersistentDataHandler $persistent_data_handler, SimpleFbConnectFbFactory $fb_factory) {
    $this->fbManager = $fb_manager;
    $this->userManager = $user_manager;
    $this->postLoginManager = $post_login_manager;
    $this->persistentDataHandler = $persistent_data_handler;
    $this->fbFactory = $fb_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_fb_connect.fb_manager'),
      $container->get('simple_fb_connect.user_manager'),
      $container->get('simple_fb_connect.post_login_manager'),
      $container->get('simple_fb_connect.persistent_data_handler'),
      $container->get('simple_fb_connect.fb_factory')
    );
  }

  /**
   * Response for path 'user/simple-fb-connect'.
   *
   * Redirects the user to FB for authentication.
   */
  public function redirectToFb() {
    // Try to get an instance of Facebook service.
    if (!$facebook = $this->fbFactory->getFbService()) {
      drupal_set_message($this->t('Simple FB Connect is not configured properly. Please contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->fbManager->setFacebookService($facebook);

    // Save post login path to session if it was set as a query parameter.
    if ($post_login_path = $this->postLoginManager->getPostLoginPathFromRequest()) {
      $this->postLoginManager->savePostLoginPath($post_login_path);
    }

    // Generate the URL where the user will be redirected for FB login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $fb_login_url = $this->fbManager->getFbLoginUrl();
    if ($this->persistentDataHandler->get('reprompt')) {
      $fb_login_url = $this->fbManager->getFbReRequestUrl();
    }

    return new TrustedRedirectResponse($fb_login_url);
  }

  /**
   * Response for path 'user/simple-fb-connect/return'.
   *
   * Facebook returns the user here after user has authenticated in FB.
   */
  public function returnFromFb() {
    // Try to get an instance of Facebook service.
    if (!$facebook = $this->fbFactory->getFbService()) {
      drupal_set_message($this->t('Simple FB Connect is not configured properly. Please contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Facebook service was returned, inject it to $fbManager.
    $this->fbManager->setFacebookService($facebook);

    // Read user's access token from Facebook.
    if (!$access_token = $this->fbManager->getAccessTokenFromFb()) {
      drupal_set_message($this->t('Facebook login failed.'), 'error');
      return $this->redirect('user.login');
    }

    // Check that user authorized our app to access user's email address.
    if (!$this->fbManager->checkPermission('email')) {
      if ($site_name = $this->config('system.site')->get('name')) {
        drupal_set_message($this->t('Facebook login failed. @site_name requires permission to get your email address from Facebook. Please try again and give the permission.', ['@site_name' => $site_name]), 'error');
      }
      else {
        drupal_set_message($this->t('Facebook login failed. This site requires permission to get your email address from Facebook. Please try again and give the permission.'), 'error');
      }
      $this->persistentDataHandler->set('reprompt', TRUE);
      return $this->redirect('user.login');
    }

    // Get user's FB profile from Facebook API.
    if (!$fb_profile = $this->fbManager->getFbProfile()) {
      drupal_set_message($this->t('Facebook login failed, Facebook profile could not be loaded. Please contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Get user's email from the FB profile.
    if (!$email = $this->fbManager->getEmail($fb_profile)) {
      drupal_set_message($this->t('Facebook login failed. This site requires an email address. Please add one in your Facebook profile and try again.'), 'error');
      return $this->redirect('user.login');
    }

    // Save access token to session so that event subscribers can call FB API.
    $this->persistentDataHandler->set('access_token', $access_token);

    // If we have an existing user with the same email address, try to log in.
    if ($drupal_user = $this->userManager->loadUserByProperty('mail', $email)) {
      if ($this->userManager->loginUser($drupal_user)) {
        // Redirect the user to post login path.
        return new RedirectResponse($this->postLoginManager->getPostLoginPath());
      }
      else {
        // Login was not successful. Unset access token from session.
        $this->persistentDataHandler->set('access_token', NULL);
        return $this->redirect('user.login');
      }
    }

    // If there was no existing user, try to create a new user.
    $fbid = $fb_profile->getField('id');
    $fb_profile_pic = $this->fbManager->getFbProfilePic();
    if ($drupal_user = $this->userManager->createUser($fb_profile->getField('name'), $email, $fbid, $fb_profile_pic)) {

      // Log the newly created user in.
      if ($this->userManager->loginUser($drupal_user)) {
        // Check if new users should be redirected to Drupal user form.
        if ($this->postLoginManager->getRedirectNewUsersToUserFormSetting()) {
          drupal_set_message($this->t("Please take a moment to confirm your account details. Since you logged in with Facebook, you don't need to update your password."));
          return new RedirectResponse($this->postLoginManager->getPathToUserForm($drupal_user));
        }

        // Use normal post login path if user wasn't redirected to user form.
        return new RedirectResponse($this->postLoginManager->getPostLoginPath());
      }

      else {
        // New user was created but the account is pending approval.
        // Unset access token from session.
        $this->persistentDataHandler->set('access_token', NULL);
        drupal_set_message($this->t('You will receive an email when a site administrator activates your account.'), 'warning');
        return $this->redirect('user.login');
      }
    }

    else {
      // User could not be created. Unset access token from session.
      $this->persistentDataHandler->set('access_token', NULL);
      return $this->redirect('user.login');
    }

    // This should never be reached, user should have been redirected already.
    $this->persistentDataHandler->set('access_token', NULL);
    throw new AccessDeniedHttpException();
  }

}
