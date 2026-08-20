<?php

use App\NativeComponents\Chat;
use App\NativeComponents\Contacts;
use App\NativeComponents\Conversations;
use App\NativeComponents\Login;
use App\NativeComponents\Register;
use Illuminate\Support\Facades\Route;

Route::native('/', Conversations::class);
Route::native('/login', Login::class);
Route::native('/register', Register::class);
Route::native('/contacts', Contacts::class);
Route::native('/chat/{id}', Chat::class);
