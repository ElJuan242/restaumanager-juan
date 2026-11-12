<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AdminPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminPinServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminPinService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminPinService;
        Cache::flush();
    }

    public function test_config_restauranager_loads_new_keys_with_defaults(): void
    {
        $this->assertSame(0.10, config('restauranager.service_charge_rate'));
        $this->assertSame(0.10, config('restauranager.service_charge'));
        $this->assertSame(120, config('restauranager.reservation_duration_minutes'));
        $this->assertSame(15, config('restauranager.reservation_turnover_buffer_minutes'));
        $this->assertSame(15, config('restauranager.po_overdue_days'));
    }

    public function test_user_model_hashes_and_hides_admin_pin(): void
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@restauranager.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        $this->assertTrue($user->hasAdminPin());
        $this->assertNotEquals('123456', $user->admin_pin);
        $this->assertTrue(Hash::check('123456', $user->admin_pin));

        // Verificar que admin_pin se oculte en serialización
        $this->assertArrayNotHasKey('admin_pin', $user->toArray());
    }

    public function test_admin_pin_service_verifies_valid_pin_and_returns_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin Gerente',
            'email' => 'gerente@restauranager.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        $result = $this->service->verify('123456', 'test_context');

        $this->assertNotNull($result);
        $this->assertSame($admin->id, $result->id);
        $this->assertFalse($this->service->isLocked('test_context'));
        $this->assertSame(0, $this->service->getAttempts('test_context'));
    }

    public function test_admin_pin_service_ignores_inactive_admin_or_non_admin_users(): void
    {
        // Usuario inactivo con rol admin
        User::create([
            'name' => 'Admin Inactivo',
            'email' => 'inactivo@restauranager.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => false,
            'admin_pin' => '123456',
        ]);

        // Usuario mesero con PIN
        User::create([
            'name' => 'Mesero',
            'email' => 'mesero_pin@restauranager.com',
            'password' => 'secret123',
            'role' => 'user',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        $this->assertNull($this->service->verify('123456', 'test_context'));
        $this->assertSame(1, $this->service->getAttempts('test_context'));
    }

    public function test_admin_pin_service_locks_out_after_3_consecutive_failed_attempts(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin_lock@restauranager.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        // Intento 1 fallido
        $this->assertNull($this->service->verify('000000', 'ctx_lock'));
        $this->assertSame(1, $this->service->getAttempts('ctx_lock'));
        $this->assertFalse($this->service->isLocked('ctx_lock'));

        // Intento 2 fallido
        $this->assertNull($this->service->verify('111111', 'ctx_lock'));
        $this->assertSame(2, $this->service->getAttempts('ctx_lock'));
        $this->assertFalse($this->service->isLocked('ctx_lock'));

        // Intento 3 fallido -> activa el bloqueo
        $this->assertNull($this->service->verify('222222', 'ctx_lock'));
        $this->assertTrue($this->service->isLocked('ctx_lock'));
        $this->assertGreaterThan(0, $this->service->getRemainingLockoutSeconds('ctx_lock'));

        // Intento 4: debe lanzar ValidationException debido al bloqueo activo
        $this->expectException(ValidationException::class);
        $this->service->verify('123456', 'ctx_lock');
    }

    public function test_admin_pin_service_returns_null_without_exception_if_configured(): void
    {
        $this->service->lockout('ctx_no_throw', 300);
        $this->assertTrue($this->service->isLocked('ctx_no_throw'));

        $result = $this->service->verify('123456', 'ctx_no_throw', throwOnLockout: false);
        $this->assertNull($result);
    }

    public function test_admin_pin_service_successful_pin_resets_prior_failed_attempts(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin_reset@restauranager.com',
            'password' => 'secret123',
            'role' => 'admin',
            'active' => true,
            'admin_pin' => '123456',
        ]);

        // 2 intentos fallidos
        $this->service->verify('wrong1', 'ctx_reset');
        $this->service->verify('wrong2', 'ctx_reset');
        $this->assertSame(2, $this->service->getAttempts('ctx_reset'));

        // 3er intento con PIN correcto: resetea
        $admin = $this->service->verify('123456', 'ctx_reset');
        $this->assertNotNull($admin);
        $this->assertSame(0, $this->service->getAttempts('ctx_reset'));
        $this->assertFalse($this->service->isLocked('ctx_reset'));
    }
}
