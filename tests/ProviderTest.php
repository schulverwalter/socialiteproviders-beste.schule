<?php

namespace SocialiteProviders\BesteSchule\Tests;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use SocialiteProviders\BesteSchule\Provider;

class ProviderTest extends TestCase
{
    private function map(array $response)
    {
        $provider = new Provider(Request::create('/'), 'client', 'secret', 'https://example.test/callback');
        $method = new \ReflectionMethod($provider, 'mapUserToObject');
        $method->setAccessible(true);

        return $method->invoke($provider, $response);
    }

    /**
     * The shape of the legacy `api/user` endpoint. Consumers store `id` and look
     * accounts up by `email`; the id is the numeric beste.schule user id.
     */
    public function test_legacy_teacher_response_is_mapped_as_before()
    {
        $data = [
            'id' => 42,
            'username' => 'mmustermann',
            'email' => 'max@example.test',
            'teacher' => ['forename' => 'Max', 'name' => 'Mustermann'],
            'students' => [],
        ];

        $user = $this->map(['data' => $data]);

        $this->assertSame('42', $user->getId());
        $this->assertSame('mmustermann', $user->getNickname());
        $this->assertSame('Max Mustermann', $user->getName());
        $this->assertSame('max@example.test', $user->getEmail());
        $this->assertNull($user->getAvatar());
        $this->assertSame($data, $user->getRaw());
        $this->assertSame(['id', 'nickname', 'name', 'email', 'avatar'], array_keys($user->attributes));
    }

    public function test_legacy_student_response_uses_the_first_student()
    {
        $user = $this->map(['data' => [
            'id' => 7,
            'username' => 'anna1',
            'email' => null,
            'teacher' => null,
            'students' => [['forename' => 'Anna', 'name' => 'Muster']],
        ]]);

        $this->assertSame('7', $user->getId());
        $this->assertSame('Anna Muster', $user->getName());
        $this->assertSame('anna1@besteschule.local', $user->getEmail());
    }

    public function test_legacy_response_without_an_id_falls_back_to_the_username()
    {
        $user = $this->map(['data' => [
            'username' => 'anna1',
            'email' => null,
            'teacher' => null,
            'students' => [],
        ]]);

        $this->assertSame('anna1', $user->getId());
    }

    public function test_legacy_admin_response_falls_back_to_the_username()
    {
        $user = $this->map(['data' => [
            'username' => 'admin1',
            'email' => null,
            'teacher' => null,
            'students' => [],
        ]]);

        $this->assertSame('admin1', $user->getName());
        $this->assertSame('admin1@besteschule.local', $user->getEmail());
    }

    public function test_openid_userinfo_is_mapped_without_touching_the_legacy_keys()
    {
        $claims = [
            'sub' => '42',
            'name' => 'Anna M.',
            'preferred_username' => 'anna1',
            'roles' => ['student'],
            'tenant_id' => '7',
            'tenant_name' => 'Beispielschule',
            'groups' => [['id' => 3, 'local_id' => '7b', 'name' => 'Klasse 7b']],
        ];

        $user = $this->map($claims);

        $this->assertSame('42', $user->getId());
        $this->assertSame('anna1', $user->getNickname());
        $this->assertSame('Anna M.', $user->getName());
        $this->assertSame('anna1@besteschule.local', $user->getEmail());
        $this->assertSame('student', $user->attributes['role']);
        $this->assertSame(['id' => '7', 'name' => 'Beispielschule'], $user->attributes['school']);
        $this->assertSame($claims['groups'], $user->attributes['groups']);
        $this->assertSame($claims, $user->getRaw());
    }

    public function test_openid_userinfo_without_optional_scopes_still_maps()
    {
        $user = $this->map(['sub' => '42', 'email' => 'lehrer@example.test']);

        $this->assertSame('42', $user->getId());
        $this->assertSame('42', $user->getName());
        $this->assertSame('lehrer@example.test', $user->getEmail());
        $this->assertNull($user->attributes['role']);
        $this->assertNull($user->attributes['school']);
        $this->assertSame([], $user->attributes['groups']);
    }
}
