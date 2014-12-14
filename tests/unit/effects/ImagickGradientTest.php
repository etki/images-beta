<?php
namespace effects;

use Codeception\Module\ImageHelper;
use Codeception\TestCase\Test;
use Codeception\Util\Debug;
use rico\yii2images\effects\ImagickGradient;
use UnitTester;
use Imagick;

class ImagickGradientTest extends Test
{
    /**
     * @type UnitTester
     */
    protected $tester;

    public function imagesProvider()
    {
        /** @type ImageHelper $helper */
        $helper = $this->getModule('ImageHelper');
        $samples = $helper->getSampleImages();
        $results = $helper->getExpectedResults('imagick-gradient');
        $data = array();
        foreach ($samples as $key => $path) {
            $data[] = array($path, $results[$key]);
        }
        return $data;
    }

    // tests

    /**
     *
     *
     * @dataProvider imagesProvider
     *
     * @return void
     * @since 0.1.0
     */
    public function testGradient($samplePath, $expectedResultPath)
    {
        Debug::debug('');
        Debug::debug('Sample path: ' . $samplePath);
        Debug::debug('Expected result path: ' . $expectedResultPath);
        $expectedResult = new Imagick($expectedResultPath);
        $image = new Imagick($samplePath);
        $effect = new ImagickGradient;
        $effect->coverPercent = 100;
        $effect->apply($image);
        $this->tester->assertImageConformity($expectedResult, $image, 20);
    }
}
