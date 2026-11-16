# Backup e Rollback

## Estado conhecido

[FATO COMPROVADO] Foi comprovado backup temporário do vhost Nginx usado para a contenção Scribe.

[FATO COMPROVADO] O rollback desse vhost foi documentado na governança operacional.

[PENDÊNCIA] Backup completo da aplicação, backup do RDS, AMI, EBS, releases anteriores, retenção, restore e rollback integral não foram comprovados.

## Regras

[DECISÃO] Não executar migrations ou deploy sem backup, compatibilidade e rollback verificáveis. Não declarar rollback por mera existência de arquivo.

[DECISÃO] O backup exato do vhost é temporário; artefatos canônicos devem permanecer no diretório persistente de governança.

## Continuidade

[PENDÊNCIA] Ainda é necessário formalizar backup da aplicação e do banco, retenção, responsáveis, restauração testada e procedimento pós-deploy. Nenhum mecanismo novo é criado por este documento.
