# Estado Atual Canônico

> Atualização de baseline/remote — 2026-10-23 — `AVA-MVP-REMOTE-AND-DOCTOR-BASELINE-REFRESH-001`. Esta seção prevalece sobre registros históricos de baseline ou remote quando houver divergência.

## Baseline pós-integração e remote oficial

`CURRENT_MAIN_SHA=3e977ba7032689565e09acc3e178db59236948b5`.

`OFFICIAL_REMOTE=avaproia/avamovie`; `LEGACY_REMOTE=devavapro/avamovie`; `REMOTE_MOVE=CONFIRMED`.

`DOCTOR_BASELINE_FLOOR=3e977ba7032689565e09acc3e178db59236948b5`.

`DOCTOR_CONTRACT=clean, published, origin-aligned descendant of baseline floor`. O `bin/doctor` exige `main`, remote e upstream oficiais, `HEAD=origin/main`, comparação `0/0`, zero staged/untracked e worktree limpa; o HEAD precisa descender do piso explícito. O doctor não autoriza commits futuros: essa autorização continua submetida à governança explícita.

> Atualização de continuidade — 2026-10-23 — `AVA-MVP-CONTINUITY-REFRESH-001`. Esta seção prevalece sobre os registros históricos abaixo quando houver divergência.

## Baseline operacional pós-deploy homologado

[FATO COMPROVADO] `LOCAL/GITHUB MAIN=228ebe5fa81b41e152fa43a2f25e6ef58e956a9b` e `HOMOLOGATION EC2=228ebe5fa81b41e152fa43a2f25e6ef58e956a9b`. O deploy de homologação foi `PASS`, aprovado/fechado, via `GIT_BUNDLE_OVER_SSH`; a EC2 terminou com `WORKTREE=CLEAN`.

[FATO COMPROVADO] As quatro migrations alvo foram aplicadas (`MIGRATIONS=PASS`) e não há migrations alvo pendentes (`0`). O frontend build, os healthchecks e a validação humana pós-deploy foram `PASS`. Os healthchecks incluíram `/` em HTTP 200 e as quatro rotas Scribe em HTTP 404, local e publicamente.

[FATO COMPROVADO] O backup RDS pré-migration foi criado e validado: `/var/backups/avamovie/20261022T195519Z/avamovie-before-228ebe5fa81b41e152fa43a2f25e6ef58e956a9b.sql.gz`, SHA-256 `b830f4ff6df56daf0287cc5f6d4b10df635f3e860f2ae7483c0c0a553dd9d4a9`. O rollback de código conhecido é `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`; o procedimento documentado de restore exige janela autorizada, validação de gzip/checksum, importação no RDS pretendido e validação de ledger/schema/saúde. Não houve rollback porque o deploy foi bem-sucedido.

[PENDÊNCIA] `EC2_GITHUB_AUTH=OPEN`; o mecanismo permanente de deploy ainda requer formalização. O bloqueio temporário Nginx do Scribe permanece ativo e aprovado; a remediação permanente do Scribe continua aberta. Durante o deploy não foram observados workers de fila, scheduler Laravel ou Reverb ativos; essa observação não prova ausência de configuração definitiva nem autoriza instalação/restart.

[DECISÃO] A próxima ação volta à sequência do Documento 04: `AVA-MVP-DOC04-EDITOR-INTERACTIONS-PLAYER-CHARACTERIZATION-001`, preservando as pendências operacionais acima, a formalização definitiva de backup/restore e todas as demais pendências ainda válidas. Documento 05 não é antecipado.

> Atualização de continuidade — 2026-10-22 — `AVA-MVP-DOC04-INTEGRAL-MATRIX-001-RESUME-001`. Esta seção prevalece quando divergir de descrições históricas abaixo.

## Estado MVP vigente

[FATO COMPROVADO] Baseline local autorizado: branch `mvp/foundation-phase4`, HEAD `b237f9ec82db3210ff2673a3ccfed90a3de19d9d`, base `origin/main` `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`, sem upstream, comparação local autorizada 1/0 e sem staged. As 55 divergências funcionais autorizadas são validadas por `bin/doctor`; a alteração em `app/Http/Controllers/Admin/CourseBuilderController.php` permanece intocável.

