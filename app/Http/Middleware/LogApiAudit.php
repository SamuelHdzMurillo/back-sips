<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiAudit
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'api_key',
        'api_token',
        'access_token',
        'refresh_token',
        'authorization',
    ];

    private const MAX_PAYLOAD_CHARS = 32000;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->persist($request, $response);
        } catch (\Throwable $e) {
            Log::error('LogApiAudit: no se pudo guardar auditoría', [
                'message' => $e->getMessage(),
                'path'    => $request->path(),
            ]);
        }

        return $response;
    }

    private function persist(Request $request, Response $response): void
    {
        $user = $request->user();
        $route = $request->route();

        AuditLog::query()->insert([
            'user_id'      => $user?->id,
            'user_email'   => $user?->email ?? $this->guestEmailHint($request),
            'user_name'    => $user?->name,
            'user_role'    => $user?->role,
            'empleado_no'  => $user?->empleado_no,
            'method'       => $request->method(),
            'path'         => '/'.$request->path(),
            'route_name'   => $route?->getName(),
            'action'       => $route ? $route->getActionName() : null,
            'ip_address'   => $request->ip(),
            'user_agent'   => $this->truncate($request->userAgent(), 2000),
            'status_code'  => $response->getStatusCode(),
            'payload_json' => $this->safePayloadJson($request),
            'created_at'   => now(),
        ]);
    }

    private function guestEmailHint(Request $request): ?string
    {
        if (!$request->is('api/auth/login')) {
            return null;
        }

        $email = $request->input('email');

        return is_string($email) ? $email : null;
    }

    private function safePayloadJson(Request $request): ?string
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $query = $request->query->all();

            return $query === [] ? null : $this->encodePayload($this->sanitizeArray($query));
        }

        $content = $request->all();

        return $content === [] ? null : $this->encodePayload($this->sanitizeArray($content));
    }

    private function sanitizeArray(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $keyLower = is_string($key) ? strtolower($key) : $key;

            if (is_string($keyLower) && in_array($keyLower, self::SENSITIVE_KEYS, true)) {
                $out[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->sanitizeArray($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function encodePayload(array $data): ?string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return '{"_error":"payload no serializable"}';
        }

        if (strlen($json) > self::MAX_PAYLOAD_CHARS) {
            return substr($json, 0, self::MAX_PAYLOAD_CHARS)."\n…[truncado]";
        }

        return $json;
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        return strlen($value) <= $max ? $value : substr($value, 0, $max).'…';
    }
}
