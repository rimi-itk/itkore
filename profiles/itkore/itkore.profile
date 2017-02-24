<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function itkore_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['#submit'][] = 'itkore_form_install_configure_submit';
}

/**
 * Submission handler to setup site.
 */
function itkore_form_install_configure_submit($form, FormStateInterface $form_state) {
  // Set config variables
  \Drupal::service('config.factory')
    ->getEditable('system.theme')
    ->set('admin', 'adminimal_theme')
    ->save();

  \Drupal::service('config.factory')
    ->getEditable('system.theme')
    ->set('default', 'itkore_base')
    ->save();

  // Install custom modules.
  \Drupal::service('module_installer')
    ->install(['itkore_content_types']);

  \Drupal::service('module_installer')
    ->install(['itk_paragraph']);


  // aarhus.dk hacks
  \Drupal::service('module_installer')
    ->install(['itkore_intro', 'itkore_default_content']);

  \Drupal::service('module_installer')
    ->uninstall(['update', 'itk_cookie_message']);

  // Create Danish locale and set as default
  $langcode = 'da';
  \Drupal::service('module_installer')->install(['language']);
  if (!($language = entity_load('configurable_language', $langcode))) {
    // Create the language if not already shipped with a profile.
    $language = ConfigurableLanguage::createFromLangcode($langcode);
  }
  $language->save();

  \Drupal::service('language.default')->set($language);
}
