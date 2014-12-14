<?php

namespace Codeception\Module;

use Codeception\Module;
use Codeception\Configuration;
use Codeception\Util\Debug;
use Codeception\TestCase;
use DirectoryIterator;
use Imagick;
use PHPUnit_Runner_BaseTestRunner as Runner;

/**
 * Simple helper that allows working with images.
 *
 * @version 0.1.0
 * @since   0.1.0
 * @package Codeception\Module
 * @author  Etki <etki@etki.name>
 */
class ImageHelper extends \Codeception\Module
{
    /**
     * Stash for images that failed checking during last test.
     *
     * @type Imagick[]
     * @since 0.1.0
     */
    protected $imageStash = array();
    /**
     * Symfony filesystem component.
     *
     * @type \Symfony\Component\Filesystem\Filesystem
     * @since 0.1.0
     */
    protected $filesystem;

    /**
     * Simple initializer.
     *
     * @param array|null $config Who knows.
     *
     * @return self
     * @since 0.1.0
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->filesystem = new \Symfony\Component\Filesystem\Filesystem;
    }

    /**
     * Returns all images in directory.
     *
     * @param string $directory
     *
     * @return string[] Paths to images.
     * @since 0.1.0
     */
    protected function getDirectoryImages($directory)
    {
        $images = array();
        foreach (new DirectoryIterator($directory) as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $extension = strtolower($item->getExtension());
            if (!in_array($extension, array('jpg', 'jpeg', 'png',), true)) {
                $message = sprintf(
                    'Found file of unknown extension in samples directory: ' .
                    '`%s`',
                    $item->getPathname()
                );
                $this->debug($message);
            }
            $images[] = $item->getPathname();
        }
        return $images;
    }
    /**
     * Returns list of sample images.
     *
     * @return string[] Paths to images.
     * @since 0.1.0
     */
    public function getSampleImages()
    {
        $data = $this->getDirectoryImages($this->getSamplesDirectory());
        sort($data);
        return $data;
    }

    /**
     * Returns expected results for processing.
     *
     * @param string $processingName name of the processing
     *
     * @return string[]
     * @since 0.1.0
     */
    public function getExpectedResults($processingName)
    {
        $chunks = array(
            $this->getImagesDataDirectory(),
            'results',
            $processingName
        );
        $data = $this->getDirectoryImages(
            implode(DIRECTORY_SEPARATOR, $chunks)
        );
        sort($data);
        return $data;
    }

    /**
     * Returns path to samples directory.
     *
     * @return string
     * @since 0.1.0
     */
    protected function getSamplesDirectory()
    {
        return $this->getImagesDataDirectory() . DIRECTORY_SEPARATOR .
            'samples';
    }

    /**
     * Returns data directory for all test images.
     *
     * @return string
     * @since 0.1.0
     */
    protected function getImagesDataDirectory()
    {
        return Configuration::dataDir() . DIRECTORY_SEPARATOR . 'images';
    }

    /**
     * Returns path to output directory.
     *
     * @throws \Codeception\Exception\Configuration
     *
     * @return string
     * @since 0.1.0
     */
    protected function getOutputDirectory()
    {
        return Configuration::outputDir();
    }

    /**
     * Returns path to directory for test case.
     *
     * @param TestCase $test Test to create directory for.
     *
     * @return string
     * @since 0.1.0
     */
    protected function getTestCaseOutputDirectoryPath(TestCase $test)
    {
        $class = get_class($test);
        $identifier = $class . DIRECTORY_SEPARATOR . $test->getName(true);
        $path = $this->getOutputDirectory() . DIRECTORY_SEPARATOR .
            str_replace('\\', DIRECTORY_SEPARATOR, $identifier);
        return $path;
    }

    /**
     * Creates output directory for test case.
     *
     * @param TestCase $test Test to create directory for.
     *
     * @return string Path to directory.
     * @since 0.1.0
     */
    protected function createTestCaseOutputDirectory(TestCase $test)
    {
        $path = $this->getTestCaseOutputDirectoryPath($test);
        $this->filesystem->mkdir($path, 0777, true);
        return $path;
    }

