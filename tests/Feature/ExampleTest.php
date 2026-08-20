<?php

use Native\Mobile\Testing\Native;

test('the application returns a successful login screen', function () {
    Native::visit('/login')
        ->assertSee('WhatsApp Native');
});
