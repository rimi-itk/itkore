<?php

/**
 * @file
 * Contains \Drupal\imagick\Plugin\ImageToolkit\Operation\imagick\Annotate
 */

namespace Drupal\imagick\Plugin\ImageToolkit\Operation\imagick;

use Imagick;
use ImagickPixel;
use ImagickDraw;

/**
 * Defines imagick annotate operation.
 *
 * @ImageToolkitOperation(
 *   id = "imagick_annotate",
 *   toolkit = "imagick",
 *   operation = "annotate",
 *   label = @Translation("Annotate"),
 *   description = @Translation("Annotates an image resource")
 * )
 */
class Annotate extends ImagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments() {
    return array(
      'text_fieldset' => array(
        'description' => 'Text settings.',
      ),
      'position_fieldset' => array(
        'description' => 'Position settings.',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = array()) {
    /* @var $res \Imagick */
    $res = $this->getToolkit()->getResource();

    $text = $arguments['text_fieldset'];
    $position = $arguments['position_fieldset'];

    $padding = array(
      'x' => $position['padding_x'],
      'y' => $position['padding_y'],
    );

    // Check if percent is used
    $percent_x = explode('%', $padding['x']);
    if (count($percent_x) == 2) {
      $padding['x'] = $res->getImageWidth() / 100 * reset($percent_x);
    }
    $percent_y = explode('%', $padding['y']);
    if (count($percent_y) == 2) {
      $padding['y'] = $res->getImageHeight() / 100 * reset($percent_y);
    }

    // Create new transparent layer
    $text_layer = new Imagick();
    $text_layer->newImage(
      $res->getImageWidth() - (2 * $padding['x']),
      $res->getImageHeight() - (2 * $padding['y']),
      new ImagickPixel('transparent')
    );

    // Font properties
    $draw = new ImagickDraw();
    $draw->setFont($text['font']);
    $draw->setFillColor($text['HEX']);
    $draw->setFontSize($text['size']);

    // Calculate text width and height
    $imagick = new Imagick();

    list($lines, $lineHeight) = $this::_image_imagick_word_wrap_annotation($imagick, $draw, $text['text'], $text_layer->getImageWidth());

    // Calcuate position
    $text_dimensions = $imagick->queryFontMetrics($draw, $text['text']);
    $text_height = count($lines) * $lineHeight;
    list($left, $top) = explode('-', $position['anchor']);

    $y = image_filter_keyword($top, $text_layer->getImageHeight(), $text_height);
    $y += ($text_dimensions['textHeight'] + $text_dimensions['descender']);

    foreach ($lines as $line) {
      $line_dimensions = $imagick->queryFontMetrics($draw, $line);

      $x = image_filter_keyword($left, $text_layer->getImageWidth(), $line_dimensions['textWidth']);
      $text_layer->annotateImage($draw, $x, $y, 0, $line);

      // Add lineheight for next line
      $y += $lineHeight;
    }

    return $res->compositeImage($text_layer, Imagick::COMPOSITE_OVER, $padding['x'], $padding['y']);
  }

  /**
   * Helper funtion to wrap text when it is to long
   */
  private function _image_imagick_word_wrap_annotation($image, $draw, $text, $maxWidth) {
    $text = trim($text);

    $words = preg_split('%\s%', $text, -1, PREG_SPLIT_NO_EMPTY);
    $lines = array();
    $i = 0;
    $lineHeight = 0;

    while (count($words) > 0)
    {
      $metrics = $image->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
      $lineHeight = max($metrics['textHeight'], $lineHeight);

      // check if we have found the word that exceeds the line width
      if ($metrics['textWidth'] > $maxWidth or count($words) < $i)
      {
        // handle case where a single word is longer than the allowed line width (just add this as a word on its own line?)
        if ($i == 1)
          $i++;

        $lines[] = implode(' ', array_slice($words, 0, --$i));
        $words = array_slice($words, $i);
        $i = 0;
      }
    }

    return array($lines, $lineHeight);
  }

}
