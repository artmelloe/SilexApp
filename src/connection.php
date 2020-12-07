<?php

// Esbelece conexão <user> <password>

$conn = new MongoDB\Client('mongodb+srv://<user>:<password>@php.ymnwe.mongodb.net');

// Seleciona o banco de dados <database>

$db = $conn->selectDatabase('<database>');