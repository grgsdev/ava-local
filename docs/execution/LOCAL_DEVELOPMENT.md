# Execução Local com Laradock

## 1. Finalidade

Este documento caracteriza o ambiente local observado no A5. Caracterização não é autorização para executar comandos. A AVA-DOC-002 não executou comandos dentro de container; a AVA-ENV-001 executou somente as validações funcionais explicitamente autorizadas e não executou instalação, migration, seeder, teste real, build ou acesso a dados.

## 2. Estado da evidência

- `[FATO COMPROVADO]`: observado em Git, arquivos, Docker/Compose, labels, mounts ou imagens.
- `[INFERÊNCIA]`: conclusão provável sem prova funcional completa.
- `[RISCO]`: possível regressão, exposição ou alteração de estado.
- `[PENDÊNCIA]`: informação ainda não comprovada.
- `[DECISÃO]`: orientação canônica já aprovada.

## 3. Repositório

[FATO COMPROVADO] Projeto: `/home/iomar/Dev/avamovie-platform`; branch `main`; HEAD `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`; upstream `origin/main`; ahead/behind `0/0`.

[DECISÃO AVA-MVP-BASELINE-DOCUMENTATION-ALIGNMENT-002] Para trabalho local do ciclo atual, o baseline substitui somente o preflight histórico: branch `mvp/foundation-phase4`; HEAD `b237f9ec82db3210ff2673a3ccfed90a3de19d9d`; base remota `origin/main` em `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`; ahead/behind `1/0`; upstream intencionalmente ausente; staged `0`; 55 paths funcionais; 3 de tooling; e 2 documentais persistentes, totalizando 60. Os paths documentais são `docs/governance/CURRENT_STATE.md` e `docs/product/MVP_REQUIREMENTS_INDEX.md`, introduzidos pela `AVA-MVP-DOC04-INTEGRAL-MATRIX-001-RESUME-001`. `bin/doctor` exige o conjunto exato, falha diante de path ausente ou novo e protege a assinatura atual de `CourseBuilderController`. As alterações de `bin/doctor`, `AGENTS.md` e deste documento são tooling explicitamente verificado nesta unidade e não ampliam o baseline funcional.

[FATO COMPROVADO] A alteração preexistente é `app/Http/Controllers/Admin/CourseBuilderController.php`. A árvore `docs/` da AVA-DOC-001 está untracked e contém os 16 arquivos aprovados.

## 4. Laradock oficial

[FATO COMPROVADO] Caminho: `/home/iomar/Dev/laradock`.

[FATO COMPROVADO] O vínculo é sustentado por labels dos containers: Compose project `laradock`, working directory `/home/iomar/Dev/laradock`, arquivo `docker-compose.yml`, e containers ativos com mount `/home/iomar/Dev` em `/var/www`, que contém o projeto original.

[FATO COMPROVADO] Arquivo Compose efetivamente usado: `/home/iomar/Dev/laradock/docker-compose.yml`. `.env` e `.env.example` existem no Laradock; o `.env` não foi lido.

Confiança da identificação: alta. Outros candidatos próximos foram tratados como projetos sem vínculo comprovado.

## 5. Containers e serviços

| Serviço | Container | Imagem | Estado observado | Vínculo com AVAMovie | Uso |
|---|---|---|---|---|---|
| workspace | `laradock-workspace-1` | `laradock-workspace:latest` | running | comprovado pelo mount do diretório de desenvolvimento | workspace |
| php-fpm | `laradock-php-fpm-1` | `laradock-php-fpm:latest` | running | comprovado pelo mount e dependência Compose | PHP-FPM |
| php-worker | `laradock-php-worker-1` | `laradock-php-worker:latest` | running | mount presente; programa AVAMovie específico não comprovado | worker disponível |
| nginx | `laradock-nginx-1` | `laradock-nginx:latest` | running | comprovado pelo mount e vhost mount | web |
| mysql | `laradock-mysql-1` | `laradock-mysql:latest` | running | ativo no mesmo Compose; conexão do app não testada | banco disponível |
| redis | `laradock-redis-1` | `laradock-redis:latest` | running | ativo no mesmo Compose; conexão do app não testada | cache/fila disponível |
| mailpit | `laradock-mailpit-1` | `laradock-mailpit:latest` | running/healthy | ativo no Compose; vínculo funcional não comprovado | mail local disponível |
| phpmyadmin | `laradock-phpmyadmin-1` | `laradock-phpmyadmin:latest` | running | ativo no Compose; vínculo funcional não comprovado | ferramenta disponível |

