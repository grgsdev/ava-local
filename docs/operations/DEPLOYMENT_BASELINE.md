# Baseline de Deploy

## Fatos

[FATO COMPROVADO] O reflog registra `git pull` em 27 de maio de 2026. O vhost Nginx está fora do Git.

[FATO COMPROVADO] O próximo deploy planejado na Revisão 2 é **Fundação de Acesso e Primeira Etapa do Fluxo de Aprovação**: empresa/vínculos mínimos, perfis essenciais, autorização, isolamento, transição para `Aguardando aprovação`, fila, histórico mínimo, testes MySQL e regressão de editor/player.

## Indícios e limites

[INFERÊNCIA] O histórico sugere Composer, NPM, build, Artisan, migrations, cache, geração Scribe e restart de filas em processo manual.

[PENDÊNCIA] Processo formal, executor, gates, tratamento de dirty tree, migrations, caches, serviços, backup, rollback e critérios de aceite não foram comprovados.

## Riscos

[RISCO] Um deploy pode remover a contenção Scribe, regenerar documentação sensível ou sobrescrever arquivos dirty.

## Condições obrigatórias

[DECISÃO] Antes do próximo deploy devem existir projeto local reproduzível, MySQL aprovado, migrations conhecidas, divergências decididas, Scribe protegido permanentemente, working tree controlada, backup, rollback, testes, regressão, smoke test e validação em homologação.

## Fora do próximo deploy

[DECISÃO] Rejeição, nova submissão, aprovação final, publicação, URL segura, notificações avançadas, workflow configurável, múltiplos níveis e Documento 05 ficam fora desse escopo.

Este documento não é script nem autorização de deploy.
