<?php

namespace Drupal\job_board\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class ExecSearchInitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'executive_search_initiate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'row';
    $form['blurb'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-xs-12', 'col-md-8'],
      ],
      'p1' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => '<strong>Finding the key talent for your organisation can be daunting.</strong><br> Where do you look? How do you know you have searched thoroughly? Where do you find the time to search properly? How do you contact these great prospects? Will the right candidates even be open to talking to you? How do you manage negotiations in a delicate manner?',
      ],
      'p2' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'With more than 12 years of executive search experience across both non and for-profit industries this is where ChristianJobs.co.uk can help. We know how to manage the delicate and challenging search process that results in the best appointment for your organisation.',
      ],
      'p3' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Executive campaigns allow our team to search, source and secure the best candidates for your organisation in a proactive and supportive manner.',
      ],
      'p4' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => 'Our efficient and effective multistep approach begins long before the actual search itself. We are committed to our clients throughout every step of the executive search journey and our deliberate and structured search process is proven in attaining a successful outcome.',
      ],
      
      'h4' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => new TranslatableMarkup('What\'s Included:'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => ['spaced'],
        ],
        '#items' => [
          [
            '#wrapper_attributes' => [
              'class' => ['item', 'item-spaced'],
            ],
            '#children' => 'Free 30-day featured advertising on ChristianJobs.co.uk (with option to extend to 60 days).',
          ],
          [
            '#wrapper_attributes' => [
              'class' => ['item'],
            ],
            '#children' => 'Promoted social media advertising',
          ],
          [
            '#wrapper_attributes' => [
              'class' => ['item'],
            ],
            '#children' => 'Christian Jobs director engagement & dedicated account manager',
          ],
          [
            '#wrapper_attributes' => [
              'class' => ['item'],
            ],
            '#children' => 'Outsourced application management',
          ],
          [
            '#wrapper_attributes' => [
              'class' => ['item'],
            ],
            '#children' => 'Thomas PPA behavioural assessment for final stage candidates',
          ],
          [
            '#wrapper_attributes' => [
              'class' => ['item'],
            ],
            '#children' => 'Six month placement guarantee',
          ],
        ]
      ]
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['col-xs-12', 'col-md-4', 'card', 'card-form'],
      ],
    ];
    $form['container']['title'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['card-item', 'card-title'],
      ],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => new TranslatableMarkup('Request a Callback'),
      ],
      'subtitle' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['subtitle'],
        ],
        '#value' => new TranslatableMarkup('Please fill out the form below and one of our account managers will be in touch with you shortly.'),
      ]
    ];
    $form['container']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Enter your name'),
      '#required' => TRUE,
    ];
    $form['container']['organisation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation'),
    ];
    $form['container']['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
    ];
    $form['container']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];
    $form['container']['position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role Title'),
      '#required' => TRUE,
    ];
    $form['container']['position_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Role Description'),
      '#required' => TRUE,
    ];
    $form['container']['callback_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback Time'),
      '#description' => $this->t('What is the best time for us to call you back about this placement?'),
      '#required' => TRUE,
    ];

    $form['container']['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['card-item', 'card-actions', 'divider-top']
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Request Callback'),
      ],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $mail_manager->mail('job_board', 'new_exec_search_req', 'info@christianjobs.co.uk', 'en', $form_state->getValues(), NULL, TRUE);

    \Drupal::messenger()->addStatus(new TranslatableMarkup('Thank you for your interest. An agent will be in touch shortly.'));
  }
}