O Compose expõe ainda serviços Laradock disponíveis ou ativos sem vínculo específico comprovado, como PostgreSQL, pgAdmin, MinIO, Selenium e Docker-in-Docker. Eles não são declarados dependências do AVAMovie por mera presença.

## 6. Workspace

[FATO HISTÓRICO] Serviço/container `workspace`/`laradock-workspace-1`, imagem `laradock-workspace:latest`, diretório de trabalho `/var/www`, foi observado anteriormente como `root`; o mount é `/home/iomar/Dev` → `/var/www`. O projeto aparece no container como `/var/www/avamovie-platform` por essa relação de paths.

[FATO COMPROVADO AVA-ENV-006] O workspace possui também mounts de supervisord.d, Docker-in-Docker e SSH read-only. O usuário padrão observado no container é `root`, mas os wrappers oficiais executam comandos como `laradock` UID/GID `1000:1000` via `docker compose exec --user 1000:1000`.

[FATO COMPROVADO AVA-ENV-006] O usuário local `iomar` é UID/GID `1000:1000`; o usuário `laradock` existe no workspace com UID/GID `1000:1000` e acessa PHP, Composer, Node, NPM e o projeto.

## 7. PHP e Composer

| Componente | Origem | Versão configurada | Container/serviço | Uso | Confiança |
|---|---|---|---|---|---|
| PHP | `.env.example` `PHP_VERSION` e build args | 8.3 | workspace/php-fpm/php-worker | imagem/build Laradock | alta para configuração; não é teste interno |
| Composer | `.env.example` `WORKSPACE_COMPOSER_VERSION` | 2 | workspace | ferramenta de dependências | média |

[PENDÊNCIA] A versão realmente instalada dentro do workspace não foi executada. `composer.json` possui scripts, mas isso não prova que sejam o fluxo oficial local.

## 8. Node e NPM

| Componente | Origem | Versão configurada | Container/serviço | Uso | Confiança |
|---|---|---|---|---|---|
| Node | `.env.example` `WORKSPACE_INSTALL_NODE`/`WORKSPACE_NODE_VERSION` | instalado; `node` como alias de versão | workspace | frontend | média |
| NPM | Dockerfile/Node; sem versão fixa comprovada | não fixada | workspace | dependências/build | baixa |

[PENDÊNCIA] Não foram executados Node ou NPM dentro do container. O `package.json` declara apenas `build` e `dev`; isso não autoriza execução nesta unidade.

## 9. MySQL

[FATO COMPROVADO] Serviço/container `mysql`/`laradock-mysql-1`, imagem local `laradock-mysql:latest`, configuração `.env.example` `MYSQL_VERSION=8.4`, porta publicada 3306, rede backend e volume `/home/iomar/.laradock/data/mysql` → `/var/lib/mysql`.

[PENDÊNCIA] Readiness funcional, conexão do Laravel, schema e dados não foram testados. Não conectar, listar bancos ou abrir cliente sem unidade autorizada.

## 10. Redis

[FATO COMPROVADO] Serviço/container `redis`/`laradock-redis-1`, imagem local `laradock-redis:latest`, porta publicada 6379, rede backend e volume `/home/iomar/.laradock/data/redis` → `/data`.

[PENDÊNCIA] Versão efetiva da imagem, autenticação, readiness e vínculo funcional do app não foram consultados por cliente. Não acessar chaves ou conectar sem unidade autorizada.

[RISCO] A porta publicada em todas as interfaces foi observada; a exposição e os controles locais ainda precisam de decisão específica.

## 11. Wrappers existentes

