---
title: "Components"
description: "Component syntax and compilation semantics for codemonster-ru/razor"
order: 2
---

# Components

Status: Accepted  
Decision: ARAZOR-001  
Date: 2026-08-12

Razor components are server-rendered, explicitly registered tags. They provide reusable rendering
without giving templates access to arbitrary PHP classes or view names.

## Tags and registration

A component tag is a lowercase kebab-case prefix and name separated by one hyphen:

```razor
<cm-button />
<cm-button>Save</cm-button>
```

Applications register a provider for the complete prefix. Registering `cm` with a `button`
component resolves `<cm-button>`; unregistered prefixes and names are errors. Native HTML and custom
elements that do not use a registered prefix pass through unchanged.

Component tags may be self-closing or paired. Closing tags must match exactly and components may be
nested. Tag and prop names are case-sensitive and use lowercase kebab case; slot names use lower
camel case.

## Props

Attributes become component props:

```razor
<cm-button variant="secondary" :disabled="$saving" autofocus />
```

- quoted static props compile to strings;
- a leading colon evaluates one PHP expression and stores its result under the name without the
  colon;
- a bare prop compiles to boolean `true`;
- duplicate props, empty expressions, spread syntax, and unquoted static values are errors;
- props are evaluated once, from left to right, before a component renders;
- ordinary values are data and never become trusted markup implicitly.

The generic engine transports props without component-specific coercion. Each component owns its
defaults, finite values, required props, and HTML attribute policy.

## Slots

Content inside a paired tag becomes the `default` slot:

```razor
<cm-button>Save {{ $document->title }}</cm-button>
```

Named slots are direct `<razor-slot>` children with one static `name` attribute:

```razor
<cm-button>
    <razor-slot name="leading"><span aria-hidden="true">←</span></razor-slot>
    Move
    <razor-slot name="trailing"><span aria-hidden="true">→</span></razor-slot>
</cm-button>
```

Named-slot declarations are removed from the default slot. A slot name may appear once. A
`<razor-slot>` outside a component, nested below ordinary default-slot markup, or carrying other
attributes is malformed.

Slots are lazy, zero-argument renderers. They capture the template scope at the component call site,
render at most when requested by the component, and return a trusted rendered-HTML value. Ordinary
interpolation inside a slot remains escaped. Nested components render through the same resolver and
their trusted output composes without being escaped a second time.

## Compilation and rendering

The compiler recognizes only prefixes registered on the engine's component resolver. It compiles a
component invocation to a resolver call with its tag, evaluated props, and slot renderers. Component
objects return the dedicated trusted rendered-HTML value; arbitrary strings do not cross that trust
boundary.

Compilation is deterministic for a template source and the resolver's cache signature. Adding,
removing, or replacing a registered prefix or component invalidates affected compiled templates.
Component rendering uses the current render context, so includes, layouts, escaping, and nested
components share one request-local execution state.

## Diagnostics

Malformed recognized component syntax raises `RazorException` with the template path and source
line. Diagnostics cover at least unknown components under a registered prefix, mismatched or
missing closing tags, malformed props, duplicate props or named slots, invalid slot placement, and
component output that is not trusted rendered HTML.

Component failures preserve their original exception as the cause and identify the component tag.
HTML outside registered prefixes retains existing Razor behavior.

## Security boundary

- `{{ ... }}` remains escaped.
- `{!! ... !!}` remains an explicit application-owned raw-output escape hatch.
- static prop text and evaluated prop values are not HTML.
- only compiler-created slots and component return values represented by the trusted HTML type are
  composed without escaping.
- registration is application configuration; template data cannot select providers, component
  classes, slot names, or arbitrary templates dynamically.
