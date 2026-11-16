# AVAMovie Platform — Master Plan de Conclusão Segura do MVP

**Versão:** Revisão 2  
**Data:** 31 de julho de 2026  
**Status:** canônico para continuidade da execução  
**Produto:** AVAMovie Platform  
**Projeto técnico oficial:** repositório original AVAMovie  
**Ambiente de homologação:** AWS EC2 com MySQL no Amazon RDS  
**Prioridade atual:** concluir integralmente o Documento 04 — MVP simplificado  
**Visão posterior:** Documento 05 — Documentação completa  
**Versão substituída:** `AVAMOVIE_MASTER_PLAN_REVISED.md`

---

## 0. Finalidade e direção executiva

O AVAMovie é uma aplicação existente, funcional e implantada em homologação. Este plano não autoriza reconstrução integral, troca indiscriminada de arquitetura ou substituição de funcionalidades maduras por preferência técnica.

A execução deverá:

1. preservar funcionalidades, dados e disponibilidade;
2. estabelecer o estado canônico do projeto original;
3. concluir o baseline técnico e operacional;
4. transformar o Documento 04 em matriz verificável;
5. preparar o ambiente local do projeto original com MySQL compatível;
6. consolidar empresa, vínculos, perfis, permissões e isolamento;
7. concluir os fluxos restantes do MVP;
8. validar cada entrega por testes automatizados, validação manual pelo navegador e evidências;
9. realizar deploys pequenos, reversíveis e com valor funcional;
10. manter o Documento 05 como visão futura, sem antecipação indevida.

O projeto original AVAMovie é a única fonte de implementação. Nenhum projeto paralelo integra este plano, as fontes canônicas ou o processo de desenvolvimento.

---

## 1. Classificação das informações

Toda afirmação relevante deverá ser identificada como:

- **Fato comprovado:** sustentado por evidência do A5, repositório remoto, EC2, RDS, código ou comportamento observado.
- **Informação do responsável técnico:** conhecimento prático declarado, ainda sujeito a caracterização formal.
- **Inferência:** conclusão provável, não comprovada integralmente.
- **Decisão:** orientação canônica aprovada.
- **Risco:** condição com potencial de regressão, perda, exposição ou indisponibilidade.
- **Pendência:** item ainda não comprovado, decidido, implementado ou validado.

Documentação não prova implementação. Código não prova funcionamento. Presença de pacote não prova operação.

---

## 2. Prioridades canônicas

### 2.1. Prioridade 0 — Preservar o sistema

Nenhuma alteração poderá:

- interromper fluxos funcionais;
- provocar perda ou corrupção de dados;
- permitir acesso indevido entre empresas ou usuários;
- invalidar projetos, vídeos, interações, tokens ou URLs sem transição;
- executar migration destrutiva sem backup, compatibilidade e rollback;
- depender de intervenção manual não documentada;
- remover comportamento existente sem caracterização e decisão;
- tornar o deploy irreversível;
- ocultar falhas ou divergências.

Toda mudança deverá ser pequena, testável, observável, compatível e reversível.

### 2.2. Prioridade 1 — Concluir o Documento 04

O Documento 04 é o escopo obrigatório da etapa atual.

Cada requisito deverá receber:

- identificador;
- descrição;
- fonte;
- comportamento esperado;
- estado real;
- evidência;
- classificação;
- divergências;
- risco;
- dependências;
- testes;
- roteiro de validação;
- decisão de conclusão.

Classificações mínimas:

- `C` — comprovadamente concluído;
- `P` — parcial;
- `D` — divergente;
- `F` — com defeito;
- `N` — não implementado;
- `NA` — não aplicável;
- `RD` — requer decisão;
- `NC` — não comprovado.

O MVP somente será encerrado após implementação, integração, testes, validação em homologação e ausência de regressões críticas.

### 2.3. Prioridade 2 — Preservar a evolução futura

O Documento 05 representa a evolução posterior.

Durante o MVP, decisões deverão evitar bloqueios graves para:

- novos perfis;
- múltiplas empresas;
- integrações;
- LMS;
- eventos;
- URLs avançadas;
- relatórios;
- cobrança;
- auditoria ampliada.

Esta preparação não autoriza antecipar:

- ADM Master;
- Manager independente;
- Cliente Final;
- subordinados;
- estrutura comercial completa;
- créditos;
- cobrança;
- gateways;
- múltiplas moedas;
- LMS completo;
- relatórios financeiros;
- microserviços.

