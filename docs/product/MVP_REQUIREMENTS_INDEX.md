# Índice de Requisitos do MVP

Documento 04 é a fonte normativa do MVP: `/home/iomar/Downloads/AVAMOVIE/docs/04 - AVAMovie - Documentação MVP simplificado.pdf` (SHA-256 `9f8a9bf1e6ded60563808cad3de4a5adfaae0a6e489d5c45c7f479b563936407`). Documento 05 é somente visão futura e não cria requisito do MVP.

A matriz integral requisito a requisito está em `/home/iomar/Dev/avamovie-project-governance/reports/AVA-MVP-DOC04-INTEGRAL-MATRIX-001-matrix.md` e no CSV correspondente. Ela contém 121 requisitos atômicos: 32 PASS, 39 PARCIAL, 34 NÃO COMPROVADO, 10 NÃO IMPLEMENTADO e 6 FORA DO MVP. No escopo são 115 requisitos: 27,83% PASS e 72,17% abertos. Presença de código não é prova de funcionamento.

Os principais abertos P0/P1 são: P0 nenhum; P1 editor/interações/player ainda sem cobertura integral, relatórios e exportação CSV, notificações e histórico de edição, gestão integral de usuários, tags/metadados e hardening de segurança. Os P2 concentram homologação/evidência técnica ainda ausente; P3 são preparação arquitetural e operacional.

O ciclo de aprovação está comprovado localmente e por validação humana aceita: criar/editar, enviar, bloquear edição pendente, rejeitar com feedback, corrigir, reenviar e aprovar. O ciclo de publicação também está comprovado: primeira publicação, URL estável tokenizada, player anônimo, snapshot imutável, republicação e revogação. A rejeição teve paridade frontend/runtime corrigida localmente e a validação humana pós-deploy em homologação confirmou os fluxos aceitos.

Atualização de evidência em 2026-10-23 por `AVA-MVP-CONTINUITY-REFRESH-001`: deploy homologado no SHA `228ebe5fa81b41e152fa43a2f25e6ef58e956a9b`, quatro migrations alvo aplicadas, nenhuma pendente, build e healthchecks `PASS`, EC2 limpa e validação humana pós-deploy `PASS`. Esta evidência reforça apenas os itens de approval, rejection/resubmission/approval, publication, URL tokenizada/pública, comportamento implantado e isolamento de Company já funcionalmente comprovados na matriz; não converte itens `PARTIAL`, `NOT_PROVEN` ou `NOT_IMPLEMENTED` em `PASS` por inferência. A distribuição e os status dos requisitos permanecem inalterados nesta unidade.

Próximo grupo recomendado: `EDITOR_INTERACTIONS_PLAYER` P1, começando por delimitar e comprovar as interações obrigatórias do Documento 04 sem alterar requisitos fora do MVP.

Atualizado em 2026-10-23 pela unidade `AVA-MVP-CONTINUITY-REFRESH-001`; a matriz integral continua sendo a referência de status requisito a requisito. Consulte também `docs/governance/CURRENT_STATE.md` e a continuidade de governança antes de iniciar unidade nova.

Baseline local: a unidade `AVA-MVP-BASELINE-DOCUMENTATION-ALIGNMENT-002` reconhece este arquivo e `docs/governance/CURRENT_STATE.md` como os 2 paths de documentação persistente autorizados. Com 55 paths funcionais e 3 de tooling, o conjunto exato do `bin/doctor` é de 60 paths. Próxima unidade a retomar: `AVA-MVP-DOC04-EDITOR-INTERACTIONS-PLAYER-CHARACTERIZATION-001`.
