# website-d7

## Introduction
As of 2020, IAC's web site (www.iac.org) is running on Drupal 7. This repository contains Drupal scripts, the `iac` custom Drupal module, and useful code fragments.

## Plans & Priorities
Check the [issues list](https://github.com/international-aerobatic-club/website-d7/issues).

## DevOps
### Version Control
We will use GitHub to store the project source code files. The repo is at:
https://github.com/international-aerobatic-club/website-d7.git

### Folder Structure
- scripts - Daily, annual, and ad hoc scripts
- themes - The IAC custom Drupal themes
- modules - The IAC custom Drupal module

### Issue Tracking
We will use GitHub's Issues feature. This allows us to tag our commits with the issue number that they address. (Just put something like `Fixed issue #99` in the commit comment and GitHub will link it automatically.)

We'll apply Issue Labels to categorize each entry. Labels include:
* **bug**
* **duplicate** (when we discover that an issue is a duplicate, mark it as such and close it with a reference to the issue # that it duplicates)
* **enhancement** (any new feature or functionality)
* **open issue** (topics to research, decisions that need to be made, etc.)
* **task** (non-coding work, e.g. "research commercial e-mail services")
* Priority level (**pri: low**, **pri: medium**, **pri :high**)

### Branching
Never work directly in _master_. Create a branch, code and test there, then merge with _master_ when ready. Best practice is to create one branch per issue, and only make code changes that are related to that issue. Branch names are at the developer's discretion. Consider including the issue number in the branch name.

**Cheat sheet:**
* Create a branch: `git branch <my-branch-name>`
* Create a branch and check it out: `git checkout -b <my-branch-name>`
* Push changes to your new branch: `git push --set-upstream origin <my-branch-name>`
* Switch between branches: `git checkout <my-branch-name>`
* Merge a branch into master:
    * Commit your changes
    * `git push`
    * `git checkout master`
    * `git merge <my-branch-name>`
    * Resolve any conflicts (hopefully none!)
    * `git push` (pushes the merged changes to github)
* Archive and lock a branch against future changes:
    * `git tag -a <my-branch-name> -m "Merged into master"`
    * `git branch -d <my-branch-name>`

## Contacts
- IAC's webmaster is Brennon York, webmaster@iac.org
- Stephen Kurtzahn is the club's Executive Director, execdir@iac.org
- DJ Molny helps with sysadmin and maintenance tasks, djmolny@gmail.com
