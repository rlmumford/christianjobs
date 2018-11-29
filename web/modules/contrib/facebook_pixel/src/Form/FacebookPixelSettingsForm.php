<?php

/**
 * @file
 * Contains \Drupal\facebook_pixel\Form\FacebookPixelSettingsForm.
 */

namespace Drupal\facebook_pixel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FacebookPixelSettingsForm.
 *
 * @package Drupal\Facebook\Pixel\Form
 */
class FacebookPixelSettingsForm extends ConfigFormBase {

  const CONFIG_NAME = 'facebook_pixel.settings';

    /**
     *
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'facebook_pixel_settings';
    }

    /**
     *
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
          self::CONFIG_NAME,
        ];
    }

    /**
     *
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('facebook_pixel.settings');

        $form['pixel_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Pixel ID'),
            '#description' => $this->t('Enter the Facebook Pixel ID'),
            '#default_value' => $config->get('pixel_id'),
            '#maxlength' => 64,
            '#size' => 64
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        // Retrieve the configuration
        $this->config('facebook_pixel.settings')
            ->
        // Set the submitted pixel_id setting
        set('pixel_id', $form_state->getValue('pixel_id'))
            ->save();

        parent::submitForm($form, $form_state);
  }
}
