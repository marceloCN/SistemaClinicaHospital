<?php

declare(strict_types=1);

namespace Lib\Core;

use Lib\Support\Session;

class Controller
{
    protected Request $request;

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    protected function view(string $view, array $data = []): Response
    {
        return Response::make(View::render($view, $data));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $route, array $params = []): Response
    {
        $url = \route($route, $params) ?? $route;
        return Response::redirect($url);
    }

    protected function back(int $status = 302): Response
    {
        return Response::back($status);
    }

    protected function validate(array $rules): array
    {
        $validator = \Lib\Support\Validator::make($this->request->all(), $rules);

        if ($validator->fails()) {
            throw \Lib\Support\ValidationException::fromValidator(
                $validator,
                $this->request->all(),
                !$this->request->isAjax()
            );
        }

        return $validator->validated();
    }

    protected function isAuthenticated(): bool
    {
        return Session::isLoggedIn();
    }

    protected function isAdmin(): bool
    {
        return $this->isAuthenticated();
    }
}