    /**
     * Drops test case directory if it exists.
     *
     * @param TestCase $test Test case to drop directory for,
     *
     * @return void
     * @since 0.1.0
     */
    protected function dropTestCaseOutputDirectory(TestCase $test)
    {
        $path = $this->getTestCaseOutputDirectoryPath($test);
        $this->filesystem->remove($path);
    }

    /**
     * Before-test hook.
     *
     * @param TestCase $test Test to execute.
     *
     * @return void
     * @since 0.1.0
     */
    public function _before(TestCase $test)
    {
        $this->dropTestCaseOutputDirectory($test);
    }

    /**
     * Post-fail hook.
     *
     * @param TestCase $test
     * @param mixed    $fail
     *
     * @return void
     * @since 0.1.0
     */
    public function _failed(TestCase $test, $fail)
    {
        if ($this->imageStash) {
            $path = $this->createTestCaseOutputDirectory($test);
            $this->dumpImages($path, $this->imageStash);
        }
        $this->imageStash = array();
    }

    /**
     * Stashes images.
     *
     * @param Imagick[] $images
     *
     * @return void
     * @since 0.1.0
     */
    protected function stashImages(array $images)
    {
        $images = array_filter(
            $images,
            function ($image) {
                return $image instanceof Imagick;
            }
        );
        $this->imageStash = $images;
    }

    /**
     * Dumps images to disk.
     *
     * @param string    $path   Path to output directory,
     * @param Imagick[] $images Images.
     *
     * @return void
     * @since 0.1.0
     */
    protected function dumpImages($path, array $images)
    {
        $scale = ceil(log(sizeof($images) + 1, 10));
        foreach ($images as $key => $image) {
            if (!($image instanceof Imagick)) {
                continue;
            }
            $filename = str_pad($key + 1, $scale, '0', STR_PAD_LEFT) . '.png';
            $image->writeImage($path . DIRECTORY_SEPARATOR . $filename);
        }
    }

    /**
     * Fails single image test.
     *
     * @param Imagick[] $images
     * @param string    $message
     *
     * @return void
     * @since 0.1.0
     */
    protected function failImageTest(array $images, $message)
    {
        $this->stashImages($images);
        $this->fail($message);
    }

    /**
     * Asserts that to images differ not more than `$maxConformity` (0-100).
     *
     * @param Imagick $imageA
     * @param Imagick $imageB
     * @param int     $maxConformity
     *
     * @return void
     * @since 0.1.0
     */
    public function assertImageConformity(Imagick $imageA, Imagick $imageB, $maxConformity)
    {
        if ($imageA->getImageWidth() !== $imageB->getImageWidth()
            || $imageA->getImageHeight() !== $imageB->getImageHeight()
        ) {
            $message = sprintf(
                'Provided images differ in physical size: image A is ' .
                '`%dx%d`, image B is `%dx%d',
                $imageA->getImageWidth(),
                $imageA->getImageHeight(),
                $imageB->getImageWidth(),
                $imageB->getImageHeight()
            );
            $this->failImageTest(array($imageA, $imageB), $message);
        }
        $metric = Imagick::METRIC_MEANSQUAREERROR;
        if (($result = $imageA->compareImages($imageB, $metric)) !== true) {
            Debug::debug('Initial conformity: ' . $result[1]);
            $conformity = pow($result[1], 0.25) * 100;
            Debug::debug('Normalized conformity: ' . $conformity);
            if ($conformity > $maxConformity) {
                $this->failImageTest(array($imageA, $imageB, $result[0]), '');
            }
        }
    }

    /**
     * Asserts that images are perfectly-equal.
     *
     * @param Imagick $imageA
     * @param Imagick $imageB
     *
     * @return void
     * @since 0.1.0
     */
    public function assertImageEquality(Imagick $imageA, Imagick $imageB)
    {
        $this->assertImageConformity($imageA, $imageB, 0);
    }
}
