<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Rectangle;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\Rectangle
 * @group Image
 */
class RectangleTest extends TestCase {

  /**
   * Tests wrong rectangle width.
   *
   * @covers ::rotate
   */
  public function testWrongWidth(): void {
    $this->expectException(\InvalidArgumentException::class);
    $rect = new Rectangle(-40, 20);
  }

  /**
   * Tests wrong rectangle height.
   *
   * @covers ::rotate
   */
  public function testWrongHeight(): void {
    $this->expectException(\InvalidArgumentException::class);
    $rect = new Rectangle(40, 0);
  }

  /**
   * Tests getting rectangle dimensions after a rotation operation.
   *
   * @param int $width
   *   The width of the rectangle.
   * @param int $height
   *   The height of the rectangle.
   * @param float $angle
   *   The angle for rotation.
   * @param int $exp_width
   *   The expected width of the rotated rectangle.
   * @param int $exp_height
   *   The expected height of the rotated rectangle.
   *
   * @covers ::rotate
   * @covers ::getBoundingWidth
   * @covers ::getBoundingHeight
   *
   * @dataProvider providerPhp55RotateDimensions
   */
  public function testRotateDimensions($width, $height, $angle, $exp_width, $exp_height): void {
    $rect = new Rectangle($width, $height);
    $rect->rotate($angle);
    $this->assertEquals($exp_width, $rect->getBoundingWidth());
    $this->assertEquals($exp_height, $rect->getBoundingHeight());
  }

  /**
   * Provides data for image dimension rotation tests.
   *
   * This dataset sample was generated by running on PHP 5.5 the function below
   * - first, for all integer rotation angles (-360 to 360) on a rectangle
   *   40x20;
   * - second, for 500 random float rotation angle in the range -360 to 360 on
   *   a rectangle 40x20;
   * - third, on 1000 rectangles of random WxH rotated to a random float angle
   *   in the range -360 to 360
   * - fourth, on 2000 rectangles of random WxH rotated to a random integer
   *   angle multiple of 30 degrees in the range -360 to 360 (which is the most
   *   tricky case).
   * Using the GD toolkit operations gives us true data coming from the GD
   * library that can be used to match against the Rectangle class under test.
   * @code
   *   protected function rotateResults($width, $height, $angle, &$new_width, &$new_height) {
   *     $image = \Drupal::service('image.factory')->get(NULL, 'gd');
   *     $image->createNew($width, $height);
   *     $old = $image->getToolkit()->getGdImage();
   *     $image->rotate($angle);
   *     $new_width = $image->getWidth();
   *     $new_height = $image->getHeight();
   *   }
   * @endcode
   *
   * @return array[]
   *   A simple array of simple arrays, each having the following elements:
   *   - original image width
   *   - original image height
   *   - rotation angle in degrees
   *   - expected image width after rotation
   *   - expected image height after rotation
   *
   * @see testRotateDimensions()
   */
  public static function providerPhp55RotateDimensions() {
    // The dataset is stored in a .json file because it is very large and causes
    // problems for PHPCS.
    return json_decode(file_get_contents(__DIR__ . '/fixtures/RectangleTest.json'));
  }

}
