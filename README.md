# Student Tracker (`local_student_tracker`)

Plugin local do Moodle que monitora o acesso dos alunos dentro de cada curso. Classifica os matriculados pelo último acesso **ao curso** (nunca acessaram, em risco, ativos), exibe cartões clicáveis com totais e percentuais e lista os alunos em uma tabela filtrável.

> Status: **alfa** (release 0.1.0). Teste em ambiente de homologação antes de usar em produção.

## Requisitos

| Item | Requisito |
|---|---|
| Moodle | 4.1 LTS ou superior |
| Tema | Boost (Bootstrap 4). No Moodle 5.0, troque as classes `badge-*` por `badge bg-*` no `index.php` |
| Papel de aluno | Nome curto `student` (padrão do Moodle) |
| PHP | O mesmo exigido pela sua versão do Moodle |
| Acesso | Administrador do site |

## Estrutura do plugin

A pasta do plugin **precisa** se chamar `student_tracker`, igual ao nome do componente.

```text
student_tracker/
├── version.php
├── index.php
├── lib.php
├── settings.php
├── db/
│   └── access.php
├── classes/
│   └── privacy/
│       └── provider.php
└── lang/
    ├── en/
    │   └── local_student_tracker.php
    └── pt_br/
        └── local_student_tracker.php
```

## Instalação

Escolha **um** dos três métodos.

### Método A: copiar a pasta (servidor próprio ou Docker)

1. Copie a pasta `student_tracker` para dentro de `local/` na raiz do Moodle:

   ```bash
   cp -r student_tracker /var/www/html/moodle/local/
   ```

   O caminho final deve ser `moodle/local/student_tracker/version.php`.

2. Ajuste o dono e as permissões para o usuário do servidor web:

   ```bash
   sudo chown -R www-data:www-data /var/www/html/moodle/local/student_tracker
   sudo find /var/www/html/moodle/local/student_tracker -type d -exec chmod 755 {} \;
   sudo find /var/www/html/moodle/local/student_tracker -type f -exec chmod 644 {} \;
   ```

   Troque `www-data` pelo usuário do seu servidor (`apache`, `nginx` etc.).

3. Conclua a instalação pelo navegador (passo 4 abaixo) ou pela linha de comando:

   ```bash
   sudo -u www-data php admin/cli/upgrade.php
   ```

### Método B: upload de arquivo ZIP

1. Compacte a pasta de modo que o ZIP contenha `student_tracker/` na raiz:

   ```bash
   zip -r student_tracker.zip student_tracker/
   ```

2. Acesse **Administração do site > Plugins > Instalar plugins**.
3. Envie o ZIP, confirme o tipo **Plugin local (local)** e clique em **Instalar plugin do arquivo ZIP**.
4. Siga as telas de validação e confirme.

Se o instalador por ZIP não aparecer, ele pode estar desativado no servidor (`$CFG->disableupdateautodeploy`). Use o método A.

### Método C: Git

```bash
cd /var/www/html/moodle/local
git clone <URL-DO-REPOSITORIO> student_tracker
sudo chown -R www-data:www-data student_tracker
```

## Finalizar a instalação (passo 4)

1. Entre como administrador e acesse **Administração do site > Notificações**.
2. O Moodle detecta o novo plugin e mostra a tela de verificação de plugins. Confira a linha **Student Tracker** e clique em **Atualizar banco de dados Moodle agora**.
3. Aguarde a mensagem de sucesso e clique em **Continuar**.
4. Limpe os caches: **Administração do site > Desenvolvimento > Limpar todos os caches** (ou `php admin/cli/purge_caches.php`).

## Configuração

1. Acesse **Administração do site > Plugins > Plugins locais > Student Tracker**.
2. Defina **Dias para considerar "em risco"** (`riskdays`). O padrão é 7.
3. Salve as alterações.

## Permissões

A capability `local/student_tracker:view` é avaliada no **contexto do curso** e vem liberada por padrão para:

- **Gerente** (herda o acesso em todos os cursos);
- **Professor** (papel `editingteacher`, no curso em que leciona).

