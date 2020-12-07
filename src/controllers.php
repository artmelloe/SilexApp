<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

Request::enableHttpMethodParameterOverride();

// Index

$app->get('/', function () use ($app, $db) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('index');

// Listar estados

$app->get('/estados', function (Request $request) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('estado');

    // Lista todos os estados

    $estados = $collection->find(
	    [],
	    []
    );

    // Lista via requisição json

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json(iterator_to_array($estados), Response::HTTP_OK);
    }

    // Retorna para a listagem

    return $app['twig']->render('estados.html.twig', array('estados' => iterator_to_array($estados)));
})
->bind('estados');

// Novo estado

$app->get('/estado/novo', function (Request $request) use ($app, $db) {

    // Acessa a coleção


    $collection = $db->selectCollection('estado');

    // Seleciona o último estado cadastrado para pegar seu ID e fazer um "increment"

    $estado = $collection->find(
        [],
        [
            'sort' => ['id' => -1],
            'limit' => 1
        ]
    );

    // Joga os dados para o novo estado

    return $app['twig']->render('estado_novo.html.twig', array('estado' => iterator_to_array($estado)));
})
->bind('estado_novo');

// Salvar novo estado

$app->post('/estado/novo/salvar', function (Request $request) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('estado');

    // Verifica a origem da requisição dos dados

    if($request->headers->get('Content-Type') == 'application/json'){
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $id = $request->request->get('id');
        $nome = $request->request->get('nome');
        $abreviacao = $request->request->get('abreviacao');
    }else{
        $id = $request->get('id');
        $nome = $request->get('nome');
        $abreviacao = $request->get('abreviacao');
    }

    // Transforma data do MongoDB para PHP

    $date_now = new DateTime();
    $date_now = $date_now->getTimestamp();
    $date_mongodb = new MongoDB\BSON\UTCDateTime($date_now * 1000);

    $data_criacao = $date_mongodb;
    $data_modificao = $date_mongodb;

    // Validação de campos

    $validacao = array(
        'id' => $id,
        'nome' => $nome,
        'abreviacao' => $abreviacao
    );

    $regra = new Assert\Collection(array(
        'id' => array(new Assert\NotBlank()),
        'nome' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 4))),
        'abreviacao' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2)))
    ));

    $erros = $app['validator']->validate($validacao, $regra);
    
    // Caso tenha erros, joga em uma sessão flash

    if (count($erros) > 0) {

        // Verifica a origem da requisição de dados

        if($request->headers->get('Content-Type') == 'application/json'){

            // Armazena os erros num array

            $erros_json = [];

            foreach ($erros as $erro) {
                $erros_json[] = $erro->getPropertyPath().' '.$erro->getMessage();
            }

            return $app->json($erros_json, Response::HTTP_BAD_REQUEST);
        }else{
            foreach ($erros as $erro) {
                $app['session']->getFlashBag()->add('validacao', $erro->getPropertyPath().' '.$erro->getMessage()."\n");
            }
    
            return $app->redirect('/estado/novo');
        }
    }

    // Insere os dados no banco

    $insertResult = $collection->insertOne([
        'id' => $id,
        'nome' => $nome,
        'abreviacao' => $abreviacao,
        'data_criacao' => $data_criacao,
        'data_modificao' => $data_modificao
    ]);
    
    // Retorna para a listagem

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Estado cadastrado com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/estados');
    }
});

// Editar estado

$app->get('/estado/editar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção
    
    $collection = $db->selectCollection('estado');

    // Seleciona o estado baseado no ID

    $estado = $collection->find(
	    [
            'id' => $id
        ],
	    [
            'limit' => 1
        ]
    );

    // Joga os dados para editar o estado

    return $app['twig']->render('estado_editar.html.twig', array('estado' => iterator_to_array($estado)));
})
->bind('estado_editar');

// Salvar estado editado

