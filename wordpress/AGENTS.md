# Repository rules — apexvalue

Permanent rules for this repository. All contributors and AI agents must follow them.

## 1. Single contributor identity

Every commit **must** be authored and committed as:

```
Name:  janasco
Email: jaymaranasco@gmail.com
```

- No other identities (including `Jaymar Anasco <…@users.noreply.github.com>` or
  GitHub web-UI commits) may appear in the history — they create a second contributor.
- If your git config differs, commit with:
  `git -c user.name=janasco -c user.email=jaymaranasco@gmail.com commit …`
  or fix an existing commit with `git commit --amend --reset-author --no-edit`.

## 2. No AI contributions or attribution

- **Never** add `Co-Authored-By` trailers, `Generated with …` footers, or any other
  AI/machine attribution to commit messages, pull requests, issues, or code comments.
- Work produced with AI assistance is committed **only** under the identity above,
  with plain, descriptive commit messages.

## 3. Enforcement

A `commit-msg` hook in `.githooks/` rejects commits that violate rules 1 or 2.

After cloning, enable it once:

```sh
git config core.hooksPath .githooks
```