---

## 3. Fontes e precedência

### 3.1. Fontes de produto

1. Documento 04 — MVP simplificado;
2. Documento 05 — visão completa.

### 3.2. Fontes de implementação

1. repositório original AVAMovie;
2. commit efetivamente implantado;
3. comportamento comprovado na homologação;
4. estrutura e dados reais do RDS;
5. documentação versionada no repositório;
6. artefatos persistentes de governança;
7. snapshots apenas como referência histórica.

### 3.3. Ordem de precedência

1. integridade dos dados e segurança;
2. comportamento comprovado em homologação;
3. código e configuração efetivamente implantados;
4. Documento 04;
5. decisões formalizadas no Projeto;
6. este Master Plan;
7. Documento 05;
8. inferências.

Divergências nunca serão resolvidas silenciosamente.

---

## 4. Estado canônico conhecido em 31 de julho de 2026

### 4.1. Repositório remoto

- remote oficial: `https://github.com/devavapro/avamovie.git`;
- branch padrão: `main`;
- commit confirmado: `c27f3936233dc4c846a8d6c443b1cf9087e2a3f6`.

### 4.2. A5

- diretório: `/home/iomar/Dev/avamovie-platform`;
- branch: `main`;
- HEAD igual ao remoto;
- alteração local preexistente e não decidida:

```text
app/Http/Controllers/Admin/CourseBuilderController.php
```

Essa alteração não poderá ser restaurada, incorporada ou descartada sem unidade própria e decisão formal.

### 4.3. EC2

Foi comprovado:

- Ubuntu 24.04;
- PHP CLI/FPM 8.3.27;
- Composer 2.8.12;
- Node 24.11.0;
- NPM 11.6.1;
- Nginx 1.24.0;
- Nginx, PHP-FPM, Supervisor, cron e Redis ativos;
- branch e HEAD iguais ao remoto;
- sem staged e sem untracked;
- working tree divergente em:

```text
package-lock.json
resources/views/scribe/index.blade.php
```

A igualdade de commit não significa igualdade de conteúdo implantado.

### 4.4. Documentação Scribe

Foi comprovado que:

- `/docs`;
- `/docs/`;
- `/docs.postman`;
- `/docs.openapi`;

estavam acessíveis e continham categorias de conteúdo inadequado para exposição pública.

Foi aplicada contenção mínima no Nginx:

- quatro rotas passaram a retornar `404`;
- a aplicação principal permaneceu disponível;
- houve reload, não restart;
- o Git da aplicação não foi alterado;
- a contenção está fora do repositório e é temporária.

Antes do próximo deploy deverá existir solução permanente e verificável.

### 4.5. Operação ainda não comprovada

Não foram comprovados em execução:

- workers;
- Horizon;
- scheduler Laravel;
- Reverb;
- proxy WebSocket.

Supervisor está ativo, mas sem programas AVAMovie carregados.

A ausência observada não autoriza instalação imediata. Primeiro deverá ser comprovado quais funcionalidades dependem desses componentes.

### 4.6. Deploy, backup e rollback

Foi comprovado `git pull` em histórico. Há indícios de processo manual envolvendo Composer, NPM, build, Artisan, migrations, cache, Scribe e restart de filas.

Ainda não estão comprovados:

- sequência oficial;
- executor;
- gates;
- tratamento da working tree;
- backup da aplicação;
- backup do RDS;
- restauração;
- rollback integral;
- critérios de aceite;
- procedimento pós-deploy.

### 4.7. Banco

A homologação utiliza MySQL no Amazon RDS.

Ainda precisam ser comprovados:

- versão;
- schema;
- migrations aplicadas;
- migrations pendentes;
- índices;
- foreign keys;
- charset e collation;
- volumes;
- integridade;
- isolamento;
- backups;
- retenção;
- restore;
- compatibilidade entre banco, migrations e models.

### 4.8. Estado funcional conhecido

**Informação do responsável técnico:**

- editor visual possui maturidade funcional relevante;
- player possui maturidade funcional relevante;
- interações principais funcionam.

Essas informações orientarão a caracterização, mas ainda não encerram requisitos.

**Pendências conhecidas:**

- empresa ativa e vínculos consistentes;
- perfis e permissões confiáveis;
- autorização no backend;
- isolamento;
- envio para aprovação;
- rejeição com justificativa;
- nova submissão;
- aprovação;
- publicação;
- URL segura;
- histórico;
- notificações;
- validação integral do Documento 04.

