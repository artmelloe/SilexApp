# Silex App

Aplicação super simples desenvolvida em PHP com Silex/Mongodb.

# Especificações

  - PHP: 7.4
  - Mongodb Driver: 1.8
  - Apache: 2.4

# Configuração

  - Atualizar as dependências com o "composer".
  - Habilitar o módulo "mod_rewrite" no Apache.
  - Fazer o apontamento da pasta "web/" no "VirtualHost" do Apache.
  - Opção "FallbackResource /index.php" dentro de "Directory" para remover o index.php.
  - Editar o arquivo "src/connection.php" com as informações do banco de dados.

# Requisições API

Exemplos da entidade **Estado**:

| Method | Header | URL | Json/Body |
| ------ | ------ | ------ | ------ |
| GET | Content-Type: application/json | silex.app/estados | **NULL** |
| POST | Content-Type: application/json | silex.app/estado/novo/salvar | {"id":"29","nome":"Paraná","abreviacao":"PR"} |
| PUT | Content-Type: application/json | silex.app/estado/editar/salvar/{id} | {"nome":"Paraná","abreviacao":"PR"} |
| DELETE | Content-Type: application/json | silex.app/estado/deletar/{id} | **NULL** |

Exemplos da entidade **Cidade**:

| Method | Header | URL | Json/Body |
| ------ | ------ | ------ | ------ |
| GET | Content-Type: application/json | silex.app/cidades | **NULL** |
| POST | Content-Type: application/json | silex.app/cidade/novo/salvar | {"id":"29","nome":"Santos","estado_id":"16"} |
| PUT | Content-Type: application/json | silex.app/cidade/editar/salvar/{id} | {"nome":"Santos","estado_id":"16"} |
| DELETE | Content-Type: application/json | silex.app/cidade/deletar/{id} | **NULL** |