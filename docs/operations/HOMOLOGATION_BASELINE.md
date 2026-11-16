# Baseline de Homologação

| Item | Estado conhecido |
|---|---|
| Commit/branch | `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6` / `main` |
| Dirty files | `package-lock.json`; `resources/views/scribe/index.blade.php` |
| Sistema | Ubuntu 24.04 |
| PHP CLI/FPM | 8.3.27 |
| Composer | 2.8.12 |
| Node/NPM | 24.11.0 / 11.6.1 |
| Nginx | 1.24.0 |
| PHP-FPM | ativo |
| Supervisor | ativo, sem programas AVAMovie carregados |
| Cron | ativo |
| Redis | ativo |
| Workers | não observados |
| Scheduler Laravel | não observado |
| Reverb | não observado |
| Scribe | quatro rotas contidas no Nginx com 404 |

[RISCO] O vhost está fora do Git; um deploy pode remover a contenção. [PENDÊNCIA] Deploy formal, backup, rollback, RDS, schema e funcionamento requisito por requisito ainda não estão comprovados.

Não são reproduzidos neste arquivo IPs, credenciais, segredos ou caminhos de chave.
