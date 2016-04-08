---
title: Introduction to Stakx
date: 2016-03-03
---

By now you must understand that Stakx is an alternative to Jekyll. But how is it different and what added features does it have that Jekyll doesn't?

### The Configuration

Just like Jekyll, the `_config.yml` file is the main configuration for building any Stakx website. Any file beginning with an underscore or a period will not be copied into the compiled website, which by default is written to `_site`. In addition to these similarities, FrontMatter is here to stay in all ContentItems.

### Collections, ContentItems, & DataItems

Everything in Stakx is either ContentItem or a DataItem, and both of these types are stored in Collections defined in the site's `_config.yml`. Using Stakx to build a blog is as simple as defining a "Posts" collection, pointing it to a directory, and defining a permalink pattern to be used for each generated page.

After you've defined a collection, simply create a Twig template defining which collection to used and access all of the information for that ContentItem through the `item` variable.

```_config.yml```

```yaml
collections:
  - name: posts
    folder: _posts
```

```_pages/blog/show.html.twig```

```twig
---
collections: posts
permalink: /blog/%year/%month/%day/%title
---

<h1>{{ item.title }}</h1>
<p>{{ item.date | date('F d, Y') }}</p>
<section>
    {{ item.content | nl2br }}
</section>

```

### PageViews, PartialViews, & Themes

A PageView in Stakx is a Twig file that will be compiled into a HTML page with respect to the permalink. A PartialView is a Twig file that is not compiled into a page but instead is used to support other Twig files. For example, themes are built mainly of PartialViews and do not result in a page being created but instead are designed to be extended by PageViews.

PartialViews do not contain FrontMatter are intended solely for layouts or macros making them key to an Stakx theme. Themes in Stakx are a little different from Jekyll's concept of themes. In Jekyll, a theme is different merged and intertwined with the website itself and lead to some undesirable frustration when trying to change themes if you have built upon them. Stakx keeps themes entirely separate from the website inside of the special `_themes` folder. The default Bootstrap theme provided with a stock Stakx website is an example of how a theme can be built to remain separate but still be reusable across different themes.