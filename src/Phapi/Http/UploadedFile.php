<?php
namespace Phapi\Http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Implementation of PSR Uploaded files interface
 *
 * @category Phapi
 * @package  Phapi\Http
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/http
 * @link     https://github.com/phly/http This class is based upon
 *           Matthew Weier O'Phinney's UploadedFile implementation in phly/http.
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * @var string
     */
    private $clientFilename;

    /**
     * @var string
     */
    private $clientMediaType;

    /**
     * @var int
     */
    private $error;

    /**
     * @var null|string
     */
    private $file;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var int
     */
    private $size;

    /**
     * @var null|StreamInterface
     */
    private $stream;

    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        }
        if (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        }

        if (! $this->file && ! $this->stream) {
            if (! $streamOrFile instanceof StreamInterface) {
                throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
            }
            $this->stream = $streamOrFile;
        }

        if (! is_int($size)) {
            throw new InvalidArgumentException('Invalid size provided for UploadedFile; must be an int');
        }
        $this->size = $size;

        $this->validateErrorStatus($errorStatus);
        $this->error = $errorStatus;

        $this->validateClientFilename($clientFilename);
        $this->clientFilename = $clientFilename;

        $this->validateClientMediaType($clientMediaType);
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $path Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if (! is_string($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a string'
            );
        }

        if (empty($targetPath)) {
            throw new InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );
        }

        if ($this->moved) {
            throw new RuntimeException('Cannot move file; already moved!');
        }

        $sapi = PHP_SAPI;
        switch (true) {
            case (empty($sapi) || 0 === strpos($sapi, 'cli') || ! $this->file):
                // Non-SAPI environment, or no filename present
                $this->writeFile($targetPath);
                break;
            default:
                // SAPI environment, with file present
                if (false === move_uploaded_file($this->file, $targetPath)) {
                    throw new RuntimeException('Error occurred while moving uploaded file');
                }
                break;
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    private function writeFile($path)
    {
        $handle = @fopen($path, 'wb+');
        if (false === $handle) {
            throw new RuntimeException('Unable to write to designated path');
        }

        $this->stream->rewind();
        while (! $this->stream->eof()) {
            fwrite($handle, $this->stream->read(4096));
        }

        fclose($handle);
    }

    /**
     * Validate filename
     *
     * @param $clientFilename
     */
    private function validateClientFilename($clientFilename)
    {
        if (null !== $clientFilename && ! is_string($clientFilename)) {
            throw new InvalidArgumentException(
                'Invalid client filename provided for UploadedFile; must be null or a string'
            );
        }
    }

    /**
     * Validate media type
     *
     * @param $clientMediaType
     */
    private function validateClientMediaType($clientMediaType)
    {
        if (null !== $clientMediaType && ! is_string($clientMediaType)) {
            throw new InvalidArgumentException(
                'Invalid client media type provided for UploadedFile; must be null or a string'
            );
        }
    }

    /**
     * Validate error status
     *
     * @param $errorStatus
     */
    private function validateErrorStatus($errorStatus)
    {
        if (! is_int($errorStatus)
            || 0 > $errorStatus
            || 8 < $errorStatus
        ) {
            throw new InvalidArgumentException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant'
            );
        }
    }
}