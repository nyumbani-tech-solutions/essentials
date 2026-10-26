---
paths:
  - 'lang/**'
  - 'app/Http/**'
---

# Lang

## Language keys
Write user-facing and log messages through `__()` or `trans()`. The key is either the English sentence itself, translated in `lang/{code}.json` (`__('Sign In')`), or a short dotted key from a `lang/en/*.php` file (`trans('alerts.success.saved')`, `trans('logs.login')`). Dotted keys are not required: use whichever the file you are in already uses, and do not rename existing keys. Blade copy is plain English unless the app serves more than one language, then it goes through `__()` with the English text as the key.
