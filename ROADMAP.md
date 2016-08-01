# Development Roadmap

This is the general road map of the direction stakx is heading

## Version 1.0.0

- Add support for Sass
    - Experiment with the difference between [libsass bindings](https://github.com/absalomedia/sassphp) and [scssphp](https://github.com/leafo/scssphp)
- Introduce `serve` command to serve a web server for the stakx website currently being built
    - Should build the website first and then watch for changes to rebuild accordingly.
- Rebuilding a website using the `watch` command should not reparse everything, instead just rebuild what's been changed
- Add custom Twig filter/function support
    - Possibly add plug-in support if there is a need for something that cannot be achieved by extending Twig
