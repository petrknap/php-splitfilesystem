<?php

namespace PetrKnap\Php\SplitFilesystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\RootViolationException;
use League\Flysystem\Util;
use Nunzion\Expect;

class SplitFilesystem implements FilesystemInterface
{
    use PluggableTrait;

    const CONFIG_HASH_PARTS_FOR_DIRECTORIES = 'hash_parts_for_directories';
    const CONFIG_HASH_PARTS_FOR_FILES = 'hash_parts_for_files';
    const CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES = 'hash_part_length_for_directories';
    const CONFIG_HASH_PART_LENGTH_FOR_FILES = 'hash_part_length_for_files';

    const INNER_NAME_PREFIX = '_';

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param AdapterInterface $adapter
     * @param Config|array $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $config = Util::ensureConfig($config);

        $this->filesystem = new Filesystem($adapter, $config);
        $this->config = $config;
    }

    /**
     * @return int
     */
    protected function getHashPartsForDirectories()
    {
        return (int) $this->config->get(static::CONFIG_HASH_PARTS_FOR_DIRECTORIES, 1);
    }

    /**
     * @return int
     */
    protected function getHashPartsForFiles()
    {
        return (int) $this->config->get(static::CONFIG_HASH_PARTS_FOR_FILES, 3);
    }

    /**
     * @return int
     */
    protected function getHashPartLengthForDirectories()
    {
        return (int) $this->config->get(static::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES, 3);
    }

    /**
     * @return int
     */
    protected function getHashPartLengthForFiles()
    {
        return (int) $this->config->get(static::CONFIG_HASH_PART_LENGTH_FOR_FILES, 2);
    }

    /**
     * @internal
     * @param string $path
     * @param bool $isDirectory
     * @return string
     */
    public function getInnerPath($path, $isDirectory)
    {
        Expect::that($path)->isString();

        if (empty($path)) {
            return '';
        }

        if ('/' === $path[0]) {
            $path = (string) substr($path, 1);
        }

        if (empty($path)) {
            return '';
        }

        $pathParts = explode('/', $path);
        $iMax = count($pathParts) - 1;

        for ($i = 0; $i <= $iMax; $i++) {
            $pathPart = &$pathParts[$i];
            if ($isDirectory || $i < $iMax) {
                $hashParts = $this->getHashPartsForDirectories();
                $hashPartLength = $this->getHashPartLengthForDirectories();
            } else {
                $hashParts = $this->getHashPartsForFiles();
                $hashPartLength = $this->getHashPartLengthForFiles();
            }
            $pathPartHash = sha1($pathPart);
            $pathPart = static::INNER_NAME_PREFIX . $pathPart;
            for ($j = $hashParts; $j > 0; $j--) {
                $pathPart = substr($pathPartHash, $j * $hashPartLength, $hashPartLength) . '/' . $pathPart;
            }
        }

        return implode('/', $pathParts);
    }

    /**
     * @internal
     * @param array $metadata
     * @return array
     */
    private function translateMetadata(array $metadata)
    {
        $pathParts = explode('/', $metadata['path']);

        $path = '';
        foreach ($pathParts as $pathPart) {
            if (static::INNER_NAME_PREFIX === $pathPart[0]) {
                $path .= '/' . substr($pathPart, 1);
            }
        }

        $metadata['_inner'] = $metadata;
        $metadata['path'] = substr($path, 1);
        if (isset($metadata['basename'])) {
            $metadata['basename'] = substr($metadata['basename'], 1);
        }
        if (isset($metadata['filename'])) {
            $metadata['filename'] = substr($metadata['filename'], 1);
        }
        $metadata['dirname'] = substr($path, 1, strrpos($path, '/') - 1);

        return $metadata;
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->filesystem->has($this->getInnerPath($path, false));
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        try {
            return $this->filesystem->read(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        try {
            return $this->filesystem->readStream(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false)
    {
        $listedContents = [];

        foreach ($this->listInnerContents($this->getInnerPath($directory, true), 'dir') as $metadata) {
            $listedContents[] = $metadata;
            if (true === $recursive) {
                foreach ($this->listContents($metadata['path'], $recursive) as $subMetadata) {
                    $listedContents[] = $subMetadata;
                }
            }
        }
        foreach ($this->listInnerContents($this->getInnerPath($directory, true), 'file') as $metadata) {
            $listedContents[] = $metadata;
        }

        return $listedContents;
    }

    /**
     * @internal
     * @param string $directory
     * @param string $type file or dir
     * @param int|null $depth
     * @param array $listedContents
     * @return array
     */
    private function listInnerContents($directory, $type, $depth = null, array $listedContents = [])
    {
        if (null === $depth) {
            if ('dir' === $type) {
                $depth = $this->getHashPartsForDirectories();
            } else {
                $depth = $this->getHashPartsForFiles();
            }
        }

        foreach ($this->filesystem->listContents($directory, false) as $metadata) {
            if (0 < $depth) {
                if ('dir' === $metadata['type']) {
                    $listedContents = $this->listInnerContents($metadata['path'], $type, $depth - 1, $listedContents);
                }
            } elseif (static::INNER_NAME_PREFIX === $metadata['basename'][0]) {
                if ($type === $metadata['type']) {
                    $listedContents[] = $this->translateMetadata($metadata);
                }
            }
        }

        return $listedContents;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        try {
            $metadata = $this->filesystem->getMetadata(
                $this->getInnerPath($path, false)
            );
            if (is_array($metadata)) {
                $metadata = $this->translateMetadata($metadata);
            }
            return $metadata;
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->filesystem->getSize(
            $this->getInnerPath($path, false)
        );
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        try {
            return $this->filesystem->getMimetype(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        try {
            return $this->filesystem->getTimestamp(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        try {
            return $this->filesystem->getVisibility(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, array $config = [])
    {
        try {
            return $this->filesystem->write(
                $this->getInnerPath($path, false),
                $contents,
                $config
            );
        } catch (FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, array $config = [])
    {
        try {
            return $this->filesystem->writeStream(
                $this->getInnerPath($path, false),
                $resource,
                $config
            );
        } catch (FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, array $config = [])
    {
        try {
            return $this->filesystem->update(
                $this->getInnerPath($path, false),
                $contents,
                $config
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        try {
            return $this->filesystem->updateStream(
                $this->getInnerPath($path, false),
                $resource,
                $config
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newPath)
    {
        try {
            return $this->filesystem->rename(
                $this->getInnerPath($path, false),
                $this->getInnerPath($newPath, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        } catch (FileExistsException $e) {
            throw $this->exceptionWrapper($e, $newPath);
        }
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newPath)
    {
        try {
            return $this->filesystem->copy(
                $this->getInnerPath($path, false),
                $this->getInnerPath($newPath, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        } catch (FileExistsException $e) {
            throw $this->exceptionWrapper($e, $newPath);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        try {
            return $this->filesystem->delete(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        try {
            return $this->filesystem->deleteDir(
                $this->getInnerPath($dirname, true)
            );
        } catch (RootViolationException $e) {
            throw $this->exceptionWrapper($e, $dirname);
        }
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, array $config = [])
    {
        return $this->filesystem->createDir(
            $this->getInnerPath($dirname, true),
            $config
        );
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return $this->filesystem->setVisibility(
            $this->getInnerPath($path, false),
            $visibility
        );
    }

    /**
     * @inheritdoc
     */
    public function put($path, $contents, array $config = [])
    {
        return $this->filesystem->put(
            $this->getInnerPath($path, false),
            $contents,
            $config
        );
    }

    /**
     * @inheritdoc
     */
    public function putStream($path, $resource, array $config = [])
    {
        return $this->filesystem->putStream(
            $this->getInnerPath($path, false),
            $resource,
            $config
        );
    }

    /**
     * @inheritdoc
     */
    public function readAndDelete($path)
    {
        try {
            return $this->filesystem->readAndDelete(
                $this->getInnerPath($path, false)
            );
        } catch (FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($path, Handler $handler = null)
    {
        return $this->filesystem->get(
            $this->getInnerPath($path, false),
            $handler
        );
    }

    /**
     * @param \Exception $exception
     * @param string $path
     * @return \Throwable
     */
    private function exceptionWrapper(\Exception $exception, $path)
    {
        $message = str_replace([
            $this->getInnerPath($path, false),
            $this->getInnerPath($path, true),
        ], [
            $path,
            $path,
        ], $exception->getMessage());
        $exceptionClass = get_class($exception);
        return new $exceptionClass($message, $exception->getCode(), $exception);
    }
}
