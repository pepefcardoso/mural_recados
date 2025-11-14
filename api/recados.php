<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

include_once './config/Database.php';
include_once './models/Recado.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->connect();

$recado = new Recado($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $recado->id = htmlspecialchars(strip_tags($_GET['id']));
                $result = $recado->read_single();
                if ($result) {
                    echo json_encode($result);
                } else {
                    http_response_code(404);
                    echo json_encode(['mensagem' => 'Recado não encontrado.']);
                }
            } else {
                $result = $recado->read();
                echo json_encode($result);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->mensagem) || strlen(trim($data->mensagem)) < 3) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'Mensagem deve ter no mínimo 3 caracteres.']);
                break;
            }

            $recado->mensagem = htmlspecialchars(strip_tags($data->mensagem));

            if ($recado->create()) {
                http_response_code(201);
                echo json_encode(['mensagem' => 'Recado criado com sucesso.']);
            } else {
                http_response_code(500);
                echo json_encode(['mensagem' => 'Não foi possível criar o recado.']);
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->mensagem) || empty($data->id)) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'Dados incompletos para atualização.']);
                break;
            }

            $recado->id = htmlspecialchars(strip_tags($data->id));
            $recado->mensagem = htmlspecialchars(strip_tags($data->mensagem));

            if ($recado->update()) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Recado atualizado com sucesso.']);
            } else {
                http_response_code(500);
                echo json_encode(['mensagem' => 'Não foi possível atualizar o recado.']);
            }
            break;

        case 'PATCH':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->id)) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'ID do recado não fornecido.']);
                break;
            }

            $recado->id = htmlspecialchars(strip_tags($data->id));

            $newStatus = $recado->toggleFavorite();
            if ($newStatus !== null) {
                http_response_code(200);
                echo json_encode([
                    'mensagem' => 'Status de favorito atualizado.',
                    'newStatus' => $newStatus
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['mensagem' => 'Não foi possível atualizar o favorito.']);
            }
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"));

            if (empty($data->id)) {
                http_response_code(400);
                echo json_encode(['mensagem' => 'ID do recado não fornecido.']);
                break;
            }

            $recado->id = htmlspecialchars(strip_tags($data->id));

            if ($recado->delete()) {
                http_response_code(200);
                echo json_encode(['mensagem' => 'Recado excluído com sucesso.']);
            } else {
                http_response_code(500);
                echo json_encode(['mensagem' => 'Não foi possível excluir o recado.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['mensagem' => 'Método não permitido.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'mensagem' => 'Ocorreu um erro no servidor.',
        'erro' => $e->getMessage()
    ]);
}
