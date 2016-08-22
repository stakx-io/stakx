<?php

/**
 * This file exists to overload the default `render()` function which escapes HTML entities and therefore does not allow
 * for properly highlighted code to render.
 */

namespace Gregwar\RST\HTML\Nodes;

use Gregwar\RST\Nodes\CodeNode as Base;

class CodeNode extends Base
{
    public function render ()
    {
        return "<pre><code class=\"language-" . $this->language . "\">" . $this->value . "</code></pre>";
    }
}