Para liberar para outro papel (por exemplo, tutor):

1. Acesse **Administração do site > Usuários > Permissões > Definir papéis**.
2. Edite o papel desejado, procure por `student_tracker:view` e marque **Permitir**.

Para conferir o acesso de um usuário em um curso: **Administração do curso > Participantes > Permissões**, e use **Verificar permissões**.

## Como usar

1. Abra o curso da turma (por exemplo, ADS).
2. No menu do curso, abra a aba **Mais** e clique em **Student Tracker**.
3. Veja os cartões (nunca acessaram, em risco, ativos) e o total de alunos.
4. Clique em um cartão para filtrar a tabela. Use **Limpar filtro** para voltar à lista completa.

## Checklist de validação

- [ ] O plugin aparece em **Administração do site > Plugins > Visão geral dos plugins**, versão `0.1.0`.
- [ ] O link **Student Tracker** aparece na aba **Mais** do curso para o professor e o gerente.
- [ ] O aluno **não** vê o link e recebe erro de permissão ao abrir a URL direto.
- [ ] A soma dos três cartões é igual ao total de alunos.
- [ ] Os filtros por cartão funcionam.
- [ ] Alterar `riskdays` muda o texto do cartão "Em risco" e a classificação.

## Atualização do plugin

1. Substitua os arquivos da pasta `local/student_tracker`.
2. **Incremente** `$plugin->version` no `version.php` (formato `YYYYMMDDXX`), principalmente se mudar o `db/access.php`.
3. Acesse **Administração do site > Notificações** e conclua a atualização.
4. Limpe os caches.

## Solução de problemas

| Sintoma | Causa provável | Solução |
|---|---|---|
| O link não aparece na aba "Mais" | Cache antigo, versão não incrementada ou falta de permissão | Limpe os caches, incremente o `version` e confira a permissão no curso |
| O menu mostra `[[pluginname]]` | String de idioma ausente | Confira `lang/pt_br/` e `lang/en/` e limpe os caches |
| A permissão aparece como `[[student_tracker:view]]` | String da capability ausente | Adicione `$string['student_tracker:view']` no arquivo de idioma |
| Erro "component name does not match directory" | Pasta com nome diferente de `student_tracker` | Renomeie a pasta para `student_tracker` |
| Professor recebe "sem permissão" | Capability antiga em contexto de sistema ou papel sem a permissão | Atualize o plugin, execute as notificações e confira o papel no curso |
| Tabela vazia | Alunos sem papel `student`, matrículas suspensas ou método de inscrição desativado | Verifique os participantes do curso e o status das matrículas |
| Aviso de privacidade no admin | Falta o `classes/privacy/provider.php` | Adicione o provider (`null_provider`) |
| Ativar o modo de depuração | Para ver erros detalhados | **Administração do site > Desenvolvimento > Depuração** e escolha **DEVELOPER** (somente em homologação) |

## Desinstalação

1. Acesse **Administração do site > Plugins > Visão geral dos plugins**.
2. Localize **Student Tracker**, clique em **Desinstalar** e confirme.
3. Remova a pasta `local/student_tracker` do servidor.

O plugin não cria tabelas próprias; a desinstalação remove apenas a capability e a configuração `riskdays`.

## Privacidade (LGPD)

O painel exibe nome, e-mail e último acesso dos alunos. Libere a permissão somente a quem precisa. O plugin não armazena dados pessoais próprios e se declara como `null_provider` na API de privacidade do Moodle.

## Roadmap

- Template Mustache e classe de consulta em `classes/`.
- Paginação da tabela para turmas grandes.
- Filtro por polo.
- Envio de mensagem aos alunos de uma categoria.
- Escala de status mais detalhada (atenção, risco, possível evasão).
- Monitoramento de realização de atividades.
- Testes automatizados (PHPUnit e Behat).

## Licença

Defina a licença do projeto (por exemplo, GPL v3, a mesma do Moodle) e adicione o cabeçalho nos arquivos PHP antes de publicar.