| Caminho | Tipo | Finalidade | Oficial | Modifica estado | Observações |
|---|---|---|---|---|---|
| `bin/` | diretório | inexistente | não | não aplicável | nenhum wrapper |
| `scripts/` | diretório | inexistente | não | não aplicável | nenhum script |
| `Makefile` | arquivo | inexistente | não | não aplicável | nenhum target |
| `composer.json` | manifesto | scripts Laravel padrão/customizados | não comprovado | potencialmente sim | não executar |
| `package.json` | manifesto | `build` e `dev` | não comprovado | potencialmente sim | não executar |
| `documentation/commands.md` | documentação | comandos históricos/mistos | não | vários | contém ações destrutivas e proibidas |
| Laradock Compose | infraestrutura local | serviços e mounts | comprovado para caracterização | lifecycle altera estado | não iniciar/parar nesta unidade |

## 12. Comandos oficiais comprovados

Nenhum comando completo de desenvolvimento, instalação, migration, seeder, teste, build, cache, worker, scheduler ou deploy está aprovado para execução automática.

Foram comprovadas somente consultas de caracterização read-only, usando o Compose identificado:

- `docker compose -f /home/iomar/Dev/laradock/docker-compose.yml config --services` — lista declarativa de serviços; não inicia containers.
- `docker compose -f /home/iomar/Dev/laradock/docker-compose.yml ps --all` — estado dos serviços; não altera lifecycle.
- `docker ps`, `docker inspect` com campos sanitizados e `docker compose ls` — inventário Docker; não executar campos de ambiente/comando sensíveis.

Essas consultas não são fluxo de desenvolvimento nem substituem uma unidade autorizada.

## 13. Comandos não aprovados automaticamente

Instalação; Composer; NPM; build; migrations; seeders; testes; lint; formatter; cache; workers; scheduler; deploy; acesso MySQL; acesso Redis; `docker exec`; `docker compose run`; `up`; `down`; `start`; `stop`; `restart`; `build`; `pull`; e qualquer comando em container.

Os comandos encontrados em `documentation/commands.md` são referências históricas, não comandos oficiais aprovados. Alguns incluem reset, pull, restart, migrations, seeders, cache, Scribe e comandos de Supervisor.

## 14. Permissões e ownership

[FATO COMPROVADO AVA-ENV-006] O mount principal é read-write. O Dockerfile do Laradock configura usuário não-root potencial com PUID/PGID; o workspace efetivo possui `laradock` UID/GID `1000:1000`.

[DECISÃO AVA-ENV-006] Os wrappers oficiais passam explicitamente o UID/GID local não root ao `workspace`; não há fallback silencioso para root. O ownership esperado dos artefatos é `iomar:iomar` no host.

[RISCO] Execuções diretas fora dos wrappers podem continuar produzindo ownership inadequado; o contrato oficial exige os wrappers e a unidade deve parar diante de artefato root inesperado.

## 15. Preflight local

Antes de futura execução autorizada: confirmar repositório, branch, HEAD, upstream, ahead/behind, staged, unstaged e untracked; confirmar Laradock path, Compose project e mounts; confirmar que a tarefa autoriza o comando; não ler `.env`; não usar `docker exec`; registrar estado e rollback quando aplicável.

## 16. Condições de parada

Parar diante de baseline Git divergente, Laradock inconclusivo, múltiplos Compose projects plausíveis, segredo, necessidade de comando em container, mudança de container, alteração de código/dependência, risco de perda, decisão de produto ou comando inventado.

## 17. Lacunas

Não foram comprovados: programa real do php-worker; vínculo funcional do Laravel com MySQL/Redis; readiness além da porta TCP; comandos de desenvolvimento aprovados; e política de ownership.

## 18. Validação funcional da AVA-ENV-001

[FATO HISTÓRICO] O serviço `workspace` está ativo no projeto Compose `laradock`, com o projeto em `/var/www/avamovie-platform`. A observação anterior registrou usuário efetivo `root` (`uid=0`, `gid=0`); AVA-ENV-006 validou execução oficial como `laradock` (`uid=1000`, `gid=1000`).

[FATO COMPROVADO] PHP: `/usr/bin/php`, versão 8.3.30, CLI, configuração principal em `/etc/php/8.3/cli/php.ini`. Composer: `/usr/local/bin/composer`, versão 2.9.5. Node: `/usr/local/bin/node`, versão 25.9.0. NPM: `/usr/local/bin/npm`, versão 11.12.1.

