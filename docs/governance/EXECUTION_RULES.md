# Regras de Execução

## Precedência

1. Integridade dos dados e segurança.
2. Comportamento comprovado em homologação.
3. Código e configuração implantados.
4. Documento 04.
5. Decisões formalizadas.
6. Plano Mestre.
7. Documento 05.
8. Inferências.

Divergências não podem ser resolvidas silenciosamente.

## Papéis e unidades

O ChatGPT governa, planeja, revisa e prepara unidades. O Codex executa tecnicamente no A5 e no repositório original, produzindo código, testes, relatórios e evidências quando autorizado. Cada unidade possui escopo próprio, uma execução por vez e decisão final documentada.

## Verificações iniciais

Confirmar diretório, branch, HEAD, upstream, ahead/behind, staged, unstaged, untracked, diff e artefatos preexistentes. Preservar divergências conhecidas e parar diante de divergência não compreendida.

## Restrições

Sem autorização específica: não fazer push, PR, merge, rebase, tag, release, deploy, alteração AWS, migrations, alterações destrutivas, alteração de banco, ou ações de serviço. Não executar instalação ou build fora do escopo da unidade.

## Segurança e dados

Não registrar segredos, credenciais, cookies, tokens, dados pessoais ou valores de `.env`. Usar dados fictícios apenas em ambientes autorizados. Sanitizar logs e evidências.

## Banco, migrations e seeders

Não acessar ou alterar RDS sem autorização. Migrations exigem compatibilidade, backup, rollback e revisão. Evidências dependentes de banco devem usar MySQL compatível. Seeders devem ser idempotentes e separados entre estruturais e locais.

## Testes e validação

Quando aplicável, usar caracterização, testes unitários/integrados, MySQL, autorização, isolamento, regressão, análise estática, lint, typecheck, build, smoke test e validação manual pelo navegador. Nenhum teste substitui evidência de homologação quando ela for necessária.

## Evidências

Registrar comandos, estado Git, resultados, riscos, rollback e limitações. Relatórios e manifests devem permanecer em diretório persistente de governança, nunca depender exclusivamente de `/tmp`.

## Condições de parada

Parar diante de segredo, risco de perda, backup ausente, rollback inviável, divergência não compreendida, regressão, migration destrutiva, acesso cruzado, alteração AWS, decisão de produto/arquitetura ou ampliação de escopo. Preservar o estado e relatar o bloqueio.

## Definition of Done

Uma unidade exige escopo entregue, revisão, testes aplicáveis, MySQL quando necessário, autorização/isolamento quando aplicável, lint/análise/typecheck/build quando aplicável, roteiro executado, evidências, documentação, estado Git, riscos residuais, rollback e decisão canônica.

Não declarar conclusão por inferência, documentação, presença de código ou pacote.
