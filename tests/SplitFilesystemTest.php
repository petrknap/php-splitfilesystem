<?php

namespace PetrKnap\Php\SplitFilesystem\Test;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use PetrKnap\Php\SplitFilesystem\SplitFilesystem;

class SplitFilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return string
     */
    protected static function getTemporaryDirectory()
    {
        $temporaryDirectory = tempnam(__DIR__ . '/../var', 'test_');

        unlink($temporaryDirectory);

        return $temporaryDirectory;
    }

    public static function tearDownAfterClass()
    {
        passthru(sprintf('rm -rf %s/test_*', escapeshellarg(__DIR__ . '/../var')));

        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider dataGeneratesCorrectInnerPath
     * @param string $expectedInnerPath
     * @param array|null $config
     * @param string $path
     * @param bool $isDirectory
     */
    public function testGeneratesCorrectInnerPath($expectedInnerPath, $config, $path, $isDirectory)
    {
        /** @var AdapterInterface $adapter */
        $adapter = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->assertEquals(
            $expectedInnerPath,
            (new SplitFilesystem(
                $adapter,
                $config
            ))->getInnerPath($path, $isDirectory)
        );
    }

    public function dataGeneratesCorrectInnerPath()
    {
        return [
            ['', null, '', true],
            ['', null, '', false],
            ['0ec/_path/4aa/_to/966/_node', null, 'path/to/node', true],
            ['0ec/_path/4aa/_to/c6/ab/b7/_node.ext', null, 'path/to/node.ext', false],
            ['', null, '/', true],
            ['', null, '/', false],
            ['0ec/_path/4aa/_to/966/_node', null, '/path/to/node', true],
            ['0ec/_path/4aa/_to/c6/ab/b7/_node.ext', null, '/path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '', true],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '', false],
            ['cd5e0/_path/aee24/_to/6d1e2/_node', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], 'path/to/node', true],
            ['cd5e0/_path/aee24/_to/c6/ab/b7/_node.ext', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], 'path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '/', true],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '/', false],
            ['cd5e0/_path/aee24/_to/6d1e2/_node', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '/path/to/node', true],
            ['cd5e0/_path/aee24/_to/c6/ab/b7/_node.ext', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 5], '/path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '', true],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '', false],
            ['0ec/_path/4aa/_to/966/_node', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], 'path/to/node', true],
            ['0ec/_path/4aa/_to/bb762/7280b/795c7/_node.ext', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], 'path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '/', true],
            ['', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '/', false],
            ['0ec/_path/4aa/_to/966/_node', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '/path/to/node', true],
            ['0ec/_path/4aa/_to/bb762/7280b/795c7/_node.ext', [SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 5], '/path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '', true],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '', false],
            ['0ec/d5e/029/453/4a8/_path/4aa/ee2/47f/b23/7ce/_to/966/d1e/207/d02/c44/_node', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], 'path/to/node', true],
            ['0ec/d5e/029/453/4a8/_path/4aa/ee2/47f/b23/7ce/_to/c6/ab/b7/_node.ext', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], 'path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '/', true],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '/', false],
            ['0ec/d5e/029/453/4a8/_path/4aa/ee2/47f/b23/7ce/_to/966/d1e/207/d02/c44/_node', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '/path/to/node', true],
            ['0ec/d5e/029/453/4a8/_path/4aa/ee2/47f/b23/7ce/_to/c6/ab/b7/_node.ext', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 5], '/path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '', true],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '', false],
            ['0ec/_path/4aa/_to/966/_node', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], 'path/to/node', true],
            ['0ec/_path/4aa/_to/c6/ab/b7/62/72/_node.ext', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], 'path/to/node.ext', false],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '/', true],
            ['', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '/', false],
            ['0ec/_path/4aa/_to/966/_node', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '/path/to/node', true],
            ['0ec/_path/4aa/_to/c6/ab/b7/62/72/_node.ext', [SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 5], '/path/to/node.ext', false],
        ];
    }

    public function testTransformsMetadataCorrectly()
    {
        $filesystem = new SplitFilesystem(new Local(static::getTemporaryDirectory()));
        $filesystem->write('path/to/file.ext', 'content');

        $metadata = $filesystem->getMetadata('path/to/file.ext');

        $expected = [
            'type' => 'file',
            'path' => 'path/to/file.ext',
            'size' => 7,
            'dirname' => 'path/to',
        ];
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $metadata);
            $this->assertSame($value, $metadata[$key]);
        }

        $metadata = $filesystem->listContents('path/to')[0];

        $expected = [
            'type' => 'file',
            'path' => 'path/to/file.ext',
            'size' => 7,
            'dirname' => 'path/to',
            'basename' => 'file.ext',
            'extension' => 'ext',
            'filename' => 'file',
        ];
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $metadata);
            $this->assertSame($value, $metadata[$key]);
        }
    }

    /**
     * @dataProvider dataListContentsWorks
     * @param SplitFilesystem $filesystem
     * @param string $directory
     * @param bool $recursive
     * @param array $expected
     */
    public function testListContentsWorks(SplitFilesystem $filesystem, $directory, $recursive, $expected)
    {
        $listedContents = $filesystem->listContents($directory, $recursive);

        $listed = [];
        foreach ($listedContents as $listedContent) {
            $listed[$listedContent['path']] = $listedContent['type'];
        }
        $this->assertEquals($expected, $listed);
    }

    public function dataListContentsWorks()
    {
        $filesystem = new SplitFilesystem(new Local(static::getTemporaryDirectory()));
        $filesystem->createDir('empty_directory');
        $filesystem->write('file.ext', 'content');
        $filesystem->write('directory/file.ext', 'content');
        $filesystem->write('directory/subdirectory/file.ext', 'content');

        return [
            [$filesystem, '', false, [
                'empty_directory' => 'dir',
                'file.ext' => 'file',
                'directory' => 'dir',
            ]],
            [$filesystem, '', true, [
                'empty_directory' => 'dir',
                'file.ext' => 'file',
                'directory' => 'dir',
                'directory/file.ext' => 'file',
                'directory/subdirectory' => 'dir',
                'directory/subdirectory/file.ext' => 'file',
            ]],
            [$filesystem, 'empty_directory', false, []],
            [$filesystem, 'empty_directory', true, []],
            [$filesystem, 'directory', false, [
                'directory/file.ext' => 'file',
                'directory/subdirectory' => 'dir',
            ]],
            [$filesystem, 'directory', true, [
                'directory/file.ext' => 'file',
                'directory/subdirectory' => 'dir',
                'directory/subdirectory/file.ext' => 'file',
            ]],
            [$filesystem, 'directory/subdirectory', false, [
                'directory/subdirectory/file.ext' => 'file',
            ]],
            [$filesystem, 'directory/subdirectory', true, [
                'directory/subdirectory/file.ext' => 'file',
            ]],
        ];
    }

    /**
     * @dataProvider dataPerformanceIsNotIntrusive
     * @param SplitFilesystem $fileSystem
     * @param int $from
     * @param int $to
     */
    public function testPerformanceIsNotIntrusive(SplitFilesystem $fileSystem, $from, $to)
    {
        $startStopStack = [];
        $start = function () use (&$startStopStack) {
            $startStopStack[] = microtime(true);
        };
        $stop = function ($allowedDuration) use (&$startStopStack) {
            if ($allowedDuration < (microtime(true) - array_pop($startStopStack))) {
                $this->markTestSkipped('WARNING: Performance was intrusive');
            }
        };

        #region Build storage
        for ($i = $from; $i < $to; $i++) {
            $file = "/file_{$i}.tmp";

            #region Create file
            $start();
            $fileSystem->write($file, null);
            $stop(5);
            #endregion

            #region Write content
            $start();
            $fileSystem->update($file, sha1($i, true));
            $fileSystem->update($file, md5($i, true), ["append" => true]);
            $stop(10);
            #endregion

            #region Read content
            $start();
            $fileSystem->read($file);
            $stop(5);
            #endregion
        }
        #endregion

        #region Iterate all files
        $start();
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($fileSystem->listContents() as $unused) {
            // no-op
        }
        $stop(5 * $to);
        #endregion
    }

    public function dataPerformanceIsNotIntrusive()
    {
        $iMax = 2048;
        $step = 512;
        $output = [];
        $fileSystem = new SplitFilesystem(new Local(static::getTemporaryDirectory()));
        for ($i = 0; $i < $iMax; $i += $step) {
            $output[] = [$fileSystem, $i, $i + $step];
        }
        return $output;
    }
}
