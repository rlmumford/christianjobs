<?php

namespace Drupal\job_application;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\job_application\Annotation\ApplicationQuestionAnswerType;
use Drupal\job_application\Plugin\ApplicationQuestionAnswerType\ApplicationQuestionAnswerTypeInterface;

class ApplicationQuestionAnswerTypeManager extends DefaultPluginManager {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/ApplicationQuestionAnswerType',
      $namespaces,
      $module_handler,
      ApplicationQuestionAnswerTypeInterface::class,
      ApplicationQuestionAnswerType::class
    );

    $this->alterInfo('job_application_question_answer_type');
    $this->setCacheBackend(
      $cache_backend,
      'job_application_question_answer_type'
    );
  }
}
