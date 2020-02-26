<?php declare(strict_types=1);

// Just for the PhpStorms's auto-importing
namespace Acme;

use Siler\Route;
use Swoole\Table;
use function Siler\array_get_str;
use function Siler\Encoder\Json\decode;
use function Siler\Swoole\{http, json, no_content, raw};

require_once __DIR__ . '/vendor/autoload.php';

$todos = new Table(1024);
$todos->column('id', Table::TYPE_STRING, 13);
$todos->column('body', Table::TYPE_STRING, 240);
$todos->column('status', Table::TYPE_STRING, 8);
$todos->create();

$handler = function () use ($todos) {
    // Create
    Route\post('/todos', function () use ($todos): void {
        $data = decode(raw());

        $todo = [
            'id' => uniqid(),
            'body' => array_get_str($data, 'body'),
            'status' => array_get_str($data, 'status', 'undone'),
        ];

        $todos[$todo['id']] = $todo;

        json($todo, 201);
    });

    // Read
    Route\get('/todos', fn() => json(iterator_to_array($todos)));

    // Update
    Route\put('/todos/{id}', function (array $params) use ($todos): void {
        $id = array_get_str($params, 'id');
        $data = decode(raw());

        $todos[$id]['body'] = array_get_str($data, 'body', $todos[$id]['body']);
        $todos[$id]['status'] = array_get_str($data, 'status', $todos[$id]['status']);

        json($todos[$id]->value);
    });

    // Delete
    Route\delete('/todos/{id}', fn(array $params) => $todos->del($params['id']) & no_content());
};

http($handler, 8000)->start();