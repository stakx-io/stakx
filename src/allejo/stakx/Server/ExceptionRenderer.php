<?php

/**
 * @copyright 2018 Vladimir Jimenez
 * @license   https://github.com/stakx-io/stakx/blob/master/LICENSE.md MIT
 */

namespace allejo\stakx\Server;

use allejo\stakx\Compiler;
use allejo\stakx\Filesystem\FilesystemLoader as fs;
use allejo\stakx\Templating\TemplateErrorInterface;

class ExceptionRenderer
{
    public static function render(\Exception $exception, Compiler $compiler)
    {
        $source = fs::getInternalResource('error.html.twig');
        $template = $compiler->getTemplateBridge()->createTemplate($source);

        if ($exception instanceof TemplateErrorInterface)
        {
            $message = [
                sprintf('File: %s:%d', $exception->getRelativeFilePath(), $exception->getTemplateLine()),
            ];
        }

        $message[] = $exception->getMessage();

        return $template->render([
            'error_title' => 'Internal Server Error (500)',
            'error_exception' => get_class($exception),
            'error_message' => implode("\n\n", $message),
            'error_trace' => $exception->getTraceAsString(),
        ]);
    }
}