$app->put('/estado/editar/salvar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('estado');

    // Verifica a origem da requisição dos dados
    
    if($request->headers->get('Content-Type') == 'application/json'){
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $nome = $request->request->get('nome');
        $abreviacao = $request->request->get('abreviacao');
    }else{
        $nome = $request->get('nome');
        $abreviacao = $request->get('abreviacao');
    }

    // Transforma data do MongoDB para PHP

    $date_now = new DateTime();
    $date_now = $date_now->getTimestamp();
    $date_mongodb = new MongoDB\BSON\UTCDateTime($date_now * 1000);

    $data_modificao = $date_mongodb;

    // Validação de campos

    $validacao = array(
        'id' => $id,
        'nome' => $nome,
        'abreviacao' => $abreviacao
    );

    $regra = new Assert\Collection(array(
        'id' => array(new Assert\NotBlank()),
        'nome' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 4))),
        'abreviacao' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 2)))
    ));

    $erros = $app['validator']->validate($validacao, $regra);
    
    // Caso tenha erros, joga em uma sessão flash

    if (count($erros) > 0) {

        // Verifica a origem da requisição de dados

        if($request->headers->get('Content-Type') == 'application/json'){

            // Armazena os erros num array

            $erros_json = [];

            foreach ($erros as $erro) {
                $erros_json[] = $erro->getPropertyPath().' '.$erro->getMessage();
            }

            return $app->json($erros_json, Response::HTTP_BAD_REQUEST);
        }else{
            foreach ($erros as $erro) {
                $app['session']->getFlashBag()->add('validacao', $erro->getPropertyPath().' '.$erro->getMessage()."\n");
            }
    
            return $app->redirect('/estado/editar/'.$id);
        }
    }

    // Insere os dados no banco

    $updateResult = $collection->updateOne(
        [
            'id' => $id
        ],
        [
            '$set' => [
                'nome' => $nome,
                'abreviacao' => $abreviacao,
                'data_modificado' => $data_modificao
            ]
        ]
    );
    
    // Retorna para a listagem

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Estado editado com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/estados');
    }
});

// Deletar estado

$app->delete('/estado/deletar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('estado');

    // Seleciona o estado baseado no ID

    $deleteResult = $collection->deleteOne(
        [
            'id' => $id
        ]
    );

    // Retorna para a listagem

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Estado deletado com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/estados');
    }
});

// Listar cidades

$app->get('/cidades', function (Request $request) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('cidade');

    // Lista todos os cidades

    $cidades = $collection->find(
	    [],
	    []
    );

    // Lista via requisição json

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json(iterator_to_array($cidades), Response::HTTP_OK);
    }

    // Retorna para a listagem

    return $app['twig']->render('cidades.html.twig', array('cidades' => iterator_to_array($cidades)));
})
->bind('cidades');

// Nova cidade

$app->get('/cidade/novo', function (Request $request) use ($app, $db) {

    // Acessa a coleção de estados

    $collection_estados = $db->selectCollection('estado');

    // Seleciona os estados em ordem ASC

    $estados = $collection_estados->find(
	    [],
	    [
            'sort' => ['nome' => 1],
        ]
    );

    // Acessa a coleção de cidade

    $collection_cidade = $db->selectCollection('cidade');

    // Seleciona a última cidade cadastrado para pegar seu ID e fazer um "increment"

    $cidade = $collection_cidade->find(
        [],
        [
            'sort' => ['id' => -1],
            'limit' => 1
        ]
    );

    // Joga os dados para a nova cidade

    return $app['twig']->render('cidade_novo.html.twig', array('estados' => iterator_to_array($estados), 'cidade' => iterator_to_array($cidade)));
})
->bind('cidade_novo');

// Salvar nova cidade

$app->post('/cidade/novo/salvar', function (Request $request) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('cidade');

    // Verifica a origem da requisição dos dados

    if($request->headers->get('Content-Type') == 'application/json'){
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $id = $request->request->get('id');
        $nome = $request->request->get('nome');
        $estado = $request->request->get('estado_id');
    }else{
        $id = $request->get('id');
        $nome = $request->get('nome');
        $estado = $request->get('estado_id');
    }

    // Transforma data do MongoDB para PHP

    $date_now = new DateTime();
    $date_now = $date_now->getTimestamp();
    $date_mongodb = new MongoDB\BSON\UTCDateTime($date_now * 1000);

    $data_criacao = $date_mongodb;
    $data_modificao = $date_mongodb;

    // Validação de campos

    $validacao = array(
        'id' => $id,
        'nome' => $nome,
        'estado' => $estado
    );

    $regra = new Assert\Collection(array(
        'id' => array(new Assert\NotBlank()),
        'nome' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 4))),
        'estado' => array(new Assert\NotBlank()),
    ));

    $erros = $app['validator']->validate($validacao, $regra);
    
    // Caso tenha erros, joga em uma sessão flash

    if (count($erros) > 0) {

        // Verifica a origem da requisição de dados

        if($request->headers->get('Content-Type') == 'application/json'){

            // Armazena os erros num array

            $erros_json = [];

            foreach ($erros as $erro) {
                $erros_json[] = $erro->getPropertyPath().' '.$erro->getMessage();
            }

            return $app->json($erros_json, Response::HTTP_BAD_REQUEST);
        }else{
            foreach ($erros as $erro) {
                $app['session']->getFlashBag()->add('validacao', $erro->getPropertyPath().' '.$erro->getMessage()."\n");
            }
    
            return $app->redirect('/cidade/novo');
        }
    }

    // Insere os dados no banco

    $insertResult = $collection->insertOne([
        'id' => $id,
        'nome' => $nome,
        'estado_id' => $estado,
        'data_criacao' => $data_criacao,
        'data_modificao' => $data_modificao
    ]);
    
    // Retorna para a listagem
    
    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Cidade cadastrada com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/cidades');
    }
});

