<?php

namespace Drupal\contacts_jobs_dashboard;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contacts Jobs Dashboard user helper service.
 */
class UserHelper {

  protected const RECRUITER_PATHS = [
    '/job/post',
    '/recruiter',
    '/recruiter/*',
  ];

  protected const CANDIDATE_PATHS = [
    '/job/*/apply',
  ];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Construct the user helper.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(RequestStack $request_stack, PathMatcherInterface $path_matcher) {
    $this->requestStack = $request_stack;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Get the default registration type based.
   *
   * Looks in query parameters, including the destination, to work out the
   * default registration type.
   *
   * @return string|null
   *   The registration type, either 'recruiter' or 'candidate' or NULL if
   *   we can't work it out.
   */
  public function getDefaultRegistrationType(string $destination = NULL): ?string {
    // See if there is an explicit setting in the query.
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($query->has('register')) {
      $type = $query->get('register');
      if (in_array($type, ['recruiter', 'candidate'])) {
        return $type;
      }
    }

    // See if we can sniff the type from the destination.
    $destination = $this->getDestination($destination);
    if ($destination) {
      if ($this->pathMatcher->matchPath($destination, implode("\n", self::RECRUITER_PATHS))) {
        return 'recruiter';
      }
      elseif ($this->pathMatcher->matchPath($destination, implode("\n", self::CANDIDATE_PATHS))) {
        return 'candidate';
      }
    }

    // Otherwise the user will need to select.
    return NULL;
  }

  /**
   * Get the post registration destination for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string|null $destination
   *   Any explicit destination.
   *
   * @return \Drupal\Core\Url|null
   *   The destination URL, if there is one.
   */
  public function getRegistrationDestination(UserInterface $user, string $destination = NULL): ?Url {
    // If there is already a destination set, return that.
    if ($destination = $this->getDestination($destination)) {
      return Url::fromUserInput($destination);
    }

    // Otherwise get a default from the role.
    if ($user->hasRole('recruiter')) {
      return Url::fromRoute('contacts_jobs_dashboard.recruiter_organisation');
    }
    if ($user->hasRole('candidate')) {
      return Url::fromRoute('contacts_jobs_candidates.personal_profile', ['user' => $user->id()]);
    }

    return NULL;
  }

  /**
   * Get the destination.
   *
   * @param string|null $destination
   *   A known destination.
   *
   * @return string|null
   *   The destination, if there is one.
   */
  protected function getDestination(?string $destination): ?string {
    // If we don't have a destination, see if there is one in the query.
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($destination === NULL && $query->has('destination')) {
      $destination = $query->get('destination');
    }
    return $destination;
  }

}
