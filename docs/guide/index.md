---
layout: home

hero:
  name: Laravel Autentique
  text: Electronic signatures from Laravel, through the Autentique API
  tagline: Documents, signers, folders and webhooks as typed values behind a facade. Every request goes through Laravel's HTTP client, so your tests fake it like any other.
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: The Autentique API
      link: https://docs.autentique.com.br/api

features:
  - title: Operations you can read
    details: Every call is a GraphQL file in the package, sent with variables and validated against the API's schema, rather than a string assembled at runtime.
  - title: Fakes with the framework's own tools
    details: One transport, Laravel's HTTP client. Http::fake() and preventStrayRequests() reach every request, uploads included.
  - title: Typed in, typed out
    details: Signers, positions and verifications are built as value objects, and every answer comes back as one, with enums and dates rather than nested arrays.
---

## A release candidate

`1.0.0-rc.1` carries everything planned for 1.0.0. It has not yet run against
Autentique itself, which `1.0.0` waits for
([#48](https://github.com/lsnepomuceno/laravel-autentique/issues/48)).

`lsnepomuceno/laravel-autentique` replaces
[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2),
unfinished since 2021. Why it was replaced rather than upgraded is
[part of this site's history](/history/from-laravel-autentique-v2).

## What this package is

A Laravel client for the [Autentique API v2](https://docs.autentique.com.br/api),
a GraphQL API for electronic signatures. What it adds over sending the
operations yourself:

- the operations, written once, checked against the API's schema;
- the rules the API documents, enforced before a request is spent;
- the API's errors, which arrive with HTTP 200, turned into exceptions that
  name the fault;
- uploads from a path, a request or a `Storage` disk;
- webhooks verified and delivered as a Laravel event;
- a fake for your own tests.

Why each is built the way it is lives in [the decision records](/decisions/README).