// Editar cidade

$app->get('/cidade/editar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção de estados

    $collection_estados = $db->selectCollection('estado');

    // Seleciona os estados em ordem ASC

    $estados = $collection_estados->find(
	    [],
	    [
            'sort' => ['nome' => 1],
        ]
    );

    // Acessa a coleção de cidade

    $collection = $db->selectCollection('cidade');

    // Seleciona o cidade baseado no ID

    $cidade = $collection->find(
	    [
            'id' => $id
        ],
	    [
            'limit' => 1
        ]
    );

    // Joga os dados para editar o cidade

    return $app['twig']->render('cidade_editar.html.twig', array('estados' => iterator_to_array($estados), 'cidade' => iterator_to_array($cidade)));
})
->bind('cidade_editar');

// Salvar cidade editada

$app->put('/cidade/editar/salvar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('cidade');

    // Verifica a origem da requisição dos dados
    
    if($request->headers->get('Content-Type') == 'application/json'){
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());

        $nome = $request->request->get('nome');
        $estado = $request->request->get('estado_id');
    }else{
        $nome = $request->get('nome');
        $estado = $request->get('estado_id');
    }

    // Transforma data do MongoDB para PHP

    $date_now = new DateTime();
    $date_now = $date_now->getTimestamp();
    $date_mongodb = new MongoDB\BSON\UTCDateTime($date_now * 1000);

    $data_modificao = $date_mongodb;

    // Validação de campos

    $validacao = array(
        'id' => $id,
        'nome' => $nome,
        'estado' => $estado
    );

    $regra = new Assert\Collection(array(
        'id' => array(new Assert\NotBlank()),
        'nome' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 4))),
        'estado' => array(new Assert\NotBlank())
    ));

    $erros = $app['validator']->validate($validacao, $regra);
    
    // Caso tenha erros, joga em uma sessão flash

    if (count($erros) > 0) {

        // Verifica a origem da requisição de dados

        if($request->headers->get('Content-Type') == 'application/json'){

            // Armazena os erros num array

            $erros_json = [];

            foreach ($erros as $erro) {
                $erros_json[] = $erro->getPropertyPath().' '.$erro->getMessage();
            }

            return $app->json($erros_json, Response::HTTP_BAD_REQUEST);
        }else{
            foreach ($erros as $erro) {
                $app['session']->getFlashBag()->add('validacao', $erro->getPropertyPath().' '.$erro->getMessage()."\n");
            }
    
            return $app->redirect('/cidade/editar/'.$id);
        }
    }

    // Insere os dados no banco

    $updateResult = $collection->updateOne(
        [
            'id' => $id
        ],
        [
            '$set' => [
                'nome' => $nome,
                'estado' => $estado,
                'data_modificado' => $data_modificao
            ]
        ]
    );
    
    // Retorna para a listagem

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Cidade editada com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/cidades');
    }
});

$app->delete('/cidade/deletar/{id}', function (Request $request, $id) use ($app, $db) {

    // Acessa a coleção

    $collection = $db->selectCollection('cidade');

    // Seleciona oa cidade baseado no ID

    $deleteResult = $collection->deleteOne(
        [
            'id' => $id
        ]
    );

    // Retorna para a listagem

    if($request->headers->get('Content-Type') == 'application/json'){
        return $app->json('Cidade deletada com sucesso!', Response::HTTP_OK);
    }else{
        return $app->redirect('/cidades');
    }
});

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
