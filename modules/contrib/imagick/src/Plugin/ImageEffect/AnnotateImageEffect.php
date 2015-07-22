<?php

/**
 * @file
 * Contains \Drupal\imagick\Plugin\ImageEffect\AnnotateImageEffect
 */

namespace Drupal\imagick\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Imagick;

/**
 * Annotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_annotate",
 *   label = @Translation("Annotate"),
 *   description = @Translation("Annotates an image resource.")
 * )
 */
class AnnotateImageEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->apply('annotate', $this->configuration)) {
      $this->logger->error('Image annotate failed using the %toolkit toolkit on %path (%mimetype)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'text_fieldset' => array(
        'text' => 'Annotation',
        'font' => 'Helvetica',
        'size' => 20,
        'HEX' => '#000000',
      ),
      'position_fieldset' => array(
        'anchor' => 'right-bottom',
        'padding_x' => 20,
        'padding_y' => 20,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('colorform'),
      ),
    );

    // Get fonts
    $imagick = new Imagick();
    $available_fonts = $imagick->queryFonts();

    $form['text_fieldset'] = array(
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#title' => t('Text'),

      'text' => array(
        '#type' => 'textfield',
        '#title' => t('Text'),
        '#description' => t('Text to annotate the image with.'),
        '#default_value' => $this->configuration['text_fieldset']['text'],
      ),
      'font' => array(
        '#type' => 'select',
        '#options' => array_combine($available_fonts, $available_fonts),
        '#title' => t('Font'),
        '#description' => t('Fonts that ImageMagick knows about.'),
        '#default_value' => $this->configuration['text_fieldset']['font'],
      ),
      'size' => array(
        '#type' => 'textfield',
        '#title' => t('Font size'),
        '#default_value' => $this->configuration['text_fieldset']['size'],
        '#size' => 3,
      ),
    );
    $form['text_fieldset']['HEX'] = array(
      '#type' => 'textfield',
      '#title' => t('HEX'),
      '#default_value' => $this->configuration['text_fieldset']['HEX'],
      '#element_validate' => array('imagecache_rgb_validate'),
      '#attributes' => array(
        'class' => array('colorentry'),
      ),
    );
    $form['text_fieldset']['colorpicker'] = array(
      '#weight' => -1,
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('colorpicker'),
        'style' => array('float:right'),
      ),
    );

    // Add Farbtastic color picker.
    $form['text_fieldset']['matte_color']['#attached'] = array(
      'library' => array('imagick/colorpicker'),
    );
    $form['position_fieldset'] = array(
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#title' => t('Position'),

      'anchor' => array(
        '#type' => 'radios',
        '#title' => t('Anchor'),
        '#options' => array(
          'left-top'      => t('Top left'),
          'center-top'    => t('Top center'),
          'right-top'     => t('Top right'),
          'left-center'   => t('Center left'),
          'center-center' => t('Center'),
          'right-center'  => t('Center right'),
          'left-bottom'   => t('Bottom left'),
          'center-bottom' => t('Bottom center'),
          'right-bottom'  => t('Bottom right'),
        ),
        '#theme' => 'image_anchor',
        '#default_value' => $this->configuration['position_fieldset']['anchor'],
      ),
      'padding_x' => array(
        '#type' => 'textfield',
        '#title' => t('Padding X'),
        '#default_value' => $this->configuration['position_fieldset']['padding_x'],
        '#description' => t('Enter a value in pixels or percent'),
        '#size' => 3,
      ),
      'padding_y' => array(
        '#type' => 'textfield',
        '#title' => t('Padding Y'),
        '#default_value' => $this->configuration['position_fieldset']['padding_y'],
        '#description' => t('Enter a value in pixels or percent'),
        '#size' => 3,
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['text_fieldset']['text'] = $form_state->getValue(['text_fieldset', 'text']);
    $this->configuration['text_fieldset']['font'] = $form_state->getValue(['text_fieldset', 'font']);
    $this->configuration['text_fieldset']['size'] = $form_state->getValue(['text_fieldset', 'size']);
    $this->configuration['text_fieldset']['HEX'] = $form_state->getValue(['text_fieldset', 'HEX']);

    $this->configuration['position_fieldset']['anchor'] = $form_state->getValue(['position_fieldset', 'anchor']);
    $this->configuration['position_fieldset']['padding_x'] = $form_state->getValue(['position_fieldset', 'padding_x']);
    $this->configuration['position_fieldset']['padding_y'] = $form_state->getValue(['position_fieldset', 'padding_y']);
  }

}
