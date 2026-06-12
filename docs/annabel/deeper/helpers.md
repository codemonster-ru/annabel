---
title: "Helpers"
description: "Global helper functions available in Annabel"
order: 10
---

# Helpers

Annabel provides helpers for common framework services.

## Application

Application helpers expose paths, configuration, environment values, and the
service container.

- `app()`
- `base_path()`
- `config()`
- `env()`

## HTTP

HTTP helpers provide access to the current request, routing, and common
response types.

- `request()`
- `response()`
- `json()`
- `router()`
- `route()`

## Views and assets

View and asset helpers render templates and resolve frontend build entries.

- `view()`
- `vite()`

## Services

Service helpers resolve commonly used framework managers from the container.

- `session()`
- `auth()`
- `user()`
- `db()`
- `schema()`
- `transaction()`
- `cache()`
- `storage()`
- `mailer()`
- `queue()`
- `dispatch()`
- `schedule()`
- `validator()`

## Debugging

Debug helpers inspect values during development.

- `dump()`
- `dd()`

Core package code should prefer explicit dependencies. Helpers are mainly an
application convenience layer.
