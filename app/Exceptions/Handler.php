<?php

namespace App\Exceptions;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        AppException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $e
     * @return void
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return \Illuminate\Http\Response
     */
    //phpcs:ignore
    public function render($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return $this->handle($e, $request, $this->renderValidationException($e));
        }

        if ($e instanceof AuthenticationException) {
            return $this->handle($e, $request, $this->renderAuthenticationException($e));
        }

        if ($e instanceof AuthorizationException) {
            return $this->handle($e, $request, $this->renderAuthorizationException($e));
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->handle($e, $request, $this->renderModelNotFoundException());
        }

        if ($e instanceof QueryException) {
            return $this->handle($e, $request, $this->renderQueryException($e));
        }

        if ($e instanceof AppException) {
            return $this->handle($e, $request, $this->renderAppException($e));
        }

        // Default response to status code 500
        $statusCode = 500;

        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
        }

        $response = app(Controller::class)
            ->setStatusCode($statusCode)
            ->respondError($e->getMessage(), $e->getCode());

        return $this->handle($e, $request, $response);
    }

    private function renderValidationException(ValidationException $e)
    {
        return app(Controller::class)
            ->respondFailValidation(get_arr_one_dimen($e->validator->errors()->toArray()));
    }

    private function renderAuthenticationException(AuthenticationException $e)
    {
        return app(Controller::class)
            ->setStatusCode(401)
            ->respondError($e->getMessage());
    }

    private function renderAuthorizationException(AuthorizationException $e)
    {
        return app(Controller::class)
            ->setStatusCode(403)
            ->respondError($e->getMessage());
    }

    private function renderModelNotFoundException()
    {
        return app(Controller::class)
            ->setStatusCode(404)
            ->respondError('Resource not found');
    }

    private function renderQueryException(QueryException $e)
    {
        return app(Controller::class)
            ->setStatusCode(400)
            ->respondError(config('app.debug') ? $e->getMessage() : 'Oops, something when wrong!');
    }

    private function renderAppException(AppException $e)
    {
        return app(Controller::class)
            ->setStatusCode($e->getStatusCode())
            ->respondError($e->getMessage(), $e->getCode(), $e->getErrors());
    }

    private function handle(Throwable $e, Request $request, JsonResponse $response)
    {
        if (config('app.debug') && !($e instanceof ValidationException)) {
            $data = $response->getData();

            $data->trace = explode(PHP_EOL, $e);
            $data->request = [
                'url' => $request->fullUrl(),
                'headers' => $request->header(),
                'payload' => $request->json()->all(),
            ];

            $response->setData($data);
        }

        return $response;
    }
}
