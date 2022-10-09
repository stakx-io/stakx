<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\RuntimeStatus;
use allejo\stakx\Service;

trait PermalinkDocumentTrait
{
    protected ?string $permalink = null;

    protected ?array $redirects = null;

    /** Extensions that need to be stripped from permalinks. */
    private static array $extensionsToStrip = ['twig'];

    /**
     * {@inheritdoc}
     */
    public function handleSpecialRedirects(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    final public function getTargetFile($permalink = null): string
    {
        if ($permalink === null) {
            $permalink = $this->getPermalink();
        }

        $missingFile = substr((string)$permalink, -1) == '/';
        $permalink = str_replace('/', DIRECTORY_SEPARATOR, (string)$permalink);

        if ($missingFile) {
            $permalink = rtrim($permalink, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        }

        return ltrim($permalink, DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    final public function getPermalink(): string
    {
        $this->buildPermalink();

        $this->permalink = $this->sanitizePermalink($this->permalink);
        $this->permalink = str_replace(DIRECTORY_SEPARATOR, '/', (string)$this->permalink);
        $this->permalink = '/' . ltrim($this->permalink, '/'); // Permalinks should always use '/' and not be OS specific

        return $this->permalink;
    }

    /**
     * {@inheritdoc}
     */
    final public function getRedirects(): array
    {
        if ($this->redirects === null) {
            $this->getPermalink();
        }

        $this->handleSpecialRedirects();

        return $this->redirects;
    }

    /**
     * Get the permalink based off the location of where the file is relative to the website. This permalink is to be
     * used as a fallback in the case that a permalink is not explicitly specified in the Front Matter.
     */
    final protected function getPathPermalink(): string
    {
        // Remove the protocol of the path, if there is one and prepend a '/' to the beginning
        $cleanPath = preg_replace('/[\w|\d]+:\/\//', '', $this->getRelativeFilePath());
        $cleanPath = ltrim($cleanPath, DIRECTORY_SEPARATOR);

        // Handle vfs:// paths by replacing their forward slashes with the OS appropriate directory separator
        if (DIRECTORY_SEPARATOR !== '/') {
            $cleanPath = str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        }

        // Check the first folder and see if it's a data folder (starts with an underscore) intended for stakx
        $folders = explode(DIRECTORY_SEPARATOR, $cleanPath);

        if (str_starts_with($folders[0], '_')) {
            array_shift($folders);
        }

        return implode(DIRECTORY_SEPARATOR, $folders);
    }

    /**
     * Sanitize a permalink to remove unsupported characters or multiple '/' and replace spaces with hyphens.
     *
     * @param string $permalink A permalink
     *
     * @return string $permalink The sanitized permalink
     */
    private function sanitizePermalink($permalink): string
    {
        // Remove multiple '/' together
        $permalink = preg_replace('/\/+/', '/', $permalink);

        // Replace all spaces with hyphens
        $permalink = str_replace(' ', '-', $permalink);

        // Remove all disallowed characters
        $permalink = preg_replace('/[^0-9a-zA-Z-_\/\\\.]/', '', $permalink);

        if (in_array(fs::getExtension($permalink), self::$extensionsToStrip)) {
            $permalink = fs::removeExtension($permalink);
        }

        // Remove any special characters before a sane value
        $permalink = preg_replace('/^[^0-9a-zA-Z-_]*/', '', $permalink);

        // Convert permalinks to lower case
        if (!Service::hasRunTimeFlag(RuntimeStatus::COMPILER_PRESERVE_CASE)) {
            $permalink = mb_strtolower($permalink, 'UTF-8');
        }

        return $permalink;
    }
}
