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

### Custom Language Definitions

Have you created your own language or working with a custom language for your project? You can define your own language definition files and have stakx load them up. As an example, I'd like to load a custom language for [BZFlag](https://www.bzflag.org/)'s map syntax so I would do the following:

```yaml
highlighter:
  languages:
    bzw: _languages/bzw.json
```

And then I'd be able to use that definition by using `bzw` in my fenced code block.

```bzw
options
  -j
  +r
  -mp 0,10,10,10,10,50
  -set _gravity -15
  +f GM{1}
end

# comment here
box
  name Box box copy copy
  position 0 0 0 # another box comment
  size 5 5 10
  rotation 45
  matref toast
end

pyramid
  pos 0 0 0
  size 10 10 15
end

base
  position 0 10 0
  color 1
end

zone
  position 20 10 0
  size
  team 1
  flag G
  zoneflag R*
end
```