[DECISÃO] `AVA-MVP-BASELINE-DOCUMENTATION-ALIGNMENT-002` reconcilia o conjunto exato do doctor: `FUNCTIONAL_AUTHORIZED_PATHS=55`, `TOOLING_AUTHORIZED_PATHS=3`, `DOCUMENTATION_AUTHORIZED_PATHS=2`, `RAW_DIVERGENT_PATHS=60`. Os paths documentais `docs/governance/CURRENT_STATE.md` e `docs/product/MVP_REQUIREMENTS_INDEX.md` foram introduzidos por `AVA-MVP-DOC04-INTEGRAL-MATRIX-001-RESUME-001` e são baseline autorizado. Próxima unidade: `AVA-MVP-DOC04-EDITOR-INTERACTIONS-PLAYER-CHARACTERIZATION-001`.

[FATO COMPROVADO] Ambiente local oficial: Laradock documentado em `docs/execution/LOCAL_DEVELOPMENT.md`; `bin/doctor` passou nesta unidade. Não foram executados testes, migrations, build ou qualquer alteração de banco nesta unidade.

[DECISÃO] O Documento 04 em `/home/iomar/Downloads/AVAMOVIE/docs/04 - AVAMovie - Documentação MVP simplificado.pdf` é a fonte de requisitos do MVP. Documento 05 continua visão futura. A matriz integral está em `/home/iomar/Dev/avamovie-project-governance/reports/AVA-MVP-DOC04-INTEGRAL-MATRIX-001-matrix.md`.

[FATO COMPROVADO] A matriz contém 121 requisitos atômicos: 32 PASS, 39 PARCIAL, 34 NÃO COMPROVADO, 10 NÃO IMPLEMENTADO e 6 FORA DO MVP. Dos 115 itens em escopo, 27,83% são PASS. Não há P0 aberto; há 38 P1, 35 P2 e 10 P3 abertos.

[FATO COMPROVADO] Fluxos humanos aceitos: approval completo incluindo feedback e ressubmissão; publicação inicial, URL tokenizada estável, player anônimo, snapshot preservado, republicação e revogação. A rejeição teve build compilado stale corrigido localmente; a validação humana posterior confirmou o fluxo.

[DECISÃO] Conflito de escopo do Editor: onde o Documento 04 alterna entre “todos da Company” e “próprios”, vale a decisão posterior `AVA-MVP-COURSE-COMPANY-001`: Editor visualiza Courses da Company ativa; não se acrescenta filtro `user_id`.

[PENDÊNCIA] Grupos abertos, em ordem: `EDITOR_INTERACTIONS_PLAYER` P1; `REPORTING` P1; `NOTIFICATIONS_HISTORY` P1; `USER_MANAGEMENT` P1; `SECURITY` P1/P2; `TAXONOMY_METADATA` P1; `TECHNICAL_OPERATIONAL` P2/P3. Próxima unidade recomendada: delimitar e comprovar `EDITOR_INTERACTIONS_PLAYER` P1.

[RISCO] Não inferir PASS de presença de código. HTTPS runtime, sessão, login throttling, upload seguro, preview e todas as interações ainda requerem evidência própria. Não alterar AWS/RDS, executar migrations destrutivas, editar o controller protegido, nem ampliar para Documento 05.

[DECISÃO] Em unidade futura, atualizar este estado e a continuidade de governança somente se mudar baseline, requisito da matriz, decisão canônica, risco, pendência, próxima ação ou estado de fase; não atualizar por mero rerun de teste sem mudança de estado.

## 1. Identificação

[FATO COMPROVADO] O projeto é o AVAMovie Platform, no repositório original, com homologação em AWS EC2 e MySQL no Amazon RDS.

## 2. Classificação das informações

As afirmações usam as categorias do Plano Mestre: `[FATO COMPROVADO]`, `[INFORMAÇÃO DO RESPONSÁVEL TÉCNICO]`, `[INFERÊNCIA]`, `[DECISÃO]`, `[RISCO]` e `[PENDÊNCIA]`.

## 3. Estado atual do baseline

[FATO COMPROVADO] O baseline conhecido está no commit `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`, branch `main`, com A5 e remoto alinhados no baseline observado.