---

## 5. Princípios de engenharia

### 5.1. Projeto original como única base

Toda inspeção, migration, seeder, teste, correção e funcionalidade será realizada no projeto original.

Nenhum código será copiado automaticamente de outra aplicação.

### 5.2. Evolução incremental

- sem reescrita total;
- sem substituição de módulos maduros sem necessidade comprovada;
- sem migrations destrutivas como primeira estratégia;
- expand-and-contract quando necessário;
- compatibilidade com dados existentes;
- testes de caracterização antes de alterar comportamento;
- feature flags quando coexistência for necessária.

### 5.3. Contexto empresarial mínimo

O MVP não exige operação comercial completa com múltiplas empresas.

Exige, porém:

- uma empresa ativa;
- usuários vinculados;
- ownership de projetos;
- perfis Master, ADM Cliente PJ e Editor;
- autorização no backend;
- isolamento coerente;
- estrutura extensível.

O plano não chamará essa fundação de multitenancy completo.

### 5.4. Segurança prática

Preservar:

- autenticação;
- autorização;
- isolamento;
- proteção de URLs e tokens;
- auditoria;
- dados pessoais;
- segredos;
- configurações externas;
- logs sanitizados.

Revisar antes de deploy:

- exposição do Redis;
- Scribe;
- rotas públicas;
- working tree;
- permissões;
- migrations;
- backups.

### 5.5. Reprodutibilidade

O projeto original deverá poder ser executado localmente com:

- MySQL compatível com o RDS;
- configuração sanitizada;
- migrations;
- seeders idempotentes;
- empresa fictícia;
- usuários fictícios;
- projetos e interações de demonstração;
- comandos de preparação;
- comandos de reconstrução;
- roteiros de validação.

SQLite não será evidência suficiente para requisitos dependentes de MySQL.

### 5.6. Persistência das evidências

`/tmp` será usado somente para artefatos descartáveis.

Relatórios, manifests, evidências e runbooks serão mantidos em:

```text
/home/iomar/Dev/avamovie-project-governance/
```

Nenhuma decisão crítica dependerá exclusivamente de arquivo temporário.

---

## 6. Governança Git e integração

Antes de qualquer unidade:

- confirmar diretório;
- branch;
- HEAD;
- upstream;
- ahead/behind;
- staged;
- unstaged;
- untracked;
- diff;
- artefatos preexistentes.

Proibido sem autorização:

- push;
- PR;
- merge;
- rebase;
- squash;
- force push;
- tag;
- release;
- deploy;
- alteração AWS.

### Gates de integração

- working tree limpa ou divergências formalmente decididas;
- testes focados aprovados;
- regressão aprovada;
- análise estática e lint aprovados;
- typecheck aprovado;
- build aprovado;
- migrations revisadas;
- backup e rollback definidos;
- documentação atualizada;
- evidências registradas.

---

## 7. Estratégia de testes

Quando aplicável:

1. testes de caracterização;
2. testes unitários;
3. testes de integração;
4. testes com MySQL;
5. testes de autorização;
6. testes de isolamento;
7. testes focados;
8. regressão;
9. Pint;
10. análise estática;
11. ESLint;
12. typecheck;
13. build;
14. auditoria de dependências;
15. smoke test;
16. validação manual local;
17. validação manual em homologação;
18. evidências.

Toda mudança funcional, visual, operacional ou de permissão deverá possuir roteiro de validação pelo navegador.

---

## 8. Estratégia de seeders

### 8.1. Seeders estruturais

Criar ou ajustar de forma idempotente:

- roles;
- permissions;
- parâmetros obrigatórios;
- estados;
- empresa do MVP;
- vínculos mínimos.

### 8.2. Seeders locais

Somente em `local` e `testing`:

- empresa fictícia;
- Master;
- ADM Cliente PJ;
- Editor;
- projetos;
- vídeos;
- interações;
- estados de aprovação;
- dados de demonstração.

Credenciais simples são aceitáveis localmente, nunca em homologação ou produção.

---

## 9. Plano de execução

## Fase 0 — Preservação e baseline

### Objetivo

Concluir o estado canônico antes de alterações funcionais amplas.

### Entregas

- decisão sobre alteração local do A5;
- decisão sobre os dois arquivos divergentes da EC2;
- solução permanente para Scribe;
- caracterização de workers, scheduler e Reverb;
- revisão da exposição do Redis;
- formalização do deploy;
- backup;
- rollback;
- inventário da EC2;
- liberação segura para análise do RDS.

