# Contrato do Codex — AVAMovie Platform

Este é o projeto original AVAMovie Platform. Ele é a única base de implementação.

- Plano Mestre canônico: Revisão 2.
- Prioridade atual: Documento 04 — MVP.
- Documento 05: visão futura, sem implementação antecipada.
- Projetos paralelos não são fontes nem opções de execução.

## Leitura obrigatória

Antes de qualquer tarefa, leia:

- [Documentação](docs/README.md);
- [Plano Mestre](docs/governance/AVAMOVIE_MASTER_PLAN.md);
- [Estado atual](docs/governance/CURRENT_STATE.md);
- [Registro de decisões](docs/governance/DECISION_LOG.md);
- [Regras de execução](docs/governance/EXECUTION_RULES.md);
- [Execução local](docs/execution/LOCAL_DEVELOPMENT.md).

Leia também os documentos específicos da unidade quando aplicável.

## Preflight obrigatório

Antes de agir, confirme diretório, raiz Git, remote, branch, HEAD, upstream, ahead/behind, staged, unstaged, untracked e divergências preexistentes. Pare diante de qualquer divergência adicional.

### Baseline local autorizado — AVA-MVP-BASELINE-TOOLING-ALIGNMENT-001

Para continuidade local do MVP, o baseline canônico é: branch `mvp/foundation-phase4`, HEAD `b237f9ec82db3210ff2673a3ccfed90a3de19d9d`, base remota `origin/main` em `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`, comparação `1/0`, sem upstream e sem staged. O conjunto exato contém 55 paths funcionais, 3 paths de tooling e 2 paths documentais persistentes, totalizando 60 divergências brutas. Os paths documentais `docs/governance/CURRENT_STATE.md` e `docs/product/MVP_REQUIREMENTS_INDEX.md` foram introduzidos por `AVA-MVP-DOC04-INTEGRAL-MATRIX-001-RESUME-001`. `bin/doctor` valida o conjunto exato e protege a assinatura atual do `CourseBuilderController`. Pare diante de qualquer divergência além desses conjuntos.

A alteração conhecida em `app/Http/Controllers/Admin/CourseBuilderController.php` deve permanecer intocada. Não abrir para edição, copiar, restaurar, incorporar, descartar ou reformular essa alteração.

## Regras permanentes

- Não alterar AWS sem autorização.
- Não acessar RDS fora de unidade autorizada.
- Não revelar segredos, credenciais, cookies, tokens ou valores de `.env`.
- Não usar dados pessoais reais.
- Não executar migrations destrutivas sem compatibilidade, backup e rollback.
- Não restaurar dirty files silenciosamente.
- Não fazer commit, push, PR, merge, rebase, tag, release ou deploy sem autorização.
- Não tratar documentação, código ou presença de pacote como prova de funcionamento.
- Não ampliar escopo nem depender de `/tmp` para evidência canônica.
- Preservar a contenção Scribe até decisão permanente.
- Produzir evidências persistentes, sanitizadas e com estado Git final.

## Contrato de testes MySQL local

- O ambiente canônico de testes usa exclusivamente `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306`, banco `avamovie_testing` e Redis `redis:6379`.
- SQLite não é evidência nem configuração canônica para testes do projeto.
- `bin/test --focused <alvo>` é o entrypoint oficial para testes reais focados; a suíte completa continua recusada.
- O wrapper valida ambiente efetivo, banco exclusivo e hosts locais antes de executar PHPUnit.
- Migrations de preparação só podem ser executadas no banco exclusivo de testes, sem `fresh`, `refresh`, `reset`, `rollback`, `db:wipe` ou `schema:drop`.
- Hosts externos, RDS, AWS, homologação e produção são sempre recusados.
- A configuração rastreável é `phpunit.xml.dist`; `phpunit.xml` local ignorado não é pré-condição para clone nem para `bin/test`.

## Execução local

O Laradock caracterizado pelo projeto está documentado em [LOCAL_DEVELOPMENT.md](docs/execution/LOCAL_DEVELOPMENT.md).

Entrypoints oficiais: `bin/doctor` para preflight local, `bin/dev` para recusar o fluxo ainda não validado, `bin/test` para listagem/dry-run de testes e `bin/quality` para listagem/dry-run de qualidade. Eles usam o Laradock, o projeto Compose e o workspace documentados em `LOCAL_DEVELOPMENT.md`.

Nesta etapa, a execução dentro do workspace deve usar explicitamente o UID/GID não root do usuário local (`1000:1000`, usuário `laradock` no container). Execução root não é fallback permitido; arquivos gerados devem permanecer pertencentes ao usuário local.

Nesta etapa, podem ser executados automaticamente somente `bin/doctor`, `bin/dev --help`, `bin/test --help`, `bin/test --list`, `bin/test --dry-run`, `bin/quality --help`, `bin/quality --list` e os modos dry-run documentados. Execução real de desenvolvimento, testes, qualidade, build, instalação, migration, seeder, cache, worker, scheduler ou deploy exige unidade específica.

Nenhum comando local completo de desenvolvimento está aprovado para execução automática. As consultas Docker/Compose registradas no documento local são somente de caracterização. Consulte a unidade autorizada antes de executar instalação, migration, seeder, teste, build, cache, worker, scheduler ou deploy.

## Condições de parada

Pare diante de commit ou ambiente desconhecido, divergência não compreendida, working tree inesperada, risco de perda, backup ausente, rollback inviável, segredo exposto, migration destrutiva, risco de acesso entre empresas, incompatibilidade de dados, regressão não compreendida, AWS não autorizada, decisão de produto necessária ou ampliação de escopo.

Ao parar, preserve o estado e registre fatos, ações realizadas, ações não realizadas, testes, evidências, Git, riscos, pendências e decisão recomendada.

## Saída esperada

Cada tarefa deve informar fatos comprovados, inferências, decisões, riscos e pendências; ações realizadas e não realizadas; testes e evidências; estado Git; riscos residuais; rollback quando aplicável; e a decisão recomendada. Nenhuma tarefa é concluída sem evidência adequada.
