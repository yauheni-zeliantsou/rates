# Exchange Rates

## Quick start

**Requirements:** [Make](https://www.gnu.org/software/make/) and Docker (with Docker Compose) must be installed locally.


| Setting                          | Value                                            |
| -------------------------------- | ------------------------------------------------ |
| **URL**                          | [https://localhost:8888](https://localhost:8888) |
| **Demo user (email / password)** | `user@miractal.com` / `password`                 |


```bash
make deploy
```

This command starts the containers, installs dependencies, runs migrations, and creates the demo user.

---

## About

Web app for viewing Central Bank of Russia exchange rates. The frontend is static HTML/JS/SCSS; the backend is a PHP API with OAuth2 (password grant). Rates are cached in Redis, the currency catalog is stored in PostgreSQL, and rates are refreshed on a cron schedule.

**Stack:** PHP, PostgreSQL, Redis, Nginx, HTML, JavaScript, SCSS.

## Structure

```
client/            — frontend (HTML, JS, SCSS)
server/            — PHP API, migrations, console scripts
docker/            — Nginx and PHP Dockerfiles, cron schedule
docker-compose.yml — Nginx, PHP, cron, PostgreSQL, Redis
```

## Useful commands


| Command                | Description                                                   |
| ---------------------- | ------------------------------------------------------------- |
| `make deploy`          | Full deploy (env, build, client, up, composer, migrate, seed) |
| `make env`             | Create `.env` from `.env.example` if missing                  |
| `make build`           | Build Docker images                                           |
| `make client`          | Install frontend npm dependencies                             |
| `make up`              | Start containers                                              |
| `make composer`        | Install PHP dependencies                                      |
| `make migrate`         | Run database migrations                                       |
| `make seed-currencies` | Seed the currency catalog                                     |
| `make seed-user`       | Create a user (defaults to `user@miractal.com` / `password`)  |


The app port is configured via the `APP_PORT` variable in `.env` (default: `8888`).

## Logs

Application errors and warnings are written via `error_log()`, which php-fpm forwards to the container's stdout/stderr — no extra setup needed:

```bash
docker logs --tail 100 miractal_rates_php    # web requests
docker logs --tail 100 miractal_rates_cron   # fetch-rates cron job
```