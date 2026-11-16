# Documentação AVAMovie

Esta árvore reúne a documentação persistente do projeto original AVAMovie.

- [Plano Mestre canônico](governance/AVAMOVIE_MASTER_PLAN.md)
- [Estado atual](governance/CURRENT_STATE.md)
- [Registro de decisões](governance/DECISION_LOG.md)
- [Regras de execução](governance/EXECUTION_RULES.md)
- [Índice do MVP](product/MVP_REQUIREMENTS_INDEX.md)
- [Visão futura](product/FUTURE_VISION_INDEX.md)
- [Operações](operations/README.md)
- [Unidades de execução](execution/UNITS.md)
- [Contrato do Codex](../AGENTS.md)
- [Execução local com Laradock](execution/LOCAL_DEVELOPMENT.md)
- [Preflight local](../bin/doctor)
- [Entrypoint de desenvolvimento](../bin/dev)
- [Entrypoint de testes](../bin/test)
- [Entrypoint de qualidade](../bin/quality)

O contrato atual de testes é MySQL local no Laradock, no banco exclusivo `avamovie_testing`, definido de forma rastreável em `phpunit.xml.dist`. O entrypoint oficial para execução real é `bin/test --focused <alvo>`; a suíte completa permanece fora do escopo sem unidade autorizada.

## Organização

- `governance/`: plano, decisões, estado e regras canônicas.
- `product/`: índices do Documento 04 e do Documento 05.
- `architecture/`: visão arquitetural estritamente baseada no estado conhecido.
- `operations/`: baseline operacional, homologação, deploy e rollback.
- `execution/`: processo por unidades e estados registrados.

O Documento 04 é o escopo obrigatório atual. O Documento 05 é visão futura e não autoriza implementação antecipada.

Documentação não prova implementação; código não prova funcionamento; presença de pacote não prova operação. Requisitos somente podem ser declarados concluídos com evidências apropriadas.

O Plano Mestre e o changelog preservados aqui são cópias integrais das fontes canônicas externas. A governança persistente complementar fica em `/home/iomar/Dev/avamovie-project-governance/`.

Atualizações documentais devem ocorrer somente dentro de unidade autorizada, preservar as fontes canônicas e registrar fatos, decisões, riscos e pendências sem resolver divergências silenciosamente.
