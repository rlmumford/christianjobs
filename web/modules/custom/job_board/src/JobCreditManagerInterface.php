<?php

namespace Drupal\job_board;

use Drupal\organization\Entity\Organization;

interface JobCreditManagerInterface {

  public function hasAvailableCredit(Organization $organization);

  public function getAvailableCredit(Organization $organization);

}
