---
paths:
  - 'resources/js/**'
---

# Js

## Variables are camelCase
Every JavaScript variable, constant, parameter and function is camelCase: `someExample`, never `some_example`. Keep snake_case only for keys that the server or another API dictates (a JSON field such as `first_name`, a form field name); a variable that holds one is still camelCase (`const firstName = data.first_name`). The PHP rule in `app.md` is the same.

## Header comment for every function
Every function, event listener and option object gets a `/** ... */` block comment above it with a one-line description, e.g. `/** Add a keydown event listener to the document */` laid out over three lines. The description says what it does, in a short phrase.

## Single-statement functions skip the inline comment
When a function body is exactly one statement (one top-level `;`), it keeps its header comment but gets no inline `//` comment above the statement. This holds however many lines the statement spans.

Functions with two or more top-level statements keep a `//  ` comment before each. Statements nested inside the single statement (e.g. in a callback) still follow the normal comment rule.

## Chain method calls one per line
When a statement chains more than three links, counting the object, or the chain is too long for one line (roughly over 100 characters), put the object on the first line and each further call on its own line, indented once: `document` then `.querySelector('[data-menu]')` then `.addEventListener(...)`. A short chain of three links or fewer stays on one line. Give each chained statement the usual `//  ` comment above it. The PHP rule in `app.md` is the same.
