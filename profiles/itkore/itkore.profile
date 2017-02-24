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

  itkore_aarhus_dk_hacks();
}

function itkore_aarhus_dk_hacks() {
  // aarhus.dk hacks
  \Drupal::service('module_installer')
    ->install(['itkore_intro', 'itkore_default_content']);

  \Drupal::service('module_installer')
    ->uninstall(['update']);

  // Create Danish locale
  $langcode = 'da';
  \Drupal::service('module_installer')->install(['language']);
  if (!($language = entity_load('configurable_language', $langcode))) {
    // Create the language if not already shipped with a profile.
    $language = ConfigurableLanguage::createFromLangcode($langcode);
  }
  $language->save();

  // Set default language
  \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', $langcode)->save();

  // Import translations
  $file = (object)[
    'uri' => drupal_get_path('profile', 'itkore') . '/translations/itkore.da.po',
    'langcode' => 'da',
  ];
  $options = [];
  $result = \Drupal\locale\Gettext::fileToDatabase($file, $options);

  $role = 'editor';
  $username = 'editor';
  $email = 'editor@example.com';
  $password = 'editor2017';

  // Create editor user.
  $editor = user_load_by_name($username);
  if (!$editor) {
    $editor = \Drupal\user\Entity\User::create();
    $editor->setUsername($username);
    $editor->setEmail($email);
    $editor->setPassword($password);
    $editor->addRole($role);
    $editor->activate();
    $editor->set('langcode', $langcode);
    $editor->set('preferred_langcode', $langcode);
    $editor->save();
  }

  // Set admin user default language.
  $admin = user_load_by_name('admin');
  if ($admin) {
    $admin->set('langcode', $langcode);
    $admin->set('preferred_langcode', $langcode);
    $admin->save();
  }
}
