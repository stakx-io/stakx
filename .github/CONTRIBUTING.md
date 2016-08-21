# Contributing to stakx

Contributions take many forms, from submitting issues, writing documentation, to making code changes. We welcome it all. Don't forget to sign up for a [GitHub account](https://github.com/join), if you haven't already.

## Getting Started

You can clone this repository locally from GitHub using the "Clone in Desktop" button from the main project site, or run this command from the command line:

`git clone https://github.com/allejo/stakx.git stakx`

If you want to make contributions to the project, [forking the project](https://help.github.com/articles/fork-a-repo) is the easiest way to do this. You can then clone down your fork instead:

`git clone git@github.com:MY-USERNAME-HERE/stakx.git stakx`

### How is the code organized?

The actual library is housed in the `src/` folder. All of the public classes are in the **allejo\DaPulse** namespace and are located in the root of `src/`. Other utilities that are mainly intended for internal usage are housed in subfolders inside of `src/` such as exceptions, converters, and utilities.

#### Unit Tests & PHPDoc

The unit tests are located in the `tests/` directory. All contributions should have the appropriate tests written and/or modified to reflect the changes made. In addition to unit tests, we have documentation provided for our classes so any changes made should also be reflected appropriately in the documentation.

### What needs to be done?

Looking at our [**issue tracker**](https://github.com/allejo/stakx/issues?state=open) is the quickest way to see what needs to get done.

If you've found something you'd like to contribute to, leave a comment in the issue or start a new issue so everyone is aware.

## Making Changes

When you're ready to make a change, [create a branch](https://help.github.com/articles/fork-a-repo#create-branches) off the `master` branch. We use `master` as the default branch for the repository, and it holds the most recent contributions, so any changes you make in master might cause conflicts down the track.

If you make focused commits (instead of one monolithic commit) and have descriptive commit messages, this will help speed up the review process.

Be sure that you thoroughly test your changes and that your new features do not break existing features. In addition, write the appropriate unit tests and documentation for any new features that you have added or changes you've made.

### Coding Style

The stakx project uses `.editorconfig` to keep the coding style as uniform as possible. Please be sure your text editor or IDE properly supports the `.editorconfig` file. If it does not, please install the [respective plugin](http://editorconfig.org/#download) for your IDE or text editor. For more information, take a look at the [EditorConfig website](http://editorconfig.org/).

In addition, this library is built with PhpStorm and a `.idea/codeStyleSettings.xml` is provided, so if you also use PhpStorm, please use that to format your code appropriately.

#### Coding Practices

- Curly braces (`{` `}`) should always be on their own line

```php
if (true)
{
    // ...
}
```

- If statements, for/while loops, and switch statements, should always have braces even if only contains a single line of code. This allows for adding more code quickly in the future should the need arise.

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

## Contributor License Agreement

By committing to a source code repository or submitting a patch/pull request, you the developer are assigning copyright of the submission to the project maintainer, and his successors.

### Grant of Copyright Assignment

Each Contributor hereby assign to the project maintainer all right, title, and interest worldwide in all Copyright covering the Contribution.

All submissions are subject to these terms.

### Notes
You may not place any restrictions on how the project maintainer maintains or handles your submitted works. Works that are derivations of existing works in the project are subject to the current project license and any other licenses the project may be released under. Authors that submit works must have the legal right to agree to these terms, either as original author or authorized licensee of derived works. Minors must have a parent or guardian sign the acceptance form for them.

## Acknowledgements

- Thanks to the [Octokit team](https://github.com/octokit/octokit.net/blob/master/CONTRIBUTING.md) for inspiring these contributions guidelines.