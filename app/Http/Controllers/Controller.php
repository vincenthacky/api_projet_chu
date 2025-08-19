<?php

namespace App\Http\Controllers;

abstract class Controller
{
     protected function responseError($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'status_code' => $code,
            'message' => $message,
        ], $code);
    }

    protected function responseSuccessMessage($message, $code = 200)
    {
        return response()->json([
            'success' => true,
            'status_code' => $code,
            'message' => $message,
        ], $code);
    }

    protected function responseSuccess($data = null, $message = "", $code = 200)
    {
        return response()->json([
            'success' => true,
            'status_code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function responseSuccessPaginate($data = null, $message = "", $code = 200)
    {
        return response()->json([
            'success' => true,
            'status_code' => $code,
            'message' => $message ? "$message récupéré avec succès" : "Données récupérées avec succès",
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ]
        ], $code);
    }
}
