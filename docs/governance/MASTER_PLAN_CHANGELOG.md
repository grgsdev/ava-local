# AVAMovie Platform — Changelog da Revisão 2 do Master Plan

**Data:** 31 de julho de 2026  
**Versão anterior:** `AVAMOVIE_MASTER_PLAN_REVISED.md`  
**Nova versão:** `AVAMOVIE_MASTER_PLAN_REVISION_2.md`  
**Motivo:** incorporar fatos comprovados do projeto original e ajustar a sequência de execução sem alterar a direção estratégica do MVP.

---

## 1. Fontes utilizadas

- Plano Mestre vigente;
- entrada canônica para revisão;
- Documento 04 — MVP simplificado;
- Documento 05 — documentação completa;
- fatos consolidados do A5, remoto e EC2;
- decisões formais do Projeto AVAMovie.

Nenhum projeto paralelo integra esta revisão.

---

## 2. Conteúdo preservado

Foram mantidos:

- preservação do sistema e dos dados;
- Documento 04 como prioridade;
- Documento 05 como visão futura;
- evolução incremental;
- proibição de reescrita total;
- MySQL para testes críticos;
- seeders idempotentes;
- validações pelo navegador;
- autorização no backend;
- alterações reversíveis;
- backup e rollback;
- autorização explícita para Git, deploy e AWS.

---

## 3. Conteúdo substituído

### Estado técnico inicial

O estado baseado em snapshot foi substituído por baseline comprovado:

- A5;
- remoto;
- EC2;
- runtime;
- Git;
- Nginx;
- Scribe;
- serviços;
- lacunas operacionais.

### Capacidades aparentes

Capacidades deixaram de ser tratadas como praticamente concluídas. Agora são separadas em:

- fatos;
- informação do responsável;
- inferências;
- pendências.

### Multitenancy

A noção genérica de multitenancy foi substituída por:

- contexto empresarial mínimo;
- empresa ativa;
- vínculos;
- ownership;
- isolamento;
- extensibilidade futura.

---

## 4. Conteúdo incorporado

- commit canônico datado;
- divergência local em `CourseBuilderController.php`;
- divergências da EC2 em `package-lock.json` e Scribe;
- exposição do Scribe;
- contenção temporária no Nginx;
- ausência observada de workers, scheduler e Reverb;
- risco aparente do Redis;
- deploy manual não formalizado;
- backup e rollback não comprovados;
- persistência fora de `/tmp`;
- bloqueio do RDS até fechamento suficiente da EC2;
- empresa, perfis e autorização como fundação;
- fluxo de aprovação dividido em ciclos;
- próximo deploy de até quinze dias;
- gates obrigatórios para o deploy.

---

## 5. Sequência revisada

### Antes

A sequência anterior era genérica:

1. baseline;
2. matriz do Documento 04;
3. ambiente local;
4. testes;
5. unidades funcionais.

### Agora

A sequência passa a ser:

1. concluir baseline operacional;
2. inventariar RDS e schema;
3. produzir matriz do Documento 04;
4. reproduzir o projeto original localmente;
5. consolidar empresa, vínculos, perfis, permissions e policies;
6. entregar envio para aprovação e fila pendente;
7. concluir rejeição, nova submissão e aprovação;
8. implementar publicação e URL;
9. concluir demais requisitos;
10. encerrar o MVP.

---

## 6. Próximo deploy revisado

O próximo deploy não promete conclusão integral do fluxo de aprovação.

### Nome

**Fundação de Acesso e Primeira Etapa do Fluxo de Aprovação**

### Janela

Até quinze dias corridos, condicionados aos gates técnicos.

### Entrega funcional

- Editor envia projeto;
- estado muda para `Aguardando aprovação`;
- ADM Cliente PJ visualiza fila pendente;
- autorização backend;
- registro da transição.

### Fora do ciclo

- rejeição com justificativa;
- nova submissão;
- aprovação final;
- publicação;
- URL segura.

---

## 7. Itens reclassificados como não comprovados

- workers;
- filas;
- scheduler;
- Reverb;
- deploy completo;
- backup integral;
- rollback integral;
- schema do RDS;
- migrations aplicadas;
- perfis confiáveis;
- isolamento;
- aprovação;
- publicação;
- URL;
- requisitos do editor e player, apesar da maturidade declarada.

---

## 8. Itens explicitamente excluídos

- projeto paralelo de testes;
- microserviços;
- perfis do Documento 05;
- créditos;
- cobrança;
- gateways;
- LMS completo;
- relatórios financeiros;
- promessas de conclusão integral do MVP em quinze dias.

---

## 9. Impacto da revisão

A revisão não altera o objetivo do projeto.

Ela:

- reduz risco;
- transforma hipóteses em pendências explícitas;
- aproxima o plano do estado real;
- protege editor e player;
- ordena corretamente a fundação de acesso;
- torna o próximo deploy factível;
- melhora rastreabilidade;
- evita implementação sobre permissões frágeis.

---

## 10. Estado final da revisão

A Revisão 2 substitui a versão anterior como Plano Mestre canônico, após sua adoção no Projeto ChatGPT.

A versão anterior deverá ser mantida apenas como histórico ou removida das fontes ativas para evitar conflito.
