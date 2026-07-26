<?php

use App\Models\User;

test('a guest is redirected to login from the admin panel', function () {
    $this->get('/admin/sincronizacao')->assertRedirect(route('login'));
});

test('a non-admin user is forbidden from the admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/sincronizacao')->assertForbidden();
    $this->actingAs($user)->get('/admin/loterias')->assertForbidden();
});

test('an admin user can access the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin/sincronizacao')->assertOk();
    $this->actingAs($admin)->get('/admin/loterias')->assertOk();
});

test('is_admin is not mass-assignable', function () {
    expect((new User)->isFillable('is_admin'))->toBeFalse();
});
