# Contributing to stakx

Contributions take many forms, from submitting issues, writing documentation, to making code changes. We welcome it all. Don't forget to sign up for a [GitHub account](https://github.com/join), if you haven't already.

## Getting Started

You can clone this repository locally from GitHub using the "Clone in Desktop" button from the main project site, or run this command from the command line:

`git clone https://github.com/allejo/stakx.git stakx`

If you want to make contributions to the project, [forking the project](https://help.github.com/articles/fork-a-repo) is the easiest way to do this. You can then clone down your fork instead:

`git clone git@github.com:MY-USERNAME-HERE/stakx.git stakx`

### How is the code organized?

The project is housed in the `src/` folder (following PSR-4 convention) and uses the `allejo\stakx` namespace.

#### Unit Tests & PHPDoc

The unit tests are located in the `tests/` directory and follow the same namespace pattern. For example, a class in the `allejo\stakx\Object` will have a unit test in the `allejo\stakx\Test\Object` namespace. The folder structure for the tests follow the same pattern as the core project.

All contributions should have the appropriate tests written and/or modified to reflect the changes made. In addition to unit tests, we have documentation provided for our classes so any changes made should also be reflected appropriately in the documentation.

### What needs to be done?

Looking at our [**issue tracker**](https://github.com/allejo/stakx/issues?state=open) is the quickest way to see what needs to get done.

If you've found something you'd like to contribute to, leave a comment in the issue or start a new issue so everyone is aware.

## Making Changes

When you're ready to make a change, [create a branch](https://help.github.com/articles/fork-a-repo#create-branches) off the `master` branch. We use `master` as the default branch for the repository, and it holds the most recent contributions, so any changes you make in master might cause conflicts down the track.

If you make focused commits (instead of one monolithic commit) and have descriptive commit messages, this will help speed up the review process.

Be sure that you thoroughly test your changes and that your new features do not break existing features. In addition, write the appropriate unit tests and documentation for any new features that you have added or changes you've made.

### Coding Style

The stakx project uses `.editorconfig` to keep the coding style as uniform as possible. Please be sure your text editor or IDE properly supports the `.editorconfig` file. If it does not, please install the [respective plugin](http://editorconfig.org/#download) for your IDE or text editor. For more information, take a look at the [EditorConfig website](http://editorconfig.org/).

For code formatting, we use [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) and have a `.php_cs` configuration, however this is not the final format we use. After, we use the provided `.idea/codeStyleSettings.xml` in PhpStorm to fix up some changes that PHP-CS-Fixer doesn't do quite right at the time of writing this.

However, use your best judgement and follow the existing coding style; e.g. do **not** reformat the entire project for a PR that only modifies a single file.

#### Coding Practices

- Curly braces (`{` `}`) **must** be on their own line

```php
if (true)
{
    // ...
}
```

- If statements, for/while loops, and switch statements, **must** have braces even if only contains a single line of code.

```php
if (true)
{
    return false;
}
```

- When defining multiple variables in a section, align the equal signs

```php
$aString = "Hello World";
$number  = 42;
```

### Submitting Changes

You can publish your branch from the official GitHub app or run this command from the command line:

`git push origin MY-BRANCH-NAME`

Once your changes are ready to be reviewed, publish the branch to GitHub and [open a pull request](https://help.github.com/articles/using-pull-requests) against it.

A few tips with pull requests:

 - prefix the title with `[WIP]` to indicate this is a work-in-progress. It's always good to get feedback early, so don't be afraid to open the pull request
   before it's "done"
 - use [checklists](https://github.com/blog/1375-task-lists-in-gfm-issues-pulls-comments) to indicate the tasks which need to be done, so everyone knows how close you are to done
 - add comments to the pull request about things that are unclear or you would like suggestions on

Don't forget to mention in the pull request description which issue(s) are being addressed.

Some things that will increase the chance that your pull request is accepted.

- Follows the [specified coding style](#coding-style) properly
- Update the documentation, the surrounding comments/docs, examples elsewhere, guides, whatever is affected by your contribution

## Acknowledgements

- Thanks to the [Octokit team](https://github.com/octokit/octokit.net/blob/master/CONTRIBUTING.md) for inspiring these contributions guidelines.