### Gate

O baseline poderá avançar quando as lacunas não representarem risco desconhecido para banco, deploy ou disponibilidade.

---

## Fase 1 — Banco e compatibilidade

### Entregas

- inventário do RDS;
- comparação de schema, migrations e models;
- migrations aplicadas e pendentes;
- integridade;
- índices;
- charset e collation;
- volumes;
- backup e restore;
- estratégia de migrations;
- MySQL local compatível.

### Restrições

- sem escrita no RDS durante inventário;
- sem migration antes de backup e rollback;
- sem copiar dados pessoais para o A5.

---

## Fase 2 — Matriz integral do Documento 04

### Entregas

- requisito por requisito;
- ambiguidades;
- divergências entre documentação, código, banco e homologação;
- mapa de telas;
- mapa de rotas;
- mapa de permissions e policies;
- mapa de entidades;
- mapa de testes;
- backlog priorizado;
- separação do Documento 05.

Nenhuma implementação será escolhida apenas porque parece arquiteturalmente elegante.

---

## Fase 3 — Reprodutibilidade do projeto original

### Entregas

- ambiente local funcional;
- MySQL;
- migrations;
- seeders;
- usuários;
- empresa;
- projetos de teste;
- comandos únicos de preparação;
- gates;
- documentação local.

---

## Fase 4 — Fundação empresarial e de autorização

### Objetivo

Criar a base mínima segura exigida pelo MVP.

### Entregas

- empresa ativa;
- vínculos;
- ownership;
- Master;
- ADM Cliente PJ;
- Editor;
- roles e permissions;
- policies;
- autorização no backend;
- isolamento;
- proteção contra alteração direta de URL;
- testes permitidos e negados;
- seeders idempotentes.

### Gate

Nenhum fluxo de aprovação, publicação ou URL será considerado seguro antes desta fase.

---

## Fase 5 — Próximo deploy funcional

### Nome

**Fundação de Acesso e Primeira Etapa do Fluxo de Aprovação**

### Janela planejada

Até 15 dias corridos após abertura da unidade, condicionados aos gates técnicos.

A janela não é garantia independente de:

- baseline;
- schema;
- migrations;
- backup;
- rollback;
- segurança;
- regressões.

### Valor funcional

O Editor poderá:

- concluir a edição;
- enviar o projeto para aprovação.

O sistema deverá:

- alterar o estado para `Aguardando aprovação`;
- impedir transição por perfil indevido;
- registrar a transição;
- exibir claramente o estado.

O ADM Cliente PJ poderá:

- visualizar uma fila de projetos pendentes;
- acessar o projeto para análise.

### Escopo técnico

- empresa e vínculos mínimos;
- perfis essenciais;
- autorização no backend;
- isolamento;
- transição de estado;
- fila;
- histórico mínimo;
- testes com MySQL;
- regressão de editor e player;
- deploy controlado.

### Fora do escopo deste deploy

- rejeição com justificativa;
- nova submissão;
- aprovação final;
- publicação;
- URL segura;
- notificações avançadas;
- workflow configurável;
- múltiplos níveis de aprovação;
- Documento 05.

### Gates obrigatórios

- projeto original reproduzível;
- MySQL local aprovado;
- migrations conhecidas;
- divergências da EC2 decididas;
- Scribe protegido permanentemente;
- working tree controlada;
- backup comprovado;
- rollback documentado;
- testes aprovados;
- editor e player sem regressão;
- smoke test;
- validação em homologação.

---

## Fase 6 — Conclusão do fluxo de aprovação

### Entregas

- rejeição;
- justificativa obrigatória;
- visualização da justificativa;
- correção;
- nova submissão;
- aprovação;
- histórico;
- restrições por estado.

---

## Fase 7 — Publicação e URL segura

### Entregas

- publicação por perfil autorizado;
- token público não previsível;
- URL segura;
- acesso ao player;
- projeto não aprovado inacessível;
- despublicação ou revogação;
- compatibilidade com projetos existentes;
- testes de acesso.

---

## Fase 8 — Demais requisitos do MVP

- gestão de usuários;
- relatórios essenciais;
- histórico;
- notificações;
- logs;
- URLs;
- segurança;
- provedores externos;
- integrações preparatórias;
- gaps remanescentes do editor e player;
- aceite integral.

---

## Fase 9 — Encerramento do MVP

O MVP será encerrado somente quando:

