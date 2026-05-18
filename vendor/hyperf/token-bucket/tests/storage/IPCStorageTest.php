<?php

namespace bandwidthThrottle\tokenBucket\storage;

use PHPUnit\Framework\TestCase;

/**
 * Tests for IPCStorage.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @see  IPCStorage
 */
class IPCStorageTest extends TestCase
{

    /**
     * Tests building fails for an invalid key.
     */
    public function testBuildFailsForInvalidKey()
    {
        $this->expectException(\TypeError::class);

        @new IPCStorage("invalid");
    }

    /**
     * Tests removing semaphore fails.
     */
    public function testfailRemovingSemaphore()
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Could not remove semaphore.');

        $key = ftok(__FILE__, "a");
        $storage = new IPCStorage($key);

        sem_remove(sem_get($key));
        @$storage->remove();
    }

    /**
     * Tests setMicrotime() fails.
     */
    public function testSetMicrotimeFails()
    {
        $this->expectException(StorageException::class);

        $storage = new IPCStorage(ftok(__FILE__, "a"));
        $storage->remove();
        @$storage->setMicrotime(123);
    }

    /**
     * Tests getMicrotime() fails.
     */
    public function testGetMicrotimeFails()
    {
        $this->expectException(StorageException::class);

        $storage = new IPCStorage(ftok(__FILE__, "b"));
        @$storage->getMicrotime();
    }
}
