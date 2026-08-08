<?php

test('authenticated application layout exposes an accessible mobile shell', function () {
    $user = createUserWithRole('Super Admin');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('name="viewport" content="width=device-width, initial-scale=1.0"', false)
        ->assertSee('class="app-shell', false)
        ->assertSee('id="mobile-menu-button"', false)
        ->assertSee('aria-controls="mobile-sidebar"', false)
        ->assertSee('id="mobile-sidebar"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('class="app-main', false);
});
