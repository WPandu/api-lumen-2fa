<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /** @var int */
    protected $statusCode = 200;

    protected $request;

    /**
     * Controller constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /** @return int */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @param $data
     * @param array $headers
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond($data, $headers = [])
    {
        return response()->json($data, $this->getStatusCode(), $headers);
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondCreated(array $data = [], $message = 'Resource Created')
    {
        return $this->setStatusCode(201)
            ->respond(array_merge(['message' => $message], $data));
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondUpdated(array $data = [], $message = 'Resource Updated')
    {
        return $this->setStatusCode(200)
            ->respond(array_merge(['message' => $message], $data));
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondDeleted(array $data = [], $message = 'Resource Deleted')
    {
        return $this->setStatusCode(200)
            ->respond(array_merge(['message' => $message], $data));
    }

    /**
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithError($message)
    {
        if (is_array($message)) {
            return $this->respond([
                'errors' => $message,
            ]);
        }

        return $this->respond([
            'errors' => [$message],
        ]);
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondMethodNotAllowed($message = 'Method Not Allowed')
    {
        return $this->setStatusCode(405)->respondWithError($message);
    }

    /**
     * @param $messages
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondFailValidation($messages)
    {
        return $this->setStatusCode(422)->respondWithError($messages);
    }

    /**
     * @param $messages
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondFailed($message)
    {
        return $this->setStatusCode(422)->respondWithError($message);
    }

    /**
     * @param $messages,$code,$error
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondError($message, $code = 0, $errors = [])
    {
        return $this->respond([
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ]);
    }
}
