<?php

namespace Drupal\contacts_jobs_dashboard\EventSubscriber;

use Drupal\contacts_jobs_dashboard\UserHelper;
use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\social_auth\Event\BeforeRedirectEvent;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\UserEvent;
use Drupal\social_auth\SocialAuthDataHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to handle social authentication.
 */
class SocialAuthSubscriber implements EventSubscriberInterface {

  /**
   * The key in the social auth data storage for registration type.
   */
  protected const USER_REG_TYPE_KEY = 'user_reg_type';

  /**
   * The user helper service.
   *
   * @var \Drupal\contacts_jobs_dashboard\UserHelper
   */
  protected $userHelper;

  /**
   * The social auth data handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  protected $dataHandler;

  /**
   * SocialAuthSubscriber constructor.
   *
   * @param \Drupal\contacts_jobs_dashboard\UserHelper $user_helper
   *   The user helper service.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   The social auth data handler.
   */
  public function __construct(UserHelper $user_helper, SocialAuthDataHandler $data_handler) {
    $this->userHelper = $user_helper;
    $this->dataHandler = $data_handler;
  }

  /**
   * Before redirection for social auth.
   *
   * Capture the relevant registration type.
   *
   * @param \Drupal\social_auth\Event\BeforeRedirectEvent $event
   *   The redirect event.
   */
  public function onBeforeRedirect(BeforeRedirectEvent $event) {
    $destination = $event->getDestination();
    $registration_type = $this->userHelper->getDefaultRegistrationType($destination);
    $event->getDataHandler()->set(self::USER_REG_TYPE_KEY, $registration_type);

    // If the registration type is NULL, we may need to get the user to choose,
    // so set an explicit redirect to our post login page.
    if ($registration_type === NULL) {
      $options = [];
      if ($destination) {
        $options['query']['destination'] = $destination;
      }
      $url = Url::fromRoute(
        'contacts_jobs_dashboard.user_type_selection',
        [],
        $options,
      );
      $this->dataHandler->set('login_destination', $url->toString());
    }
  }

  /**
   * When a user is created via social auth.
   *
   * Add the relevant role, if we have one.
   *
   * @param \Drupal\social_auth\Event\UserEvent $event
   *   The user event.
   */
  public function onUserCreated(UserEvent $event) {
    $user = $event->getUser();
    $user->addRole('crm_indiv');

    if ($type = $this->dataHandler->get(self::USER_REG_TYPE_KEY)) {
      $user->addRole($type);
    }

    $user->save();

    // Populate details on the profile.
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $user->get('profile_crm_indiv')->entity ??
      Profile::create(['type' => 'crm_indiv'])->setOwner($user);

    $social_user = $event->getSocialAuthUser();

    if ($profile->hasField('crm_name')) {
      if ($profile->get('crm_name')->isEmpty()) {
        $name = array_filter([
          'given' => $social_user->getFirstName(),
          'family' => $social_user->getLastName(),
        ]);

        if (count($name) !== 2) {
          $parts = explode(' ', $social_user->getName());
          $name += [
            'given' => $parts[0],
            'middle' => implode(' ', array_slice($parts, 1, -1)),
            'family' => end($parts),
          ];
        }

        $profile->set('crm_name', $name);
      }
    }

    $profile->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SocialAuthEvents::BEFORE_REDIRECT => ['onBeforeRedirect'],
      SocialAuthEvents::USER_CREATED => ['onUserCreated'],
    ];
  }

}