[FATO COMPROVADO] `php artisan --version` respondeu `Laravel Framework 12.21.0`, sem alteração Git ou arquivo inesperado observada.

[FATO COMPROVADO] `mysql` e `redis` resolveram na rede do workspace e aceitaram verificação TCP passiva nas portas 3306 e 6379, sem autenticação, SQL, cliente MySQL ou cliente Redis.

[FATO COMPROVADO] `composer.json` declara os scripts `dev`, `test` e eventos de ciclo de vida. `package.json` declara `build` e `dev`. O script Composer `dev` inicia múltiplos processos; o script `test` limpa configuração e executa testes. Nenhum script foi executado.

[FATO COMPROVADO] Foram criados `bin/dev`, `bin/doctor`, `bin/test` e `bin/quality`, com Bash, modo executável, preflight de caminho e interface conservadora. `bin/doctor` foi executado com sucesso; os demais foram validados somente em help/list/dry-run, conforme a unidade.

[DECISÃO] Nenhum fluxo completo de desenvolvimento, teste ou qualidade está aprovado automaticamente. Os wrappers recusam execução real ou exibem apenas dry-run até unidade específica.

[DECISÃO AVA-ENV-006] A execução oficial de comandos mutáveis no workspace usa usuário não root explícito compatível com o host. Execução root não é fallback permitido.

[PENDÊNCIA] Ainda não foram executados scripts Composer/NPM, testes, build, lint, análise estática, instalação, migrations ou seeders. A confirmação de comportamento funcional além do bootstrap mínimo permanece pendente.

## 19. Contrato AVA-ENV-003 — testes MySQL locais

[DECISÃO] O PHPUnit usa exclusivamente `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306` e o banco local exclusivo `avamovie_testing`. SQLite não é configuração canônica de testes.

[FATO COMPROVADO] O serviço `mysql` está ativo no Compose `laradock` e o serviço `redis` resolve localmente como `redis` na porta `6379`.

[DECISÃO] Credenciais de banco não são versionadas. O runtime reutiliza somente credenciais locais já presentes no `.env` ignorado, sem imprimi-las ou persistí-las. O arquivo `phpunit.xml` contém apenas o contrato sanitizado e versionável.

[DECISÃO] `bin/test --focused <alvo>` é o entrypoint oficial para teste real focado. Ele recusa suíte completa, exige alvo explícito, rejeita opções destrutivas e valida efetivamente `testing/mysql/mysql/avamovie_testing/redis` antes do PHPUnit.

[DECISÃO] A preparação do banco deve ser feita separadamente com `php artisan migrate --force`, somente após o gate efetivo, e apenas em `avamovie_testing`. São proibidos `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `rollback`, `db:wipe` e `schema:drop`.

[RISCO] O banco local de testes é mutável e deve permanecer isolado do banco de desenvolvimento. Execuções diretas como root continuam fora do contrato dos wrappers.

[PENDÊNCIA] A aplicação autenticada com MySQL, a execução das 61 migrations e os testes focados dependem da unidade AVA-ENV-003 e de credenciais locais válidas.

## 20. Decisão AVA-ENV-004 — configuração rastreável

[DECISÃO] `phpunit.xml.dist` é a configuração rastreável e sanitizada do ambiente de testes. Ela fixa `APP_ENV=testing`, MySQL em `mysql:3306`, banco `avamovie_testing` e Redis em `redis:6379`, sem usuário ou senha.

[DECISÃO] `bin/test` passa explicitamente `--configuration phpunit.xml.dist`; portanto um clone novo não depende do `phpunit.xml` local, que permanece ignorado pela regra preexistente do `.gitignore`.

[FATO COMPROVADO] A configuração dist contém apenas placeholders/valores locais de teste e não contém endpoint externo, RDS, AWS ou credencial real.

[PENDÊNCIA] Credenciais locais continuam sendo pré-condição operacional fora do repositório; sua ausência deve fazer o gate falhar, nunca provocar fallback para SQLite ou host externo.
