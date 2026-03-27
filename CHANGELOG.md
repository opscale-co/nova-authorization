# Changelog

All notable changes to opscale-co/nova-api will be documented in this file.

## <small>1.1.2 (2026-03-27)</small>

* Merge branch 'main' of https://github.com/opscale-co/nova-authorization ([326eb46](https://github.com/opscale-co/nova-authorization/commit/326eb46))
* fix(muti tenant support): fixed how policies are registered ([de9df4b](https://github.com/opscale-co/nova-authorization/commit/de9df4b))

## <small>1.1.1 (2026-03-09)</small>

* Merge pull request #6 from findex-la/main ([79c8a9c](https://github.com/opscale-co/nova-authorization/commit/79c8a9c)), closes [#6](https://github.com/opscale-co/nova-authorization/issues/6)
* fix(roletag): refactor role assignment logic to improve clarity and efficiency ([8ebb747](https://github.com/opscale-co/nova-authorization/commit/8ebb747))

## 1.1.0 (2026-03-09)

* Merge pull request #5 from findex-la/main ([d6787a0](https://github.com/opscale-co/nova-authorization/commit/d6787a0)), closes [#5](https://github.com/opscale-co/nova-authorization/issues/5)
* feat(migration): add ModifyPermissionMigration action to update permission migration to use ULIDs ([ae29129](https://github.com/opscale-co/nova-authorization/commit/ae29129))
* refactor(models): add HasUlids trait to Permission and Role classes ([62f6c7e](https://github.com/opscale-co/nova-authorization/commit/62f6c7e))

## <small>1.0.4 (2026-03-05)</small>

* fix(roletag): added RoleTag field to trigger events properly ([ba2378a](https://github.com/opscale-co/nova-authorization/commit/ba2378a))

## <small>1.0.3 (2026-02-23)</small>

* fix(service provider): policies are registered only when Nova is being served ([bb3f47f](https://github.com/opscale-co/nova-authorization/commit/bb3f47f))
* Merge branch 'main' into main ([2f7850f](https://github.com/opscale-co/nova-authorization/commit/2f7850f))
* Merge pull request #3 from findex-la/main ([83a602f](https://github.com/opscale-co/nova-authorization/commit/83a602f)), closes [#3](https://github.com/opscale-co/nova-authorization/issues/3)
* refactor(core): use configured role and permission models instead of hardcoded classes ([817e6ed](https://github.com/opscale-co/nova-authorization/commit/817e6ed))

## <small>1.0.2 (2026-01-23)</small>

* Merge pull request #2 from findex-la/main ([85ad482](https://github.com/opscale-co/nova-authorization/commit/85ad482)), closes [#2](https://github.com/opscale-co/nova-authorization/issues/2)
* fix(nova): update authorization settings and fix translation function in User resource ([8c5814a](https://github.com/opscale-co/nova-authorization/commit/8c5814a))

## <small>1.0.1 (2025-12-22)</small>

* perf(project): rewrite using Opscale standards ([377b700](https://github.com/opscale-co/nova-authorization/commit/377b700))
* perf(project): updated project following Opscale guidelines ([18bf0c1](https://github.com/opscale-co/nova-authorization/commit/18bf0c1))
* docs(readme): added instructions ([d93afa5](https://github.com/opscale-co/nova-authorization/commit/d93afa5))
* docs(readme): added more details to the setup ([cae09c7](https://github.com/opscale-co/nova-authorization/commit/cae09c7))
* Merge pull request #1 from opscale-co/develop ([cda8167](https://github.com/opscale-co/nova-authorization/commit/cda8167)), closes [#1](https://github.com/opscale-co/nova-authorization/issues/1)
* Update CHANGELOG ([4e0d52f](https://github.com/opscale-co/nova-authorization/commit/4e0d52f))


### BREAKING CHANGE

* Command signatures changed

# Changelog

## v1.0.0 - 2025-03-26

Secure your Nova resources with roles and permissions



All notable changes to `nova-authorization` will be documented in this file

## v1.0.0 - 2025-03-26

Secure your Nova resources with roles and permissions

## 1.0.0 - 2024-XX-XX

- initial release
