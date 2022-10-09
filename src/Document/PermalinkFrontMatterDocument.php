<?php declare(strict_types=1);

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Document;

use Exception;

/**
 * A document that builds its permalink from FrontMatter data.
 */
abstract class PermalinkFrontMatterDocument extends FrontMatterDocument implements PermalinkDocument
{
    use PermalinkDocumentTrait;

    /**
     * {@inheritdoc}
     */
    public function buildPermalink($force = false): void
    {
        if ($this->permalink !== null && !$force) {
            return;
        }

        if ($this->frontMatterParser !== null && $this->frontMatterParser->hasExpansion()) {
            throw new Exception('The permalink for this item has not been set');
        }

        $permalink = (is_array($this->frontMatter) && isset($this->frontMatter['permalink'])) ?
            $this->frontMatter['permalink'] : $this->getPathPermalink();

        if (is_array($permalink)) {
            $this->permalink = $permalink[0];
            array_shift($permalink);
            $this->redirects = $permalink;
        } else {
            $this->permalink = $permalink;
            $this->redirects = [];
        }
    }
}
