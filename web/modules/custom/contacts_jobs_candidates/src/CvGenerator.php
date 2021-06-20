<?php

namespace Drupal\contacts_jobs_candidates;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\pdf_tools\PDFGeneratorInterface;
use Drupal\user\UserInterface;

/**
 * The CV Generator.
 */
class CvGenerator {

  /**
   * The PDF generator.
   *
   * @var \Drupal\pdf_tools\PDFGeneratorInterface
   */
  protected $pdf;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  private $themeManager;

  /**
   * Constructs a CvGenerator object.
   *
   * @param \Drupal\pdf_tools\PDFGeneratorInterface $pdf
   *   The PDF generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(PDFGeneratorInterface $pdf, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->pdf = $pdf;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Build the CV render array.
   *
   * @param \Drupal\user\UserInterface $candidate
   *   The user entity.
   *
   * @return array
   *   The render array, containing the header and content keys.
   */
  public function build(UserInterface $candidate): array {
    /** @var \Drupal\profile\Entity\ProfileInterface $candidate_profile */
    $candidate_profile = $candidate->get('profile_candidate')->entity;
    $individual_profile = $candidate->get('profile_crm_indiv')->entity;

    $build['header'] = [
      '#theme' => 'cv_pdf_header',
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => new TranslatableMarkup('CV/Resume'),
      ],
      'name' => [
        '#plain_text' => $candidate->label(),
      ],
      'mail' => [
        '#plain_text' => $candidate->getEmail(),
      ],
      'phone' => $candidate_profile && !$candidate_profile->get('phone_number')->isEmpty() ? $candidate_profile->get('phone_number')->first()->view() : NULL,
      '#candidate' => $candidate,
      '#attached' => ['library' => ['contacts_jobs_candidates/cv']],
    ];

    if ($candidate_profile) {
      $build['content'] = $this->entityTypeManager
        ->getViewBuilder('profile')
        ->view($candidate_profile, 'cj_cv');
      $build['content']['#theme'] = 'profile__cj_cv__pdf';
      $build['content']['#attached']['library'][] = 'contacts_jobs_candidates/cv';
    }

    return $build;
  }

  /**
   * Generate a PDF version of a candidate's CV.
   *
   * @param \Drupal\user\UserInterface $candidate
   *   The candidate user entity.
   *
   * @return string|null
   *   The path to the PDF, or NULL if unable to generate.
   *
   * @throws \Drupal\pdf_tools\PDFGenerationException
   *   Thrown when PDF generation fails.
   */
  public function generatePdf(UserInterface $candidate): string {
    $build = $this->build($candidate);
    $build['content']['#pdf_title'] = new TranslatableMarkup('CV/Resume');

    $options = [
      'page-size' => 'A4',
      'encoding' => 'UTF-8',
      'margin-top' => '15mm',
      'margin-bottom' => '10mm',
      'margin-left' => '0mm',
      'margin-right' => '0mm',
      'header-type' => 'elements',
      'header-elements' => $build['header'],
      'header-right' => "'[page] / [toPage]'",
      'print-media-type' => TRUE,
    ];

    $this->moduleHandler->alter('contacts_jobs_candidates_cv_pdf', $build, $options);
    $this->themeManager->alter('contacts_jobs_candidates_cv_pdf', $build, $options);

    return $this->pdf->renderArrayToPdf($build['content'], $options);
  }

}
