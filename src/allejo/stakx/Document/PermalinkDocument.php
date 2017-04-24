<?php

/**
 * @copyright 2017 Vladimir Jimenez
 * @license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

abstract class PermalinkDocument extends ReadableDocument
{
    protected $permalink;
    protected $redirects;

    /**
     * Get the destination of where this Content Item would be written to when the website is compiled.
     *
     * @return string
     */
    final public function getTargetFile()
    {
        $permalink = $this->getPermalink();
        $missingFile = (substr($permalink, -1) == '/');
        $permalink = str_replace('/', DIRECTORY_SEPARATOR, $permalink);

        if ($missingFile)
        {
            $permalink = rtrim($permalink, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        }

        return ltrim($permalink, DIRECTORY_SEPARATOR);
    }

    /**
     * Get the permalink of this Content Item.
     *
     * @throws \Exception
     *
     * @return string
     */
    final public function getPermalink()
    {
        $this->buildPermalink();

        $this->permalink = $this->sanitizePermalink($this->permalink);
        $this->permalink = str_replace(DIRECTORY_SEPARATOR, '/', $this->permalink);
        $this->permalink = '/' . ltrim($this->permalink, '/'); // Permalinks should always use '/' and not be OS specific

        return $this->permalink;
    }

    /**
     * Get an array of URLs that will redirect to.
     *
     * @return string[]
     */
    final public function getRedirects()
    {
        if (is_null($this->redirects))
        {
            $this->getPermalink();
        }

        return $this->redirects;
    }

    /**
     * Get the permalink based off the location of where the file is relative to the website. This permalink is to be
     * used as a fallback in the case that a permalink is not explicitly specified in the Front Matter.
     *
     * @return string
     */
    protected function getPathPermalink()
    {
        // Remove the protocol of the path, if there is one and prepend a '/' to the beginning
        $cleanPath = preg_replace('/[\w|\d]+:\/\//', '', $this->getRelativeFilePath());
        $cleanPath = ltrim($cleanPath, DIRECTORY_SEPARATOR);

        // Handle vfs:// paths by replacing their forward slashes with the OS appropriate directory separator
        if (DIRECTORY_SEPARATOR !== '/')
        {
            $cleanPath = str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
        }

        // Check the first folder and see if it's a data folder (starts with an underscore) intended for stakx
        $folders = explode(DIRECTORY_SEPARATOR, $cleanPath);

        if (substr($folders[0], 0, 1) === '_')
        {
            array_shift($folders);
        }

        $cleanPath = implode(DIRECTORY_SEPARATOR, $folders);

        return $cleanPath;
    }

    /**
     * Sanitize a permalink to remove unsupported characters or multiple '/' and replace spaces with hyphens.
     *
     * @param string $permalink A permalink
     *
     * @return string $permalink The sanitized permalink
     */
    protected function sanitizePermalink($permalink)
    {
        // Remove multiple '/' together
        $permalink = preg_replace('/\/+/', '/', $permalink);

        // Replace all spaces with hyphens
        $permalink = str_replace(' ', '-', $permalink);

        // Remove all disallowed characters
        $permalink = preg_replace('/[^0-9a-zA-Z-_\/\\\.]/', '', $permalink);

        // Handle unnecessary extensions
        $extensionsToStrip = array('twig');

        if (in_array($this->fs->getExtension($permalink), $extensionsToStrip))
        {
            $permalink = $this->fs->removeExtension($permalink);
        }

        // Remove any special characters before a sane value
        $permalink = preg_replace('/^[^0-9a-zA-Z-_]*/', '', $permalink);

        // Convert permalinks to lower case
        $permalink = mb_strtolower($permalink, 'UTF-8');

        return $permalink;
    }

    /**
     * @return void
     */
    abstract protected function buildPermalink();
}
