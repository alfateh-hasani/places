<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureStaffCan;
use App\Models\User;
use App\Services\Locks\LockAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Covers the staff.can gate on admin routes that aren't CRUD screens, and the
 * smart-lock passcode generator (must use the full digit space from a CSPRNG).
 */
class StaffPermissionAndPasscodeTest extends TestCase
{
    /**
     * @param  list<string>  $permissions
     */
    private function actingAsStaffWith(array $permissions): void
    {
        $user = new class($permissions) extends User
        {
            /**
             * @param  list<string>  $granted
             */
            public function __construct(private array $granted = [])
            {
                parent::__construct();
            }

            public function can($abilities, $arguments = []): bool
            {
                return in_array($abilities, $this->granted, true);
            }
        };

        auth('backpack')->setUser($user);
    }

    private function runMiddleware(string $permission): Response
    {
        return (new EnsureStaffCan)->handle(Request::create('/admin/reports'), fn () => new Response('ok'), $permission);
    }

    public function test_staff_with_the_permission_passes(): void
    {
        $this->actingAsStaffWith(['booking.list']);

        $this->assertSame('ok', $this->runMiddleware('booking.list')->getContent());
    }

    public function test_staff_without_the_permission_is_forbidden(): void
    {
        $this->actingAsStaffWith(['apartment.list']);

        $this->expectException(HttpException::class);
        $this->runMiddleware('role.update');
    }

    public function test_guest_is_forbidden(): void
    {
        $this->expectException(HttpException::class);
        $this->runMiddleware('booking.list');
    }

    public function test_passcodes_are_six_digits_and_can_repeat_digits(): void
    {
        $generate = new ReflectionMethod(LockAccessService::class, 'generatePasscode');
        $service = app(LockAccessService::class);

        $codes = array_map(fn () => $generate->invoke($service), range(1, 500));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }

        $withRepeatedDigit = array_filter($codes, fn (string $code) => count(array_unique(str_split($code))) < 6);
        $this->assertNotEmpty($withRepeatedDigit, 'Passcodes must draw from the full 10^6 space, not unique-digit permutations.');
    }
}