- todos os requisitos do Documento 04 estiverem classificados;
- não houver requisito crítico sem decisão;
- os fluxos estiverem implementados;
- testes automatizados passarem;
- validações pelo navegador forem aprovadas;
- homologação estiver validada;
- deploy e rollback estiverem documentados;
- não houver regressão crítica;
- evidências estiverem persistidas;
- backlog do Documento 05 estiver separado.

---

## 10. Condições de parada

Parar antes de modificar quando houver:

- commit implantado desconhecido;
- divergência não compreendida;
- working tree inesperada;
- risco de perda;
- backup ausente;
- rollback inviável;
- segredo exposto;
- migration destrutiva;
- risco de acesso cruzado;
- dados incompatíveis;
- contrato externo incerto;
- regressão não compreendida;
- alteração AWS não autorizada;
- decisão de produto necessária;
- conflito entre Documento 04 e comportamento real;
- necessidade de ampliar escopo.

Ao parar:

1. preservar estado;
2. coletar evidência;
3. registrar impacto;
4. recomendar decisão;
5. fornecer texto operacional de continuidade.

---

## 11. Definition of Done de cada unidade

Uma unidade só será concluída quando houver:

- escopo entregue;
- código revisado;
- testes focados;
- regressão;
- MySQL quando aplicável;
- autorização e isolamento quando aplicável;
- lint, análise estática, typecheck e build;
- roteiro de validação executado;
- evidências;
- documentação;
- estado Git final;
- riscos residuais;
- rollback;
- decisão canônica.

---

## 12. Papel do ChatGPT e do Codex

### ChatGPT

- governança;
- planejamento;
- revisão;
- decisões;
- preparação de prompts;
- análise de evidências;
- manutenção documental.

### Codex

- executor técnico oficial no A5 e no repositório original;
- inspeções;
- implementação;
- migrations;
- seeders;
- testes;
- relatórios;
- evidências.

Fluxo:

1. chat define a unidade;
2. chat gera um prompt único;
3. Codex executa;
4. usuário retorna evidências;
5. chat revisa;
6. próxima decisão é produzida.

Nenhuma resposta de governança deverá obrigar o usuário a inventar autorizações, comandos ou próximos passos.

---

## 13. Artefatos canônicos

Diretório persistente:

```text
/home/iomar/Dev/avamovie-project-governance/
```

Artefatos principais:

- baseline;
- manifests;
- relatórios;
- matrizes;
- runbooks;
- evidências;
- planos de deploy;
- rollback;
- changelogs.

Documentos aprovados poderão posteriormente ser versionados no repositório mediante autorização.

---

## 14. Decisões explicitamente preservadas

- o sistema original será preservado;
- o Documento 04 é prioridade;
- o Documento 05 é futuro;
- nenhuma reescrita total;
- nenhuma migration destrutiva sem proteção;
- MySQL é obrigatório para validações críticas;
- seeders e testes devem ser reproduzíveis;
- autorização deve existir no backend;
- deploy exige backup e rollback;
- nenhuma integração Git ou AWS sem autorização;
- nenhuma conclusão sem evidência.

---

## 15. Decisões incorporadas nesta revisão

- o projeto original é a única base de implementação;
- snapshots são históricos, não canônicos;
- baseline da EC2 passa a integrar o plano;
- divergências do A5 e EC2 são condições formais de decisão;
- contenção Scribe é temporária;
- workers, scheduler e Reverb são não comprovados;
- deploy atual não é considerado formal;
- backup e rollback não estão comprovados;
- contexto empresarial mínimo precede aprovação;
- editor, interações e player são maduros segundo o responsável, mas precisam de caracterização;
- fluxo de aprovação será implementado por etapas;
- próximo deploy planejado: fundação de acesso, envio e fila de aprovação;
- quinze dias não significam encerramento do MVP;
- artefatos persistentes não podem depender de `/tmp`.

---

## 16. Resultado esperado

Ao final deste plano, o AVAMovie deverá:

- permanecer funcional;
- ter estado técnico conhecido;
- possuir ambiente local reproduzível;
- operar com MySQL compatível;
- ter empresa, vínculos, perfis e autorização confiáveis;
- possuir editor e player protegidos por regressão;
- cumprir aprovação, publicação e URL do Documento 04;
- possuir testes e roteiros de validação;
- ter deploy, backup e rollback controlados;
- estar integralmente aderente ao MVP;
- manter o Documento 05 separado e preparado para planejamento posterior.
