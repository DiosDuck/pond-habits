# Changelog

## 0.1.0 (2026-11-27)


### Features

* add Laravel 13 API skeleton and backend quality tools ([#3](https://github.com/DiosDuck/pond-habits/issues/3)) ([aff72a5](https://github.com/DiosDuck/pond-habits/commit/aff72a5849604d26ff70045bd6ba42d0ffff7bd1))
* add React PWA skeleton and frontend quality tools ([#4](https://github.com/DiosDuck/pond-habits/issues/4)) ([2ce7185](https://github.com/DiosDuck/pond-habits/commit/2ce7185f1da89ce3d5717841bdbf79d4c1b643f6))
* **admin:** add the users and invites admin pages ([#25](https://github.com/DiosDuck/pond-habits/issues/25)) ([1c0b30f](https://github.com/DiosDuck/pond-habits/commit/1c0b30fa39a1ccfbce1eabf0bf59fb07312ce713))
* **auth:** add the first admin command, set password, login and logout ([#21](https://github.com/DiosDuck/pond-habits/issues/21)) ([389b47a](https://github.com/DiosDuck/pond-habits/commit/389b47afbbe7246c1f34dc8e2f3d27992683ce51))
* **auth:** add the login and set-password pages and the login check ([#22](https://github.com/DiosDuck/pond-habits/issues/22)) ([1003b77](https://github.com/DiosDuck/pond-habits/commit/1003b7772fdaba6aaf54268ec539496b7a3c98eb))
* **auth:** add the password reset ([#24](https://github.com/DiosDuck/pond-habits/issues/24)) ([7b380ed](https://github.com/DiosDuck/pond-habits/commit/7b380edc169ec9bff3d07cdf50f31d22f7479177))
* **backup:** nightly restic backup to B2 and a monthly restore test ([#43](https://github.com/DiosDuck/pond-habits/issues/43)) ([63c50d5](https://github.com/DiosDuck/pond-habits/commit/63c50d5bedac1b77d1db66ed2d1e9d003f15421d))
* **deploy:** add the server Compose stacks for staging and production ([#35](https://github.com/DiosDuck/pond-habits/issues/35)) ([9415dd7](https://github.com/DiosDuck/pond-habits/commit/9415dd7cb0b0f626c904f0e078929605f28bc44f))
* **deploy:** deploy staging on main and production on a tag ([#37](https://github.com/DiosDuck/pond-habits/issues/37)) ([cc7f4c6](https://github.com/DiosDuck/pond-habits/commit/cc7f4c6d92b8fca79ed49d80ecae09de50bd577a))
* **infra:** add the Ansible server setup and the Tailscale policy ([#34](https://github.com/DiosDuck/pond-habits/issues/34)) ([5f51e55](https://github.com/DiosDuck/pond-habits/commit/5f51e55b66cacfb41dc1c4a1831a7affd65c9ef5))
* **invites:** add invites, registration and the admin invite form ([#23](https://github.com/DiosDuck/pond-habits/issues/23)) ([ef33b32](https://github.com/DiosDuck/pond-habits/commit/ef33b329b82ff4d39d30021bd4ca8e85384f1690))
* **layout:** add the app layout, navigation and update prompt ([#20](https://github.com/DiosDuck/pond-habits/issues/20)) ([f9a9509](https://github.com/DiosDuck/pond-habits/commit/f9a95090423875af172ab200006f1094fd12fa0a))
* **logging:** remove personal data from all logs ([#29](https://github.com/DiosDuck/pond-habits/issues/29)) ([bd8113e](https://github.com/DiosDuck/pond-habits/commit/bd8113e396c731187680126b8f9d7c7cbb6154ae))
* **monitoring:** daily mail cap and warnings for mail and failed logins ([#46](https://github.com/DiosDuck/pond-habits/issues/46)) ([a0c2c9e](https://github.com/DiosDuck/pond-habits/commit/a0c2c9e28caa09c409d46ec9d73724b8ba2bc3f9))
* **monitoring:** send error reports to Sentry (EU) with no personal data ([#45](https://github.com/DiosDuck/pond-habits/issues/45)) ([7ece207](https://github.com/DiosDuck/pond-habits/commit/7ece207c9e8a7a4408d1866220e3973f4f6a9b17))
* **security:** add the edge Caddy, the CSP and the trusted proxies ([#38](https://github.com/DiosDuck/pond-habits/issues/38)) ([ce27c25](https://github.com/DiosDuck/pond-habits/commit/ce27c2583bc8c7af517fe95dcd0d548572492755))
* **security:** run the OWASP ZAP baseline scan after each staging deploy ([#41](https://github.com/DiosDuck/pond-habits/issues/41)) ([3f66f73](https://github.com/DiosDuck/pond-habits/commit/3f66f735140c512f2a9038e4e5d30ee176a167cc))
* **seeders:** add the demo admin data ([#28](https://github.com/DiosDuck/pond-habits/issues/28)) ([a07a225](https://github.com/DiosDuck/pond-habits/commit/a07a22555d37323f7ba7254db967ac14ffd386fa))
* **settings:** add the Settings page, export, deletion and operator commands ([#26](https://github.com/DiosDuck/pond-habits/issues/26)) ([d7cecff](https://github.com/DiosDuck/pond-habits/commit/d7cecff540a0d1234abf0aa189b286722e86363e))
* **staging:** Mailpit UI and API docs on the tailnet only ([#39](https://github.com/DiosDuck/pond-habits/issues/39)) ([7d016ea](https://github.com/DiosDuck/pond-habits/commit/7d016ea1ddd5292cf250d5ffc837ca51daa48039))


### Bug fixes

* **ci:** give Renovate the status permissions and start releases at 0… ([#13](https://github.com/DiosDuck/pond-habits/issues/13)) ([37a0a1e](https://github.com/DiosDuck/pond-habits/commit/37a0a1e8b1f59a0f7967041ab5daf0aeb6cd041a))
* **ci:** repair the frontend mutation run and isolate the test database ([#31](https://github.com/DiosDuck/pond-habits/issues/31)) ([372500e](https://github.com/DiosDuck/pond-habits/commit/372500e93614117c0e64172aca7e1996013b3223))
* **ci:** repair the nightly mutation tests ([#32](https://github.com/DiosDuck/pond-habits/issues/32)) ([bf4a5a6](https://github.com/DiosDuck/pond-habits/commit/bf4a5a6113e2115fcbb590adfbdd3e6909059dba))
* **deploy:** check the APP_KEY format and test an encrypted cookie ([#40](https://github.com/DiosDuck/pond-habits/issues/40)) ([c239a6b](https://github.com/DiosDuck/pond-habits/commit/c239a6b3321b63557436bd986ad340b5b4525b16))
* **deploy:** wait for the images before a deploy, and no mail cap in the dev stack ([#47](https://github.com/DiosDuck/pond-habits/issues/47)) ([8360924](https://github.com/DiosDuck/pond-habits/commit/836092460082cc91c180fe261920bc1e9781e566))
* **deps:** update Symfony packages to fix 7 security advisories ([#18](https://github.com/DiosDuck/pond-habits/issues/18)) ([4d61aa1](https://github.com/DiosDuck/pond-habits/commit/4d61aa1c633cbb25fa224436fb463e1150b589e1))
* **logging:** rotate the domain event log daily on the server and keep 14 days ([#36](https://github.com/DiosDuck/pond-habits/issues/36)) ([1b447d2](https://github.com/DiosDuck/pond-habits/commit/1b447d299d494aed5ef4f297a432d3de926091db))
