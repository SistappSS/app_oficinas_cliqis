<?php

namespace App\Traits;

trait HttpResponse
{
    protected function trait($type, $data = null, $message = null)
    {
        switch ($type) {
            case 'get':
                return response()->json([
                    'status' => 'sucesso',
                    'message' => '<i class="fa-solid fa-check"></i> Requisição de dados efetuada com sucesso!',
                    'data' => $data
                ], 200);

                break;
            case 'store':
                return response()->json([
                    'status' => 'sucesso',
                    'message' => '<i class="fa-solid fa-check"></i> Inserção de dados efetuada com sucesso!',
                    'data' => $data
                ], 200);

                break;
            case 'update':
                return response()->json([
                    'status' => 'sucesso',
                    'message' => '<i class="fa-solid fa-pen-to-square"></i> Atualização de dados efetuada com sucesso!',
                    'data' => $data
                ], 200);

                break;
            case 'delete':
                return response()->json([
                    'status' => 'sucesso',
                    'message' => '<i class="fa-solid fa-trash-can"></i> Remoção de dados efetuada com sucesso!',
                    'data' => $data
                ], 200);

                break;
            case 'notify':
                return response()->json([
                    'status' => 'notify',
                    'message' => '<i class="fa-solid fa-bell"></i> Atenção, verifique as informações!'
                ], 404);

                break;
            case 'error':
                return response()->json([
                    'status' => 'erro',
                    'message' => '<i class="fas fa-exclamation-triangle"></i> Erro! Requisição de dados não efetuada.'
                ], 404);

                break;
        }
    }
}