[DECISÃO] O projeto original é a única fonte de implementação. O Documento 04 é a prioridade atual; o Documento 05 permanece futuro.

## 4. A5

[FATO COMPROVADO] O A5 é `/home/iomar/Dev/avamovie-platform`, em `main`, com a alteração local unstaged preexistente em `app/Http/Controllers/Admin/CourseBuilderController.php`.

[DECISÃO] Essa alteração permanece intocada até decisão própria.

## 5. Remoto

[FATO HISTÓRICO] O remote oficial observado nesta fase era o repositório GitHub `devavapro/avamovie`, branch padrão `main`, com o commit canônico então vigente. O remote oficial atual está registrado na seção de baseline pós-integração.

## 6. EC2

[FATO COMPROVADO] A EC2 tem Ubuntu 24.04, PHP CLI/FPM 8.3.27, Composer 2.8.12, Node 24.11.0, NPM 11.6.1 e Nginx 1.24.0. Nginx, PHP-FPM, Supervisor, cron e Redis estavam ativos.

[FATO COMPROVADO] A working tree efetiva da EC2 diverge em `package-lock.json` e `resources/views/scribe/index.blade.php`, embora o commit coincida.

## 7. Divergências conhecidas

[RISCO] A igualdade de commit não garante igualdade de conteúdo implantado. O lockfile e o Scribe precisam de decisão formal.

[PENDÊNCIA] Workers, scheduler Laravel, Reverb, deploy formal, backup integral, rollback integral, RDS e schema não estão comprovados.

## 8. Contenção Scribe

[FATO COMPROVADO] As rotas `/docs`, `/docs/`, `/docs.postman` e `/docs.openapi` foram contidas no vhost Nginx com `404`; `/` permaneceu disponível.

[DECISÃO] A contenção temporária deve permanecer até uma solução permanente e verificável.

[RISCO] Como a contenção está fora do Git, um deploy pode removê-la.

## 9. Serviços e operação

[FATO COMPROVADO] Supervisor está ativo, mas sem programas AVAMovie carregados. Não foram observados workers, scheduler Laravel ou processo Reverb.

[PENDÊNCIA] Ainda não foi determinado quais funcionalidades dependem desses componentes.

## 10. Deploy

[FATO COMPROVADO] O reflog comprova atualização por `git pull`.

[INFERÊNCIA] O histórico sugere processo manual com Composer, NPM, build, Artisan, migrations, cache, Scribe e restart de filas.

[PENDÊNCIA] Sequência oficial, executor, gates, aceite e resultado do deploy não estão comprovados.

## 11. Backup e rollback

[FATO COMPROVADO] Foi comprovado backup temporário do vhost Scribe.

[PENDÊNCIA] Backup completo da aplicação, backup do RDS, restauração e rollback integral não estão comprovados.

## 12. Banco e RDS

[FATO COMPROVADO] A homologação utiliza MySQL no Amazon RDS.

[PENDÊNCIA] Versão, schema, migrations, integridade, isolamento, backup, retenção e restore ainda precisam ser comprovados.

## 13. Estado funcional conhecido

[INFORMAÇÃO DO RESPONSÁVEL TÉCNICO] Editor, player e interações principais possuem maturidade funcional relevante.

[PENDÊNCIA] Isso não encerra os requisitos do Documento 04; aprovação, publicação, URL segura, perfis, autorização, isolamento, histórico e notificações ainda exigem evidências.

## 14. Riscos atuais

[RISCO] Exposição futura do Scribe, working trees divergentes, lockfile inconsistente, migrations sem rollback, ausência de processos assíncronos compreendidos e Redis potencialmente exposto.

## 15. Pendências

[PENDÊNCIA] Matriz integral do Documento 04, reprodutibilidade local com MySQL, seeders, autorização, isolamento, testes, runbooks, deploy, backup e rollback.

## 16. Sequência vigente

[DECISÃO] Encerrar baseline operacional; validar RDS e banco; criar matriz do Documento 04; reproduzir o projeto original; consolidar empresa, perfis e autorização; avançar incrementalmente no fluxo de aprovação; publicar; concluir o MVP; manter Documento 05 separado.
