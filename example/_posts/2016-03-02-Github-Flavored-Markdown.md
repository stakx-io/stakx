---
title: GitHub Flavored Markdown
date: 2016-03-02
---

Stakx uses [Parsedown](http://parsedown.org/) for rendering markdown which is:

- Super Fast
- GitHub Flavored
- Extensible

Because Parsedown allows for it to be extended, Stakx has extended it support syntax highlighting for fenced code blocks just like GitHub! The rendered code blocks are rendered to be [highlight.js](https://highlightjs.org/) compatible; this means you can load any highlight.js stylesheet to theme your syntax highlighting. The rendered code blocks are already rendered with the highlight.js classes so there is no need to have Javascript enabled or to add the Javascript library!

The color schemes provided by the default Bootstrap theme are:

- GitHub
- Monokai
- Tomorrow
- Xcode

### PHP

Here's a basic Hello World snippet in PHP,

```php
<?php

echo "Hello World";
```

### Ruby

...and here's one in Ruby with some class usage as well.

```ruby
class HelloWorld
   def initialize(name)
      @name = name.capitalize
   end
   def sayHi
      puts "Hello #{@name}!"
   end
end

hello = HelloWorld.new("World")
hello.sayHi
```

### Regular Fenced Code Block

Not writing in a specific language? No problem! Use a fenced code block without specifying a language and it'll render just fine. Worried if your language is supported or not? Well, your markdown will render as plain text if the language can't be determined.

```
I just want to write
  some plain preformatted
     text without a language
```
