<?php

namespace PetrKnap\Php\SplitFilesystem\Test;

use League\Flysystem\Adapter\Local;
use PetrKnap\Php\SplitFilesystem\SplitFilesystem;
use PetrKnap\Php\Profiler\SimpleProfiler;

class PerformanceTest extends AbstractTestCase
{
    /**
     * @dataProvider dataPerformanceIsNotIntrusive
     * @param SplitFilesystem $fileSystem
     * @param int $from
     * @param int $to
     */
    public function testPerformanceIsNotIntrusive(SplitFilesystem $fileSystem, $from, $to)
    {
        $profilerWasEnabled = SimpleProfiler::start();
        if (!$profilerWasEnabled) {
            SimpleProfiler::enable();
        }

        #region Build storage
        for ($i = $from; $i < $to; $i++) {
            $file = "/file_{$i}.tmp";

            #region Create file
            SimpleProfiler::start();
            $fileSystem->write($file, null);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion

            #region Write content
            SimpleProfiler::start();
            $fileSystem->update($file, sha1($i, true));
            $fileSystem->update($file, md5($i, true), ["append" => true]);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(10, $profile->absoluteDuration);
            #endregion

            #region Read content
            SimpleProfiler::start();
            $fileSystem->read($file);
            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(5, $profile->absoluteDuration);
            #endregion
        }
        #endregion

        #region Iterate all files
        SimpleProfiler::start();
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection LoopWhichDoesNotLoopInspection */
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        foreach ($fileSystem->listContents() as $unused) {
            // noop
        }
        $profile = SimpleProfiler::finish();
        $this->assertLessThanOrEqual(5 * $to, $profile->absoluteDuration);
        #endregion

        if (!$profilerWasEnabled) {
            SimpleProfiler::disable();
        }
        SimpleProfiler::finish();
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
