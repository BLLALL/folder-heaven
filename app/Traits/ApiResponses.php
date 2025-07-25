<?php

namespace App\Traits;

trait ApiResponses
{
    protected function ok($message, $data = [])
    {
        return $this->success($message, $data, 200);
    }

    protected function success($message, $data = [], $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
        ], $status);
    }

    protected function error($errors, $status = 200)
    {
        if (is_string($errors)) {
            return response()->json([
                'message' => $errors,
                'status' => $status,
            ], $status);
        }

        return response()->json([
            'errors' => $errors,
        ], $status);
    }

    protected function unauthorized($message)
    {
        return $this->error([
            [
                'status' => 401,
                'message' => $message,
                'source' => '',
            ],
        ], 401);
    }
}
