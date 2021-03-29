<?php

namespace Drupal\contacts_jobs_credits;

use Drupal\contacts_jobs_credits\Entity\JobCredit;
use Drupal\user\Entity\User;

/**
 * Interface for the job credit manager service.
 *
 * @package Drupal\contacts_jobs_credits
 */
interface JobCreditManagerInterface {

  /**
   * Check whether the user has available credit.
   *
   * @param \Drupal\user\Entity\User $poster
   *   The organisation posting the job.
   *
   * @return bool
   *   True if the organisation hast credit, false otherwise.
   */
  public function hasAvailableCredit(User $poster) : bool;

  /**
   * Get an available credit to cover this job.
   *
   * @param \Drupal\user\Entity\User $poster
   *   The organisation posting the job.
   *
   * @return \Drupal\contacts_jobs_credits\Entity\JobCredit|NULL
   *   The job credit entity that is available.
   */
  public function getAvailableCredit(User $poster) : ?JobCredit;

}
