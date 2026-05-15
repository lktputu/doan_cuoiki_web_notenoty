<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->validator->errors()->first(),
                    'errors' => $exception->validator->errors(),
                ], 422);
            }

            if ($exception instanceof PostTooLargeException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ảnh tải lên quá lớn. Vui lòng chọn ảnh nhỏ hơn hoặc giảm số lượng ảnh.',
                ], 413);
            }

            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy dữ liệu cần thao tác.',
                ], 404);
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $message = $status >= 500
                ? 'Máy chủ đang gặp lỗi xử lý. Vui lòng thử lại sau ít phút.'
                : ($exception->getMessage() ?: 'Yêu cầu chưa hợp lệ.');

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return parent::render($request, $exception);
    }
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
