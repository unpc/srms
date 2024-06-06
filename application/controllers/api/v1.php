<?php
class _API_V1_Controller extends Controller
{
    public function _before_call($method, &$params)
    {
        $route = join('/', Input::args());

        $tokenFreeRoutes = Config::get('api.auth', [])['token_free_routes'];
        foreach ($tokenFreeRoutes as $regxp) {
            if (preg_match($regxp, $route)) {
                goto next;
            }
        }

        $agentTokenRoutes = Config::get('api.auth', [])['agent_token_routes'];
        foreach ($agentTokenRoutes as $regxp) {
            if (preg_match($regxp, $route)) {
                if (API_V1::keyValidate()) {
                    goto next;
                }
            }
        }

        $middlewareName = [];
        foreach (Input::args() as $routeName) {
            $middlewareName[] = $routeName;
            $triggers = [
                join('.', $middlewareName) . '.middlewares.*',
                join('.', $middlewareName) . '.middlewares.' . $_SERVER['REQUEST_METHOD']
            ];
            try {
                if (Event::trigger($triggers)) {
                    goto next;
                }
            } catch (Exception $e) {
                $response_code = $e->getCode();
                $response_body = $e->getMessage();
            }
        }
        err401:
        while (ob_end_clean());
        header('Content-Type: application/json; charset=utf-8');
        header("Status: " . ($response_code ?: 401));
        echo @json_encode(['error' => $response_body ?: 'unauthorized'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;

        next:
        parent::_before_call($method, $params);
    }

    protected function dispatch($pathParam)
    {
        Cache::L('IS_API_REQUEST', true);
        $code = 200;
        $routeArr = [];
        foreach (Input::args() as $r) {
            if (is_array($pathParam)) {
                if (in_array($r, $pathParam)) continue;
            } else {
                if ($pathParam === $r) continue;
            }
            $routeArr[] = $r;
        }
        $trigger = join('.', $routeArr) . '.' . $_SERVER['REQUEST_METHOD'];
        $triggerAll = join('.', Input::args()) . '.' . $_SERVER['REQUEST_METHOD'];
        if (count(Config::get("hooks.{$trigger}")) == 0 && count(Config::get("hooks.{$triggerAll}")) == 0) {
            $code = 404;
            $response = [
                'error' => 'Method not found',
            ];
        } else {
            $params = array_map("urldecode", Config::get('system.controller_params'));
            $query = $_GET;
            $content = file_get_contents('php://input');
            $data = @json_decode($content, true);

            try {
                $response = Event::trigger($trigger, $params, $data, $query) ?: Event::trigger($triggerAll, $params, $data, $query);
            } catch (Exception $e) {
                $code = $e->getCode() ?: 500;
                $response = [
                    'code' => $code,
                    'message' => $e->getMessage()
                ];
            }
        }

        while (ob_end_clean());

        header('Content-Type: application/json; charset=utf-8');
        if (intdiv($code, 200) != 1) {
            header("Status: {$code}");
        }
        echo @json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
class API_V1_Controller extends _API_V1_Controller
{
    public function index()
    {
        $args = func_get_args();
        $this->dispatch($args[0]);
    }
}
