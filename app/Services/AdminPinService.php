<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPinService
{
    /**
     * Número máximo de intentos fallidos antes de bloquear.
     */
    public const MAX_ATTEMPTS = 3;

    /**
     * Tiempo de bloqueo en segundos (5 minutos).
     */
    public const LOCKOUT_SECONDS = 300;

    /**
     * Prefijo para las claves de caché de intentos.
     */
    public const ATTEMPTS_PREFIX = 'admin_pin_attempts_';

    /**
     * Prefijo para las claves de caché de bloqueo.
     */
    public const LOCKOUT_PREFIX = 'admin_pin_lockout_';

    /**
     * Verifica el PIN proporcionado contra los administradores activos.
     *
     * Tras 3 intentos fallidos consecutivos, bloquea la verificación por 5 minutos.
     * Si la verificación es exitosa, reinicia el contador de intentos y retorna el User admin que autorizó.
     *
     * @param  string  $pin  PIN a verificar.
     * @param  string|null  $identifier  Identificador de contexto (IP, sesión, usuario o 'global').
     * @param  bool  $throwOnLockout  Si es true y el servicio está bloqueado, lanza ValidationException.
     * @return User|null Administrador que autorizó, o null si el PIN es inválido.
     *
     * @throws ValidationException Si la verificación está bloqueada y $throwOnLockout es true.
     */
    public function verify(string $pin, ?string $identifier = null, bool $throwOnLockout = true): ?User
    {
        $id = $this->resolveIdentifier($identifier);

        if ($this->isLocked($id)) {
            if ($throwOnLockout) {
                $restantes = $this->getRemainingLockoutSeconds($id);
                $minutos = (int) ceil($restantes / 60);

                throw ValidationException::withMessages([
                    'pin' => sprintf(
                        'Demasiados intentos fallidos. Verificación de PIN bloqueada durante %d minuto(s) (%d segundos restantes).',
                        $minutos,
                        $restantes
                    ),
                ]);
            }

            return null;
        }

        if (trim($pin) === '') {
            $this->recordFailedAttempt($id);

            return null;
        }

        // Buscar entre todos los administradores activos que tengan un PIN configurado
        $admins = User::query()
            ->where('role', 'admin')
            ->where('active', true)
            ->whereNotNull('admin_pin')
            ->where('admin_pin', '!=', '')
            ->get();

        foreach ($admins as $admin) {
            if (Hash::check($pin, $admin->admin_pin)) {
                $this->resetAttempts($id);

                return $admin;
            }
        }

        // PIN incorrecto: registrar intento fallido
        $this->recordFailedAttempt($id);

        return null;
    }

    /**
     * Alias de verify().
     */
    public function verifyPin(string $pin, ?string $identifier = null, bool $throwOnLockout = true): ?User
    {
        return $this->verify($pin, $identifier, $throwOnLockout);
    }

    /**
     * Comprueba si la verificación se encuentra actualmente bloqueada.
     */
    public function isLocked(?string $identifier = null): bool
    {
        $id = $this->resolveIdentifier($identifier);

        return Cache::has($this->lockoutKey($id));
    }

    /**
     * Obtiene los segundos restantes de bloqueo.
     */
    public function getRemainingLockoutSeconds(?string $identifier = null): int
    {
        $id = $this->resolveIdentifier($identifier);
        $expiresAt = Cache::get($this->lockoutKey($id));

        if (! $expiresAt) {
            return 0;
        }

        return max(0, (int) $expiresAt - now()->timestamp);
    }

    /**
     * Obtiene el número actual de intentos fallidos acumulados.
     */
    public function getAttempts(?string $identifier = null): int
    {
        $id = $this->resolveIdentifier($identifier);

        return (int) Cache::get($this->attemptsKey($id), 0);
    }

    /**
     * Reinicia los intentos y levanta cualquier bloqueo activo.
     */
    public function resetAttempts(?string $identifier = null): void
    {
        $id = $this->resolveIdentifier($identifier);

        Cache::forget($this->attemptsKey($id));
        Cache::forget($this->lockoutKey($id));
    }

    /**
     * Aplica un bloqueo manual por la duración indicada en segundos (por defecto 300 s / 5 min).
     */
    public function lockout(?string $identifier = null, int $seconds = self::LOCKOUT_SECONDS): void
    {
        $id = $this->resolveIdentifier($identifier);
        $expiresAt = now()->addSeconds($seconds)->timestamp;

        Cache::put($this->lockoutKey($id), $expiresAt, $seconds);
        Cache::forget($this->attemptsKey($id));
    }

    /**
     * Registra un intento fallido e incrementa el contador.
     * Si alcanza el umbral de 3 intentos, bloquea por 5 minutos.
     */
    protected function recordFailedAttempt(string $identifier): int
    {
        $attemptsKey = $this->attemptsKey($identifier);
        $attempts = ((int) Cache::get($attemptsKey, 0)) + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->lockout($identifier, self::LOCKOUT_SECONDS);

            return $attempts;
        }

        Cache::put($attemptsKey, $attempts, self::LOCKOUT_SECONDS);

        return $attempts;
    }

    /**
     * Resuelve el identificador de contexto.
     */
    protected function resolveIdentifier(?string $identifier = null): string
    {
        if ($identifier !== null && trim($identifier) !== '') {
            return trim($identifier);
        }

        try {
            $ip = request()?->ip();
            if ($ip) {
                return $ip;
            }
        } catch (\Throwable $e) {
            // Fuera de contexto HTTP
        }

        return 'global';
    }

    protected function attemptsKey(string $identifier): string
    {
        return self::ATTEMPTS_PREFIX.$identifier;
    }

    protected function lockoutKey(string $identifier): string
    {
        return self::LOCKOUT_PREFIX.$identifier;
    }
}
