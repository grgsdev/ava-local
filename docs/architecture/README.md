# Visão Arquitetural Conhecida

## Fatos

[FATO COMPROVADO] Existe uma aplicação Laravel no projeto original, executada em EC2 com Nginx e PHP-FPM, usando MySQL no Amazon RDS. Redis, Supervisor e cron estavam ativos na EC2.

[FATO COMPROVADO] O código possui estruturas de `Project`, `Video` e `Interaction`, além de `Course`, `Module`, `Lesson`, `Question` e `Option`. Há suporte aparente a players externos.

## Informações e inferências

[INFORMAÇÃO DO RESPONSÁVEL TÉCNICO] Editor e player possuem maturidade funcional relevante.

[INFERÊNCIA] A coexistência de conjuntos de domínio pode representar sobreposição de responsabilidades, mas não autoriza substituição.

## Pendências

[PENDÊNCIA] Workers, scheduler Laravel e Reverb não foram observados em execução. Proxy WebSocket e necessidade funcional também não foram comprovados.

[PENDÊNCIA] Schema, migrations, isolamento empresarial, autorização e comportamento completo do MVP ainda exigem validação.

## Princípios

[DECISÃO] Evoluir incrementalmente, preservar módulos maduros, usar testes de caracterização antes de mudanças e evitar reconstrução ampla. Não propor nova arquitetura nesta documentação.
